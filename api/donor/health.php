<?php
/**
 * Donor Health Profile Endpoint
 * GET /api/donor/health.php
 * 
 * Normalized Schema: Reads from users + donors + donor_health + blood_groups
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

// Require approved status to access health information
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
    // Get donor's profile from normalized tables
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.created_at,
               d.id as donor_id, d.age, d.weight, d.gender, d.city, d.address,
               d.total_donations, d.last_donation_date, d.next_eligible_date,
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
        echo json_encode(['success' => false, 'message' => 'Donor not found']);
        exit;
    }

    $donorId = $donor['donor_id'];

    // Get detailed health info from donor_health table
    $stmt = $conn->prepare("SELECT * FROM donor_health WHERE donor_id = ?");
    $stmt->execute([$donorId]);
    $healthInfo = $stmt->fetch();

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
    $nextEligibleDate = $donor['next_eligible_date'];
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

    // Check weight (use health weight if available, otherwise donor weight)
    $weight = $healthInfo && $healthInfo['height'] ? $healthInfo['height'] : $donor['weight'];
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

    // Check health conditions
    if ($healthInfo) {
        if ($healthInfo['has_heart_disease']) {
            $isEligible = false;
            $eligibilityReasons[] = 'Heart disease detected';
        }
        if ($healthInfo['has_infectious_disease']) {
            $isEligible = false;
            $eligibilityReasons[] = 'Infectious disease detected';
        }
        if ($healthInfo['has_blood_disorders']) {
            $isEligible = false;
            $eligibilityReasons[] = 'Blood disorder detected';
        }
    }

    echo json_encode([
        'success' => true,
        'health' => [
            'blood_group' => $donor['blood_group'],
            'age' => $donor['age'],
            'weight' => $donor['weight'],
            'height' => $healthInfo ? $healthInfo['height'] : null,
            'gender' => $donor['gender'],
            'city' => $donor['city'],
            'address' => $donor['address'],
            'blood_pressure_systolic' => $healthInfo ? $healthInfo['blood_pressure_systolic'] : null,
            'blood_pressure_diastolic' => $healthInfo ? $healthInfo['blood_pressure_diastolic'] : null,
            'hemoglobin' => $healthInfo ? $healthInfo['hemoglobin'] : null,
            'has_diabetes' => $healthInfo ? (bool)$healthInfo['has_diabetes'] : false,
            'has_hypertension' => $healthInfo ? (bool)$healthInfo['has_hypertension'] : false,
            'has_heart_disease' => $healthInfo ? (bool)$healthInfo['has_heart_disease'] : false,
            'has_blood_disorders' => $healthInfo ? (bool)$healthInfo['has_blood_disorders'] : false,
            'has_infectious_disease' => $healthInfo ? (bool)$healthInfo['has_infectious_disease'] : false,
            'has_asthma' => $healthInfo ? (bool)$healthInfo['has_asthma'] : false,
            'has_allergies' => $healthInfo ? (bool)$healthInfo['has_allergies'] : false,
            'has_recent_surgery' => $healthInfo ? (bool)$healthInfo['has_recent_surgery'] : false,
            'is_on_medication' => $healthInfo ? (bool)$healthInfo['is_on_medication'] : false,
            'smoking_status' => $healthInfo ? $healthInfo['smoking_status'] : 'no',
            'alcohol_consumption' => $healthInfo ? $healthInfo['alcohol_consumption'] : 'none',
            'exercise_frequency' => $healthInfo ? $healthInfo['exercise_frequency'] : 'rarely',
            'last_medical_checkup' => $healthInfo ? $healthInfo['last_medical_checkup'] : null,
            'additional_notes' => $healthInfo ? $healthInfo['additional_notes'] : null
        ],
        'eligibility' => [
            'is_eligible' => $isEligible,
            'reasons' => $eligibilityReasons,
            'last_donation' => $lastDonation ? $lastDonation['completed_at'] : $donor['last_donation_date'],
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
    echo json_encode(['success' => false, 'message' => 'Failed to fetch health information']);
}
    error_log("Donor Health Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch health info']);
}
