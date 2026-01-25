<?php
/**
 * Cancel Voluntary Donation Request
 * POST /api/donor/voluntary/cancel.php
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

$user = requireAuth(['donor']);

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
    // Get donor ID
    $stmt = $conn->prepare("SELECT id FROM donors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $donor = $stmt->fetch();

    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor profile not found']);
        exit;
    }

    // Verify ownership and cancellable status
    $stmt = $conn->prepare("
        SELECT v.id, v.status, v.hospital_id, h.user_id as hospital_user_id
        FROM voluntary_donations v
        LEFT JOIN hospitals h ON v.hospital_id = h.id
        WHERE v.id = ? AND v.donor_id = ?
    ");
    $stmt->execute([$input['voluntary_id'], $donor['id']]);
    $voluntary = $stmt->fetch();

    if (!$voluntary) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Voluntary donation not found']);
        exit;
    }

    // Allow cancelling pending, approved, or scheduled status
    if (!in_array($voluntary['status'], ['pending', 'approved', 'scheduled'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only pending, approved, or scheduled requests can be cancelled']);
        exit;
    }

    // Cancel the request
    $stmt = $conn->prepare("
        UPDATE voluntary_donations 
        SET status = 'cancelled', updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$input['voluntary_id']]);
    
    // If there was an appointment scheduled, cancel it
    if ($voluntary['status'] === 'scheduled' && $voluntary['hospital_id']) {
        $stmt = $conn->prepare("
            UPDATE appointments 
            SET status = 'cancelled', updated_at = NOW()
            WHERE donor_id = ? AND hospital_id = ? AND status = 'scheduled'
        ");
        $stmt->execute([$donor['id'], $voluntary['hospital_id']]);
        
        // Notify hospital about cancellation
        if ($voluntary['hospital_user_id']) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type)
                VALUES (?, 'Voluntary Donation Cancelled', 'A donor has cancelled their scheduled voluntary donation.', 'voluntary')
            ");
            $stmt->execute([$voluntary['hospital_user_id']]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Voluntary donation request cancelled successfully'
    ]);

} catch (PDOException $e) {
    error_log("Cancel Voluntary Donation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to cancel voluntary donation request']);
}
