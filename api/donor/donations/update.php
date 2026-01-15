<?php
/**
 * Donor Update Donation Status Endpoint
 * POST /api/donor/donations/update.php
 * Status flow: accepted -> on_the_way -> reached -> completed
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

requireAuth(['donor']);

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['donation_id']) || empty($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Donation ID and status are required']);
    exit;
}

$donationId = (int) $input['donation_id'];
$newStatus = $input['status'];
$donorId = $_SESSION['user_id'];

// Valid status transitions
$validStatuses = ['on_the_way', 'reached', 'completed'];
if (!in_array($newStatus, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status. Must be: on_the_way, reached, or completed']);
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
    // Get donation and verify ownership
    $stmt = $conn->prepare("SELECT d.*, r.id as request_id FROM donations d JOIN blood_requests r ON d.request_id = r.id WHERE d.id = ? AND d.donor_id = ?");
    $stmt->execute([$donationId, $donorId]);
    $donation = $stmt->fetch();

    if (!$donation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donation not found or not authorized']);
        exit;
    }

    if ($donation['status'] === 'completed' || $donation['status'] === 'cancelled') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Donation is already ' . $donation['status']]);
        exit;
    }

    // Validate status transition
    $statusOrder = ['accepted' => 0, 'on_the_way' => 1, 'reached' => 2, 'completed' => 3];
    $currentOrder = $statusOrder[$donation['status']] ?? 0;
    $newOrder = $statusOrder[$newStatus] ?? 0;

    if ($newOrder <= $currentOrder) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status transition. Current: ' . $donation['status']]);
        exit;
    }

    $conn->beginTransaction();

    // Update donation status with appropriate timestamp
    $timestampField = match ($newStatus) {
        'on_the_way' => 'started_at',
        'reached' => 'reached_at',
        'completed' => 'completed_at',
        default => null
    };

    if ($timestampField) {
        $sql = "UPDATE donations SET status = ?, $timestampField = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$newStatus, $donationId]);
    }

    // If completed, update request status too
    if ($newStatus === 'completed') {
        $stmt = $conn->prepare("UPDATE blood_requests SET status = 'completed' WHERE id = ?");
        $stmt->execute([$donation['request_id']]);
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Status updated to ' . $newStatus,
        'donation' => [
            'id' => $donationId,
            'status' => $newStatus
        ]
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Donation Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}
