<?php
/**
 * Authentication Middleware
 * Include this file in protected PHP pages/endpoints
 * 
 * Usage:
 *   require_once __DIR__ . '/../api/middleware/auth.php';
 *   requireAuth();                    // Require any logged-in user
 *   requireAuth(['admin']);           // Require admin role only
 *   requireAuth(['admin', 'donor']);  // Require admin OR donor role
 */

/**
 * Check if user is authenticated
 * @param array|null $allowedRoles - Optional array of allowed roles
 * @return array|false - Returns user data if authenticated, false otherwise
 */
function checkAuth($allowedRoles = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Session timeout (1 hour)
    $session_timeout = 3600;

    // Check if logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }

    // Check session timeout
    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > $session_timeout) {
            session_destroy();
            return false;
        }
    }

    // Check role if specified
    if ($allowedRoles !== null) {
        if (!in_array($_SESSION['role'], $allowedRoles)) {
            return false;
        }
    }

    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'name' => $_SESSION['name']
    ];
}

/**
 * Require authentication - returns 401 JSON response if not authenticated
 * Use this in API endpoints
 * @param array|null $allowedRoles - Optional array of allowed roles
 */
function requireAuth($allowedRoles = null)
{
    $user = checkAuth($allowedRoles);

    if (!$user) {
        // Clean output buffer if active to prevent headers already sent error
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please login to continue.'
        ]);
        exit;
    }

    return $user;
}

/**
 * Require authentication - redirects to login page if not authenticated
 * Use this in HTML pages
 * @param array|null $allowedRoles - Optional array of allowed roles
 * @param string $loginUrl - URL to redirect to if not authenticated
 */
function requireAuthRedirect($allowedRoles = null, $loginUrl = '/src/pages/auth/login.html')
{
    $user = checkAuth($allowedRoles);

    if (!$user) {
        header('Location: ' . $loginUrl);
        exit;
    }

    return $user;
}

/**
 * Get current user if logged in, null otherwise
 * Does not block/redirect if not authenticated
 */
function getCurrentUser()
{
    return checkAuth();
}

/**
 * Get user status from database
 * @param int $userId - User ID
 * @return string|null - Returns status or null if not found
 */
function getUserStatus($userId)
{
    require_once __DIR__ . '/../config/database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? $result['status'] : null;
    } catch (PDOException $e) {
        error_log("Get User Status Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Require approved status - returns 403 if user is not approved
 * Use this in protected API endpoints for donors and hospitals
 * @param int $userId - User ID
 * @param string $role - User role (donor/hospital)
 */
function requireApprovedStatus($userId, $role)
{
    // Admin and seeker roles don't need approval check
    if (!in_array($role, ['donor', 'hospital'])) {
        return true;
    }
    
    $status = getUserStatus($userId);
    
    if ($status === 'rejected') {
        // Clean output buffer if active to prevent headers already sent error
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Your account has been rejected by the admin.',
            'status' => 'rejected',
            'rejected' => true
        ]);
        exit;
    }
    
    if ($status !== 'approved') {
        // Clean output buffer if active to prevent headers already sent error
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Your account is under review. Please wait for admin approval.',
            'status' => $status ?? 'pending',
            'requires_approval' => true
        ]);
        exit;
    }
    
    return true;
}
