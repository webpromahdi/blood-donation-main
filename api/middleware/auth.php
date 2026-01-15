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
