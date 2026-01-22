<?php
/**
 * Donor Update Donation Status Endpoint
 * POST /api/donor/donations/update.php
 * 
 * Normalized Schema: Updates donations table with proper donor_id reference
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

$user = requireAuth(['donor']);

// Require approved status to update donation status
requireApprovedStatus($_SESSION['user_id'], 'donor');

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['donation_id']) || empty($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Donation ID and status are required']);
    exit;
}

$donationId = (int) $input['donation_id'];
$newStatus = $input['status'];
$userId = $_SESSION['user_id'];

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
    // Get donor_id from donors table
    $stmt = $conn->prepare("SELECT id FROM donors WHERE user_id = ?");
    $stmt->execute([$userId]);
    $donor = $stmt->fetch();
    
    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor record not found']);
        exit;
    }
    
    $donorId = $donor['id'];

    // Get donation and verify ownership
    $stmt = $conn->prepare("
        SELECT d.*, r.id as request_id, r.hospital_name, bg.blood_type
        FROM donations d 
        JOIN blood_requests r ON d.request_id = r.id 
        JOIN blood_groups bg ON r.blood_group_id = bg.id
        WHERE d.id = ? AND d.donor_id = ?
    ");
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

    // If completed, update request status and donor stats
    if ($newStatus === 'completed') {
        // Update request status
        $stmt = $conn->prepare("UPDATE blood_requests SET status = 'completed' WHERE id = ?");
        $stmt->execute([$donation['request_id']]);
        
        // Update donor stats
        $stmt = $conn->prepare("
            UPDATE donors 
            SET total_donations = total_donations + 1,
                last_donation_date = CURDATE(),
                next_eligible_date = DATE_ADD(CURDATE(), INTERVAL 56 DAY)
            WHERE id = ?
        ");
        $stmt->execute([$donorId]);
        
        // Generate certificate
        $certCode = 'CERT-' . date('Y') . '-DON' . str_pad($donationId, 5, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("
            SELECT u.name, bg.blood_type 
            FROM donors d 
            JOIN users u ON d.user_id = u.id
            JOIN blood_groups bg ON d.blood_group_id = bg.id
            WHERE d.id = ?
        ");
        $stmt->execute([$donorId]);
        $donorInfo = $stmt->fetch();
        
        $stmt = $conn->prepare("
            INSERT INTO certificates (certificate_code, donation_id, donor_id, donor_name, blood_group, donation_date, hospital_name, quantity)
            VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?)
            ON DUPLICATE KEY UPDATE downloaded_at = downloaded_at
        ");
        $stmt->execute([
            $certCode,
            $donationId,
            $donorId,
            $donorInfo['name'],
            $donorInfo['blood_type'],
            $donation['hospital_name'],
            $donation['quantity'] ?? 1
        ]);
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
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Donation Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}
