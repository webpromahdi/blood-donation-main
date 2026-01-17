<?php
/**
 * Donor Profile & Stats Endpoint
 * GET /api/donor/profile.php
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

// Check if account is approved before allowing access to full profile data
// Note: Profile endpoint returns status for dashboard to check, so we don't block here
// But we do include status in response

$donorId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get donor profile including status
    $stmt = $conn->prepare("SELECT id, name, email, phone, blood_group, age, weight, city, address, status, created_at FROM users WHERE id = ?");
    $stmt->execute([$donorId]);
    $donor = $stmt->fetch();

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

    // Total donations completed
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donations WHERE donor_id = ? AND status = 'completed'");
    $stmt->execute([$donorId]);
    $totalDonations = $stmt->fetch()['count'];

    // Last donation date
    $stmt = $conn->prepare("SELECT completed_at FROM donations WHERE donor_id = ? AND status = 'completed' ORDER BY completed_at DESC LIMIT 1");
    $stmt->execute([$donorId]);
    $lastDonation = $stmt->fetch();

    // Active donation (if any)
    $stmt = $conn->prepare("SELECT d.*, r.request_code, r.hospital_name, r.city, r.blood_type,
                                   r.urgency, r.quantity, r.patient_name, r.required_date, r.id as request_id
                            FROM donations d 
                            JOIN blood_requests r ON d.request_id = r.id 
                            WHERE d.donor_id = ? AND d.status NOT IN ('completed', 'cancelled')
                            ORDER BY d.created_at DESC LIMIT 1");
    $stmt->execute([$donorId]);
    $activeDonation = $stmt->fetch();

    // Calculate next eligible date (56 days after last donation)
    $nextEligible = null;
    if ($lastDonation && $lastDonation['completed_at']) {
        $lastDate = new DateTime($lastDonation['completed_at']);
        $nextDate = $lastDate->modify('+56 days');
        $nextEligible = $nextDate->format('Y-m-d');
    }

    // Lives saved estimate (each donation can save up to 3 lives)
    $livesSaved = $totalDonations * 3;

    echo json_encode([
        'success' => true,
        'profile' => [
            'id' => $donor['id'],
            'name' => $donor['name'],
            'email' => $donor['email'],
            'phone' => $donor['phone'],
            'blood_group' => $donor['blood_group'],
            'age' => $donor['age'],
            'weight' => $donor['weight'],
            'city' => $donor['city'],
            'address' => $donor['address'],
            'status' => $accountStatus,
            'member_since' => $donor['created_at']
        ],
        'account_status' => $accountStatus,
        'stats' => [
            'total_donations' => (int) $totalDonations,
            'lives_saved' => (int) $livesSaved,
            'last_donation' => $lastDonation ? $lastDonation['completed_at'] : null,
            'next_eligible' => $nextEligible
        ],
        'active_donation' => $activeDonation ? [
            'id' => $activeDonation['id'],
            'request_id' => $activeDonation['request_id'],
            'request_code' => $activeDonation['request_code'],
            'status' => $activeDonation['status'],
            'hospital_name' => $activeDonation['hospital_name'],
            'city' => $activeDonation['city'],
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
