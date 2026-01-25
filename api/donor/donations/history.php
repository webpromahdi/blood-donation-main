<?php
/**
 * Donor Donation History Endpoint
 * GET /api/donor/donations/history.php
 * Returns past donations for the logged-in donor
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireAuth(['donor']);

// Require approved status to view donation history
requireApprovedStatus($_SESSION['user_id'], 'donor');

$userId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // First get the donor record ID from users.id
    $stmt = $conn->prepare("SELECT id FROM donors WHERE user_id = ?");
    $stmt->execute([$userId]);
    $donorRecord = $stmt->fetch();
    
    if (!$donorRecord) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor profile not found']);
        exit;
    }
    
    $donorId = $donorRecord['id'];
    
    // Query uses donor_id which references donors.id, not users.id
    $sql = "SELECT d.*, r.request_code, r.patient_name, r.quantity, 
                   r.hospital_name, r.city, r.urgency, bg.blood_type
            FROM donations d
            JOIN blood_requests r ON d.request_id = r.id
            LEFT JOIN blood_groups bg ON r.blood_group_id = bg.id
            WHERE d.donor_id = ?
            ORDER BY d.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$donorId]);
    $donations = $stmt->fetchAll();

    $formattedDonations = array_map(function ($d) {
        return [
            'id' => $d['id'],
            'request_code' => $d['request_code'],
            'patient_name' => $d['patient_name'],
            'blood_type' => $d['blood_type'],
            'quantity' => $d['quantity'],
            'hospital_name' => $d['hospital_name'],
            'city' => $d['city'],
            'urgency' => $d['urgency'],
            'status' => $d['status'],
            'accepted_at' => $d['accepted_at'],
            'completed_at' => $d['completed_at'],
            'cancelled_at' => $d['cancelled_at'],
            'cancel_reason' => $d['cancel_reason']
        ];
    }, $donations);

    // Statistics
    $stats = [
        'total' => count($formattedDonations),
        'completed' => count(array_filter($formattedDonations, fn($d) => $d['status'] === 'completed')),
        'cancelled' => count(array_filter($formattedDonations, fn($d) => $d['status'] === 'cancelled')),
        'active' => count(array_filter($formattedDonations, fn($d) => !in_array($d['status'], ['completed', 'cancelled'])))
    ];

    echo json_encode([
        'success' => true,
        'donations' => $formattedDonations,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    error_log("Donor History Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch history']);
}
