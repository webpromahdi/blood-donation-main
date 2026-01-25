<?php
/**
 * User Registration Endpoint
 * POST /api/auth/register.php
 * 
 * Normalized Schema: Creates user + role-specific record (donors/hospitals/seekers)
 */

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly - log them instead
ini_set('log_errors', 1);

// CORS headers for frontend
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Start session
session_start();

// Include database and notification service
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/NotificationService.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['email', 'password', 'role'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

// For donors, name is required
if ($input['role'] === 'donor' && empty($input['name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name is required for donors']);
    exit;
}

$email = filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL);
$password = $input['password'];
$role = $input['role'];
$name = isset($input['name']) ? trim($input['name']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate role - Admin registration is not allowed through public API
$validRoles = ['donor', 'hospital', 'seeker'];
if (!in_array($role, $validRoles)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

// Validate password strength (minimum requirements)
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Connect to database
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email is already registered']);
        exit;
    }

    // Hash password with bcrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Determine initial status based on role
    $status = in_array($role, ['donor', 'hospital']) ? 'pending' : 'approved';

    // Begin transaction for atomic insert
    $conn->beginTransaction();

    // Insert into users table
    $stmt = $conn->prepare('INSERT INTO users (name, email, password, phone, role, status) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, $hashedPassword, $phone, $role, $status]);
    $userId = $conn->lastInsertId();

    // Insert into role-specific table
    if ($role === 'donor') {
        // Get blood group ID - blood_group_id is NOT NULL, so we must find a valid ID
        $bloodGroup = isset($input['bloodGroup']) && !empty($input['bloodGroup']) ? trim($input['bloodGroup']) : 'O+';
        $bloodGroupId = null;
        
        // First try to get the specified blood group
        $stmt = $conn->prepare('SELECT id FROM blood_groups WHERE blood_type = ?');
        $stmt->execute([$bloodGroup]);
        $bgResult = $stmt->fetch();
        
        if ($bgResult) {
            $bloodGroupId = $bgResult['id'];
        } else {
            // Fall back to O+ as default
            $stmt = $conn->prepare('SELECT id FROM blood_groups WHERE blood_type = ?');
            $stmt->execute(['O+']);
            $bgResult = $stmt->fetch();
            if ($bgResult) {
                $bloodGroupId = $bgResult['id'];
            } else {
                // Last resort: get the first available blood group
                $stmt = $conn->prepare('SELECT id FROM blood_groups LIMIT 1');
                $stmt->execute();
                $bgResult = $stmt->fetch();
                if ($bgResult) {
                    $bloodGroupId = $bgResult['id'];
                } else {
                    // No blood groups exist in database - cannot proceed
                    throw new Exception('No blood groups found in database. Please ensure blood_groups table is populated.');
                }
            }
        }
        
        $age = isset($input['age']) && !empty($input['age']) ? (int) $input['age'] : null;
        $weight = isset($input['weight']) && !empty($input['weight']) ? (float) $input['weight'] : null;
        $gender = isset($input['gender']) && !empty($input['gender']) ? trim($input['gender']) : null;
        $city = isset($input['city']) ? trim($input['city']) : null;
        $address = isset($input['address']) ? trim($input['address']) : null;

        $stmt = $conn->prepare('INSERT INTO donors (user_id, blood_group_id, age, weight, gender, city, address) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $bloodGroupId, $age, $weight, $gender, $city, $address]);
        $donorId = $conn->lastInsertId();
        
        // Initialize donor_health record
        $stmt = $conn->prepare('INSERT INTO donor_health (donor_id) VALUES (?)');
        $stmt->execute([$donorId]);
        
    } elseif ($role === 'hospital') {
        $registrationNumber = isset($input['registrationNumber']) ? trim($input['registrationNumber']) : null;
        $hospitalAddress = isset($input['hospitalAddress']) ? trim($input['hospitalAddress']) : null;
        $city = isset($input['city']) ? trim($input['city']) : (isset($input['hospitalCity']) ? trim($input['hospitalCity']) : null);
        $state = isset($input['state']) ? trim($input['state']) : null;
        $pincode = isset($input['pincode']) ? trim($input['pincode']) : null;
        $website = isset($input['website']) ? trim($input['website']) : null;
        $contactPerson = isset($input['contactPerson']) ? trim($input['contactPerson']) : null;
        $hospitalType = isset($input['hospitalType']) && in_array($input['hospitalType'], ['government', 'private', 'charity']) 
            ? $input['hospitalType'] : 'private';
        $licenseExpiryDate = isset($input['licenseExpiry']) ? trim($input['licenseExpiry']) : null;
        $hasBloodBank = isset($input['hasBloodBank']) ? (bool)$input['hasBloodBank'] : false;

        $stmt = $conn->prepare('INSERT INTO hospitals (user_id, registration_number, hospital_type, address, city, state, pincode, website, contact_person, license_expiry_date, has_blood_bank) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $registrationNumber, $hospitalType, $hospitalAddress, $city, $state, $pincode, $website, $contactPerson, $licenseExpiryDate, $hasBloodBank]);
        
    } elseif ($role === 'seeker') {
        $city = isset($input['city']) ? trim($input['city']) : null;
        $address = isset($input['address']) ? trim($input['address']) : null;

        $stmt = $conn->prepare('INSERT INTO seekers (user_id, city, address) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $city, $address]);
    }

    $conn->commit();
    
    // Send notifications for pending registrations (A1, A2)
    if ($status === 'pending') {
        $notificationService = new NotificationService($conn);
        
        if ($role === 'donor') {
            // A2: Notify admins of new donor registration
            $notificationService->notifyAdminNewDonorRegistration($userId, $name);
        } elseif ($role === 'hospital') {
            // A1: Notify admins of new hospital registration
            $notificationService->notifyAdminNewHospitalRegistration($userId, $name);
        }
    }

    // Set session data
    $_SESSION['user_id'] = $userId;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['name'] = $name;
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful' . ($status === 'pending' ? '. Awaiting admin approval.' : ''),
        'user' => [
            'id' => $userId,
            'email' => $email,
            'role' => $role,
            'name' => $name,
            'status' => $status
        ]
    ]);

} catch (PDOException $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Registration PDO Error: " . $e->getMessage() . " | Code: " . $e->getCode());
    
    // Check for duplicate entry error (MySQL error code 1062)
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email is already registered']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again. Error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Registration Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}