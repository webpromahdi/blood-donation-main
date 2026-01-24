<?php
/**
 * Notification Service
 * Centralized service for creating all system notifications
 * 
 * Following the NOTIFICATION_SYSTEM.md design document exactly:
 * - 8 Admin notifications (A1-A8)
 * - 13 Donor notifications (D1-D13)
 * - 11 Hospital notifications (H1-H11)
 * - 8 Seeker notifications (S1-S8)
 */

class NotificationService
{
    private $conn;
    
    /**
     * Notification types from design document
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    const TYPE_REQUEST = 'request';
    const TYPE_DONATION = 'donation';
    const TYPE_ANNOUNCEMENT = 'announcement';
    
    /**
     * Related entity types
     */
    const RELATED_REQUEST = 'request';
    const RELATED_DONATION = 'donation';
    const RELATED_APPOINTMENT = 'appointment';
    const RELATED_USER = 'user';
    const RELATED_VOLUNTARY = 'voluntary_donation';
    const RELATED_CERTIFICATE = 'certificate';
    
    public function __construct($conn)
    {
        $this->conn = $conn;
    }
    
    /**
     * Create a notification record in the database
     * 
     * @param int $userId Receiver user ID
     * @param string $title Notification title
     * @param string $message Human-readable message
     * @param string $type One of: info, success, warning, error, request, donation, announcement
     * @param string|null $relatedType Entity type (request, donation, appointment, user, voluntary_donation)
     * @param int|null $relatedId Entity ID
     * @return int|false Notification ID or false on failure
     */
    private function create($userId, $title, $message, $type = self::TYPE_INFO, $relatedType = null, $relatedId = null)
    {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_type, related_id, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$userId, $title, $message, $type, $relatedType, $relatedId]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("NotificationService Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all admin user IDs
     */
    private function getAdminIds()
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'approved'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Notify all admins
     */
    private function notifyAdmins($title, $message, $type, $relatedType = null, $relatedId = null)
    {
        $adminIds = $this->getAdminIds();
        foreach ($adminIds as $adminId) {
            $this->create($adminId, $title, $message, $type, $relatedType, $relatedId);
        }
    }
    
    // =========================================================================
    // ADMIN NOTIFICATIONS (A1 - A8)
    // =========================================================================
    
    /**
     * A1: New Hospital Registration
     * Trigger: Hospital submits registration
     */
    public function notifyAdminNewHospitalRegistration($hospitalUserId, $hospitalName)
    {
        $this->notifyAdmins(
            'New Hospital Registration',
            "Hospital '{$hospitalName}' has registered and is awaiting approval.",
            self::TYPE_WARNING,
            self::RELATED_USER,
            $hospitalUserId
        );
    }
    
    /**
     * A2: New Donor Registration
     * Trigger: Donor registers (if approval required)
     */
    public function notifyAdminNewDonorRegistration($donorUserId, $donorName)
    {
        $this->notifyAdmins(
            'New Donor Registration',
            "Donor '{$donorName}' has registered and is awaiting approval.",
            self::TYPE_WARNING,
            self::RELATED_USER,
            $donorUserId
        );
    }
    
    /**
     * A3: New Blood Request (Hospital)
     * Trigger: Hospital creates blood request
     */
    public function notifyAdminNewHospitalRequest($requestId, $hospitalName, $bloodType, $urgency)
    {
        $title = $urgency === 'emergency' ? '🚨 Emergency Blood Request (Hospital)' : 'New Blood Request (Hospital)';
        $type = $urgency === 'emergency' ? self::TYPE_ERROR : self::TYPE_REQUEST;
        
        $this->notifyAdmins(
            $title,
            "Hospital '{$hospitalName}' has submitted a blood request for {$bloodType}.",
            $type,
            self::RELATED_REQUEST,
            $requestId
        );
        
        // A5: Also send emergency alert if urgent
        if ($urgency === 'emergency') {
            $this->notifyAdminEmergencyRequest($requestId, $hospitalName, $bloodType);
        }
    }
    
    /**
     * A4: New Blood Request (Seeker)
     * Trigger: Seeker creates blood request
     */
    public function notifyAdminNewSeekerRequest($requestId, $seekerName, $bloodType, $urgency)
    {
        $title = $urgency === 'emergency' ? '🚨 Emergency Blood Request (Seeker)' : 'New Blood Request (Seeker)';
        $type = $urgency === 'emergency' ? self::TYPE_ERROR : self::TYPE_REQUEST;
        
        $this->notifyAdmins(
            $title,
            "Seeker '{$seekerName}' has submitted a blood request for {$bloodType}.",
            $type,
            self::RELATED_REQUEST,
            $requestId
        );
        
        // A5: Also send emergency alert if urgent
        if ($urgency === 'emergency') {
            $this->notifyAdminEmergencyRequest($requestId, $seekerName, $bloodType);
        }
    }
    
    /**
     * A5: Emergency Request Alert
     * Trigger: Request marked as "emergency" urgency
     * Note: This is called internally from A3/A4 when urgency is emergency
     */
    private function notifyAdminEmergencyRequest($requestId, $requesterName, $bloodType)
    {
        // This is already combined with A3/A4 to avoid duplicate notifications
        // The emergency indicator is in the title/type
    }
    
    /**
     * A6: Voluntary Donation Submitted
     * Trigger: Donor submits voluntary donation offer
     */
    public function notifyAdminVoluntaryDonationSubmitted($voluntaryId, $donorName, $bloodType, $city)
    {
        $this->notifyAdmins(
            'Voluntary Donation Submitted',
            "Donor '{$donorName}' ({$bloodType}) from {$city} has offered to donate blood voluntarily.",
            self::TYPE_INFO,
            self::RELATED_VOLUNTARY,
            $voluntaryId
        );
    }
    
    /**
     * A7: Donation Completed
     * Trigger: Donation marked as completed
     */
    public function notifyAdminDonationCompleted($donationId, $donorName, $hospitalName)
    {
        $this->notifyAdmins(
            'Donation Completed',
            "Donor '{$donorName}' has completed a donation at {$hospitalName}.",
            self::TYPE_SUCCESS,
            self::RELATED_DONATION,
            $donationId
        );
    }
    
    /**
     * A8: Donation Cancelled
     * Trigger: Donor cancels accepted donation
     */
    public function notifyAdminDonationCancelled($donationId, $donorName, $requestCode)
    {
        $this->notifyAdmins(
            'Donation Cancelled',
            "Donor '{$donorName}' has cancelled their donation for request {$requestCode}. A replacement donor may be needed.",
            self::TYPE_WARNING,
            self::RELATED_DONATION,
            $donationId
        );
    }
    
    // =========================================================================
    // DONOR NOTIFICATIONS (D1 - D13)
    // =========================================================================
    
    /**
     * D1: Account Approved
     * Trigger: Admin approves donor registration
     */
    public function notifyDonorAccountApproved($donorUserId)
    {
        $this->create(
            $donorUserId,
            'Account Approved',
            'Congratulations! Your donor account has been approved. You can now browse blood requests and help save lives.',
            self::TYPE_SUCCESS,
            self::RELATED_USER,
            $donorUserId
        );
    }
    
    /**
     * D2: Account Rejected
     * Trigger: Admin rejects donor registration
     */
    public function notifyDonorAccountRejected($donorUserId, $reason = null)
    {
        $message = 'Your donor account registration has been rejected.';
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        $message .= ' Please contact support for more information.';
        
        $this->create(
            $donorUserId,
            'Account Rejected',
            $message,
            self::TYPE_ERROR,
            self::RELATED_USER,
            $donorUserId
        );
    }
    
    /**
     * D3: New Matching Request
     * Trigger: Blood request approved matching donor's blood group + city
     */
    public function notifyDonorMatchingRequest($donorUserId, $requestId, $bloodType, $hospitalName, $city)
    {
        $this->create(
            $donorUserId,
            'New Blood Request Match',
            "A patient needs {$bloodType} blood at {$hospitalName} in {$city}. You can help save a life!",
            self::TYPE_REQUEST,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * D4: Emergency Request (Matching)
     * Trigger: Emergency request matching donor's profile
     */
    public function notifyDonorEmergencyRequest($donorUserId, $requestId, $bloodType, $hospitalName, $city)
    {
        $this->create(
            $donorUserId,
            '🚨 Emergency Blood Request',
            "URGENT: A patient critically needs {$bloodType} blood at {$hospitalName} in {$city}. Immediate help needed!",
            self::TYPE_ERROR,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * D5: Donation Accepted Confirmation
     * Trigger: Donor accepts a blood request
     */
    public function notifyDonorDonationAccepted($donorUserId, $donationId, $requestCode, $hospitalName)
    {
        $this->create(
            $donorUserId,
            'Donation Commitment Confirmed',
            "Thank you for accepting request {$requestCode}. Please proceed to {$hospitalName} for your donation.",
            self::TYPE_SUCCESS,
            self::RELATED_DONATION,
            $donationId
        );
    }
    
    /**
     * D6: Appointment Scheduled
     * Trigger: Hospital schedules appointment for donation
     */
    public function notifyDonorAppointmentScheduled($donorUserId, $appointmentId, $hospitalName, $date, $time = null)
    {
        $dateFormatted = date('F j, Y', strtotime($date));
        $timeStr = $time ? ' at ' . date('g:i A', strtotime($time)) : '';
        
        $this->create(
            $donorUserId,
            'Appointment Scheduled',
            "Your donation appointment has been scheduled at {$hospitalName} on {$dateFormatted}{$timeStr}.",
            self::TYPE_INFO,
            self::RELATED_APPOINTMENT,
            $appointmentId
        );
    }
    
    /**
     * D7: Appointment Reminder
     * Trigger: 24 hours before scheduled appointment
     * Note: This should be called by a cron job
     */
    public function notifyDonorAppointmentReminder($donorUserId, $appointmentId, $hospitalName, $date, $time = null)
    {
        $dateFormatted = date('F j, Y', strtotime($date));
        $timeStr = $time ? ' at ' . date('g:i A', strtotime($time)) : '';
        
        $this->create(
            $donorUserId,
            'Appointment Reminder',
            "Reminder: Your donation appointment is tomorrow at {$hospitalName}{$timeStr}. Please don't forget!",
            self::TYPE_WARNING,
            self::RELATED_APPOINTMENT,
            $appointmentId
        );
    }
    
    /**
     * D8: Donation Completed
     * Trigger: Hospital marks donation as complete
     */
    public function notifyDonorDonationCompleted($donorUserId, $donationId, $hospitalName)
    {
        $this->create(
            $donorUserId,
            'Donation Completed - Thank You!',
            "Your blood donation at {$hospitalName} has been completed. Thank you for saving a life! Your donation certificate is now available.",
            self::TYPE_SUCCESS,
            self::RELATED_DONATION,
            $donationId
        );
    }
    
    /**
     * D9: Voluntary Donation Approved
     * Trigger: Admin approves voluntary donation offer
     */
    public function notifyDonorVoluntaryApproved($donorUserId, $voluntaryId)
    {
        $this->create(
            $donorUserId,
            'Voluntary Donation Approved',
            'Your voluntary donation request has been approved. Hospitals can now see your availability and schedule an appointment.',
            self::TYPE_SUCCESS,
            self::RELATED_VOLUNTARY,
            $voluntaryId
        );
    }
    
    /**
     * D10: Voluntary Donation Rejected
     * Trigger: Admin rejects voluntary donation
     */
    public function notifyDonorVoluntaryRejected($donorUserId, $voluntaryId, $reason = null)
    {
        $message = 'Your voluntary donation request has been rejected.';
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        
        $this->create(
            $donorUserId,
            'Voluntary Donation Rejected',
            $message,
            self::TYPE_ERROR,
            self::RELATED_VOLUNTARY,
            $voluntaryId
        );
    }
    
    /**
     * D11: Hospital Assigned (Voluntary)
     * Trigger: Hospital assigned for voluntary donation
     */
    public function notifyDonorHospitalAssigned($donorUserId, $voluntaryId, $hospitalName, $scheduledDate)
    {
        $dateFormatted = date('F j, Y', strtotime($scheduledDate));
        
        $this->create(
            $donorUserId,
            'Hospital Assigned for Voluntary Donation',
            "{$hospitalName} has accepted your voluntary donation and scheduled it for {$dateFormatted}. Please check your appointments.",
            self::TYPE_SUCCESS,
            self::RELATED_VOLUNTARY,
            $voluntaryId
        );
    }
    
    /**
     * D12: Eligibility Restored
     * Trigger: 56/90 days passed since last donation
     * Note: This should be called by a cron job
     */
    public function notifyDonorEligibilityRestored($donorUserId)
    {
        $this->create(
            $donorUserId,
            'You Can Donate Again!',
            'Great news! The waiting period since your last donation has passed. You are now eligible to donate blood again.',
            self::TYPE_SUCCESS,
            self::RELATED_USER,
            $donorUserId
        );
    }
    
    /**
     * D13: Achievement Unlocked
     * Trigger: Milestone reached (5, 10, 25 donations)
     */
    public function notifyDonorAchievementUnlocked($donorUserId, $milestone, $certificateId = null)
    {
        $messages = [
            5 => 'Congratulations! You have completed 5 donations and earned the Bronze Donor badge!',
            10 => 'Amazing! You have completed 10 donations and earned the Silver Donor badge!',
            25 => 'Incredible! You have completed 25 donations and earned the Gold Donor badge! You are a true lifesaver!',
            50 => 'Legendary! You have completed 50 donations and earned the Platinum Donor badge!',
            100 => 'Heroic! You have completed 100 donations! You are an absolute hero!'
        ];
        
        $message = $messages[$milestone] ?? "Congratulations! You have completed {$milestone} donations!";
        
        $this->create(
            $donorUserId,
            'Achievement Unlocked: ' . $milestone . ' Donations',
            $message . ' Your achievement certificate is available for download.',
            self::TYPE_SUCCESS,
            self::RELATED_CERTIFICATE,
            $certificateId
        );
    }
    
    // =========================================================================
    // HOSPITAL NOTIFICATIONS (H1 - H11)
    // =========================================================================
    
    /**
     * H1: Account Approved
     * Trigger: Admin approves hospital registration
     */
    public function notifyHospitalAccountApproved($hospitalUserId)
    {
        $this->create(
            $hospitalUserId,
            'Account Approved',
            'Your hospital account has been approved. You can now create blood requests and connect with donors.',
            self::TYPE_SUCCESS,
            self::RELATED_USER,
            $hospitalUserId
        );
    }
    
    /**
     * H2: Account Rejected
     * Trigger: Admin rejects hospital registration
     */
    public function notifyHospitalAccountRejected($hospitalUserId, $reason = null)
    {
        $message = 'Your hospital registration has been rejected.';
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        $message .= ' Please contact support for more information.';
        
        $this->create(
            $hospitalUserId,
            'Account Rejected',
            $message,
            self::TYPE_ERROR,
            self::RELATED_USER,
            $hospitalUserId
        );
    }
    
    /**
     * H3: Blood Request Approved
     * Trigger: Admin approves hospital's request
     */
    public function notifyHospitalRequestApproved($hospitalUserId, $requestId, $requestCode)
    {
        $this->create(
            $hospitalUserId,
            'Blood Request Approved',
            "Your blood request {$requestCode} has been approved and is now visible to matching donors.",
            self::TYPE_SUCCESS,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * H4: Blood Request Rejected
     * Trigger: Admin rejects hospital's request
     */
    public function notifyHospitalRequestRejected($hospitalUserId, $requestId, $requestCode, $reason = null)
    {
        $message = "Your blood request {$requestCode} has been rejected.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        
        $this->create(
            $hospitalUserId,
            'Blood Request Rejected',
            $message,
            self::TYPE_ERROR,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * H5: Donor Accepted Request
     * Trigger: Donor accepts hospital's blood request
     */
    public function notifyHospitalDonorAccepted($hospitalUserId, $donationId, $donorName, $bloodType, $requestCode)
    {
        $this->create(
            $hospitalUserId,
            'Donor Accepted Request',
            "Good news! Donor '{$donorName}' ({$bloodType}) has accepted your blood request {$requestCode} and will be coming to donate.",
            self::TYPE_SUCCESS,
            self::RELATED_DONATION,
            $donationId
        );
    }
    
    /**
     * H6: Donor On The Way
     * Trigger: Donor updates status to "on_the_way"
     */
    public function notifyHospitalDonorOnTheWay($hospitalUserId, $donationId, $donorName)
    {
        $this->create(
            $hospitalUserId,
            'Donor On The Way',
            "Donor '{$donorName}' is now on their way to your hospital. Please prepare for their arrival.",
            self::TYPE_INFO,
            self::RELATED_DONATION,
            $donationId
        );
    }
    
    /**
     * H7: Donor Reached
     * Trigger: Donor updates status to "reached"
     */
    public function notifyHospitalDonorReached($hospitalUserId, $donationId, $donorName)
    {
        $this->create(
            $hospitalUserId,
            'Donor Has Arrived',
            "Donor '{$donorName}' has arrived at your hospital. Please begin the donation process.",
            self::TYPE_INFO,
            self::RELATED_DONATION,
            $donationId
        );
    }
    
    /**
     * H8: Donor Cancelled
     * Trigger: Donor cancels after accepting
     */
    public function notifyHospitalDonorCancelled($hospitalUserId, $donationId, $donorName, $requestCode, $reason = null)
    {
        $message = "Donor '{$donorName}' has cancelled their commitment to request {$requestCode}.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        $message .= ' The request is now available for other donors.';
        
        $this->create(
            $hospitalUserId,
            'Donor Cancelled',
            $message,
            self::TYPE_WARNING,
            self::RELATED_DONATION,
            $donationId
        );
    }
    
    /**
     * H9: Request Fulfilled
     * Trigger: All units collected for a request
     */
    public function notifyHospitalRequestFulfilled($hospitalUserId, $requestId, $requestCode)
    {
        $this->create(
            $hospitalUserId,
            'Request Fulfilled',
            "Blood request {$requestCode} has been completely fulfilled. All requested units have been collected.",
            self::TYPE_SUCCESS,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * H10: Voluntary Donor Assigned
     * Trigger: Admin assigns voluntary donor to hospital
     * Note: In our system, hospital schedules directly, so this triggers when hospital schedules
     */
    public function notifyHospitalVoluntaryDonorAssigned($hospitalUserId, $voluntaryId, $donorName, $bloodType, $date)
    {
        $dateFormatted = date('F j, Y', strtotime($date));
        
        $this->create(
            $hospitalUserId,
            'Voluntary Donor Assigned',
            "Voluntary donor '{$donorName}' ({$bloodType}) has been scheduled for donation on {$dateFormatted}. Please prepare for their appointment.",
            self::TYPE_INFO,
            self::RELATED_VOLUNTARY,
            $voluntaryId
        );
    }
    
    /**
     * H11: Voluntary Donation Ready
     * Trigger: Scheduled time approaching (24h)
     * Note: This should be called by a cron job
     */
    public function notifyHospitalVoluntaryDonationReady($hospitalUserId, $voluntaryId, $donorName, $date, $time = null)
    {
        $timeStr = $time ? ' at ' . date('g:i A', strtotime($time)) : '';
        
        $this->create(
            $hospitalUserId,
            'Voluntary Donation Tomorrow',
            "Reminder: Voluntary donor '{$donorName}' is scheduled for tomorrow{$timeStr}. Please prepare for their arrival.",
            self::TYPE_WARNING,
            self::RELATED_VOLUNTARY,
            $voluntaryId
        );
    }
    
    // =========================================================================
    // SEEKER NOTIFICATIONS (S1 - S8)
    // =========================================================================
    
    /**
     * S1: Request Submitted
     * Trigger: Seeker submits blood request
     */
    public function notifySeekerRequestSubmitted($seekerUserId, $requestId, $requestCode)
    {
        $this->create(
            $seekerUserId,
            'Request Submitted',
            "Your blood request {$requestCode} has been submitted and is pending admin approval. You will be notified once it's reviewed.",
            self::TYPE_INFO,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * S2: Request Approved
     * Trigger: Admin approves seeker's request
     */
    public function notifySeekerRequestApproved($seekerUserId, $requestId, $requestCode)
    {
        $this->create(
            $seekerUserId,
            'Request Approved',
            "Your blood request {$requestCode} has been approved and is now visible to matching donors. We'll notify you when a donor responds.",
            self::TYPE_SUCCESS,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * S3: Request Rejected
     * Trigger: Admin rejects seeker's request
     */
    public function notifySeekerRequestRejected($seekerUserId, $requestId, $requestCode, $reason = null)
    {
        $message = "Your blood request {$requestCode} has been rejected.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        $message .= ' You may submit a new request with corrected information.';
        
        $this->create(
            $seekerUserId,
            'Request Rejected',
            $message,
            self::TYPE_ERROR,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * S4: Donor Found
     * Trigger: First donor accepts the request
     */
    public function notifySeekerDonorFound($seekerUserId, $requestId, $requestCode, $donorName)
    {
        $this->create(
            $seekerUserId,
            'Donor Found!',
            "Great news! Donor '{$donorName}' has accepted your blood request {$requestCode}. Help is on the way!",
            self::TYPE_SUCCESS,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * S5: Donor On The Way
     * Trigger: Donor starts journey to hospital
     */
    public function notifySeekerDonorOnTheWay($seekerUserId, $requestId, $donorName)
    {
        $this->create(
            $seekerUserId,
            'Donor On The Way',
            "Donor '{$donorName}' is now on their way to the hospital for your blood request.",
            self::TYPE_INFO,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * S6: Donation Completed
     * Trigger: Donor completes donation for their request
     */
    public function notifySeekerDonationCompleted($seekerUserId, $requestId, $donorName)
    {
        $this->create(
            $seekerUserId,
            'Donation Completed',
            "Donor '{$donorName}' has completed their blood donation for your request. One unit has been fulfilled.",
            self::TYPE_SUCCESS,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * S7: Request Fulfilled
     * Trigger: All requested units collected
     */
    public function notifySeekerRequestFulfilled($seekerUserId, $requestId, $requestCode)
    {
        $this->create(
            $seekerUserId,
            'Request Fulfilled',
            "Your blood request {$requestCode} has been completely fulfilled. All requested blood units have been collected. Thank you for using our platform!",
            self::TYPE_SUCCESS,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    /**
     * S8: Request Expired
     * Trigger: Required date passed without fulfillment
     * Note: This should be called by a cron job
     */
    public function notifySeekerRequestExpired($seekerUserId, $requestId, $requestCode)
    {
        $this->create(
            $seekerUserId,
            'Request Expired',
            "Your blood request {$requestCode} has expired as the required date has passed. You may create a new request if still needed.",
            self::TYPE_WARNING,
            self::RELATED_REQUEST,
            $requestId
        );
    }
    
    // =========================================================================
    // HELPER METHODS
    // =========================================================================
    
    /**
     * Notify all matching donors when a request is approved
     * Matches by blood type and city
     */
    public function notifyMatchingDonors($requestId, $bloodType, $city, $hospitalName, $urgency)
    {
        try {
            // Get blood group ID
            $stmt = $this->conn->prepare("SELECT id FROM blood_groups WHERE blood_type = ?");
            $stmt->execute([$bloodType]);
            $bloodGroup = $stmt->fetch();
            
            if (!$bloodGroup) {
                return;
            }
            
            // Get matching donors (same blood type, same city, available, approved)
            $stmt = $this->conn->prepare("
                SELECT d.user_id 
                FROM donors d
                JOIN users u ON d.user_id = u.id
                WHERE d.blood_group_id = ?
                AND d.city = ?
                AND d.is_available = 1
                AND u.status = 'approved'
                AND (d.next_eligible_date IS NULL OR d.next_eligible_date <= CURDATE())
            ");
            $stmt->execute([$bloodGroup['id'], $city]);
            $donors = $stmt->fetchAll();
            
            foreach ($donors as $donor) {
                if ($urgency === 'emergency') {
                    $this->notifyDonorEmergencyRequest($donor['user_id'], $requestId, $bloodType, $hospitalName, $city);
                } else {
                    $this->notifyDonorMatchingRequest($donor['user_id'], $requestId, $bloodType, $hospitalName, $city);
                }
            }
        } catch (PDOException $e) {
            error_log("NotificationService - notifyMatchingDonors Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get request details including requester info
     */
    public function getRequestDetails($requestId)
    {
        $stmt = $this->conn->prepare("
            SELECT r.*, bg.blood_type, u.name as requester_name
            FROM blood_requests r
            JOIN blood_groups bg ON r.blood_group_id = bg.id
            JOIN users u ON r.requester_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$requestId]);
        return $stmt->fetch();
    }
    
    /**
     * Get hospital user ID from hospital table ID
     */
    public function getHospitalUserId($hospitalTableId)
    {
        $stmt = $this->conn->prepare("SELECT user_id FROM hospitals WHERE id = ?");
        $stmt->execute([$hospitalTableId]);
        $result = $stmt->fetch();
        return $result ? $result['user_id'] : null;
    }
    
    /**
     * Get donor user ID from donor table ID
     */
    public function getDonorUserId($donorTableId)
    {
        $stmt = $this->conn->prepare("SELECT user_id FROM donors WHERE id = ?");
        $stmt->execute([$donorTableId]);
        $result = $stmt->fetch();
        return $result ? $result['user_id'] : null;
    }
    
    /**
     * Get hospital user ID from request (if requester is hospital)
     */
    public function getRequestHospitalUserId($requestId)
    {
        $stmt = $this->conn->prepare("
            SELECT requester_id 
            FROM blood_requests 
            WHERE id = ? AND requester_type = 'hospital'
        ");
        $stmt->execute([$requestId]);
        $result = $stmt->fetch();
        return $result ? $result['requester_id'] : null;
    }
    
    /**
     * Check and notify donor achievement milestones
     */
    public function checkAndNotifyAchievement($donorUserId, $totalDonations)
    {
        $milestones = [5, 10, 25, 50, 100];
        
        if (in_array($totalDonations, $milestones)) {
            $this->notifyDonorAchievementUnlocked($donorUserId, $totalDonations);
        }
    }
    
    // =========================================================================
    // ANNOUNCEMENT NOTIFICATIONS
    // =========================================================================
    
    /**
     * Send announcement notification to users based on target audience
     * 
     * @param int $announcementId The announcement ID
     * @param string $title Announcement title
     * @param string $message Announcement message
     * @param string $targetAudience One of: all, donors, hospitals, seekers
     * @param string $priority One of: normal, high, urgent
     */
    public function notifyAnnouncementPublished($announcementId, $title, $message, $targetAudience = 'all', $priority = 'normal')
    {
        // Determine which roles to notify
        $roles = [];
        switch ($targetAudience) {
            case 'donors':
                $roles = ['donor'];
                break;
            case 'hospitals':
                $roles = ['hospital'];
                break;
            case 'seekers':
                $roles = ['seeker'];
                break;
            case 'all':
            default:
                $roles = ['donor', 'hospital', 'seeker'];
                break;
        }
        
        // Get all user IDs for the target roles
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $this->conn->prepare("
            SELECT id FROM users 
            WHERE role IN ($placeholders) AND status = 'approved'
        ");
        $stmt->execute($roles);
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Determine notification type based on priority
        $type = self::TYPE_ANNOUNCEMENT;
        $notificationTitle = $title;
        if ($priority === 'urgent') {
            $notificationTitle = '🚨 ' . $title;
        } elseif ($priority === 'high') {
            $notificationTitle = '📢 ' . $title;
        }
        
        // Create notification for each user
        foreach ($userIds as $userId) {
            $this->create(
                $userId,
                $notificationTitle,
                $message,
                $type,
                'announcement',
                $announcementId
            );
        }
        
        return count($userIds);
    }
    
    // =========================================================================
    // CHAT NOTIFICATIONS (C1)
    // =========================================================================
    
    /**
     * C1: New Chat Message Received
     * Trigger: When a user receives a new chat message
     */
    public function notifyNewChatMessage($receiverId, $senderName, $messagePreview, $messageId)
    {
        $this->create(
            $receiverId,
            "New Message from {$senderName}",
            $messagePreview,
            self::TYPE_INFO,
            'chat_message',
            $messageId
        );
    }
}
