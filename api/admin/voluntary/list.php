<?php
/**
 * Get All Voluntary Donations (Admin)
 * GET /api/admin/voluntary/list.php
 * Optional query params: status, blood_group, city
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

requireAuth(['admin']);

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Build query with optional filters
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
    $bloodGroupFilter = isset($_GET['blood_group']) ? $_GET['blood_group'] : null;
    $cityFilter = isset($_GET['city']) ? $_GET['city'] : null;

    $sql = "
        SELECT 
            v.id,
            v.availability_date,
            v.preferred_time,
            v.city,
            v.notes,
            v.status,
            v.approved_at,
            v.rejected_at,
            v.rejection_reason,
            v.scheduled_date,
            v.scheduled_time,
            v.created_at,
            bg.blood_type,
            d.id as donor_id,
            d.age as donor_age,
            d.total_donations,
            u_donor.id as donor_user_id,
            u_donor.name as donor_name,
            u_donor.email as donor_email,
            u_donor.phone as donor_phone,
            h.id as hospital_id,
            u_hospital.name as hospital_name,
            admin.name as approved_by_name
        FROM voluntary_donations v
        JOIN donors d ON v.donor_id = d.id
        JOIN users u_donor ON d.user_id = u_donor.id
        JOIN blood_groups bg ON v.blood_group_id = bg.id
        LEFT JOIN hospitals h ON v.hospital_id = h.id
        LEFT JOIN users u_hospital ON h.user_id = u_hospital.id
        LEFT JOIN users admin ON v.approved_by_admin_id = admin.id
        WHERE 1=1
    ";

    $params = [];

    if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected', 'completed', 'cancelled'])) {
        $sql .= " AND v.status = ?";
        $params[] = $statusFilter;
    }

    if ($bloodGroupFilter) {
        $sql .= " AND bg.blood_type = ?";
        $params[] = $bloodGroupFilter;
    }

    if ($cityFilter) {
        $sql .= " AND v.city LIKE ?";
        $params[] = '%' . $cityFilter . '%';
    }

    $sql .= " ORDER BY 
        CASE v.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            ELSE 3 
        END,
        v.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $donations = $stmt->fetchAll();

    // Get stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM voluntary_donations
    ");
    $stmt->execute();
    $stats = $stmt->fetch();

    // Get unique cities for filter dropdown
    $stmt = $conn->prepare("SELECT DISTINCT city FROM voluntary_donations ORDER BY city");
    $stmt->execute();
    $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'donations' => $donations,
        'stats' => [
            'total' => (int)$stats['total'],
            'pending' => (int)$stats['pending'],
            'approved' => (int)$stats['approved'],
            'rejected' => (int)$stats['rejected'],
            'completed' => (int)$stats['completed']
        ],
        'filters' => [
            'cities' => $cities
        ]
    ]);

} catch (PDOException $e) {
    error_log("Admin Get Voluntary Donations Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch voluntary donations']);
}
