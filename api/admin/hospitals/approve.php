<?php
/**
 * Admin Approve Hospital Endpoint
 * POST /api/admin/hospitals/approve.php
 * Body: { hospital_id: int }
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

requireAuth(['admin']);

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['hospital_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Hospital ID is required']);
    exit;
}

$hospitalId = (int) $input['hospital_id'];

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Verify hospital exists and is a hospital user
    $stmt = $conn->prepare('SELECT id, status FROM users WHERE id = ? AND role = ?');
    $stmt->execute([$hospitalId, 'hospital']);
    $hospital = $stmt->fetch();

    if (!$hospital) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Hospital not found']);
        exit;
    }

    // Update status to approved
    $stmt = $conn->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute(['approved', $hospitalId]);

    echo json_encode([
        'success' => true,
        'message' => 'Hospital approved successfully',
        'hospital_id' => $hospitalId,
        'new_status' => 'approved'
    ]);

} catch (PDOException $e) {
    error_log("Approve Hospital Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to approve hospital']);
}
