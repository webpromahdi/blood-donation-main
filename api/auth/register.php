<?php
/**
 * User Registration Endpoint
 * POST /api/auth/register.php
 */

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

// Include database
require_once __DIR__ . '/../config/database.php';

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

// Validate role
$validRoles = ['admin', 'donor', 'hospital', 'seeker'];
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
    // Check if email already exists with the same role
    $stmt = $conn->prepare('SELECT id, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // Check if user already has this role
        $stmt = $conn->prepare('SELECT id FROM user_roles WHERE user_id = ? AND role = ?');
        $stmt->execute([$existingUser['id'], $role]);
        
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'You are already registered as a ' . $role]);
            exit;
        }
        
        // User exists but wants to add a new role (e.g., seeker wants to also be a donor)
        // Add the new role to user_roles table
        $conn->beginTransaction();
        
        try {
            // Determine status for the new role
            $newRoleStatus = in_array($role, ['donor', 'hospital']) ? 'pending' : 'approved';
            
            // Prepare role-specific data
            $roleData = null;
            if ($role === 'donor') {
                $bloodGroup = isset($input['bloodGroup']) ? $input['bloodGroup'] : null;
                $age = isset($input['age']) ? (int) $input['age'] : null;
                $weight = isset($input['weight']) ? (int) $input['weight'] : null;
                $city = isset($input['city']) ? trim($input['city']) : null;
                $address = isset($input['address']) ? trim($input['address']) : null;
                
                $roleData = json_encode([
                    'blood_group' => $bloodGroup,
                    'age' => $age,
                    'weight' => $weight,
                    'city' => $city,
                    'address' => $address
                ]);
                
                // Update user's donor-specific fields
                $stmt = $conn->prepare('UPDATE users SET blood_group = ?, age = ?, weight = ?, city = COALESCE(city, ?), address = COALESCE(address, ?) WHERE id = ?');
                $stmt->execute([$bloodGroup, $age, $weight, $city, $address, $existingUser['id']]);
            } elseif ($role === 'hospital') {
                $registrationNumber = isset($input['registrationNumber']) ? trim($input['registrationNumber']) : null;
                $hospitalAddress = isset($input['hospitalAddress']) ? trim($input['hospitalAddress']) : null;
                $city = isset($input['city']) ? trim($input['city']) : null;
                $website = isset($input['website']) ? trim($input['website']) : null;
                $contactPerson = isset($input['contactPerson']) ? trim($input['contactPerson']) : null;
                
                $roleData = json_encode([
                    'registration_number' => $registrationNumber,
                    'hospital_address' => $hospitalAddress,
                    'city' => $city,
                    'website' => $website,
                    'contact_person' => $contactPerson
                ]);
            }
            
            // Insert the new role
            $stmt = $conn->prepare('INSERT INTO user_roles (user_id, role, status, role_data) VALUES (?, ?, ?, ?)');
            $stmt->execute([$existingUser['id'], $role, $newRoleStatus, $roleData]);
            
            $conn->commit();
            
            // Update session with new role if switching
            $_SESSION['user_id'] = $existingUser['id'];
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            $_SESSION['name'] = $name ?: $_SESSION['name'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            echo json_encode([
                'success' => true,
                'message' => 'New role added successfully. ' . ($newRoleStatus === 'pending' ? 'Awaiting admin approval.' : ''),
                'user' => [
                    'id' => $existingUser['id'],
                    'email' => $email,
                    'role' => $role,
                    'name' => $name,
                    'status' => $newRoleStatus
                ]
            ]);
            exit;
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    // Hash password with bcrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Prepare insert query based on role
    $sql = 'INSERT INTO users (name, email, password, phone, role';
    $params = [$name, $email, $hashedPassword, $phone, $role];

    // Add donor-specific fields
    if ($role === 'donor') {
        $bloodGroup = isset($input['bloodGroup']) ? $input['bloodGroup'] : null;
        $age = isset($input['age']) ? (int) $input['age'] : null;
        $weight = isset($input['weight']) ? (int) $input['weight'] : null;
        $city = isset($input['city']) ? trim($input['city']) : null;
        $address = isset($input['address']) ? trim($input['address']) : null;

        $sql .= ', blood_group, age, weight, city, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $params = array_merge($params, [$bloodGroup, $age, $weight, $city, $address, 'pending']);
    }
    // Add hospital-specific fields
    else if ($role === 'hospital') {
        $registrationNumber = isset($input['registrationNumber']) ? trim($input['registrationNumber']) : null;
        $hospitalAddress = isset($input['hospitalAddress']) ? trim($input['hospitalAddress']) : null;
        $city = isset($input['city']) ? trim($input['city']) : null;
        $website = isset($input['website']) ? trim($input['website']) : null;
        $contactPerson = isset($input['contactPerson']) ? trim($input['contactPerson']) : null;

        $sql .= ', registration_number, hospital_address, city, website, contact_person, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $params = array_merge($params, [$registrationNumber, $hospitalAddress, $city, $website, $contactPerson, 'pending']);
    } else {
        $sql .= ') VALUES (?, ?, ?, ?, ?)';
    }

    // Insert user
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $userId = $conn->lastInsertId();

    // Also insert into user_roles table for multi-role support
    $roleStatus = in_array($role, ['donor', 'hospital']) ? 'pending' : 'approved';
    $roleData = null;
    
    if ($role === 'donor') {
        $roleData = json_encode([
            'blood_group' => $bloodGroup ?? null,
            'age' => $age ?? null,
            'weight' => $weight ?? null,
            'city' => $city ?? null,
            'address' => $address ?? null
        ]);
    } elseif ($role === 'hospital') {
        $roleData = json_encode([
            'registration_number' => $registrationNumber ?? null,
            'hospital_address' => $hospitalAddress ?? null,
            'city' => $city ?? null,
            'website' => $website ?? null,
            'contact_person' => $contactPerson ?? null
        ]);
    }
    
    // Insert into user_roles (ignore if already exists - for migration purposes)
    $stmt = $conn->prepare('INSERT IGNORE INTO user_roles (user_id, role, status, role_data) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $role, $roleStatus, $roleData]);

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
        'message' => 'Registration successful',
        'user' => [
            'id' => $userId,
            'email' => $email,
            'role' => $role,
            'name' => $name
        ]
    ]);

} catch (PDOException $e) {
    error_log("Registration Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
