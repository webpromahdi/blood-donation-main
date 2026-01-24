<?php
/**
 * Get Donor Health Details Endpoint
 * GET /api/donor/health/details.php?donor_id={id}&request_id={id}
 * Returns detailed health information for a specific donor
 * Used by seekers to view assigned donor's health info
 * 
 * Normalized Schema: donors.id is the donor_id, linked to users via user_id
 *                    donor_health.donor_id references donors.id
 *                    donations.donor_id references donors.id
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Allow seekers and admins to view donor health details
$user = requireAuth(['seeker', 'admin', 'hospital']);

$donorId = isset($_GET['donor_id']) ? intval($_GET['donor_id']) : null;
$requestId = isset($_GET['request_id']) ? intval($_GET['request_id']) : null;

if (!$donorId && !$requestId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Donor ID or Request ID is required']);
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
    // If request_id is provided, get donor_id from the donation
    // donations.donor_id references donors.id (NOT users.id)
    if ($requestId && !$donorId) {
        $stmt = $conn->prepare("SELECT dn.donor_id FROM donations dn WHERE dn.request_id = ? AND dn.status != 'cancelled' LIMIT 1");
        $stmt->execute([$requestId]);
        $donation = $stmt->fetch();
        
        if (!$donation) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No active donation found for this request']);
            exit;
        }
        $donorId = $donation['donor_id'];
    }

    // Verify the requester has access (seeker must own the request, or admin/hospital)
    if ($_SESSION['role'] === 'seeker') {
        // Check that the seeker owns a request that has this donor assigned
        $stmt = $conn->prepare("
            SELECT dn.id FROM donations dn 
            JOIN blood_requests r ON dn.request_id = r.id 
            WHERE dn.donor_id = ? 
            AND r.requester_id = ? 
            AND r.requester_type = 'seeker'
            AND dn.status != 'cancelled'
        ");
        $stmt->execute([$donorId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have access to this donor\'s information']);
            exit;
        }
    }

    // Get donor basic info from normalized tables (donors + users + blood_groups)
    $stmt = $conn->prepare("
        SELECT d.id as donor_id, u.id as user_id, u.name, u.phone, u.email, 
               bg.blood_type as blood_group, d.age, d.city, d.total_donations as stored_donations,
               d.weight as donor_weight, u.created_at
        FROM donors d
        JOIN users u ON d.user_id = u.id
        LEFT JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE d.id = ?
    ");
    $stmt->execute([$donorId]);
    $donor = $stmt->fetch();

    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor not found']);
        exit;
    }

    // Get donor health info - donor_health.donor_id references donors.id
    $stmt = $conn->prepare("SELECT * FROM donor_health WHERE donor_id = ?");
    $stmt->execute([$donorId]);
    $health = $stmt->fetch();

    // Get donation stats - donations.donor_id references donors.id
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donations WHERE donor_id = ? AND status = 'completed'");
    $stmt->execute([$donorId]);
    $totalDonations = $stmt->fetch()['count'];

    // Get weight - prefer from health table if available, otherwise use donors table
    $donorWeight = $health && $health['weight'] ? $health['weight'] : ($donor['donor_weight'] ?? null);

    // Format response
    $response = [
        'success' => true,
        'donor' => [
            'id' => $donor['donor_id'],
            'user_id' => $donor['user_id'],
            'name' => $donor['name'],
            'phone' => $donor['phone'],
            'email' => $donor['email'],
            'blood_group' => $donor['blood_group'],
            'age' => $donor['age'],
            'city' => $donor['city'],
            'weight' => $donorWeight,
            'total_donations' => max((int)$totalDonations, (int)$donor['stored_donations']),
            'member_since' => $donor['created_at']
        ],
        'health' => $health ? [
            'weight' => $donorWeight,
            'height' => $health['height'],
            'blood_pressure' => $health['blood_pressure_systolic'] && $health['blood_pressure_diastolic'] 
                ? $health['blood_pressure_systolic'] . '/' . $health['blood_pressure_diastolic'] . ' mmHg'
                : null,
            'hemoglobin' => $health['hemoglobin'] ? $health['hemoglobin'] . ' g/dL' : null,
            'conditions' => [
                'diabetes' => !$health['has_diabetes'],
                'hypertension' => !$health['has_hypertension'],
                'heart_disease' => !$health['has_heart_disease'],
                'asthma' => !$health['has_asthma'],
                'allergies' => !$health['has_allergies']
            ],
            'lifestyle' => [
                'smoking' => $health['smoking_status'] === 'no' ? 'No' : ($health['smoking_status'] === 'occasionally' ? 'Occasionally' : 'Yes'),
                'alcohol' => $health['alcohol_consumption'] === 'none' ? 'None' : ($health['alcohol_consumption'] === 'occasionally' ? 'Occasional' : 'Regular'),
                'exercise' => formatExerciseFrequency($health['exercise_frequency'])
            ],
            'last_checkup' => $health['last_medical_checkup'],
            'is_eligible' => calculateEligibility($health),
            'updated_at' => $health['updated_at']
        ] : [
            'weight' => null,
            'height' => null,
            'blood_pressure' => null,
            'hemoglobin' => null,
            'conditions' => [
                'diabetes' => true,
                'hypertension' => true,
                'heart_disease' => true,
                'asthma' => true,
                'allergies' => true
            ],
            'lifestyle' => [
                'smoking' => 'Not specified',
                'alcohol' => 'Not specified',
                'exercise' => 'Not specified'
            ],
            'last_checkup' => null,
            'is_eligible' => true,
            'updated_at' => null
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Donor Health Details Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch donor health details']);
}

function formatExerciseFrequency($freq) {
    $map = [
        'rarely' => 'Rarely',
        'weekly' => '1-2 times/week',
        '1-2_weekly' => '1-2 times/week',
        '3-4_weekly' => '3-4 times/week',
        'daily' => 'Daily'
    ];
    return $map[$freq] ?? 'Not specified';
}

function calculateEligibility($health) {
    if (!$health) return true;
    
    // Basic eligibility checks
    if ($health['has_heart_disease']) return false;
    if ($health['weight'] && $health['weight'] < 50) return false;
    
    return true;
}
