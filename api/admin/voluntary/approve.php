<?php
/**
 * Approve Voluntary Donation (Admin)
 * POST /api/admin/voluntary/approve.php
 * Body: { voluntary_id: int }
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

if (empty($input['voluntary_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Voluntary donation ID is required']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Verify voluntary donation exists and is pending
    $stmt = $conn->prepare("
        SELECT v.id, v.status, v.donor_id, d.user_id as donor_user_id, u.name as donor_name
        FROM voluntary_donations v
        JOIN donors d ON v.donor_id = d.id
        JOIN users u ON d.user_id = u.id
        WHERE v.id = ?
    ");
    $stmt->execute([$input['voluntary_id']]);
    $voluntary = $stmt->fetch();

    if (!$voluntary) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Voluntary donation not found']);
        exit;
    }

    if ($voluntary['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only pending voluntary donations can be approved']);
        exit;
    }

    // Approve the voluntary donation
    $stmt = $conn->prepare("
        UPDATE voluntary_donations 
        SET status = 'approved', 
            approved_by_admin_id = ?, 
            approved_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $input['voluntary_id']]);

    // D9: Notify donor their voluntary donation was approved using NotificationService
    $notificationService = new NotificationService($conn);
    $notificationService->notifyDonorVoluntaryApproved($voluntary['donor_user_id'], $input['voluntary_id']);

    echo json_encode([
        'success' => true,
        'message' => 'Voluntary donation approved successfully',
        'donor_name' => $voluntary['donor_name']
    ]);

} catch (PDOException $e) {
    error_log("Approve Voluntary Donation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to approve voluntary donation']);
}
