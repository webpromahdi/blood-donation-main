<?php
/**
 * Seeker Blood Request Creation Endpoint
 * POST /api/seeker/requests/create.php
 * 
 * Normalized Schema: Uses requester_id + requester_type for polymorphic relation,
 * blood_group_id FK from blood_groups table
 */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log

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

// Require seeker role
requireAuth(['seeker']);

$input = json_decode(file_get_contents('php://input'), true);

// Check if JSON parsing failed
if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

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
$patientAge = isset($input['patientAge']) && $input['patientAge'] !== '' ? (int) $input['patientAge'] : null;
$contactPhone = trim($input['contactPhone']);
$contactEmail = isset($input['email']) && $input['email'] !== '' ? trim($input['email']) : null;
$bloodType = $input['bloodType'];
$quantity = (int) $input['quantity'];
$hospitalName = trim($input['hospitalName']);
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
    
    // Get user_id from session (this is the requester_id for blood_requests)
    $userId = $_SESSION['user_id'];
    
    // Verify seeker exists in seekers table (for updating total_requests later)
    $stmt = $conn->prepare("SELECT id FROM seekers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $seeker = $stmt->fetch();
    
    if (!$seeker) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Seeker profile not found. Please contact support.']);
        exit;
    }
    
    $seekerTableId = $seeker['id'];

    // Generate unique request code
    $stmt = $conn->query('SELECT MAX(id) as max_id FROM blood_requests');
    $result = $stmt->fetch();
    $nextId = ($result['max_id'] ?? 0) + 1;
    $requestCode = 'REQ' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

    $conn->beginTransaction();

    // Insert blood request with correct column names (requester_id, requester_type)
    $sql = 'INSERT INTO blood_requests (
        request_code, blood_group_id, requester_id, requester_type, patient_name, patient_age,
        contact_phone, contact_email, quantity, hospital_name,
        city, required_date, medical_reason, urgency, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $requestCode,
        $bloodGroupId,
        $userId,           // requester_id is the user_id
        'seeker',          // requester_type
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
    $stmt->execute([$seekerTableId]);

    $conn->commit();
    
    // Get seeker name for notifications
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $seekerUser = $stmt->fetch();
    $seekerName = $seekerUser ? $seekerUser['name'] : 'Unknown';
    
    // Send notifications
    $notificationService = new NotificationService($conn);
    
    // S1: Notify seeker that request was submitted
    $notificationService->notifySeekerRequestSubmitted($userId, $requestId, $requestCode);
    
    // A4: Notify admins of new seeker request (A5 emergency alert is handled inside)
    $notificationService->notifyAdminNewSeekerRequest($requestId, $seekerName, $bloodType, $isEmergency ? 'emergency' : 'normal');

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
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to create request. Please try again.',
        'debug' => $e->getMessage() // Remove in production
    ]);
}
