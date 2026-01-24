<?php
/**
 * API: Search Users for Chat
 * GET /api/chat/search-users.php
 * 
 * Finds users that the current user can chat with based on role permissions:
 * - Admin: Can chat with donors, hospitals, seekers (not other admins)
 * - Hospital: Can chat with donors, seekers, admins
 * - Donor: Can chat with hospitals, seekers, admins
 * - Seeker: Can chat with hospitals, donors, admins
 * 
 * Query params:
 * - search (string, optional): Search by name or email
 * - role (string, optional): Filter by role (donor, hospital, seeker, admin)
 * - limit (int, default 20): Max results
 * - context (string, optional): 'request', 'donation', 'voluntary' - filter by context
 * - context_id (int, optional): The ID of the context entity
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/chat_utils.php';

$user = requireAuth();
if (!$user) {
    exit;
}

$conn = getDbConnection();
$currentUserId = (int)$_SESSION['user_id'];
$currentUserRole = $_SESSION['role'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20;
$context = isset($_GET['context']) ? trim($_GET['context']) : '';
$contextId = isset($_GET['context_id']) ? (int)$_GET['context_id'] : 0;

// Define which roles each user type can chat with
$allowedRoles = [];
switch ($currentUserRole) {
    case 'admin':
        $allowedRoles = ['donor', 'hospital', 'seeker']; // Admin can't chat with other admins
        break;
    case 'hospital':
        $allowedRoles = ['donor', 'seeker', 'admin'];
        break;
    case 'donor':
        $allowedRoles = ['hospital', 'seeker', 'admin'];
        break;
    case 'seeker':
        $allowedRoles = ['hospital', 'donor', 'admin'];
        break;
    default:
        jsonResponse(403, ['success' => false, 'error' => 'Invalid role']);
}

// If role filter specified, validate it's in allowed roles
if ($roleFilter && !in_array($roleFilter, $allowedRoles)) {
    jsonResponse(400, ['success' => false, 'error' => 'Cannot chat with users of role: ' . $roleFilter]);
}

try {
    $users = [];

    // Context-based filtering (e.g., users involved in a specific request)
    if ($context === 'request' && $contextId > 0) {
        $users = getUsersForRequestContext($conn, $contextId, $currentUserId, $currentUserRole, $search, $limit);
    } elseif ($context === 'donation' && $contextId > 0) {
        $users = getUsersForDonationContext($conn, $contextId, $currentUserId, $currentUserRole, $search, $limit);
    } elseif ($context === 'voluntary' && $contextId > 0) {
        $users = getUsersForVoluntaryContext($conn, $contextId, $currentUserId, $currentUserRole, $search, $limit);
    } else {
        // General user search
        $users = searchChattableUsers($conn, $currentUserId, $allowedRoles, $roleFilter, $search, $limit);
    }

    jsonResponse(200, [
        'success' => true,
        'data' => [
            'users' => $users,
            'current_role' => $currentUserRole,
            'allowed_roles' => $allowedRoles
        ]
    ]);

} catch (Exception $e) {
    error_log('Search Users Error: ' . $e->getMessage());
    jsonResponse(500, ['success' => false, 'error' => 'Failed to search users']);
}

/**
 * Search for chattable users (general search)
 */
function searchChattableUsers(PDO $conn, int $currentUserId, array $allowedRoles, string $roleFilter, string $search, int $limit): array
{
    $rolesToQuery = $roleFilter ? [$roleFilter] : $allowedRoles;
    $placeholders = implode(',', array_fill(0, count($rolesToQuery), '?'));

    $query = "
        SELECT 
            u.id,
            u.name,
            u.email,
            u.role,
            u.status,
            CASE 
                WHEN u.role = 'donor' THEN (SELECT blood_type FROM donors WHERE user_id = u.id LIMIT 1)
                ELSE NULL
            END as blood_type,
            CASE 
                WHEN u.role = 'hospital' THEN (SELECT name FROM hospitals WHERE user_id = u.id LIMIT 1)
                ELSE NULL
            END as hospital_name,
            (
                SELECT COUNT(*) FROM chat_messages 
                WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?)
            ) as message_count
        FROM users u
        WHERE u.id != ?
        AND u.role IN ($placeholders)
        AND (u.status = 'approved' OR u.role IN ('admin', 'seeker'))
    ";

    $params = [$currentUserId, $currentUserId, $currentUserId];
    $params = array_merge($params, $rolesToQuery);

    if ($search) {
        $query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $query .= " ORDER BY message_count DESC, u.name ASC LIMIT ?";
    $params[] = $limit;

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(function($row) {
        return [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => $row['role'],
            'role_label' => ucfirst($row['role']),
            'blood_type' => $row['blood_type'],
            'hospital_name' => $row['hospital_name'],
            'has_conversation' => (int)$row['message_count'] > 0
        ];
    }, $rows);
}

/**
 * Get users involved in a specific blood request
 */
