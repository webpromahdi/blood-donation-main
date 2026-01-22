<?php
/**
 * Submit Voluntary Donation Request
 * POST /api/donor/voluntary/submit.php
 * Body: { hospital_id, availability_date, preferred_time, notes }
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

$user = requireAuth(['donor']);

$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($input['hospital_id']) || !is_numeric($input['hospital_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please select a hospital']);
    exit;
}

if (empty($input['availability_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Availability date is required']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $hospitalId = (int)$input['hospital_id'];

    // Verify hospital exists and is approved
    $stmt = $conn->prepare("
        SELECT h.id, h.city, u.name as hospital_name
        FROM hospitals h
        JOIN users u ON h.user_id = u.id
        WHERE h.id = ? AND u.status = 'approved'
    ");
    $stmt->execute([$hospitalId]);
    $hospital = $stmt->fetch();

    if (!$hospital) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Selected hospital is not available']);
        exit;
    }

    // Get donor info including blood_group_id and last donation date
    $stmt = $conn->prepare("
        SELECT d.id as donor_id, d.blood_group_id, d.city as donor_city, 
               d.last_donation_date, d.next_eligible_date
        FROM donors d 
        WHERE d.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $donor = $stmt->fetch();

    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor profile not found']);
        exit;
    }

    // === ELIGIBILITY CHECK (Server-side enforcement) ===
    // Cooldown period in days (90 days)
    $cooldownDays = 90;

    // Get last completed donation date from donations table
    $stmt = $conn->prepare("
        SELECT MAX(completed_at) as last_donation_date
        FROM donations 
        WHERE donor_id = ? AND status = 'completed'
    ");
    $stmt->execute([$donor['donor_id']]);
    $lastDonationResult = $stmt->fetch();

    // Use either donations table or donors table date (whichever is more recent)
    $lastDonationDate = null;
    if ($lastDonationResult && $lastDonationResult['last_donation_date']) {
        $lastDonationDate = $lastDonationResult['last_donation_date'];
    }
    if ($donor['last_donation_date']) {
        if (!$lastDonationDate || strtotime($donor['last_donation_date']) > strtotime($lastDonationDate)) {
            $lastDonationDate = $donor['last_donation_date'];
        }
    }

    // Check cooldown period
    if ($lastDonationDate) {
        $lastDate = new DateTime($lastDonationDate);
        $today = new DateTime('today');
        $nextEligibleDate = clone $lastDate;
        $nextEligibleDate->modify('+' . $cooldownDays . ' days');
        
        if ($today < $nextEligibleDate) {
            $daysRemaining = $today->diff($nextEligibleDate)->days;
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'You are not eligible to donate yet. You can donate again after ' . $nextEligibleDate->format('F d, Y') . ' (' . $daysRemaining . ' days remaining).',
                'next_eligible_date' => $nextEligibleDate->format('Y-m-d'),
                'days_remaining' => $daysRemaining,
                'reason' => 'cooldown_period'
            ]);
            exit;
        }
    }

    // Check for existing pending/approved voluntary donations
    $stmt = $conn->prepare("
        SELECT COUNT(*) as active_count
        FROM voluntary_donations 
        WHERE donor_id = ? AND status IN ('pending', 'approved', 'scheduled')
    ");
    $stmt->execute([$donor['donor_id']]);
    $activeRequests = $stmt->fetch();

    if ($activeRequests['active_count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'You already have an active voluntary donation request. Please wait for it to be completed or cancelled before submitting a new one.',
            'reason' => 'active_request_exists'
        ]);
        exit;
    }
    // === END ELIGIBILITY CHECK ===

    // Validate date is in the future
    $availabilityDate = $input['availability_date'];
    if (strtotime($availabilityDate) < strtotime('today')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Availability date must be in the future']);
        exit;
    }

    // Check if donor already has a pending request for this date and hospital
    $stmt = $conn->prepare("
        SELECT id FROM voluntary_donations 
        WHERE donor_id = ? AND availability_date = ? AND hospital_id = ? AND status IN ('pending', 'approved')
    ");
    $stmt->execute([$donor['donor_id'], $availabilityDate, $hospitalId]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You already have a voluntary donation request for this date at this hospital']);
        exit;
    }

    // Insert voluntary donation request with hospital_id
    $stmt = $conn->prepare("
        INSERT INTO voluntary_donations (
            donor_id, 
            blood_group_id,
            hospital_id,
            city, 
            availability_date, 
            preferred_time, 
            notes, 
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->execute([
        $donor['donor_id'],
        $donor['blood_group_id'],
        $hospitalId,
        $hospital['city'] ?? null,
        $availabilityDate,
        $input['preferred_time'] ?? 'any',
        $input['notes'] ?? null
    ]);

    $voluntaryId = $conn->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Voluntary donation request submitted successfully! The hospital will review your request.',
        'voluntary_id' => $voluntaryId,
        'hospital_name' => $hospital['hospital_name']
    ]);

} catch (PDOException $e) {
    error_log("Submit Voluntary Donation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit voluntary donation request']);
}
