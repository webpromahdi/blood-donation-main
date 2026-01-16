<?php
/**
 * Admin List Donors Endpoint
 * GET /api/admin/donors.php
 * Query params: ?blood_type=O+&status=available
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

requireAuth(['admin']);

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.blood_group, u.age, u.city, u.address, u.status, u.created_at,
                   COUNT(DISTINCT d.id) as total_donations,
                   MAX(d.completed_at) as last_donation_date
            FROM users u
            LEFT JOIN donations d ON u.id = d.donor_id AND d.status = 'completed'
            WHERE u.role = 'donor'";

    $params = [];

    // Filter by blood type
    if (isset($_GET['blood_type']) && !empty($_GET['blood_type'])) {
        $sql .= " AND u.blood_group = ?";
        $params[] = $_GET['blood_type'];
    }

    // Filter by city
    if (isset($_GET['city']) && !empty($_GET['city'])) {
        $sql .= " AND u.city LIKE ?";
        $params[] = '%' . $_GET['city'] . '%';
    }

    // Filter by approval status (pending, approved, rejected)
    if (isset($_GET['approval_status']) && !empty($_GET['approval_status'])) {
        $sql .= " AND u.status = ?";
        $params[] = $_GET['approval_status'];
    }

    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $donors = $stmt->fetchAll();

    // Format response with approval status
    $formattedDonors = array_map(function ($donor) {
        // Get donor approval status from database
        $approvalStatus = $donor['status'] ?? 'pending';
        
        // Map status to display format for backward compatibility
        $displayStatus = $approvalStatus === 'approved' ? 'Approved' : ($approvalStatus === 'rejected' ? 'Rejected' : 'Pending');

        return [
            'id' => $donor['id'],
            'name' => $donor['name'],
            'email' => $donor['email'],
            'phone' => $donor['phone'],
            'blood_group' => $donor['blood_group'],
            'age' => $donor['age'],
            'city' => $donor['city'],
            'address' => $donor['address'],
            'status' => $approvalStatus,
            'display_status' => $displayStatus,
            'total_donations' => (int) $donor['total_donations'],
            'last_donation' => $donor['last_donation_date'],
            'registered_at' => $donor['created_at']
        ];
    }, $donors);

    // Filter by approval status if specified in params
    if (isset($_GET['approval_status'])) {
        $formattedDonors = array_values(array_filter($formattedDonors, fn($d) => $d['status'] === $_GET['approval_status']));
    }

    // Calculate stats by approval status
    $stats = [
        'total' => count($formattedDonors),
        'pending' => count(array_filter($formattedDonors, function($d) { return $d['status'] === 'pending'; })),
        'approved' => count(array_filter($formattedDonors, function($d) { return $d['status'] === 'approved'; })),
        'rejected' => count(array_filter($formattedDonors, function($d) { return $d['status'] === 'rejected'; })),
        'by_blood_type' => []
    ];

    foreach ($formattedDonors as $donor) {
        $bg = $donor['blood_group'] ?? 'Unknown';
        if (!isset($stats['by_blood_type'][$bg])) {
            $stats['by_blood_type'][$bg] = 0;
        }
        $stats['by_blood_type'][$bg]++;
    }

    echo json_encode([
        'success' => true,
        'donors' => $formattedDonors,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    error_log("Admin Donors Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch donors']);
}
