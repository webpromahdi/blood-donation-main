<?php
/**
 * Update Voluntary Donation Status (Hospital)
 * POST /api/hospital/voluntary/update-status.php
 * Body: { voluntary_id: int, status: 'confirmed'|'completed'|'cancelled', notes?: string }
 * 
 * This endpoint allows hospitals to:
 * - Confirm a voluntary donation (set appointment)
 * - Mark it as completed after donation
 * - Cancel if needed
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

requireAuth(['hospital']);

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['voluntary_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Voluntary donation ID is required']);
    exit;
}

if (empty($input['status']) || !in_array($input['status'], ['confirmed', 'completed', 'cancelled'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid status (confirmed, completed, cancelled) is required']);
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
    // Get hospital ID (hospital name is in users table)
    $stmt = $conn->prepare("SELECT h.id, u.name FROM hospitals h JOIN users u ON h.user_id = u.id WHERE h.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $hospital = $stmt->fetch();

    if (!$hospital) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Hospital profile not found']);
        exit;
    }

    // Verify voluntary donation exists and belongs to this hospital
    $stmt = $conn->prepare("
        SELECT v.id, v.status, v.donor_id, v.hospital_id, v.availability_date, v.preferred_time,
               u.name as donor_name, d.user_id as donor_user_id, bg.blood_type
        FROM voluntary_donations v
        JOIN donors d ON v.donor_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN blood_groups bg ON v.blood_group_id = bg.id
        WHERE v.id = ? AND v.hospital_id = ?
    ");
    $stmt->execute([$input['voluntary_id'], $hospital['id']]);
    $voluntary = $stmt->fetch();

    if (!$voluntary) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Voluntary donation not found or does not belong to your hospital']);
        exit;
    }

    $newStatus = $input['status'];
    $currentStatus = $voluntary['status'];
    
    // Validate status transitions
    $validTransitions = [
        'approved' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
    ];

    // Map 'confirmed' to how we store it (we'll store as a flag or use scheduled fields)
    // For database, we keep status as 'approved' but set scheduled_date/time when confirmed
    // Or we can add a new status. For simplicity, we'll update scheduling fields for 'confirmed'
    
    $notificationTitle = '';
    $notificationMessage = '';

    if ($newStatus === 'confirmed') {
        if ($currentStatus !== 'approved') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Can only confirm approved voluntary donations']);
            exit;
        }

        // Set scheduled date/time (use availability date as default)
        $scheduledDate = $input['scheduled_date'] ?? $voluntary['availability_date'];
        $scheduledTime = $input['scheduled_time'] ?? null;
        
        // Map preferred_time to actual time if not provided
        if (!$scheduledTime) {
            $timeMap = [
                'morning' => '09:00:00',
                'afternoon' => '14:00:00',
                'evening' => '18:00:00',
                'any' => '10:00:00'
            ];
            $scheduledTime = $timeMap[$voluntary['preferred_time']] ?? '10:00:00';
        }

        $stmt = $conn->prepare("
            UPDATE voluntary_donations 
            SET scheduled_date = ?,
                scheduled_time = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$scheduledDate, $scheduledTime, $input['voluntary_id']]);

        $notificationTitle = 'Donation Appointment Confirmed';
        $notificationMessage = "Your voluntary donation has been confirmed by {$hospital['name']}. Scheduled for " . date('M d, Y', strtotime($scheduledDate)) . " at " . date('h:i A', strtotime($scheduledTime));

    } elseif ($newStatus === 'completed') {
        if ($currentStatus !== 'approved') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Can only complete approved/confirmed voluntary donations']);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE voluntary_donations 
            SET status = 'completed',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$input['voluntary_id']]);

        $notificationTitle = 'Thank You for Donating!';
        $notificationMessage = "Your voluntary blood donation at {$hospital['name']} has been completed. Thank you for saving lives!";

    } elseif ($newStatus === 'cancelled') {
        $stmt = $conn->prepare("
            UPDATE voluntary_donations 
            SET status = 'cancelled',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$input['voluntary_id']]);

        $notificationTitle = 'Donation Appointment Cancelled';
        $notificationMessage = "Your voluntary donation appointment at {$hospital['name']} has been cancelled.";
        if (!empty($input['notes'])) {
            $notificationMessage .= " Reason: " . $input['notes'];
        }
    }

    // Create notification for donor
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_type, related_id)
        VALUES (?, ?, ?, ?, 'voluntary_donation', ?)
    ");
    $notificationType = $newStatus === 'completed' ? 'success' : ($newStatus === 'cancelled' ? 'warning' : 'info');
    $stmt->execute([
        $voluntary['donor_user_id'],
        $notificationTitle,
        $notificationMessage,
        $notificationType,
        $input['voluntary_id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'donor_name' => $voluntary['donor_name'],
        'new_status' => $newStatus
    ]);

} catch (PDOException $e) {
    error_log("Update Voluntary Status Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}
