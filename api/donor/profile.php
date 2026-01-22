<?php
/**
 * Donor Profile & Stats Endpoint
 * GET /api/donor/profile.php
 * 
 * Normalized Schema: Reads from users + donors + blood_groups tables
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

$userId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get donor profile from normalized tables (users + donors + blood_groups)
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at,
               d.id as donor_id, d.age, d.weight, d.gender, d.city, d.address,
               d.is_available, d.total_donations, d.last_donation_date, d.next_eligible_date,
               bg.blood_type as blood_group
        FROM users u
        JOIN donors d ON u.id = d.user_id
        JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $donor = $stmt->fetch();

    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor profile not found']);
        exit;
    }

    // Get account status - return early if pending
    $accountStatus = $donor['status'] ?? 'pending';
    
    // If account is pending, return limited profile with status only
    if ($accountStatus !== 'approved') {
        echo json_encode([
            'success' => true,
            'profile' => [
                'id' => $donor['id'],
                'name' => $donor['name'],
                'email' => $donor['email'],
                'status' => $accountStatus
            ],
            'account_status' => $accountStatus,
            'requires_approval' => true,
            'stats' => null,
            'active_donation' => null
        ]);
        exit;
    }

    $donorId = $donor['donor_id'];

    // Cooldown period in days (90 days)
    $cooldownDays = 90;

    // Get total completed donations from donations table
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donations WHERE donor_id = ? AND status = 'completed'");
    $stmt->execute([$donorId]);
    $totalDonations = $stmt->fetch()['count'];

    // Last donation date from donations table
    $stmt = $conn->prepare("SELECT completed_at FROM donations WHERE donor_id = ? AND status = 'completed' ORDER BY completed_at DESC LIMIT 1");
    $stmt->execute([$donorId]);
    $lastDonation = $stmt->fetch();

    // Use last donation date from either donations table or donors table (whichever is more recent)
    $lastDonationDate = null;
    if ($lastDonation && $lastDonation['completed_at']) {
        $lastDonationDate = $lastDonation['completed_at'];
    }
    if ($donor['last_donation_date']) {
        if (!$lastDonationDate || strtotime($donor['last_donation_date']) > strtotime($lastDonationDate)) {
            $lastDonationDate = $donor['last_donation_date'];
        }
    }

    // Active donation (if any)
    $stmt = $conn->prepare("
        SELECT d.*, r.request_code, r.hospital_name, r.city as request_city, 
               bg.blood_type, r.urgency, r.quantity, r.patient_name, r.required_date, r.id as request_id
        FROM donations d 
        JOIN blood_requests r ON d.request_id = r.id 
        JOIN blood_groups bg ON r.blood_group_id = bg.id
        WHERE d.donor_id = ? AND d.status NOT IN ('completed', 'cancelled')
        ORDER BY d.created_at DESC LIMIT 1
    ");
    $stmt->execute([$donorId]);
    $activeDonation = $stmt->fetch();

    // Calculate next eligible date (90 days after last donation)
    $nextEligible = null;
    $isEligible = true;
    $daysUntilEligible = 0;

    if ($lastDonationDate) {
        $lastDate = new DateTime($lastDonationDate);
        $today = new DateTime('today');
        $nextDate = clone $lastDate;
        $nextDate->modify('+' . $cooldownDays . ' days');
        $nextEligible = $nextDate->format('Y-m-d');
        
        if ($today < $nextDate) {
            $isEligible = false;
            $interval = $today->diff($nextDate);
            $daysUntilEligible = $interval->days;
        }
    }

    // Lives saved estimate (1 donation = 1 life as per requirement)
    $livesSaved = $totalDonations;

    echo json_encode([
        'success' => true,
        'profile' => [
            'id' => $donor['id'],
            'donor_id' => $donorId,
            'name' => $donor['name'],
            'email' => $donor['email'],
            'phone' => $donor['phone'],
            'blood_group' => $donor['blood_group'],
            'age' => $donor['age'],
            'weight' => $donor['weight'],
            'gender' => $donor['gender'],
            'city' => $donor['city'],
            'address' => $donor['address'],
            'is_available' => (bool) $donor['is_available'],
            'status' => $accountStatus,
            'member_since' => $donor['created_at']
        ],
        'account_status' => $accountStatus,
        'stats' => [
            'total_donations' => (int) $totalDonations,
            'lives_saved' => (int) $livesSaved,
            'last_donation' => $lastDonationDate,
            'next_eligible' => $nextEligible,
            'is_eligible' => $isEligible,
            'days_until_eligible' => $daysUntilEligible,
            'cooldown_days' => $cooldownDays
        ],
        'eligibility' => [
            'can_donate' => $isEligible,
            'last_donation_date' => $lastDonationDate,
            'next_eligible_date' => $nextEligible,
            'days_remaining' => $daysUntilEligible,
            'cooldown_period' => $cooldownDays
        ],
        'active_donation' => $activeDonation ? [
            'id' => $activeDonation['id'],
            'request_id' => $activeDonation['request_id'],
            'request_code' => $activeDonation['request_code'],
            'status' => $activeDonation['status'],
            'hospital_name' => $activeDonation['hospital_name'],
            'city' => $activeDonation['request_city'],
            'blood_type' => $activeDonation['blood_type'],
            'urgency' => $activeDonation['urgency'],
            'quantity' => (int) $activeDonation['quantity'],
            'patient_name' => $activeDonation['patient_name'],
            'required_date' => $activeDonation['required_date'],
            'accepted_at' => $activeDonation['accepted_at']
        ] : null
    ]);

} catch (PDOException $e) {
    error_log("Donor Profile Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch profile']);
}
