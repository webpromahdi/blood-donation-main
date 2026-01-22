<?php
/**
 * Donor Health Update Endpoint
 * POST /api/donor/health/update.php
 * 
 * Normalized Schema: Updates donor_health table (linked to donors table)
 * Supports partial updates - only provided fields are updated
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

    // Check if health record exists
    $stmt = $conn->prepare("SELECT * FROM donor_health WHERE donor_id = ?");
    $stmt->execute([$donorId]);
    $existingHealth = $stmt->fetch();

    // Helper function to get value (only if explicitly provided and not empty string)
    function getValue($input, $key, $existing, $default = null) {
        // If key exists in input and is not null
        if (array_key_exists($key, $input)) {
            $value = $input[$key];
            // If value is empty string or null, preserve existing value
            if ($value === '' || $value === null) {
                return $existing ? $existing[$key] : $default;
            }
            return $value;
        }
        // Key not in input, preserve existing value
        return $existing ? $existing[$key] : $default;
    }

    // Helper function for boolean values
    function getBoolValue($input, $key, $existing) {
        if (array_key_exists($key, $input)) {
            return (bool)$input[$key];
        }
        return $existing ? (bool)$existing[$key] : false;
    }

    // Extract values - preserve existing if not provided
    $height = getValue($input, 'height', $existingHealth);
    $height = $height !== null ? floatval($height) : null;
    
    $bpSystolic = getValue($input, 'blood_pressure_systolic', $existingHealth);
    $bpSystolic = $bpSystolic !== null ? intval($bpSystolic) : null;
    
    $bpDiastolic = getValue($input, 'blood_pressure_diastolic', $existingHealth);
    $bpDiastolic = $bpDiastolic !== null ? intval($bpDiastolic) : null;
    
    $hemoglobin = getValue($input, 'hemoglobin', $existingHealth);
    $hemoglobin = $hemoglobin !== null ? floatval($hemoglobin) : null;
    
    // Medical conditions - only update if explicitly provided
    $hasDiabetes = getBoolValue($input, 'has_diabetes', $existingHealth);
    $hasHypertension = getBoolValue($input, 'has_hypertension', $existingHealth);
    $hasHeartDisease = getBoolValue($input, 'has_heart_disease', $existingHealth);
    $hasBloodDisorders = getBoolValue($input, 'has_blood_disorders', $existingHealth);
    $hasInfectiousDisease = getBoolValue($input, 'has_infectious_disease', $existingHealth);
    $hasAsthma = getBoolValue($input, 'has_asthma', $existingHealth);
    $hasAllergies = getBoolValue($input, 'has_allergies', $existingHealth);
    $hasRecentSurgery = getBoolValue($input, 'has_recent_surgery', $existingHealth);
    $isOnMedication = getBoolValue($input, 'is_on_medication', $existingHealth);
    
    // Lifestyle - preserve existing if not provided
    $smokingStatus = getValue($input, 'smoking_status', $existingHealth, 'no');
    if (!in_array($smokingStatus, ['no', 'occasionally', 'regularly'])) {
        $smokingStatus = $existingHealth ? $existingHealth['smoking_status'] : 'no';
    }
    
    $alcoholConsumption = getValue($input, 'alcohol_consumption', $existingHealth, 'none');
    if (!in_array($alcoholConsumption, ['none', 'occasionally', 'regularly'])) {
        $alcoholConsumption = $existingHealth ? $existingHealth['alcohol_consumption'] : 'none';
    }
    
    $exerciseFrequency = getValue($input, 'exercise_frequency', $existingHealth, 'rarely');
    if (!in_array($exerciseFrequency, ['rarely', 'weekly', 'daily'])) {
        $exerciseFrequency = $existingHealth ? $existingHealth['exercise_frequency'] : 'rarely';
    }
    
    // Additional text fields
    $medications = getValue($input, 'medications', $existingHealth);
    $medications = $medications !== null ? trim($medications) : null;
    
    $allergiesDetails = getValue($input, 'allergies_details', $existingHealth);
    $allergiesDetails = $allergiesDetails !== null ? trim($allergiesDetails) : null;
    
    $lastMedicalCheckup = getValue($input, 'last_medical_checkup', $existingHealth);
    $lastMedicalCheckup = !empty($lastMedicalCheckup) ? $lastMedicalCheckup : null;
    
    $additionalNotes = getValue($input, 'additional_notes', $existingHealth);
    $additionalNotes = $additionalNotes !== null ? trim($additionalNotes) : null;

    if ($existingHealth) {
        // Update existing record - preserving untouched fields
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
        $message = 'Health information updated successfully';
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
        $message = 'Health information saved successfully';
    }

    // Update weight in donors table if provided
    if (array_key_exists('weight', $input) && $input['weight'] !== '' && $input['weight'] !== null) {
        $weight = floatval($input['weight']);
        $stmt = $conn->prepare("UPDATE donors SET weight = ? WHERE id = ?");
        $stmt->execute([$weight, $donorId]);
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'is_update' => (bool)$existingHealth
    ]);

} catch (PDOException $e) {
    error_log("Donor Health Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save health information']);
}
