<?php
/**
 * Admin List Blood Requests Endpoint
 * GET /api/admin/requests.php
 * Query params: ?status=pending|approved|rejected|in_progress|completed&urgency=normal|emergency
 * 
 * Normalized Schema: Uses blood_group_id FK, seeker_id/hospital_id FKs,
 *                    donations -> donors -> users for donor info
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
    // Query with normalized schema
    $sql = "SELECT r.*, bg.blood_type,
                   -- Requester info (hospital or seeker)
                   COALESCE(h_user.name, s_user.name) as requester_name, 
                   COALESCE(h_user.email, s_user.email) as requester_email,
                   CASE WHEN r.hospital_id IS NOT NULL THEN 'hospital' ELSE 'seeker' END as requester_type,
                   -- Donation info
                   dn.id as donation_id, dn.status as donation_status, dn.donor_id,
                   donor_user.name as donor_name
            FROM blood_requests r
            JOIN blood_groups bg ON r.blood_group_id = bg.id
            LEFT JOIN hospitals h ON r.hospital_id = h.id
            LEFT JOIN users h_user ON h.user_id = h_user.id
            LEFT JOIN seekers s ON r.seeker_id = s.id
            LEFT JOIN users s_user ON s.user_id = s_user.id
            LEFT JOIN donations dn ON r.id = dn.request_id AND dn.status != 'cancelled'
            LEFT JOIN donors d ON dn.donor_id = d.id
            LEFT JOIN users donor_user ON d.user_id = donor_user.id
            WHERE 1=1";

    $params = [];

    // Filter by status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $sql .= " AND r.status = ?";
        $params[] = $_GET['status'];
    }

    // Filter by urgency
    if (isset($_GET['urgency']) && !empty($_GET['urgency'])) {
        $sql .= " AND r.urgency = ?";
        $params[] = $_GET['urgency'];
    }

    $sql .= " ORDER BY r.urgency DESC, r.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

    // Format response
    $formattedRequests = array_map(function ($req) {
        return [
            'id' => $req['id'],
            'request_code' => $req['request_code'],
            'patient_name' => $req['patient_name'],
            'patient_age' => $req['patient_age'],
            'blood_type' => $req['blood_type'],
            'quantity' => $req['quantity'],
            'hospital_name' => $req['hospital_name'],
            'city' => $req['city'],
            'contact_phone' => $req['contact_phone'],
            'contact_email' => $req['contact_email'],
            'required_date' => $req['required_date'],
            'medical_reason' => $req['medical_reason'],
            'urgency' => $req['urgency'],
            'status' => $req['status'],
            'requester_type' => $req['requester_type'],
            'requester_name' => $req['requester_name'],
            'created_at' => $req['created_at'],
            'approved_at' => $req['approved_at'],
            'rejected_at' => $req['rejected_at'],
            'rejection_reason' => $req['rejection_reason'],
            'donation' => $req['donation_id'] ? [
                'id' => $req['donation_id'],
                'status' => $req['donation_status'],
                'donor_id' => $req['donor_id'],
                'donor_name' => $req['donor_name']
            ] : null
        ];
    }, $requests);

    echo json_encode([
        'success' => true,
        'requests' => $formattedRequests,
        'total' => count($formattedRequests)
    ]);

} catch (PDOException $e) {
    error_log("Admin Requests Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch requests']);
}
