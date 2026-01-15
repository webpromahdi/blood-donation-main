<?php
/**
 * Donor Health Profile Endpoint
 * GET /api/donor/health.php
 * Returns health information for the logged-in donor
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
    // Get donor's profile
    $stmt = $conn->prepare("SELECT id, name, email, phone, blood_group, age, weight, city, address, created_at FROM users WHERE id = ?");
    $stmt->execute([$donorId]);
    $donor = $stmt->fetch();

    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor not found']);
        exit;
    }

    // Get donation stats for eligibility check
    $stmt = $conn->prepare("SELECT completed_at FROM donations WHERE donor_id = ? AND status = 'completed' ORDER BY completed_at DESC LIMIT 1");
    $stmt->execute([$donorId]);
    $lastDonation = $stmt->fetch();

    // Get total donations
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donations WHERE donor_id = ? AND status = 'completed'");
    $stmt->execute([$donorId]);
    $totalDonations = $stmt->fetch()['count'];

    // Calculate eligibility (56 days since last donation, weight >= 50kg, age 18-65)
    $isEligible = true;
    $eligibilityReasons = [];

    // Check last donation date
    $nextEligibleDate = null;
    if ($lastDonation && $lastDonation['completed_at']) {
        $lastDate = new DateTime($lastDonation['completed_at']);
        $nextDate = clone $lastDate;
        $nextDate->modify('+56 days');
        $nextEligibleDate = $nextDate->format('Y-m-d');

        if ($nextDate > new DateTime()) {
            $isEligible = false;
            $eligibilityReasons[] = 'Must wait ' . (new DateTime())->diff($nextDate)->days . ' more days since last donation';
        }
    }

    // Check weight
    if ($donor['weight'] && $donor['weight'] < 50) {
        $isEligible = false;
        $eligibilityReasons[] = 'Minimum weight requirement is 50 kg';
    }

    // Check age
    if ($donor['age']) {
        if ($donor['age'] < 18) {
            $isEligible = false;
            $eligibilityReasons[] = 'Must be at least 18 years old';
        } elseif ($donor['age'] > 65) {
            $isEligible = false;
            $eligibilityReasons[] = 'Age limit is 65 years';
        }
    }

    echo json_encode([
        'success' => true,
        'health' => [
            'blood_group' => $donor['blood_group'],
            'age' => $donor['age'],
            'weight' => $donor['weight'],
            'city' => $donor['city'],
            'address' => $donor['address']
        ],
        'eligibility' => [
            'is_eligible' => $isEligible,
            'reasons' => $eligibilityReasons,
            'last_donation' => $lastDonation ? $lastDonation['completed_at'] : null,
            'next_eligible_date' => $nextEligibleDate,
            'total_donations' => (int) $totalDonations
        ],
        'profile' => [
            'name' => $donor['name'],
            'email' => $donor['email'],
            'phone' => $donor['phone'],
            'member_since' => $donor['created_at']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Donor Health Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch health info']);
}
