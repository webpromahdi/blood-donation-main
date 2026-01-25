<?php
/**
 * Scheduled Notification Cron Job
 * Run this script daily via cron: php /path/to/cron/scheduled-notifications.php
 * 
 * Handles:
 * - D7: Appointment reminders (24 hours before)
 * - D12: Eligibility restored (donor can donate again after cooldown)
 * - H11: Voluntary donation ready (24 hours before)
 * - S8: Request expired (required date passed)
 */

// Security: Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from command line');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Include required files
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/services/NotificationService.php';

// Connect to database
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    error_log("Cron: Database connection failed");
    exit(1);
}

$notificationService = new NotificationService($conn);
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

echo "Running scheduled notifications for " . date('Y-m-d H:i:s') . "\n";

// =========================================================================
// D7 & H11: Appointment Reminders (24 hours before)
// =========================================================================
try {
    $stmt = $conn->prepare("
        SELECT a.id, a.appointment_date, a.appointment_time, a.donor_id,
               d.user_id as donor_user_id, h.user_id as hospital_user_id,
               u.name as donor_name, hu.name as hospital_name
        FROM appointments a
        JOIN donors d ON a.donor_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN hospitals h ON a.hospital_id = h.id
        JOIN users hu ON h.user_id = hu.id
        WHERE a.appointment_date = ?
        AND a.status IN ('scheduled', 'confirmed')
    ");
    $stmt->execute([$tomorrow]);
    $appointments = $stmt->fetchAll();
    
    foreach ($appointments as $apt) {
        // Check if reminder already sent (avoid duplicates)
        $checkStmt = $conn->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? 
            AND related_type = 'appointment' 
            AND related_id = ?
            AND title LIKE '%Reminder%'
            AND DATE(created_at) = CURDATE()
        ");
        $checkStmt->execute([$apt['donor_user_id'], $apt['id']]);
        
        if (!$checkStmt->fetch()) {
            // D7: Notify donor of appointment reminder
            $notificationService->notifyDonorAppointmentReminder(
                $apt['donor_user_id'],
                $apt['id'],
                $apt['hospital_name'],
                $apt['appointment_date'],
                $apt['appointment_time']
            );
            
            // H11: Notify hospital of voluntary donation ready
            $notificationService->notifyHospitalVoluntaryDonationReady(
                $apt['hospital_user_id'],
                $apt['id'],
                $apt['donor_name'],
                $apt['appointment_date'],
                $apt['appointment_time']
            );
            
            echo "Sent appointment reminder for appointment #{$apt['id']}\n";
        }
    }
    
    echo "Processed " . count($appointments) . " appointment reminders\n";
    
} catch (PDOException $e) {
    error_log("Cron Error (Appointment Reminders): " . $e->getMessage());
}

// =========================================================================
// D12: Eligibility Restored (donor can donate again)
// =========================================================================
try {
    $stmt = $conn->prepare("
        SELECT d.id as donor_id, d.user_id, d.next_eligible_date, u.name
        FROM donors d
        JOIN users u ON d.user_id = u.id
        WHERE d.next_eligible_date = ?
        AND u.status = 'approved'
    ");
    $stmt->execute([$today]);
    $donors = $stmt->fetchAll();
    
    foreach ($donors as $donor) {
        // Check if already notified today
        $checkStmt = $conn->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? 
            AND title LIKE '%Donate Again%'
            AND DATE(created_at) = CURDATE()
        ");
        $checkStmt->execute([$donor['user_id']]);
        
        if (!$checkStmt->fetch()) {
            // D12: Notify donor eligibility restored
            $notificationService->notifyDonorEligibilityRestored($donor['user_id']);
            
            echo "Sent eligibility restored notification to donor #{$donor['donor_id']}\n";
        }
    }
    
    echo "Processed " . count($donors) . " eligibility restored notifications\n";
    
} catch (PDOException $e) {
    error_log("Cron Error (Eligibility Restored): " . $e->getMessage());
}

// =========================================================================
// S8: Request Expired
// =========================================================================
try {
    $stmt = $conn->prepare("
        SELECT r.id, r.request_code, r.requester_id, r.requester_type,
               r.required_date, r.status
        FROM blood_requests r
        WHERE r.required_date < ?
        AND r.status IN ('pending', 'approved', 'in_progress')
    ");
    $stmt->execute([$today]);
    $expiredRequests = $stmt->fetchAll();
    
    foreach ($expiredRequests as $request) {
        // Update request status to expired/cancelled
        $updateStmt = $conn->prepare("
            UPDATE blood_requests 
            SET status = 'cancelled', 
                rejection_reason = 'Request expired - required date passed'
            WHERE id = ?
        ");
        $updateStmt->execute([$request['id']]);
        
        // Check if already notified
        $checkStmt = $conn->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? 
            AND related_type = 'request' 
            AND related_id = ?
            AND title LIKE '%Expired%'
        ");
        $checkStmt->execute([$request['requester_id'], $request['id']]);
        
        if (!$checkStmt->fetch()) {
            // S8: Notify seeker that request expired
            if ($request['requester_type'] === 'seeker') {
                $notificationService->notifySeekerRequestExpired(
                    $request['requester_id'],
                    $request['id'],
                    $request['request_code']
                );
            }
            
            echo "Request #{$request['id']} expired and notified\n";
        }
    }
    
    echo "Processed " . count($expiredRequests) . " expired requests\n";
    
} catch (PDOException $e) {
    error_log("Cron Error (Request Expired): " . $e->getMessage());
}

echo "Scheduled notifications completed at " . date('Y-m-d H:i:s') . "\n";
