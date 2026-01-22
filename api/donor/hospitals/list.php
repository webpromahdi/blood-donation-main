<?php
/**
 * Get Approved Hospitals for Voluntary Donation
 * GET /api/donor/hospitals/list.php
 * Optional query params: city, search
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

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Build query with optional filters
    $cityFilter = isset($_GET['city']) ? trim($_GET['city']) : null;
    $searchFilter = isset($_GET['search']) ? trim($_GET['search']) : null;

    $sql = "
        SELECT 
            h.id,
            u.name as hospital_name,
            h.city,
            h.address,
            h.hospital_type,
            h.contact_person,
            u.phone,
            h.operating_hours,
            h.has_blood_bank
        FROM hospitals h
        JOIN users u ON h.user_id = u.id
        WHERE u.status = 'approved'
    ";

    $params = [];

    // Filter by city if provided
    if ($cityFilter) {
        $sql .= " AND LOWER(h.city) LIKE LOWER(?)";
        $params[] = "%$cityFilter%";
    }

    // Search by hospital name if provided
    if ($searchFilter) {
        $sql .= " AND LOWER(u.name) LIKE LOWER(?)";
        $params[] = "%$searchFilter%";
    }

    $sql .= " ORDER BY u.name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique cities for filter dropdown
    $cityStmt = $conn->prepare("
        SELECT DISTINCT h.city 
        FROM hospitals h
        JOIN users u ON h.user_id = u.id
        WHERE u.status = 'approved' AND h.city IS NOT NULL AND h.city != ''
        ORDER BY h.city ASC
    ");
    $cityStmt->execute();
    $cities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'hospitals' => $hospitals,
        'cities' => $cities
    ]);

} catch (PDOException $e) {
    error_log("Get Approved Hospitals Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch hospitals']);
}
