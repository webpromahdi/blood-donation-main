<?php
/**
 * Admin Blood Groups Stats and Donors Endpoint
 * GET /api/admin/blood-groups.php
 * Query params: ?blood_type=O+ (optional, returns all if not specified)
 * Returns blood group statistics and donor lists
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
    // Get blood group counts
    $bloodTypes = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
    $counts = [];
    
    foreach ($bloodTypes as $type) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'donor' AND blood_group = ? AND status = 'approved'");
        $stmt->execute([$type]);
        $counts[$type] = (int)$stmt->fetch()['count'];
    }

    // If a specific blood type is requested, get donor list
    $donors = [];
    $bloodTypeFilter = isset($_GET['blood_type']) ? $_GET['blood_type'] : null;
    
    if ($bloodTypeFilter && in_array($bloodTypeFilter, $bloodTypes)) {
        $sql = "SELECT u.id, u.name, u.email, u.phone, u.blood_group, u.age, u.city, u.address,
                       u.weight, u.status, u.created_at,
                       COUNT(DISTINCT d.id) as total_donations,
                       MAX(d.completed_at) as last_donation,
                       dh.weight as health_weight, dh.height, dh.has_diabetes, dh.has_hypertension,
                       dh.has_heart_disease
                FROM users u
                LEFT JOIN donations d ON u.id = d.donor_id AND d.status = 'completed'
                LEFT JOIN donor_health dh ON u.id = dh.donor_id
                WHERE u.role = 'donor' AND u.blood_group = ? AND u.status = 'approved'
                GROUP BY u.id
                ORDER BY total_donations DESC, u.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$bloodTypeFilter]);
        $results = $stmt->fetchAll();
        
        foreach ($results as $donor) {
            // Determine health status
            $healthStatus = 'Healthy';
            if ($donor['has_diabetes'] || $donor['has_hypertension'] || $donor['has_heart_disease']) {
                $healthStatus = 'Requires Review';
            }
            
            // Determine availability status
            $displayStatus = 'Available';
            
            // Check if donor has active donation
            $stmt2 = $conn->prepare("SELECT id FROM donations WHERE donor_id = ? AND status NOT IN ('completed', 'cancelled')");
            $stmt2->execute([$donor['id']]);
            if ($stmt2->fetch()) {
                $displayStatus = 'Busy';
            }
            
            $donors[] = [
                'id' => $donor['id'],
                'name' => $donor['name'],
                'email' => $donor['email'],
                'phone' => $donor['phone'],
                'group' => $donor['blood_group'],
                'age' => $donor['age'],
                'gender' => 'Not specified', // Add gender field to users table if needed
                'weight' => $donor['health_weight'] ?? $donor['weight'],
                'city' => $donor['city'],
                'address' => $donor['address'],
                'location' => $donor['city'] ?? 'Unknown',
                'status' => $displayStatus,
                'healthStatus' => $healthStatus,
                'donations' => (int)$donor['total_donations'],
                'lastDonation' => $donor['last_donation'] ? date('M d, Y', strtotime($donor['last_donation'])) : 'Never',
                'registeredAt' => date('M d, Y', strtotime($donor['created_at']))
            ];
        }
    } else {
        // Return all donors if no specific type requested
        $sql = "SELECT u.id, u.name, u.email, u.phone, u.blood_group, u.age, u.city, u.address,
                       u.weight, u.status, u.created_at,
                       COUNT(DISTINCT d.id) as total_donations,
                       MAX(d.completed_at) as last_donation,
                       dh.weight as health_weight, dh.height, dh.has_diabetes, dh.has_hypertension,
                       dh.has_heart_disease
                FROM users u
                LEFT JOIN donations d ON u.id = d.donor_id AND d.status = 'completed'
                LEFT JOIN donor_health dh ON u.id = dh.donor_id
                WHERE u.role = 'donor' AND u.status = 'approved'
                GROUP BY u.id
                ORDER BY u.blood_group, total_donations DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        foreach ($results as $donor) {
            $healthStatus = 'Healthy';
            if ($donor['has_diabetes'] || $donor['has_hypertension'] || $donor['has_heart_disease']) {
                $healthStatus = 'Requires Review';
            }
            
            $displayStatus = 'Available';
            $stmt2 = $conn->prepare("SELECT id FROM donations WHERE donor_id = ? AND status NOT IN ('completed', 'cancelled')");
            $stmt2->execute([$donor['id']]);
            if ($stmt2->fetch()) {
                $displayStatus = 'Busy';
            }
            
            $donors[] = [
                'id' => $donor['id'],
                'name' => $donor['name'],
                'email' => $donor['email'],
                'phone' => $donor['phone'],
                'group' => $donor['blood_group'],
                'age' => $donor['age'],
                'gender' => 'Not specified',
                'weight' => $donor['health_weight'] ?? $donor['weight'],
                'city' => $donor['city'],
                'address' => $donor['address'],
                'location' => $donor['city'] ?? 'Unknown',
                'status' => $displayStatus,
                'healthStatus' => $healthStatus,
                'donations' => (int)$donor['total_donations'],
                'lastDonation' => $donor['last_donation'] ? date('M d, Y', strtotime($donor['last_donation'])) : 'Never',
                'registeredAt' => date('M d, Y', strtotime($donor['created_at']))
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'counts' => $counts,
        'donors' => $donors,
        'total_donors' => count($donors)
    ]);

} catch (PDOException $e) {
    error_log("Admin Blood Groups Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch blood group data']);
}
