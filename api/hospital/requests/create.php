<?php
/**
 * Hospital Blood Request Creation Endpoint
 * POST /api/hospital/requests/create.php
 * 
 * Normalized Schema: Uses blood_group_id FK, requester_id + requester_type for polymorphic relation
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
require_once __DIR__ . '/../../services/NotificationService.php';

// Require hospital role
$user = requireAuth(['hospital']);

// Require approved status to create blood requests
requireApprovedStatus($_SESSION['user_id'], 'hospital');

$input = json_decode(file_get_contents('php://input'), true);

// Check if JSON parsing failed
if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

// Validate required fields
$required = ['patientName', 'contactPhone', 'bloodType', 'quantity', 'city', 'requiredDate'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

// Sanitize inputs
$patientName = trim($input['patientName']);
$patientAge = isset($input['patientAge']) && $input['patientAge'] !== '' ? (int) $input['patientAge'] : null;
$contactPhone = trim($input['contactPhone']);
$contactEmail = isset($input['email']) && $input['email'] !== '' ? trim($input['email']) : null;
$bloodType = $input['bloodType'];
$quantity = (int) $input['quantity'];
$city = trim($input['city']);
$requiredDate = $input['requiredDate'];
$medicalReason = isset($input['medicalReason']) && $input['medicalReason'] !== '' ? trim($input['medicalReason']) : null;
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
        echo json_encode(['success' => false, 'message' => 'Invalid blood type: ' . $bloodType]);
        exit;
    }
    
    $bloodGroupId = $bloodGroup['id'];
    
    // Get hospital info from hospitals table
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT h.id as hospital_table_id, u.name as hospital_name 
        FROM hospitals h
        JOIN users u ON h.user_id = u.id
        WHERE h.user_id = ?
    ");
    $stmt->execute([$userId]);
    $hospital = $stmt->fetch();
    
    if (!$hospital) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Hospital profile not found. Please contact support.']);
        exit;
    }
    
    $hospitalTableId = $hospital['hospital_table_id'];
    $hospitalName = $hospital['hospital_name'];

    // Generate unique request code
    $stmt = $conn->query('SELECT MAX(id) as max_id FROM blood_requests');
    $result = $stmt->fetch();
    $nextId = ($result['max_id'] ?? 0) + 1;
    $requestCode = 'REQ' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

    $conn->beginTransaction();

    // Insert blood request with polymorphic requester_id + requester_type
    $sql = 'INSERT INTO blood_requests (
        request_code, blood_group_id, requester_id, requester_type, patient_name, patient_age,
        contact_phone, contact_email, quantity, hospital_name, city, required_date, 
        medical_reason, urgency, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $requestCode,
        $bloodGroupId,
        $userId,           // requester_id is the user_id
        'hospital',        // requester_type
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
    
    // Update hospital's total_requests count
    $stmt = $conn->prepare("UPDATE hospitals SET total_requests = total_requests + 1 WHERE id = ?");
    $stmt->execute([$hospitalTableId]);

    $conn->commit();
    
    // Send notifications
    $notificationService = new NotificationService($conn);
    
    // A3: Notify admins of new hospital request (A5 emergency alert is handled inside)
    $notificationService->notifyAdminNewHospitalRequest($requestId, $hospitalName, $bloodType, $isEmergency ? 'emergency' : 'normal');

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
    error_log("Hospital Request Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to create request. Please try again.'
    ]);
}
