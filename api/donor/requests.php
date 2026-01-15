<?php
/**
 * Donor Matching Blood Requests Endpoint
 * GET /api/donor/requests.php
 * Returns approved blood requests matching donor's blood type
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

requireAuth(['donor']);

$donorId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get donor's blood group
    $stmt = $conn->prepare("SELECT blood_group FROM users WHERE id = ?");
    $stmt->execute([$donorId]);
    $donor = $stmt->fetch();

    if (!$donor || !$donor['blood_group']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Blood group not set in profile']);
        exit;
    }

    $donorBloodGroup = $donor['blood_group'];

    // Blood compatibility chart - who can this donor donate to?
    $compatibleTypes = getCompatibleRecipients($donorBloodGroup);

    // Build SQL with compatible blood types
    $placeholders = str_repeat('?,', count($compatibleTypes) - 1) . '?';

    $sql = "SELECT r.*, 
                   u.name as requester_name,
                   (SELECT COUNT(*) FROM donations d WHERE d.request_id = r.id AND d.status != 'cancelled') as donor_responses
            FROM blood_requests r
            LEFT JOIN users u ON r.requester_id = u.id
            WHERE r.status = 'approved' 
            AND r.blood_type IN ($placeholders)
            AND NOT EXISTS (
                SELECT 1 FROM donations d 
                WHERE d.request_id = r.id 
                AND d.donor_id = ? 
                AND d.status != 'cancelled'
            )";

    // Filter by urgency if specified
    $params = $compatibleTypes;
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
            'blood_type' => $req['blood_type'],
            'quantity' => $req['quantity'],
            'hospital_name' => $req['hospital_name'],
            'city' => $req['city'],
            'required_date' => $req['required_date'],
            'urgency' => $req['urgency'],
            'medical_reason' => $req['medical_reason'],
            'created_at' => $req['created_at'],
            'donor_responses' => (int) $req['donor_responses']
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

/**
 * Returns blood types that this donor can donate to
 * Based on blood compatibility rules
 */
function getCompatibleRecipients($donorType)
{
    $compatibility = [
        'O-' => ['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'], // Universal donor
        'O+' => ['O+', 'A+', 'B+', 'AB+'],
        'A-' => ['A-', 'A+', 'AB-', 'AB+'],
        'A+' => ['A+', 'AB+'],
        'B-' => ['B-', 'B+', 'AB-', 'AB+'],
        'B+' => ['B+', 'AB+'],
        'AB-' => ['AB-', 'AB+'],
        'AB+' => ['AB+'] // Universal recipient (can only donate to AB+)
    ];

    return $compatibility[$donorType] ?? [$donorType];
}
