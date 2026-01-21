<?php
/**
 * Certificate Download Endpoint
 * GET /api/donor/certificates/download.php?donation_id=X
 * Downloads an HTML certificate for a completed donation
 */

// Start output buffering to prevent any accidental output before headers
ob_start();

// Start session FIRST before any output or headers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle CORS - use specific origin for credentials support
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Enable error logging for debugging
error_log("Certificate download request - donation_id: " . ($_GET['donation_id'] ?? 'not set'));
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Session role: " . ($_SESSION['role'] ?? 'not set'));

// Validate user is logged in as donor
$user = requireAuth(['donor']);

// Require approved status
requireApprovedStatus($_SESSION['user_id'], 'donor');

$donorId = $_SESSION['user_id'];

// Validate donation_id parameter
if (!isset($_GET['donation_id']) || !is_numeric($_GET['donation_id'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing donation ID']);
    exit;
}

$donationId = (int) $_GET['donation_id'];
error_log("Processing certificate for donation ID: $donationId, donor ID: $donorId");

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Fetch donation with all required data
    // Validate: donation belongs to logged-in donor AND status is completed
    $sql = "SELECT 
                d.id AS donation_id,
                d.status,
                d.completed_at,
                u.name AS donor_name,
                u.blood_group,
                r.hospital_name,
                r.request_code
            FROM donations d
            JOIN users u ON d.donor_id = u.id
            JOIN blood_requests r ON d.request_id = r.id
            WHERE d.id = ? AND d.donor_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$donationId, $donorId]);
    $donation = $stmt->fetch();

    // Check if donation exists and belongs to this donor
    if (!$donation) {
        ob_end_clean();
        error_log("Certificate download failed: Donation $donationId not found for donor $donorId");
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donation not found or access denied']);
        exit;
    }

    // Check if donation is completed
    if ($donation['status'] !== 'completed') {
        ob_end_clean();
        error_log("Certificate download failed: Donation $donationId status is '{$donation['status']}', not completed");
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Certificate is only available for completed donations']);
        exit;
    }

    // Generate certificate ID and formatted donation ID
    $formattedDonationId = 'DON' . str_pad($donation['donation_id'], 3, '0', STR_PAD_LEFT);
    $certId = 'CERT-' . date('Y', strtotime($donation['completed_at'])) . '-' . $formattedDonationId;
    $donationDate = date('F j, Y', strtotime($donation['completed_at']));

    // Generate HTML certificate
    $certificateHtml = generateCertificateHtml(
        $donation['donor_name'],
        $donation['blood_group'],
        $donationDate,
        $donation['hospital_name'],
        $formattedDonationId,
        $certId
    );

    // Clear output buffer before sending file
    ob_end_clean();
    
    error_log("Certificate download success: Generating certificate for donation $donationId");

    // Set headers for file download
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="certificate-' . $formattedDonationId . '.html"');
    header('Content-Length: ' . strlen($certificateHtml));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    echo $certificateHtml;
    exit;

} catch (PDOException $e) {
    ob_end_clean();
    error_log("Certificate Download Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate certificate']);
    exit;
}

/**
 * Generate HTML certificate content
 */
function generateCertificateHtml($donorName, $bloodGroup, $donationDate, $hospitalName, $donationId, $certId) {
    $currentYear = date('Y');
    
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donation Certificate - {$donationId}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #f5f5f5;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .certificate {
            background: #fff;
            width: 800px;
            max-width: 100%;
            padding: 60px;
            border: 8px double #dc2626;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 2px solid #fecaca;
            pointer-events: none;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: #dc2626;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-icon svg {
            width: 30px;
            height: 30px;
            fill: white;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #dc2626;
        }
        
        .title {
            font-size: 36px;
            color: #1f2937;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 4px;
        }
        
        .subtitle {
            font-size: 18px;
            color: #6b7280;
            font-style: italic;
        }
        
        .content {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .presented-to {
            font-size: 14px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }
        
        .donor-name {
            font-size: 42px;
            color: #dc2626;
            font-family: 'Brush Script MT', cursive, Georgia, serif;
            margin-bottom: 25px;
            border-bottom: 2px solid #fecaca;
            padding-bottom: 15px;
            display: inline-block;
        }
        
        .message {
            font-size: 16px;
            color: #4b5563;
            line-height: 1.8;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 500px;
            margin: 0 auto 40px;
            text-align: left;
        }
        
        .detail-item {
            padding: 15px;
            background: #fef2f2;
            border-radius: 8px;
            border-left: 4px solid #dc2626;
        }
        
        .detail-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 16px;
            color: #1f2937;
            font-weight: 600;
        }
        
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }
        
        .signature {
            text-align: center;
        }
        
        .signature-line {
            width: 200px;
            border-bottom: 2px solid #1f2937;
            margin-bottom: 8px;
            height: 40px;
        }
        
        .signature-title {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .cert-id {
            text-align: right;
            font-size: 12px;
            color: #9ca3af;
        }
        
        .cert-id strong {
            display: block;
            color: #6b7280;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .certificate {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                </div>
                <span class="logo-text">BloodConnect</span>
            </div>
            <h1 class="title">Certificate of Appreciation</h1>
            <p class="subtitle">Blood Donation Recognition</p>
        </div>
        
        <div class="content">
            <p class="presented-to">This certificate is proudly presented to</p>
            <h2 class="donor-name">{$donorName}</h2>
            <p class="message">
                In recognition and sincere appreciation of your generous blood donation. 
                Your selfless act of kindness has the potential to save up to three lives. 
                Thank you for being a hero in someone's life story.
            </p>
            
            <div class="details">
                <div class="detail-item">
                    <div class="detail-label">Blood Group</div>
                    <div class="detail-value">{$bloodGroup}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Donation Date</div>
                    <div class="detail-value">{$donationDate}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Hospital</div>
                    <div class="detail-value">{$hospitalName}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Donation ID</div>
                    <div class="detail-value">{$donationId}</div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <div class="signature">
                <div class="signature-line"></div>
                <p class="signature-title">Authorized Signature</p>
            </div>
            <div class="cert-id">
                <strong>Certificate ID</strong>
                {$certId}<br>
                © {$currentYear} BloodConnect
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}
