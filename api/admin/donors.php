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
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.blood_group, u.age, u.city, u.address, u.created_at,
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
    
    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $donors = $stmt->fetchAll();
    
    // Format response with availability status
    $formattedDonors = array_map(function($donor) {
        // Calculate if donor is available (56 days since last donation)
        $isAvailable = true;
        if ($donor['last_donation_date']) {
            $lastDonation = new DateTime($donor['last_donation_date']);
            $nextEligible = $lastDonation->modify('+56 days');
            $isAvailable = $nextEligible <= new DateTime();
        }
        
        return [
            'id' => $donor['id'],
            'name' => $donor['name'],
            'email' => $donor['email'],
            'phone' => $donor['phone'],
            'blood_group' => $donor['blood_group'],
            'age' => $donor['age'],
            'city' => $donor['city'],
            'address' => $donor['address'],
            'total_donations' => (int) $donor['total_donations'],
            'last_donation' => $donor['last_donation_date'],
            'is_available' => $isAvailable,
            'registered_at' => $donor['created_at']
        ];
    }, $donors);
    
    // Filter by availability if requested
    if (isset($_GET['status']) && $_GET['status'] === 'available') {
        $formattedDonors = array_values(array_filter($formattedDonors, fn($d) => $d['is_available']));
    }
    
    // Calculate stats
    $stats = [
        'total' => count($formattedDonors),
        'available' => count(array_filter($formattedDonors, fn($d) => $d['is_available'])),
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
