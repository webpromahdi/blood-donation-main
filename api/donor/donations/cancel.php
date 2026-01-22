<?php
/**
 * Donor Cancel Donation Endpoint
 * POST /api/donor/donations/cancel.php
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

// Require approved status to cancel donations
requireApprovedStatus($_SESSION['user_id'], 'donor');

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['donation_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Donation ID is required']);
    exit;
}

$donationId = (int) $input['donation_id'];
$reason = isset($input['reason']) ? trim($input['reason']) : null;
$donorId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get donor_id from donors table (normalized schema)
    $stmt = $conn->prepare("SELECT id FROM donors WHERE user_id = ?");
    $stmt->execute([$donorId]);
    $donorRecord = $stmt->fetch();
    
    if (!$donorRecord) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor record not found']);
        exit;
    }
    
    $donorRecordId = $donorRecord['id'];

    // Get donation and verify ownership - donations.donor_id references donors.id
    $stmt = $conn->prepare("SELECT d.*, r.id as request_id FROM donations d JOIN blood_requests r ON d.request_id = r.id WHERE d.id = ? AND d.donor_id = ?");
    $stmt->execute([$donationId, $donorRecordId]);
    $donation = $stmt->fetch();

    if (!$donation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donation not found or not authorized']);
        exit;
    }

    if ($donation['status'] === 'completed') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot cancel a completed donation']);
        exit;
    }

    if ($donation['status'] === 'cancelled') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Donation is already cancelled']);
        exit;
    }

    $conn->beginTransaction();

    // Cancel the donation
    $stmt = $conn->prepare("UPDATE donations SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = ? WHERE id = ?");
    $stmt->execute([$reason, $donationId]);

    // Put request back to approved (available for other donors)
    $stmt = $conn->prepare("UPDATE blood_requests SET status = 'approved' WHERE id = ?");
    $stmt->execute([$donation['request_id']]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Donation cancelled. The request is now available to other donors.',
        'donation' => [
            'id' => $donationId,
            'status' => 'cancelled'
        ]
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Donation Cancel Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to cancel donation']);
}
