<?php
/**
 * Admin Reject Blood Request Endpoint
 * POST /api/admin/requests/reject.php
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
$reason = isset($input['reason']) ? trim($input['reason']) : null;
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
        SELECT r.*, u.name as requester_name
        FROM blood_requests r
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

    // Reject the request
    $stmt = $conn->prepare("UPDATE blood_requests SET status = 'rejected', admin_id = ?, rejected_at = NOW(), rejection_reason = ? WHERE id = ?");
    $stmt->execute([$adminId, $reason, $requestId]);
    
    // Send notifications
    $notificationService = new NotificationService($conn);
    
    if ($request['requester_type'] === 'hospital') {
        // H4: Notify hospital their request was rejected
        $notificationService->notifyHospitalRequestRejected($request['requester_id'], $requestId, $request['request_code'], $reason);
    } else {
        // S3: Notify seeker their request was rejected
        $notificationService->notifySeekerRequestRejected($request['requester_id'], $requestId, $request['request_code'], $reason);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Request rejected',
        'request' => [
            'id' => $requestId,
            'status' => 'rejected'
        ]
    ]);

} catch (PDOException $e) {
    error_log("Admin Reject Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to reject request']);
}