function getUsersForRequestContext(PDO $conn, int $requestId, int $currentUserId, string $currentRole, string $search, int $limit): array
{
    $request = fetchRequest($conn, $requestId);
    if (!$request) {
        return [];
    }

    $users = [];

    // Get the requester (seeker or hospital)
    if ((int)$request['requester_id'] !== $currentUserId) {
        $requester = getUserById($conn, (int)$request['requester_id']);
        if ($requester && $requester['role'] !== $currentRole) {
            $users[] = formatUserForChat($requester, 'Requester');
        }
    }

    // Get hospital if assigned
    if (!empty($request['hospital_id'])) {
        $stmt = $conn->prepare('SELECT u.* FROM hospitals h JOIN users u ON h.user_id = u.id WHERE h.id = ?');
        $stmt->execute([$request['hospital_id']]);
        $hospitalUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($hospitalUser && (int)$hospitalUser['id'] !== $currentUserId && $hospitalUser['role'] !== $currentRole) {
            $users[] = formatUserForChat($hospitalUser, 'Assigned Hospital');
        }
    }

    // Get donors who accepted/donated
    $stmt = $conn->prepare('
        SELECT u.*, d.status as donation_status 
        FROM donations d 
        JOIN donors dn ON d.donor_id = dn.id 
        JOIN users u ON dn.user_id = u.id 
        WHERE d.request_id = ? AND d.status NOT IN ("cancelled")
    ');
    $stmt->execute([$requestId]);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($donors as $donor) {
        if ((int)$donor['id'] !== $currentUserId && $donor['role'] !== $currentRole) {
            $users[] = formatUserForChat($donor, 'Donor - ' . ucfirst($donor['donation_status']));
        }
    }

    // Filter by search if provided
    if ($search) {
        $search = strtolower($search);
        $users = array_filter($users, function($u) use ($search) {
            return strpos(strtolower($u['name']), $search) !== false 
                || strpos(strtolower($u['email']), $search) !== false;
        });
    }

    return array_slice(array_values($users), 0, $limit);
}

/**
 * Get users involved in a specific donation
 */
function getUsersForDonationContext(PDO $conn, int $donationId, int $currentUserId, string $currentRole, string $search, int $limit): array
{
    $donation = fetchDonation($conn, $donationId);
    if (!$donation) {
        return [];
    }

    $users = [];

    // Get the donor
    if ((int)$donation['donor_user_id'] !== $currentUserId) {
        $donor = getUserById($conn, (int)$donation['donor_user_id']);
        if ($donor && $donor['role'] !== $currentRole) {
            $users[] = formatUserForChat($donor, 'Donor');
        }
    }

    // Get request info for requester and hospital
    if (!empty($donation['request_id'])) {
        $request = fetchRequest($conn, (int)$donation['request_id']);
        if ($request) {
            // Get requester
            if ((int)$request['requester_id'] !== $currentUserId) {
                $requester = getUserById($conn, (int)$request['requester_id']);
                if ($requester && $requester['role'] !== $currentRole) {
                    $users[] = formatUserForChat($requester, 'Requester');
                }
            }

            // Get hospital
            $hospitalUserId = getHospitalUserIdForRequest($conn, $request);
            if ($hospitalUserId && $hospitalUserId !== $currentUserId) {
                $hospital = getUserById($conn, $hospitalUserId);
                if ($hospital && $hospital['role'] !== $currentRole) {
                    $users[] = formatUserForChat($hospital, 'Hospital');
                }
            }
        }
    }

    // Filter by search if provided
    if ($search) {
        $search = strtolower($search);
        $users = array_filter($users, function($u) use ($search) {
            return strpos(strtolower($u['name']), $search) !== false 
                || strpos(strtolower($u['email']), $search) !== false;
        });
    }

    return array_slice(array_values($users), 0, $limit);
}

/**
 * Get users involved in a voluntary donation
 */
function getUsersForVoluntaryContext(PDO $conn, int $voluntaryId, int $currentUserId, string $currentRole, string $search, int $limit): array
{
    $voluntary = fetchVoluntary($conn, $voluntaryId);
    if (!$voluntary) {
        return [];
    }

    $users = [];

    // Get donor
    if (!empty($voluntary['donor_user_id']) && (int)$voluntary['donor_user_id'] !== $currentUserId) {
        $donor = getUserById($conn, (int)$voluntary['donor_user_id']);
        if ($donor && $donor['role'] !== $currentRole) {
            $users[] = formatUserForChat($donor, 'Donor');
        }
    }

    // Get hospital
    if (!empty($voluntary['hospital_user_id']) && (int)$voluntary['hospital_user_id'] !== $currentUserId) {
        $hospital = getUserById($conn, (int)$voluntary['hospital_user_id']);
        if ($hospital && $hospital['role'] !== $currentRole) {
            $users[] = formatUserForChat($hospital, 'Hospital');
        }
    }

    // Filter by search if provided
    if ($search) {
        $search = strtolower($search);
        $users = array_filter($users, function($u) use ($search) {
            return strpos(strtolower($u['name']), $search) !== false 
                || strpos(strtolower($u['email']), $search) !== false;
        });
    }

    return array_slice(array_values($users), 0, $limit);
}

/**
 * Format user data for chat response
 */
function formatUserForChat(array $user, string $contextLabel = ''): array
{
    return [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'role_label' => ucfirst($user['role']),
        'context_label' => $contextLabel,
        'has_conversation' => false
    ];
}
