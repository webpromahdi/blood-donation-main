<?php
/**
 * Donor Health Update Endpoint
 * POST /api/donor/health/update.php
 * 
 * Normalized Schema: Updates donor_health table (linked to donors table)
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

$userId = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get donor_id from donors table
    $stmt = $conn->prepare("SELECT id FROM donors WHERE user_id = ?");
    $stmt->execute([$userId]);
    $donor = $stmt->fetch();
    
    if (!$donor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor record not found']);
        exit;
    }
    
    $donorId = $donor['id'];

    // Extract and sanitize input data
    $height = isset($input['height']) ? floatval($input['height']) : null;
    $bpSystolic = isset($input['blood_pressure_systolic']) ? intval($input['blood_pressure_systolic']) : null;
    $bpDiastolic = isset($input['blood_pressure_diastolic']) ? intval($input['blood_pressure_diastolic']) : null;
    $hemoglobin = isset($input['hemoglobin']) ? floatval($input['hemoglobin']) : null;
    
    // Medical conditions
    $hasDiabetes = isset($input['has_diabetes']) ? (bool)$input['has_diabetes'] : false;
    $hasHypertension = isset($input['has_hypertension']) ? (bool)$input['has_hypertension'] : false;
    $hasHeartDisease = isset($input['has_heart_disease']) ? (bool)$input['has_heart_disease'] : false;
    $hasBloodDisorders = isset($input['has_blood_disorders']) ? (bool)$input['has_blood_disorders'] : false;
    $hasInfectiousDisease = isset($input['has_infectious_disease']) ? (bool)$input['has_infectious_disease'] : false;
    $hasAsthma = isset($input['has_asthma']) ? (bool)$input['has_asthma'] : false;
    $hasAllergies = isset($input['has_allergies']) ? (bool)$input['has_allergies'] : false;
    $hasRecentSurgery = isset($input['has_recent_surgery']) ? (bool)$input['has_recent_surgery'] : false;
    $isOnMedication = isset($input['is_on_medication']) ? (bool)$input['is_on_medication'] : false;
    
    // Lifestyle
    $smokingStatus = isset($input['smoking_status']) && in_array($input['smoking_status'], ['no', 'occasionally', 'regularly']) 
        ? $input['smoking_status'] : 'no';
    $alcoholConsumption = isset($input['alcohol_consumption']) && in_array($input['alcohol_consumption'], ['none', 'occasionally', 'regularly'])
        ? $input['alcohol_consumption'] : 'none';
    $exerciseFrequency = isset($input['exercise_frequency']) && in_array($input['exercise_frequency'], ['rarely', 'weekly', 'daily'])
        ? $input['exercise_frequency'] : 'rarely';
    
    // Additional
    $medications = isset($input['medications']) ? trim($input['medications']) : null;
    $allergiesDetails = isset($input['allergies_details']) ? trim($input['allergies_details']) : null;
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
                height = ?, blood_pressure_systolic = ?, blood_pressure_diastolic = ?,
                hemoglobin = ?, has_diabetes = ?, has_hypertension = ?, has_heart_disease = ?,
                has_blood_disorders = ?, has_infectious_disease = ?,
                has_asthma = ?, has_allergies = ?, has_recent_surgery = ?, is_on_medication = ?,
                smoking_status = ?, alcohol_consumption = ?, exercise_frequency = ?,
                medications = ?, allergies_details = ?, last_medical_checkup = ?, additional_notes = ?, 
                updated_at = NOW()
                WHERE donor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $height, $bpSystolic, $bpDiastolic, $hemoglobin,
            $hasDiabetes, $hasHypertension, $hasHeartDisease, $hasBloodDisorders, $hasInfectiousDisease,
            $hasAsthma, $hasAllergies, $hasRecentSurgery, $isOnMedication,
            $smokingStatus, $alcoholConsumption, $exerciseFrequency,
            $medications, $allergiesDetails, $lastMedicalCheckup, $additionalNotes, $donorId
        ]);
    } else {
        // Insert new record
        $sql = "INSERT INTO donor_health (donor_id, height, blood_pressure_systolic, blood_pressure_diastolic,
                hemoglobin, has_diabetes, has_hypertension, has_heart_disease, has_blood_disorders, has_infectious_disease,
                has_asthma, has_allergies, has_recent_surgery, is_on_medication, 
                smoking_status, alcohol_consumption, exercise_frequency,
                medications, allergies_details, last_medical_checkup, additional_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $donorId, $height, $bpSystolic, $bpDiastolic, $hemoglobin,
            $hasDiabetes, $hasHypertension, $hasHeartDisease, $hasBloodDisorders, $hasInfectiousDisease,
            $hasAsthma, $hasAllergies, $hasRecentSurgery, $isOnMedication,
            $smokingStatus, $alcoholConsumption, $exerciseFrequency,
            $medications, $allergiesDetails, $lastMedicalCheckup, $additionalNotes
        ]);
    }

    // Update weight in donors table if provided
    if (isset($input['weight'])) {
        $weight = floatval($input['weight']);
        $stmt = $conn->prepare("UPDATE donors SET weight = ? WHERE id = ?");
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
