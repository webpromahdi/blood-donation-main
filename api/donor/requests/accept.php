<?php
/**
 * Donor Accept Blood Request Endpoint
 * POST /api/donor/requests/accept.php
 * 
 * Normalized Schema: Creates donation linked to donors table
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
require_once __DIR__ . '/../../services/NotificationService.php';

$user = requireAuth(['donor']);

// Require approved status to accept blood requests
requireApprovedStatus($_SESSION['user_id'], 'donor');

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['request_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit;
}

$requestId = (int) $input['request_id'];
$userId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get donor_id from donors table
    $stmt = $conn->prepare("SELECT id, next_eligible_date FROM donors WHERE user_id = ?");
    $stmt->execute([$userId]);
    $donor = $stmt->fetch();
    
    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor record not found']);
        exit;
    }
    
    $donorId = $donor['id'];

    $conn->beginTransaction();

    // Check if request exists and is available - get full details
    $stmt = $conn->prepare("
        SELECT r.id, r.status, r.request_code, r.hospital_name, r.city, 
               r.requester_id, r.requester_type, bg.blood_type
        FROM blood_requests r
        JOIN blood_groups bg ON r.blood_group_id = bg.id
        WHERE r.id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        $conn->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    // Only allow accepting 'approved' requests
    if ($request['status'] !== 'approved') {
        $conn->rollBack();
        $statusMessage = $request['status'] === 'pending' 
            ? 'Request is still pending admin approval' 
            : ($request['status'] === 'in_progress' 
                ? 'Request has already been accepted by another donor' 
                : 'Request is not available');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $statusMessage . '. Status: ' . $request['status']]);
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

    // Check donor eligibility using next_eligible_date from donors table
    if ($donor['next_eligible_date']) {
        $nextEligibleDate = new DateTime($donor['next_eligible_date']);
        $today = new DateTime();

        if ($today < $nextEligibleDate) {
            $conn->rollBack();
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'You are not eligible to donate yet. Next eligible date: ' . $nextEligibleDate->format('Y-m-d'),
                'next_eligible_date' => $nextEligibleDate->format('Y-m-d')
            ]);
            exit;
        }
    }

    // Create donation record
    $stmt = $conn->prepare("INSERT INTO donations (request_id, donor_id, status, accepted_at) VALUES (?, ?, 'accepted', NOW())");
    $stmt->execute([$requestId, $donorId]);
    $donationId = $conn->lastInsertId();

    // Update request status to in_progress
    $stmt = $conn->prepare("UPDATE blood_requests SET status = 'in_progress' WHERE id = ?");
    $stmt->execute([$requestId]);

    $conn->commit();
    
    // Get donor name for notifications
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $donorUser = $stmt->fetch();
    $donorName = $donorUser ? $donorUser['name'] : 'Unknown';
    
    // Send notifications
    $notificationService = new NotificationService($conn);
    
    // D5: Confirm to donor that they accepted the request
    $notificationService->notifyDonorDonationAccepted($userId, $donationId, $request['request_code'], $request['hospital_name']);
    
    // H5: Notify hospital that donor accepted (if requester is hospital)
    if ($request['requester_type'] === 'hospital') {
        $notificationService->notifyHospitalDonorAccepted(
            $request['requester_id'], 
            $donationId, 
            $donorName, 
            $request['blood_type'], 
            $request['request_code']
        );
    }
    
    // S4: Notify seeker that a donor was found (if requester is seeker)
    if ($request['requester_type'] === 'seeker') {
        $notificationService->notifySeekerDonorFound($request['requester_id'], $requestId, $request['request_code'], $donorName);
    }

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
