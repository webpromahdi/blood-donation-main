<?php
/**
 * Admin Blood Groups Stats and Donors Endpoint
 * GET /api/admin/blood-groups.php
 * Query params: ?blood_type=O+ (optional, returns all if not specified)
 * Returns blood group statistics and donor lists
 * 
 * Normalized Schema: Uses blood_groups table, donors table with proper JOINs
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
    // Get blood group counts from blood_groups table and donors
    $stmt = $conn->query("
        SELECT bg.blood_type, COUNT(d.id) as count 
        FROM blood_groups bg
        LEFT JOIN donors d ON bg.id = d.blood_group_id
        LEFT JOIN users u ON d.user_id = u.id AND u.status = 'approved'
        GROUP BY bg.id, bg.blood_type
        ORDER BY bg.id
    ");
    $bloodGroupRows = $stmt->fetchAll();
    
    $counts = [];
    $bloodTypes = [];
    foreach ($bloodGroupRows as $row) {
        $counts[$row['blood_type']] = (int) $row['count'];
        $bloodTypes[] = $row['blood_type'];
    }

    // If a specific blood type is requested, get donor list
    $donors = [];
    $bloodTypeFilter = isset($_GET['blood_type']) ? $_GET['blood_type'] : null;
    
    // Build base query with normalized schema
    $baseQuery = "SELECT d.id as donor_id, u.id as user_id, u.name, u.email, u.phone, 
                         bg.blood_type as blood_group, d.age, d.gender, d.weight, d.city, d.address,
                         u.status, u.created_at, d.total_donations, d.last_donation_date, d.next_eligible_date,
                         dh.has_diabetes, dh.has_hypertension, dh.has_heart_disease, dh.height
                  FROM donors d
                  JOIN users u ON d.user_id = u.id
                  LEFT JOIN blood_groups bg ON d.blood_group_id = bg.id
                  LEFT JOIN donor_health dh ON d.id = dh.donor_id
                  WHERE u.status = 'approved'";
    
    if ($bloodTypeFilter && in_array($bloodTypeFilter, $bloodTypes)) {
        $sql = $baseQuery . " AND bg.blood_type = ? ORDER BY d.total_donations DESC, u.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$bloodTypeFilter]);
    } else {
        // Return all donors if no specific type requested
        $sql = $baseQuery . " ORDER BY bg.blood_type, d.total_donations DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
    
    $results = $stmt->fetchAll();
    
    foreach ($results as $donor) {
        // Determine health status
        $healthStatus = 'Healthy';
        if ($donor['has_diabetes'] || $donor['has_hypertension'] || $donor['has_heart_disease']) {
            $healthStatus = 'Requires Review';
        }
        
        // Determine availability status based on next_eligible_date
        $displayStatus = 'Available';
        
        // Check if donor is eligible based on date
        if ($donor['next_eligible_date'] && new DateTime($donor['next_eligible_date']) > new DateTime()) {
            $displayStatus = 'Not Eligible';
        }
        
        // Check if donor has active donation
        $stmt2 = $conn->prepare("SELECT id FROM donations WHERE donor_id = ? AND status NOT IN ('completed', 'cancelled')");
        $stmt2->execute([$donor['donor_id']]);
        if ($stmt2->fetch()) {
            $displayStatus = 'Busy';
        }
        
        $donors[] = [
            'id' => $donor['user_id'],
            'donor_id' => $donor['donor_id'],
            'name' => $donor['name'],
            'email' => $donor['email'],
            'phone' => $donor['phone'],
            'group' => $donor['blood_group'],
            'age' => $donor['age'],
            'gender' => $donor['gender'] ?? 'Not specified',
            'weight' => $donor['weight'],
            'height' => $donor['height'],
            'city' => $donor['city'],
            'address' => $donor['address'],
            'location' => $donor['city'] ?? 'Unknown',
            'status' => $displayStatus,
            'healthStatus' => $healthStatus,
            'donations' => (int) $donor['total_donations'],
            'lastDonation' => $donor['last_donation_date'] ? date('M d, Y', strtotime($donor['last_donation_date'])) : 'Never',
            'nextEligibleDate' => $donor['next_eligible_date'],
            'registeredAt' => date('M d, Y', strtotime($donor['created_at']))
        ];
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
