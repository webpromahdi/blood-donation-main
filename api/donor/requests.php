<?php
/**
 * Donor Matching Blood Requests Endpoint
 * GET /api/donor/requests.php
 * 
 * Normalized Schema: Reads from donors + blood_groups + blood_requests
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

$user = requireAuth(['donor']);

// Require approved status to view/accept blood requests
requireApprovedStatus($_SESSION['user_id'], 'donor');

$userId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get donor's blood group from normalized tables
    $stmt = $conn->prepare("
        SELECT d.id as donor_id, bg.blood_type as blood_group, bg.can_donate_to
        FROM donors d 
        JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE d.user_id = ?
    ");
    $stmt->execute([$userId]);
    $donor = $stmt->fetch();

    if (!$donor || !$donor['blood_group']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Blood group not set in profile']);
        exit;
    }

    $donorId = $donor['donor_id'];
    $donorBloodGroup = $donor['blood_group'];
    
    // Get compatible blood types from the JSON field
    $canDonateTo = json_decode($donor['can_donate_to'], true) ?? [$donorBloodGroup];

    // Get blood group IDs for compatible types
    $placeholders = str_repeat('?,', count($canDonateTo) - 1) . '?';
    $stmt = $conn->prepare("SELECT id FROM blood_groups WHERE blood_type IN ($placeholders)");
    $stmt->execute($canDonateTo);
    $compatibleIds = array_column($stmt->fetchAll(), 'id');
    
    if (empty($compatibleIds)) {
        $compatibleIds = [0]; // Prevent SQL error
    }

    // Build SQL with compatible blood group IDs
    $idPlaceholders = str_repeat('?,', count($compatibleIds) - 1) . '?';

    $sql = "SELECT r.*, 
                   bg.blood_type,
                   u.name as requester_name,
                   u.phone as requester_phone,
                   u.email as requester_email,
                   (SELECT COUNT(*) FROM donations d WHERE d.request_id = r.id AND d.status != 'cancelled') as donor_responses
            FROM blood_requests r
            JOIN blood_groups bg ON r.blood_group_id = bg.id
            LEFT JOIN users u ON r.requester_id = u.id
            WHERE r.status = 'approved' 
            AND r.blood_group_id IN ($idPlaceholders)
            AND NOT EXISTS (
                SELECT 1 FROM donations d 
                WHERE d.request_id = r.id 
                AND d.donor_id = ? 
                AND d.status != 'cancelled'
            )";

    // Filter by urgency if specified
    $params = $compatibleIds;
    $params[] = $donorId;

    if (isset($_GET['urgency']) && in_array($_GET['urgency'], ['normal', 'emergency'])) {
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
            'required_date' => $req['required_date'],
            'urgency' => $req['urgency'],
            'medical_reason' => $req['medical_reason'],
            'created_at' => $req['created_at'],
            'donor_responses' => (int) $req['donor_responses'],
            // Include seeker contact info for donor view
            'contact_phone' => $req['contact_phone'],
            'contact_email' => $req['contact_email'],
            'requester_name' => $req['requester_name'],
            'requester_phone' => $req['requester_phone'],
            'requester_email' => $req['requester_email']
        ];
    }, $requests);

    // Separate emergency and normal
    $emergency = array_filter($formattedRequests, fn($r) => $r['urgency'] === 'emergency');
    $normal = array_filter($formattedRequests, fn($r) => $r['urgency'] === 'normal');

    echo json_encode([
        'success' => true,
        'donor_blood_group' => $donorBloodGroup,
        'emergency_requests' => array_values($emergency),
        'normal_requests' => array_values($normal),
        'total' => count($formattedRequests)
    ]);

} catch (PDOException $e) {
    error_log("Donor Requests Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch requests']);
}
