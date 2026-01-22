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
               v.blood_group_id, v.city,
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
    // 'approved' (by admin) -> 'scheduled'/'confirmed' (by hospital) -> 'completed' or 'cancelled'
    $notificationTitle = '';
    $notificationMessage = '';

    if ($newStatus === 'confirmed') {
        if ($currentStatus !== 'approved' && $currentStatus !== 'pending') {
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

        // Update status to 'scheduled' in the database so it shows as Confirmed/Scheduled
        $stmt = $conn->prepare("
            UPDATE voluntary_donations 
            SET status = 'scheduled',
                scheduled_date = ?,
                scheduled_time = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$scheduledDate, $scheduledTime, $input['voluntary_id']]);

        $notificationTitle = 'Donation Appointment Confirmed';
        $notificationMessage = "Your voluntary donation has been confirmed by {$hospital['name']}. Scheduled for " . date('M d, Y', strtotime($scheduledDate)) . " at " . date('h:i A', strtotime($scheduledTime));

    } elseif ($newStatus === 'completed') {
        if ($currentStatus !== 'approved' && $currentStatus !== 'scheduled') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Can only complete approved/confirmed voluntary donations']);
            exit;
        }

        // Define cooldown period (90 days as per requirement)
        $cooldownDays = 90;

        // Start transaction for atomic operations
        $conn->beginTransaction();

        try {
            // Update voluntary donation status
            $stmt = $conn->prepare("
                UPDATE voluntary_donations 
                SET status = 'completed',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$input['voluntary_id']]);

            // Create a blood request entry to link with donations table
            // This maintains referential integrity with existing donations schema
            $stmt = $conn->prepare("
                INSERT INTO blood_requests (
                    request_code,
                    requester_id,
                    requester_type,
                    patient_name,
                    contact_phone,
                    blood_group_id,
                    quantity,
                    units_fulfilled,
                    hospital_id,
                    hospital_name,
                    city,
                    required_date,
                    medical_reason,
                    urgency,
                    status,
                    approved_at,
                    created_at
                ) VALUES (
                    ?,
                    ?,
                    'hospital',
                    'Voluntary Donation',
                    '',
                    ?,
                    1,
                    1,
                    ?,
                    ?,
                    ?,
                    CURDATE(),
                    'Voluntary blood donation',
                    'normal',
                    'completed',
                    NOW(),
                    NOW()
                )
            ");

            // Generate unique request code for voluntary donation
            $requestCode = 'VOL-' . date('Ymd') . '-' . str_pad($input['voluntary_id'], 4, '0', STR_PAD_LEFT);
            
            // Get hospital user_id for requester_id
            $stmt2 = $conn->prepare("SELECT user_id FROM hospitals WHERE id = ?");
            $stmt2->execute([$hospital['id']]);
            $hospitalUser = $stmt2->fetch();

            $stmt->execute([
                $requestCode,
                $hospitalUser['user_id'],
                $voluntary['blood_group_id'] ?? 1,
                $hospital['id'],
                $hospital['name'],
                $voluntary['city'] ?? '',
            ]);

            $requestId = $conn->lastInsertId();

            // Insert into donations table - this is the key record for history
            $stmt = $conn->prepare("
                INSERT INTO donations (
                    request_id,
                    donor_id,
                    status,
                    quantity,
                    accepted_at,
                    completed_at,
                    created_at
                ) VALUES (?, ?, 'completed', 1, NOW(), NOW(), NOW())
            ");
            $stmt->execute([$requestId, $voluntary['donor_id']]);

            $donationId = $conn->lastInsertId();

            // Update donor stats with proper cooldown (90 days)
            $stmt = $conn->prepare("
                UPDATE donors 
                SET total_donations = total_donations + 1,
                    last_donation_date = CURDATE(),
                    next_eligible_date = DATE_ADD(CURDATE(), INTERVAL ? DAY)
                WHERE id = ?
            ");
            $stmt->execute([$cooldownDays, $voluntary['donor_id']]);

            // Generate certificate for completed donation
            $certCode = 'CERT-' . date('Y') . '-VOL-' . str_pad($donationId, 5, '0', STR_PAD_LEFT);
            
            $stmt = $conn->prepare("
                INSERT INTO certificates (
                    certificate_code,
                    donation_id,
                    donor_id,
                    donor_name,
                    blood_group,
                    donation_date,
                    hospital_name,
                    quantity,
                    issued_at
                ) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 1, NOW())
            ");
            $stmt->execute([
                $certCode,
                $donationId,
                $voluntary['donor_id'],
                $voluntary['donor_name'],
                $voluntary['blood_type'],
                $hospital['name']
            ]);

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }

        $notificationTitle = 'Thank You for Donating!';
        $notificationMessage = "Your voluntary blood donation at {$hospital['name']} has been completed. Thank you for saving lives! You can donate again after " . date('M d, Y', strtotime("+{$cooldownDays} days")) . ".";

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
