<?php
/**
 * Get Donor's Voluntary Donations
 * GET /api/donor/voluntary/list.php
 * Optional query params: status
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
    // Get donor ID
    $stmt = $conn->prepare("SELECT id FROM donors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $donor = $stmt->fetch();

    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor profile not found']);
        exit;
    }

    // Build query with optional status filter
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
    
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
            h.id as hospital_id,
            u_hospital.name as hospital_name
        FROM voluntary_donations v
        JOIN blood_groups bg ON v.blood_group_id = bg.id
        LEFT JOIN hospitals h ON v.hospital_id = h.id
        LEFT JOIN users u_hospital ON h.user_id = u_hospital.id
        WHERE v.donor_id = ?
    ";

    $params = [$donor['id']];

    if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected', 'completed', 'cancelled'])) {
        $sql .= " AND v.status = ?";
        $params[] = $statusFilter;
    }

    $sql .= " ORDER BY v.created_at DESC";

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
        WHERE donor_id = ?
    ");
    $stmt->execute([$donor['id']]);
    $stats = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'donations' => $donations,
        'stats' => [
            'total' => (int)$stats['total'],
            'pending' => (int)$stats['pending'],
            'approved' => (int)$stats['approved'],
            'rejected' => (int)$stats['rejected'],
            'completed' => (int)$stats['completed']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Get Voluntary Donations Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch voluntary donations']);
}
