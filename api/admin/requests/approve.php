<?php
/**
 * Admin Approve Blood Request Endpoint
 * POST /api/admin/requests/approve.php
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
require_once __DIR__ . '/../../services/NotificationService.php';

requireAuth(['admin']);

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['request_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit;
}

$requestId = (int) $input['request_id'];
$adminId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Check if request exists and is pending - get full details
    $stmt = $conn->prepare("
        SELECT r.*, bg.blood_type, u.name as requester_name
        FROM blood_requests r
        JOIN blood_groups bg ON r.blood_group_id = bg.id
        JOIN users u ON r.requester_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    if ($request['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Request is not pending. Current status: ' . $request['status']]);
        exit;
    }

    // Approve the request
    $stmt = $conn->prepare("UPDATE blood_requests SET status = 'approved', admin_id = ?, approved_at = NOW() WHERE id = ?");
    $stmt->execute([$adminId, $requestId]);
    
    // Send notifications
    $notificationService = new NotificationService($conn);
    
    if ($request['requester_type'] === 'hospital') {
        // H3: Notify hospital their request was approved
        $notificationService->notifyHospitalRequestApproved($request['requester_id'], $requestId, $request['request_code']);
    } else {
        // S2: Notify seeker their request was approved
        $notificationService->notifySeekerRequestApproved($request['requester_id'], $requestId, $request['request_code']);
    }
    
    // D3/D4: Notify matching donors (by blood type and city)
    $notificationService->notifyMatchingDonors(
        $requestId, 
        $request['blood_type'], 
        $request['city'], 
        $request['hospital_name'],
        $request['urgency']
    );

    echo json_encode([
        'success' => true,
        'message' => 'Request approved successfully',
        'request' => [
            'id' => $requestId,
            'status' => 'approved'
        ]
    ]);

} catch (PDOException $e) {
    error_log("Admin Approve Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to approve request']);
}
