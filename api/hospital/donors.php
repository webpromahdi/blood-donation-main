<?php
/**
 * Hospital Donors List Endpoint
 * GET /api/hospital/donors.php
 * Returns donors who have donated at this hospital
 * 
 * Normalized Schema: Uses hospitals table, donors table with blood_groups join
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

$userId = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get hospital_id from hospitals table
    $stmt = $conn->prepare("SELECT id FROM hospitals WHERE user_id = ?");
    $stmt->execute([$userId]);
    $hospital = $stmt->fetch();
    
    if (!$hospital) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Hospital record not found']);
        exit;
    }
    
    $hospitalId = $hospital['id'];

    // Get donors who have donated for requests made by this hospital
    // Using normalized schema: donations -> donors -> users + blood_groups
    $sql = "SELECT DISTINCT d.id as donor_id, u.id as user_id, u.name, u.email, u.phone, 
                   bg.blood_type as blood_group, d.age, d.city,
                   COUNT(DISTINCT dn.id) as donations_here,
                   MAX(dn.completed_at) as last_donation_here
            FROM donors d
            JOIN users u ON d.user_id = u.id
            JOIN blood_groups bg ON d.blood_group_id = bg.id
            JOIN donations dn ON d.id = dn.donor_id AND dn.status = 'completed'
            JOIN blood_requests r ON dn.request_id = r.id
            WHERE r.hospital_id = ?
            GROUP BY d.id
            ORDER BY last_donation_here DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$hospitalId]);
    $donors = $stmt->fetchAll();

    // Also get donors who are currently assigned to hospital's pending requests
    $sqlActive = "SELECT DISTINCT d.id as donor_id, u.id as user_id, u.name, u.email, u.phone, 
                         bg.blood_type as blood_group, d.age, d.city,
                         dn.status as current_status, r.request_code
                  FROM donors d
                  JOIN users u ON d.user_id = u.id
                  JOIN blood_groups bg ON d.blood_group_id = bg.id
                  JOIN donations dn ON d.id = dn.donor_id
                  JOIN blood_requests r ON dn.request_id = r.id
                  WHERE r.hospital_id = ?
                  AND dn.status NOT IN ('completed', 'cancelled')";

    $stmtActive = $conn->prepare($sqlActive);
    $stmtActive->execute([$hospitalId]);
    $activeDonors = $stmtActive->fetchAll();

    // Get all donors with their overall stats
    $sqlAll = "SELECT d.id as donor_id, u.id as user_id, u.name, u.email, u.phone, 
                      bg.blood_type as blood_group, d.age, d.city,
                      d.total_donations, d.last_donation_date, d.next_eligible_date
               FROM donors d
               JOIN users u ON d.user_id = u.id
               JOIN blood_groups bg ON d.blood_group_id = bg.id
               WHERE u.status = 'approved'";

    // Filter by blood type if specified
    if (isset($_GET['blood_type']) && !empty($_GET['blood_type'])) {
        $sqlAll .= " AND bg.blood_type = ?";
        $stmtAll = $conn->prepare($sqlAll . " ORDER BY d.total_donations DESC");
        $stmtAll->execute([$_GET['blood_type']]);
    } else {
        $stmtAll = $conn->prepare($sqlAll . " ORDER BY d.total_donations DESC");
        $stmtAll->execute();
    }
    $allDonors = $stmtAll->fetchAll();

    // Format response
    $formattedDonors = array_map(function ($donor) {
        // Calculate availability (56 days since last donation)
        $isAvailable = true;
        if ($donor['next_eligible_date']) {
            $nextDate = new DateTime($donor['next_eligible_date']);
            $isAvailable = $nextDate <= new DateTime();
        } elseif ($donor['last_donation_date']) {
            $lastDate = new DateTime($donor['last_donation_date']);
            $nextDate = clone $lastDate;
            $nextDate->modify('+56 days');
            $isAvailable = $nextDate <= new DateTime();
        }

        return [
            'id' => $donor['donor_id'],
            'user_id' => $donor['user_id'],
            'name' => $donor['name'],
            'email' => $donor['email'],
            'phone' => $donor['phone'],
            'blood_group' => $donor['blood_group'],
            'age' => $donor['age'],
            'city' => $donor['city'],
            'total_donations' => (int) $donor['total_donations'],
            'last_donation' => $donor['last_donation_date'],
            'next_eligible_date' => $donor['next_eligible_date'],
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
