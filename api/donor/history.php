<?php
/**
 * Donor Donation History Endpoint
 * GET /api/donor/history.php
 * Returns completed donations for the logged-in donor
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
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

requireAuth(['donor']);

$donorId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get donor's profile for certificate data
    $stmt = $conn->prepare("SELECT name, blood_group FROM users WHERE id = ?");
    $stmt->execute([$donorId]);
    $donor = $stmt->fetch();

    // Get all donations with request details
    $sql = "SELECT d.id, d.status, d.accepted_at, d.completed_at,
                   r.request_code, r.blood_type, r.quantity, r.hospital_name, r.city,
                   r.urgency, r.patient_name
            FROM donations d
            JOIN blood_requests r ON d.request_id = r.id
            WHERE d.donor_id = ?
            ORDER BY d.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$donorId]);
    $donations = $stmt->fetchAll();

    // Format response
    $formattedDonations = array_map(function ($donation) use ($donor) {
        $donationId = 'DON' . str_pad($donation['id'], 3, '0', STR_PAD_LEFT);

        return [
            'id' => $donation['id'],
            'donation_id' => $donationId,
            'request_code' => $donation['request_code'],
            'date' => $donation['completed_at'] ?? $donation['accepted_at'],
            'hospital' => $donation['hospital_name'],
            'city' => $donation['city'],
            'blood_type' => $donation['blood_type'],
            'quantity' => $donation['quantity'],
            'urgency' => $donation['urgency'],
            'status' => $donation['status'],
            'patient_name' => $donation['patient_name'],
            // Certificate data
            'certificate' => $donation['status'] === 'completed' ? [
                'cert_id' => 'CERT-' . date('Y', strtotime($donation['completed_at'])) . '-' . $donationId,
                'donor_name' => $donor['name'],
                'blood_group' => $donor['blood_group'],
                'date' => $donation['completed_at'],
                'hospital' => $donation['hospital_name'],
                'quantity' => $donation['quantity'] . ' Unit(s)'
            ] : null
        ];
    }, $donations);

    // Calculate stats
    $completedDonations = array_filter($formattedDonations, fn($d) => $d['status'] === 'completed');
    $totalQuantity = array_sum(array_map(fn($d) => $d['quantity'], $completedDonations));
    $thisYear = array_filter($completedDonations, function ($d) {
        return $d['date'] && date('Y', strtotime($d['date'])) === date('Y');
    });

    $stats = [
        'total_donations' => count($completedDonations),
        'total_volume' => $totalQuantity,
        'this_year' => count($thisYear),
        'lives_saved' => count($completedDonations) * 3
    ];

    echo json_encode([
        'success' => true,
        'donations' => $formattedDonations,
        'stats' => $stats,
        'donor' => [
            'name' => $donor['name'],
            'blood_group' => $donor['blood_group']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Donor History Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch donation history']);
}
