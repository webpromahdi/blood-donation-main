<?php
/**
 * Hospital Appointments Endpoint
 * GET /api/hospital/appointments.php
 * Returns donation appointments for the hospital's requests + approved voluntary donations
 * 
 * Normalized Schema: Uses hospitals table, donations -> donors -> users, blood_groups
 * Also includes voluntary_donations assigned to this hospital
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

// Require approved status to view appointments
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
    // Get hospital_id from hospitals table (hospital name is in users table)
    $stmt = $conn->prepare("SELECT h.id, u.name as hospital_name FROM hospitals h JOIN users u ON h.user_id = u.id WHERE h.user_id = ?");
    $stmt->execute([$userId]);
    $hospital = $stmt->fetch();
    
    if (!$hospital) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Hospital record not found']);
        exit;
    }
    
    $hospitalId = $hospital['id'];
    $hospitalName = $hospital['hospital_name'];

    // 1. Get all donations linked to hospital's requests (Request-based)
    $sqlRequests = "SELECT dn.id, dn.status, dn.accepted_at, dn.started_at, dn.reached_at, dn.completed_at,
                   r.id as request_id, r.request_code, bg.blood_type, r.quantity, r.urgency,
                   r.patient_name, r.required_date,
                   d.id as donor_id, u.name as donor_name, u.email as donor_email, 
                   u.phone as donor_phone, donor_bg.blood_type as blood_group, d.age, d.city as donor_city,
                   'request' as source_type, dn.created_at as sort_date
            FROM donations dn
            JOIN blood_requests r ON dn.request_id = r.id
            JOIN blood_groups bg ON r.blood_group_id = bg.id
            JOIN donors d ON dn.donor_id = d.id
            JOIN users u ON d.user_id = u.id
            JOIN blood_groups donor_bg ON d.blood_group_id = donor_bg.id
            WHERE r.requester_id = ? AND r.requester_type = 'hospital'";

    $stmt = $conn->prepare($sqlRequests);
    $stmt->execute([$userId]);
    $requestAppointments = $stmt->fetchAll();

    // 2. Get all approved voluntary donations assigned to this hospital
    $sqlVoluntary = "SELECT v.id, v.status, v.availability_date, v.preferred_time, v.notes,
                           v.scheduled_date, v.scheduled_time, v.approved_at, v.created_at,
                           bg.blood_type,
                           d.id as donor_id, u.name as donor_name, u.email as donor_email,
                           u.phone as donor_phone, donor_bg.blood_type as blood_group, d.age, d.city as donor_city,
                           'voluntary' as source_type
                    FROM voluntary_donations v
                    JOIN donors d ON v.donor_id = d.id
                    JOIN users u ON d.user_id = u.id
                    JOIN blood_groups bg ON v.blood_group_id = bg.id
                    JOIN blood_groups donor_bg ON d.blood_group_id = donor_bg.id
                    WHERE v.hospital_id = ? AND v.status IN ('approved', 'scheduled', 'completed')
                    ORDER BY v.availability_date ASC";

    $stmt = $conn->prepare($sqlVoluntary);
    $stmt->execute([$hospitalId]);
    $voluntaryAppointments = $stmt->fetchAll();

    // Format request-based appointments
    $formattedAppointments = array_map(function ($apt) {
        // Map donation status to display status
        $statusMap = [
            'accepted' => 'Confirmed',
            'on_the_way' => 'Confirmed',
            'reached' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];

        $displayStatus = $statusMap[$apt['status']] ?? 'Pending';

        return [
            'id' => 'APT' . str_pad($apt['id'], 3, '0', STR_PAD_LEFT),
            'donation_id' => $apt['id'],
            'voluntary_id' => null,
            'request_code' => $apt['request_code'],
            'donor' => [
                'id' => $apt['donor_id'],
                'name' => $apt['donor_name'],
                'email' => $apt['donor_email'],
                'phone' => $apt['donor_phone'],
                'blood_group' => $apt['blood_group'],
                'age' => $apt['age'],
                'city' => $apt['donor_city']
            ],
            'blood_type' => $apt['blood_type'],
            'quantity' => $apt['quantity'] ?? 1,
            'urgency' => $apt['urgency'] ?? 'normal',
            'type' => 'Request',
            'status' => $displayStatus,
            'donation_status' => $apt['status'],
            'date' => $apt['required_date'],
            'time' => null,
            'notes' => null,
            'accepted_at' => $apt['accepted_at'],
            'started_at' => $apt['started_at'],
            'reached_at' => $apt['reached_at'],
            'completed_at' => $apt['completed_at'],
            'sort_date' => $apt['sort_date']
        ];
    }, $requestAppointments);

    // Format voluntary donation appointments
    $formattedVoluntary = array_map(function ($apt) {
        // Map voluntary status to display status
        $statusMap = [
            'approved' => 'Pending',  // Approved by admin, waiting for hospital to confirm
            'scheduled' => 'Confirmed', // Confirmed/scheduled by hospital
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];

        $displayStatus = $statusMap[$apt['status']] ?? 'Pending';
        
        // Format preferred time for display
        $timeMap = [
            'morning' => '09:00 AM',
            'afternoon' => '02:00 PM',
            'evening' => '06:00 PM',
            'any' => 'Flexible'
        ];
        $displayTime = $apt['scheduled_time'] ? date('h:i A', strtotime($apt['scheduled_time'])) : ($timeMap[$apt['preferred_time']] ?? 'Flexible');

        return [
            'id' => 'VOL' . str_pad($apt['id'], 3, '0', STR_PAD_LEFT),
            'donation_id' => null,
            'voluntary_id' => $apt['id'],
            'request_code' => null,
            'donor' => [
                'id' => $apt['donor_id'],
                'name' => $apt['donor_name'],
                'email' => $apt['donor_email'],
                'phone' => $apt['donor_phone'],
                'blood_group' => $apt['blood_group'],
                'age' => $apt['age'],
                'city' => $apt['donor_city']
            ],
            'blood_type' => $apt['blood_type'],
            'quantity' => 1,
            'urgency' => 'normal',
            'type' => 'Voluntary',
            'status' => $displayStatus,
            'donation_status' => $apt['status'],
            'date' => $apt['scheduled_date'] ?? $apt['availability_date'],
            'time' => $displayTime,
            'preferred_time' => $apt['preferred_time'],
            'notes' => $apt['notes'],
            'accepted_at' => $apt['approved_at'],
            'started_at' => null,
            'reached_at' => null,
            'completed_at' => $apt['status'] === 'completed' ? $apt['scheduled_date'] : null,
            'sort_date' => $apt['created_at']
        ];
    }, $voluntaryAppointments);

    // Merge both arrays
    $allAppointments = array_merge($formattedAppointments, $formattedVoluntary);

    // Sort by date (most recent first)
    usort($allAppointments, function($a, $b) {
        return strtotime($b['sort_date'] ?? $b['date']) - strtotime($a['sort_date'] ?? $a['date']);
    });

    // Calculate stats (including voluntary)
    $stats = [
        'total' => count($allAppointments),
        'confirmed' => count(array_filter($allAppointments, fn($a) => $a['status'] === 'Confirmed')),
        'pending' => count(array_filter($allAppointments, fn($a) => $a['status'] === 'Pending')),
        'completed' => count(array_filter($allAppointments, fn($a) => $a['status'] === 'Completed')),
        'in_progress' => count(array_filter($allAppointments, fn($a) => $a['status'] === 'In Progress')),
        'voluntary' => count($formattedVoluntary),
        'request_based' => count($formattedAppointments)
    ];

    echo json_encode([
        'success' => true,
        'appointments' => $allAppointments,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    error_log("Hospital Appointments Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch appointments']);
}
