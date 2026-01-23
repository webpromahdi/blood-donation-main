<?php
/**
 * Schedule Appointment with Voluntary Donor (Hospital)
 * POST /api/hospital/voluntary/schedule.php
 * Body: { voluntary_id: int, scheduled_date: string, scheduled_time: string, notes: string }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../services/NotificationService.php';

requireAuth(['hospital']);

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['voluntary_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Voluntary donation ID is required']);
    exit;
}

if (empty($input['scheduled_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Scheduled date is required']);
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
    // Get hospital ID
    $stmt = $conn->prepare("SELECT id FROM hospitals WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $hospital = $stmt->fetch();

    if (!$hospital) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Hospital profile not found']);
        exit;
    }

    // Verify voluntary donation exists, is approved, and not already scheduled
    $stmt = $conn->prepare("
        SELECT v.id, v.status, v.donor_id, v.hospital_id, bg.blood_type, u.name as donor_name, d.user_id as donor_user_id
        FROM voluntary_donations v
        JOIN donors d ON v.donor_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN blood_groups bg ON v.blood_group_id = bg.id
        WHERE v.id = ?
    ");
    $stmt->execute([$input['voluntary_id']]);
    $voluntary = $stmt->fetch();

    if (!$voluntary) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Voluntary donation not found']);
        exit;
    }

    if ($voluntary['status'] !== 'approved') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only approved voluntary donations can be scheduled']);
        exit;
    }

    if ($voluntary['hospital_id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This voluntary donation is already scheduled with another hospital']);
        exit;
    }

    // Update voluntary donation with hospital and schedule
    $stmt = $conn->prepare("
        UPDATE voluntary_donations 
        SET hospital_id = ?, 
            scheduled_date = ?,
            scheduled_time = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $hospital['id'],
        $input['scheduled_date'],
        $input['scheduled_time'] ?? null,
        $input['voluntary_id']
    ]);

    // Create appointment record
    $stmt = $conn->prepare("
        INSERT INTO appointments (hospital_id, donor_id, appointment_date, appointment_time, notes, status)
        VALUES (?, ?, ?, ?, ?, 'scheduled')
    ");
    $stmt->execute([
        $hospital['id'],
        $voluntary['donor_id'],
        $input['scheduled_date'],
        $input['scheduled_time'] ?? '09:00:00',
        $input['notes'] ?? 'Voluntary donation appointment'
    ]);
    
    $appointmentId = $conn->lastInsertId();
    
    // Get hospital name for notifications
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $hospitalUser = $stmt->fetch();
    $hospitalName = $hospitalUser ? $hospitalUser['name'] : 'Hospital';
    
    // Send notifications using NotificationService
    $notificationService = new NotificationService($conn);
    
    // D11: Notify donor that hospital was assigned (with schedule)
    $notificationService->notifyDonorHospitalAssigned(
        $voluntary['donor_user_id'], 
        $input['voluntary_id'], 
        $hospitalName, 
        $input['scheduled_date']
    );
    
    // D6: Notify donor of scheduled appointment
    $notificationService->notifyDonorAppointmentScheduled(
        $voluntary['donor_user_id'],
        $appointmentId,
        $hospitalName,
        $input['scheduled_date'],
        $input['scheduled_time'] ?? null
    );
    
    // H10: Notify hospital of voluntary donor assigned (self-notification for record)
    $notificationService->notifyHospitalVoluntaryDonorAssigned(
        $_SESSION['user_id'],
        $input['voluntary_id'],
        $voluntary['donor_name'],
        $voluntary['blood_type'],
        $input['scheduled_date']
    );

    echo json_encode([
        'success' => true,
        'message' => 'Appointment scheduled successfully',
        'donor_name' => $voluntary['donor_name']
    ]);

} catch (PDOException $e) {
    error_log("Schedule Voluntary Donation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to schedule appointment']);
}
