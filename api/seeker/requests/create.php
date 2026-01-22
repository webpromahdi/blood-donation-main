<?php
/**
 * Seeker Blood Request Creation Endpoint
 * POST /api/seeker/requests/create.php
 * 
 * Normalized Schema: Uses seeker_id FK from seekers table, blood_group_id FK from blood_groups table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Require seeker role
requireAuth(['seeker']);

$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['patientName', 'contactPhone', 'bloodType', 'quantity', 'hospitalName', 'city', 'requiredDate'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

// Sanitize inputs
$patientName = trim($input['patientName']);
$patientAge = isset($input['patientAge']) ? (int) $input['patientAge'] : null;
$contactPhone = trim($input['contactPhone']);
$contactEmail = isset($input['email']) ? trim($input['email']) : null;
$bloodType = $input['bloodType'];
$quantity = (int) $input['quantity'];
$hospitalName = trim($input['hospitalName']);
$city = trim($input['city']);
$requiredDate = $input['requiredDate'];
$medicalReason = isset($input['medicalReason']) ? trim($input['medicalReason']) : null;
$isEmergency = isset($input['emergency']) && $input['emergency'] === true;

// Connect to database
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Lookup blood_group_id from blood_groups table
    $stmt = $conn->prepare("SELECT id FROM blood_groups WHERE blood_type = ?");
    $stmt->execute([$bloodType]);
    $bloodGroup = $stmt->fetch();
    
    if (!$bloodGroup) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid blood type']);
        exit;
    }
    
    $bloodGroupId = $bloodGroup['id'];
    
    // Get seeker_id from seekers table
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM seekers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $seeker = $stmt->fetch();
    
    if (!$seeker) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Seeker record not found']);
        exit;
    }
    
    $seekerId = $seeker['id'];

    // Generate unique request code
    $stmt = $conn->query('SELECT MAX(id) as max_id FROM blood_requests');
    $result = $stmt->fetch();
    $nextId = ($result['max_id'] ?? 0) + 1;
    $requestCode = 'REQ' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

    $conn->beginTransaction();

    // Insert blood request with normalized foreign keys
    $sql = 'INSERT INTO blood_requests (
        request_code, blood_group_id, seeker_id, patient_name, patient_age,
        contact_phone, contact_email, quantity, hospital_name,
        city, required_date, medical_reason, urgency, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $requestCode,
        $bloodGroupId,
        $seekerId,
        $patientName,
        $patientAge,
        $contactPhone,
        $contactEmail,
        $quantity,
        $hospitalName,
        $city,
        $requiredDate,
        $medicalReason,
        $isEmergency ? 'emergency' : 'normal',
        'pending'
    ]);

    $requestId = $conn->lastInsertId();
    
    // Update seeker's total_requests count
    $stmt = $conn->prepare("UPDATE seekers SET total_requests = total_requests + 1 WHERE id = ?");
    $stmt->execute([$seekerId]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Blood request submitted successfully',
        'request' => [
            'id' => $requestId,
            'request_code' => $requestCode,
            'status' => 'pending',
            'urgency' => $isEmergency ? 'emergency' : 'normal'
        ]
    ]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Seeker Request Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create request. Please try again.']);
}
