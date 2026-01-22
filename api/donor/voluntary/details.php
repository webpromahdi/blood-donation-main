<?php
/**
 * Get Single Voluntary Donation Details
 * GET /api/donor/voluntary/details.php?id=X
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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Voluntary donation ID is required']);
    exit;
}

$voluntaryId = (int)$_GET['id'];

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

    // Get voluntary donation details - ensure it belongs to this donor
    $stmt = $conn->prepare("
        SELECT 
            v.id,
            v.availability_date,
            v.preferred_time,
            v.city as requested_city,
            v.notes,
            v.status,
            v.approved_at,
            v.rejected_at,
            v.rejection_reason,
            v.scheduled_date,
            v.scheduled_time,
            v.created_at,
            v.updated_at,
            bg.blood_type,
            h.id as hospital_id,
            u_hospital.name as hospital_name,
            h.city as hospital_city,
            h.address as hospital_address,
            u_hospital.phone as hospital_phone,
            h.operating_hours as hospital_hours,
            h.hospital_type,
            u_donor.name as donor_name,
            u_donor.email as donor_email,
            u_donor.phone as donor_phone
        FROM voluntary_donations v
        JOIN blood_groups bg ON v.blood_group_id = bg.id
        JOIN donors d ON v.donor_id = d.id
        JOIN users u_donor ON d.user_id = u_donor.id
        LEFT JOIN hospitals h ON v.hospital_id = h.id
        LEFT JOIN users u_hospital ON h.user_id = u_hospital.id
        WHERE v.id = ? AND v.donor_id = ?
    ");
    $stmt->execute([$voluntaryId, $donor['id']]);
    $voluntary = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voluntary) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Voluntary donation request not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'voluntary' => $voluntary
    ]);

} catch (PDOException $e) {
    error_log("Get Voluntary Donation Details Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch voluntary donation details']);
}
