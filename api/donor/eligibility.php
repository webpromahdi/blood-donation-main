<?php
/**
 * Donor Eligibility Check Endpoint
 * GET /api/donor/eligibility.php
 * 
 * Returns donor's eligibility status for blood donation
 * based on cooldown period (90 days from last donation)
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

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Cooldown period in days (90 days as per requirement)
define('DONATION_COOLDOWN_DAYS', 90);

try {
    // Get donor profile with last donation info
    $stmt = $conn->prepare("
        SELECT d.id as donor_id, d.last_donation_date, d.next_eligible_date, d.total_donations,
               u.name, u.status as account_status, bg.blood_type
        FROM donors d
        JOIN users u ON d.user_id = u.id
        JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE d.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $donor = $stmt->fetch();

    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor profile not found']);
        exit;
    }

    // Check account status
    if ($donor['account_status'] !== 'approved') {
        echo json_encode([
            'success' => true,
            'eligible' => false,
            'reason' => 'account_not_approved',
            'message' => 'Your account is pending approval. Please wait for admin review.',
            'account_status' => $donor['account_status']
        ]);
        exit;
    }

    // Get last completed donation date from donations table (more reliable)
    $stmt = $conn->prepare("
        SELECT MAX(completed_at) as last_donation_date
        FROM donations 
        WHERE donor_id = ? AND status = 'completed'
    ");
    $stmt->execute([$donor['donor_id']]);
    $lastDonation = $stmt->fetch();

    // Use either donations table or donors table date (whichever is more recent)
    $lastDonationDate = null;
    if ($lastDonation && $lastDonation['last_donation_date']) {
        $lastDonationDate = $lastDonation['last_donation_date'];
    }
    if ($donor['last_donation_date']) {
        if (!$lastDonationDate || strtotime($donor['last_donation_date']) > strtotime($lastDonationDate)) {
            $lastDonationDate = $donor['last_donation_date'];
        }
    }

    // Calculate eligibility
    $isEligible = true;
    $nextEligibleDate = null;
    $daysUntilEligible = 0;
    $reason = null;
    $message = 'You are eligible to donate blood.';

    if ($lastDonationDate) {
        $lastDate = new DateTime($lastDonationDate);
        $today = new DateTime('today');
        $nextDate = clone $lastDate;
        $nextDate->modify('+' . DONATION_COOLDOWN_DAYS . ' days');
        
        if ($today < $nextDate) {
            $isEligible = false;
            $nextEligibleDate = $nextDate->format('Y-m-d');
            $interval = $today->diff($nextDate);
            $daysUntilEligible = $interval->days;
            $reason = 'cooldown_period';
            $message = "You can donate again after " . $nextDate->format('F d, Y') . " (" . $daysUntilEligible . " days remaining).";
        } else {
            $nextEligibleDate = $today->format('Y-m-d');
        }
    }

    // Check for pending/approved voluntary donations (donor shouldn't have active requests)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as active_count
        FROM voluntary_donations 
        WHERE donor_id = ? AND status IN ('pending', 'approved', 'scheduled')
    ");
    $stmt->execute([$donor['donor_id']]);
    $activeRequests = $stmt->fetch();

    $hasActiveRequest = $activeRequests['active_count'] > 0;

    // Get total completed donations count from donations table
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM donations 
        WHERE donor_id = ? AND status = 'completed'
    ");
    $stmt->execute([$donor['donor_id']]);
    $completedCount = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'eligible' => $isEligible,
        'reason' => $reason,
        'message' => $message,
        'cooldown_days' => DONATION_COOLDOWN_DAYS,
        'last_donation_date' => $lastDonationDate,
        'next_eligible_date' => $nextEligibleDate,
        'days_until_eligible' => $daysUntilEligible,
        'has_active_request' => $hasActiveRequest,
        'total_donations' => (int) $completedCount,
        'donor_info' => [
            'name' => $donor['name'],
            'blood_type' => $donor['blood_type']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Donor Eligibility Check Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to check eligibility']);
}
