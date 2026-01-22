<?php
/**
 * Hospital Appointments Endpoint
 * GET /api/hospital/appointments.php
 * Returns donation appointments for the hospital's requests
 * 
 * Normalized Schema: Uses hospitals table, donations -> donors -> users, blood_groups
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

    // Get all donations linked to hospital's requests - normalized schema joins
    $sql = "SELECT dn.id, dn.status, dn.accepted_at, dn.started_at, dn.reached_at, dn.completed_at,
                   r.id as request_id, r.request_code, bg.blood_type, r.quantity, r.urgency,
                   r.patient_name, r.required_date,
                   d.id as donor_id, u.name as donor_name, u.email as donor_email, 
                   u.phone as donor_phone, donor_bg.blood_type as blood_group, d.age, d.city as donor_city
            FROM donations dn
            JOIN blood_requests r ON dn.request_id = r.id
            JOIN blood_groups bg ON r.blood_group_id = bg.id
            JOIN donors d ON dn.donor_id = d.id
            JOIN users u ON d.user_id = u.id
            JOIN blood_groups donor_bg ON d.blood_group_id = donor_bg.id
            WHERE r.hospital_id = ?
            ORDER BY dn.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$hospitalId]);
    $appointments = $stmt->fetchAll();

    // Format response
    $formattedAppointments = array_map(function ($apt) {
        // Determine appointment type
        $type = 'Request'; // Default - from blood request

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
            'quantity' => $apt['quantity'],
            'urgency' => $apt['urgency'],
            'type' => $type,
            'status' => $displayStatus,
            'donation_status' => $apt['status'],
            'date' => $apt['required_date'],
            'accepted_at' => $apt['accepted_at'],
            'started_at' => $apt['started_at'],
            'reached_at' => $apt['reached_at'],
            'completed_at' => $apt['completed_at']
        ];
    }, $appointments);

    // Calculate stats
    $stats = [
        'total' => count($formattedAppointments),
        'confirmed' => count(array_filter($formattedAppointments, fn($a) => $a['status'] === 'Confirmed')),
        'pending' => count(array_filter($formattedAppointments, fn($a) => $a['status'] === 'Pending')),
        'completed' => count(array_filter($formattedAppointments, fn($a) => $a['status'] === 'Completed')),
        'in_progress' => count(array_filter($formattedAppointments, fn($a) => $a['status'] === 'In Progress'))
    ];

    echo json_encode([
        'success' => true,
        'appointments' => $formattedAppointments,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    error_log("Hospital Appointments Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch appointments']);
}
