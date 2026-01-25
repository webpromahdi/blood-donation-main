<?php
/**
 * Get Approved Voluntary Donors (Hospital)
 * GET /api/hospital/voluntary/list.php
 * Optional query params: blood_group, city, date
 * Only returns APPROVED voluntary donations
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

requireAuth(['hospital']);

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get current hospital ID
    $stmt = $conn->prepare("SELECT id FROM hospitals WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $hospital = $stmt->fetch();
    
    if (!$hospital) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Hospital profile not found']);
        exit;
    }
    
    $hospitalId = $hospital['id'];
    
    // Build query with optional filters
    // Show: approved donations assigned to THIS hospital OR with no hospital (open for any)
    // Also show scheduled donations for this hospital
    $bloodGroupFilter = isset($_GET['blood_group']) ? $_GET['blood_group'] : null;
    $cityFilter = isset($_GET['city']) ? $_GET['city'] : null;
    $dateFilter = isset($_GET['date']) ? $_GET['date'] : null;

    $sql = "
        SELECT 
            v.id,
            v.availability_date,
            v.preferred_time,
            v.city,
            v.notes,
            v.status,
            v.approved_at,
            v.created_at,
            v.scheduled_date,
            v.scheduled_time,
            bg.blood_type,
            d.id as donor_id,
            d.age as donor_age,
            d.total_donations,
            d.last_donation_date,
            u_donor.id as donor_user_id,
            u_donor.name as donor_name,
            u_donor.phone as donor_phone,
            u_donor.email as donor_email
        FROM voluntary_donations v
        JOIN donors d ON v.donor_id = d.id
        JOIN users u_donor ON d.user_id = u_donor.id
        JOIN blood_groups bg ON v.blood_group_id = bg.id
        WHERE v.status IN ('approved', 'scheduled')
        AND v.availability_date >= CURDATE()
        AND (v.hospital_id = ? OR v.hospital_id IS NULL)
    ";

    $params = [$hospitalId];

    if ($bloodGroupFilter) {
        $sql .= " AND bg.blood_type = ?";
        $params[] = $bloodGroupFilter;
    }

    if ($cityFilter) {
        $sql .= " AND v.city LIKE ?";
        $params[] = '%' . $cityFilter . '%';
    }

    if ($dateFilter) {
        $sql .= " AND v.availability_date = ?";
        $params[] = $dateFilter;
    }

    $sql .= " ORDER BY v.availability_date ASC, v.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $donors = $stmt->fetchAll();

    // Get stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(DISTINCT v.donor_id) as unique_donors,
            COUNT(DISTINCT bg.blood_type) as blood_types
        FROM voluntary_donations v
        JOIN blood_groups bg ON v.blood_group_id = bg.id
        WHERE v.status = 'approved'
        AND v.availability_date >= CURDATE()
    ");
    $stmt->execute();
    $stats = $stmt->fetch();

    // Get available cities for filter dropdown
    $stmt = $conn->prepare("
        SELECT DISTINCT city 
        FROM voluntary_donations 
        WHERE status = 'approved' 
        AND availability_date >= CURDATE()
        ORDER BY city
    ");
    $stmt->execute();
    $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get blood groups for filter
    $stmt = $conn->prepare("SELECT id, blood_type FROM blood_groups ORDER BY blood_type");
    $stmt->execute();
    $bloodGroups = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'donors' => $donors,
        'stats' => [
            'total' => (int)$stats['total'],
            'unique_donors' => (int)$stats['unique_donors'],
            'blood_types' => (int)$stats['blood_types']
        ],
        'filters' => [
            'cities' => $cities,
            'blood_groups' => $bloodGroups
        ]
    ]);

} catch (PDOException $e) {
    error_log("Hospital Get Voluntary Donors Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch voluntary donors']);
}
