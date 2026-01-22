<?php
/**
 * Donor Donation History Endpoint
 * GET /api/donor/history.php
 * 
 * Normalized Schema: Reads from donors + donations + blood_requests + certificates
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
    // Get donor's profile from normalized tables
    $stmt = $conn->prepare("
        SELECT d.id as donor_id, u.name, bg.blood_type as blood_group
        FROM donors d
        JOIN users u ON d.user_id = u.id
        JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE d.user_id = ?
    ");
    $stmt->execute([$userId]);
    $donor = $stmt->fetch();
    
    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor not found']);
        exit;
    }

    $donorId = $donor['donor_id'];

    // Get all donations with request details (includes both request-based and voluntary donations)
    // Voluntary donations are linked through blood_requests with request_code starting with 'VOL-'
    $sql = "SELECT dn.id, dn.status, dn.accepted_at, dn.completed_at, dn.quantity,
                   r.request_code, bg.blood_type, r.hospital_name, r.city,
                   r.urgency, r.patient_name, r.medical_reason,
                   c.certificate_code, c.issued_at as certificate_issued_at,
                   CASE WHEN r.request_code LIKE 'VOL-%' THEN 'voluntary' ELSE 'request' END as donation_type
            FROM donations dn
            JOIN blood_requests r ON dn.request_id = r.id
            JOIN blood_groups bg ON r.blood_group_id = bg.id
            LEFT JOIN certificates c ON dn.id = c.donation_id
            WHERE dn.donor_id = ?
            ORDER BY dn.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$donorId]);
    $donations = $stmt->fetchAll();

    // Also fetch completed voluntary donations that may not have a donations record (legacy data)
    $sqlVoluntary = "SELECT v.id, v.status, v.availability_date, v.scheduled_date, v.updated_at as completed_at,
                            v.city, bg.blood_type, h.id as hospital_id, u.name as hospital_name,
                            'Voluntary Donation' as patient_name, 'Voluntary blood donation' as medical_reason,
                            'normal' as urgency, v.blood_group_id
                     FROM voluntary_donations v
                     JOIN blood_groups bg ON v.blood_group_id = bg.id
                     LEFT JOIN hospitals h ON v.hospital_id = h.id
                     LEFT JOIN users u ON h.user_id = u.id
                     WHERE v.donor_id = ? AND v.status = 'completed'
                     AND NOT EXISTS (
                         SELECT 1 FROM donations d 
                         JOIN blood_requests r ON d.request_id = r.id 
                         WHERE d.donor_id = v.donor_id 
                         AND r.request_code = CONCAT('VOL-', DATE_FORMAT(v.updated_at, '%Y%m%d'), '-', LPAD(v.id, 4, '0'))
                     )
                     ORDER BY v.updated_at DESC";

    $stmt = $conn->prepare($sqlVoluntary);
    $stmt->execute([$donorId]);
    $legacyVoluntary = $stmt->fetchAll();

    // Format donations from donations table
    $formattedDonations = array_map(function ($donation) use ($donor) {
        $donationId = 'DON' . str_pad($donation['id'], 3, '0', STR_PAD_LEFT);
        $isVoluntary = $donation['donation_type'] === 'voluntary';

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
            'donation_type' => $isVoluntary ? 'Voluntary' : 'Request-based',
            'is_voluntary' => $isVoluntary,
            // Certificate data
            'certificate' => $donation['status'] === 'completed' ? [
                'cert_id' => $donation['certificate_code'] ?? ('CERT-' . date('Y', strtotime($donation['completed_at'])) . '-' . $donationId),
                'donor_name' => $donor['name'],
                'blood_group' => $donor['blood_group'],
                'date' => $donation['completed_at'],
                'hospital' => $donation['hospital_name'],
                'quantity' => $donation['quantity'] . ' Unit(s)'
            ] : null
        ];
    }, $donations);

    // Format legacy voluntary donations (completed but no donations record)
    $formattedLegacyVoluntary = array_map(function ($vol) use ($donor) {
        $donationId = 'VOL' . str_pad($vol['id'], 3, '0', STR_PAD_LEFT);

        return [
            'id' => 'vol_' . $vol['id'],
            'donation_id' => $donationId,
            'request_code' => 'VOL-LEGACY-' . $vol['id'],
            'date' => $vol['completed_at'] ?? $vol['scheduled_date'] ?? $vol['availability_date'],
            'hospital' => $vol['hospital_name'] ?? 'Hospital',
            'city' => $vol['city'],
            'blood_type' => $vol['blood_type'],
            'quantity' => 1,
            'urgency' => 'normal',
            'status' => 'completed',
            'patient_name' => 'Voluntary Donation',
            'donation_type' => 'Voluntary',
            'is_voluntary' => true,
            // Certificate data
            'certificate' => [
                'cert_id' => 'CERT-' . date('Y', strtotime($vol['completed_at'] ?? 'now')) . '-' . $donationId,
                'donor_name' => $donor['name'],
                'blood_group' => $donor['blood_group'],
                'date' => $vol['completed_at'] ?? $vol['scheduled_date'] ?? $vol['availability_date'],
                'hospital' => $vol['hospital_name'] ?? 'Hospital',
                'quantity' => '1 Unit(s)'
            ]
        ];
    }, $legacyVoluntary);

    // Merge both arrays
    $allDonations = array_merge($formattedDonations, $formattedLegacyVoluntary);

    // Sort by date descending
    usort($allDonations, function($a, $b) {
        return strtotime($b['date'] ?? '1970-01-01') - strtotime($a['date'] ?? '1970-01-01');
    });

    // Calculate stats using all donations (including legacy voluntary)
    $completedDonations = array_filter($allDonations, fn($d) => $d['status'] === 'completed');
    $voluntaryDonations = array_filter($completedDonations, fn($d) => $d['is_voluntary']);
    $requestDonations = array_filter($completedDonations, fn($d) => !$d['is_voluntary']);
    $totalQuantity = array_sum(array_map(fn($d) => $d['quantity'], $completedDonations));
    $thisYear = array_filter($completedDonations, function ($d) {
        return $d['date'] && date('Y', strtotime($d['date'])) === date('Y');
    });

    $stats = [
        'total_donations' => count($completedDonations),
        'voluntary_donations' => count($voluntaryDonations),
        'request_donations' => count($requestDonations),
        'total_volume' => $totalQuantity,
        'this_year' => count($thisYear),
        'lives_saved' => count($completedDonations) // 1 donation = 1 life saved
    ];

    echo json_encode([
        'success' => true,
        'donations' => $allDonations,
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
