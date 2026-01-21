<?php
/**
 * Donor Health Update Endpoint
 * POST /api/donor/health/update.php
 * Saves or updates donor health information
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

$user = requireAuth(['donor']);

// Require approved status to update health information
requireApprovedStatus($_SESSION['user_id'], 'donor');

$donorId = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Extract and sanitize input data
    $weight = isset($input['weight']) ? floatval($input['weight']) : null;
    $height = isset($input['height']) ? floatval($input['height']) : null;
    $bpSystolic = isset($input['blood_pressure_systolic']) ? intval($input['blood_pressure_systolic']) : null;
    $bpDiastolic = isset($input['blood_pressure_diastolic']) ? intval($input['blood_pressure_diastolic']) : null;
    $hemoglobin = isset($input['hemoglobin']) ? floatval($input['hemoglobin']) : null;
    
    // Medical conditions
    $hasDiabetes = isset($input['has_diabetes']) ? (bool)$input['has_diabetes'] : false;
    $hasHypertension = isset($input['has_hypertension']) ? (bool)$input['has_hypertension'] : false;
    $hasHeartDisease = isset($input['has_heart_disease']) ? (bool)$input['has_heart_disease'] : false;
    $hasAsthma = isset($input['has_asthma']) ? (bool)$input['has_asthma'] : false;
    $hasAllergies = isset($input['has_allergies']) ? (bool)$input['has_allergies'] : false;
    $hasRecentSurgery = isset($input['has_recent_surgery']) ? (bool)$input['has_recent_surgery'] : false;
    $isOnMedication = isset($input['is_on_medication']) ? (bool)$input['is_on_medication'] : false;
    
    // Lifestyle
    $smokingStatus = isset($input['smoking_status']) && in_array($input['smoking_status'], ['no', 'occasionally', 'regularly']) 
        ? $input['smoking_status'] : 'no';
    $alcoholConsumption = isset($input['alcohol_consumption']) && in_array($input['alcohol_consumption'], ['none', 'occasional', 'regular'])
        ? $input['alcohol_consumption'] : 'none';
    $exerciseFrequency = isset($input['exercise_frequency']) && in_array($input['exercise_frequency'], ['rarely', '1-2_weekly', '3-4_weekly', 'daily'])
        ? $input['exercise_frequency'] : 'rarely';
    
    // Additional
    $lastMedicalCheckup = isset($input['last_medical_checkup']) && !empty($input['last_medical_checkup']) 
        ? $input['last_medical_checkup'] : null;
    $additionalNotes = isset($input['additional_notes']) ? trim($input['additional_notes']) : null;

    // Check if health record exists
    $stmt = $conn->prepare("SELECT id FROM donor_health WHERE donor_id = ?");
    $stmt->execute([$donorId]);
    $existingHealth = $stmt->fetch();

    if ($existingHealth) {
        // Update existing record
        $sql = "UPDATE donor_health SET 
                weight = ?, height = ?, blood_pressure_systolic = ?, blood_pressure_diastolic = ?,
                hemoglobin = ?, has_diabetes = ?, has_hypertension = ?, has_heart_disease = ?,
                has_asthma = ?, has_allergies = ?, has_recent_surgery = ?, is_on_medication = ?,
                smoking_status = ?, alcohol_consumption = ?, exercise_frequency = ?,
                last_medical_checkup = ?, additional_notes = ?, updated_at = NOW()
                WHERE donor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $weight, $height, $bpSystolic, $bpDiastolic, $hemoglobin,
            $hasDiabetes, $hasHypertension, $hasHeartDisease, $hasAsthma,
            $hasAllergies, $hasRecentSurgery, $isOnMedication,
            $smokingStatus, $alcoholConsumption, $exerciseFrequency,
            $lastMedicalCheckup, $additionalNotes, $donorId
        ]);
    } else {
        // Insert new record
        $sql = "INSERT INTO donor_health (donor_id, weight, height, blood_pressure_systolic, blood_pressure_diastolic,
                hemoglobin, has_diabetes, has_hypertension, has_heart_disease, has_asthma, has_allergies,
                has_recent_surgery, is_on_medication, smoking_status, alcohol_consumption, exercise_frequency,
                last_medical_checkup, additional_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $donorId, $weight, $height, $bpSystolic, $bpDiastolic, $hemoglobin,
            $hasDiabetes, $hasHypertension, $hasHeartDisease, $hasAsthma,
            $hasAllergies, $hasRecentSurgery, $isOnMedication,
            $smokingStatus, $alcoholConsumption, $exerciseFrequency,
            $lastMedicalCheckup, $additionalNotes
        ]);
    }

    // Also update the weight in users table for compatibility
    if ($weight) {
        $stmt = $conn->prepare("UPDATE users SET weight = ? WHERE id = ?");
        $stmt->execute([$weight, $donorId]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Health information saved successfully'
    ]);

} catch (PDOException $e) {
    error_log("Donor Health Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save health information']);
}
