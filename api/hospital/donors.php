<?php
/**
 * Hospital Donors List Endpoint
 * GET /api/hospital/donors.php
 * Returns donors who have donated at this hospital
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

$user = requireAuth(['hospital']);

// Require approved status to view donors
requireApprovedStatus($_SESSION['user_id'], 'hospital');

$hospitalId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get donors who have donated for requests made by this hospital
    $sql = "SELECT DISTINCT u.id, u.name, u.email, u.phone, u.blood_group, u.age, u.city,
                   COUNT(DISTINCT d.id) as donations_here,
                   MAX(d.completed_at) as last_donation_here
            FROM users u
            JOIN donations d ON u.id = d.donor_id AND d.status = 'completed'
            JOIN blood_requests r ON d.request_id = r.id
            WHERE r.requester_id = ? AND r.requester_type = 'hospital'
            GROUP BY u.id
            ORDER BY last_donation_here DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$hospitalId]);
    $donors = $stmt->fetchAll();

    // Also get donors who are currently assigned to hospital's pending requests
    $sqlActive = "SELECT DISTINCT u.id, u.name, u.email, u.phone, u.blood_group, u.age, u.city,
                         d.status as current_status, r.request_code
                  FROM users u
                  JOIN donations d ON u.id = d.donor_id
                  JOIN blood_requests r ON d.request_id = r.id
                  WHERE r.requester_id = ? AND r.requester_type = 'hospital'
                  AND d.status NOT IN ('completed', 'cancelled')";

    $stmtActive = $conn->prepare($sqlActive);
    $stmtActive->execute([$hospitalId]);
    $activeDonors = $stmtActive->fetchAll();

    // Get all donors with their overall stats
    $sqlAll = "SELECT u.id, u.name, u.email, u.phone, u.blood_group, u.age, u.city,
                      COUNT(DISTINCT d.id) as total_donations,
                      MAX(d.completed_at) as last_donation
               FROM users u
               LEFT JOIN donations d ON u.id = d.donor_id AND d.status = 'completed'
               WHERE u.role = 'donor' AND u.blood_group IS NOT NULL
               GROUP BY u.id
               ORDER BY total_donations DESC";

    // Filter by blood type if specified
    if (isset($_GET['blood_type']) && !empty($_GET['blood_type'])) {
        $sqlAll = "SELECT u.id, u.name, u.email, u.phone, u.blood_group, u.age, u.city,
                          COUNT(DISTINCT d.id) as total_donations,
                          MAX(d.completed_at) as last_donation
                   FROM users u
                   LEFT JOIN donations d ON u.id = d.donor_id AND d.status = 'completed'
                   WHERE u.role = 'donor' AND u.blood_group = ?
                   GROUP BY u.id
                   ORDER BY total_donations DESC";
        $stmtAll = $conn->prepare($sqlAll);
        $stmtAll->execute([$_GET['blood_type']]);
    } else {
        $stmtAll = $conn->prepare($sqlAll);
        $stmtAll->execute();
    }
    $allDonors = $stmtAll->fetchAll();

    // Format response
    $formattedDonors = array_map(function ($donor) {
        // Calculate availability (56 days since last donation)
        $isAvailable = true;
        if ($donor['last_donation']) {
            $lastDate = new DateTime($donor['last_donation']);
            $nextDate = clone $lastDate;
            $nextDate->modify('+56 days');
            $isAvailable = $nextDate <= new DateTime();
        }

        return [
            'id' => $donor['id'],
            'name' => $donor['name'],
            'email' => $donor['email'],
            'phone' => $donor['phone'],
            'blood_group' => $donor['blood_group'],
            'age' => $donor['age'],
            'city' => $donor['city'],
            'total_donations' => (int) $donor['total_donations'],
            'last_donation' => $donor['last_donation'],
            'is_available' => $isAvailable
        ];
    }, $allDonors);

    // Filter by availability if requested
    if (isset($_GET['available']) && $_GET['available'] === 'true') {
        $formattedDonors = array_values(array_filter($formattedDonors, fn($d) => $d['is_available']));
    }

    echo json_encode([
        'success' => true,
        'donors' => $formattedDonors,
        'hospital_donors' => $donors,
        'active_donors' => $activeDonors,
        'total' => count($formattedDonors)
    ]);

} catch (PDOException $e) {
    error_log("Hospital Donors Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch donors']);
}
