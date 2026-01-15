<?php
/**
 * Donor Accept Blood Request Endpoint
 * POST /api/donor/requests/accept.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

requireAuth(['donor']);

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['request_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit;
}

$requestId = (int) $input['request_id'];
$donorId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $conn->beginTransaction();

    // Check if request exists and is available
    $stmt = $conn->prepare("SELECT id, status, request_code, hospital_name, city FROM blood_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        $conn->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    if ($request['status'] !== 'approved') {
        $conn->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Request is not available. Status: ' . $request['status']]);
        exit;
    }

    // Check if donor already has an active donation
    $stmt = $conn->prepare("SELECT id FROM donations WHERE donor_id = ? AND status NOT IN ('completed', 'cancelled')");
    $stmt->execute([$donorId]);
    if ($stmt->fetch()) {
        $conn->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You already have an active donation. Complete or cancel it first.']);
        exit;
    }

    // Check if donor already accepted this request
    $stmt = $conn->prepare("SELECT id FROM donations WHERE donor_id = ? AND request_id = ?");
    $stmt->execute([$donorId, $requestId]);
    if ($stmt->fetch()) {
        $conn->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You have already responded to this request']);
        exit;
    }

    // Create donation record
    $stmt = $conn->prepare("INSERT INTO donations (request_id, donor_id, status, accepted_at) VALUES (?, ?, 'accepted', NOW())");
    $stmt->execute([$requestId, $donorId]);
    $donationId = $conn->lastInsertId();

    // Update request status to in_progress
    $stmt = $conn->prepare("UPDATE blood_requests SET status = 'in_progress' WHERE id = ?");
    $stmt->execute([$requestId]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Request accepted successfully',
        'donation' => [
            'id' => $donationId,
            'request_code' => $request['request_code'],
            'status' => 'accepted',
            'hospital_name' => $request['hospital_name'],
            'city' => $request['city']
        ]
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Donor Accept Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to accept request']);
}
