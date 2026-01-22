<?php
/**
 * Achievement Certificate Download Endpoint
 * GET /api/donor/certificates/achievement.php?tier=Bronze&required=1
 * Downloads an HTML certificate for reaching a donation milestone
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

// Validate user is logged in as donor
$user = requireAuth(['donor']);

// Require approved status
requireApprovedStatus($_SESSION['user_id'], 'donor');

$donorId = $_SESSION['user_id'];

// Validate tier parameter
$validTiers = ['Bronze' => 1, 'Silver' => 3, 'Gold' => 5, 'Platinum' => 10, 'Diamond' => 25];

if (!isset($_GET['tier']) || !array_key_exists($_GET['tier'], $validTiers)) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing tier']);
    exit;
}

$tierName = $_GET['tier'];
$requiredDonations = $validTiers[$tierName];

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
    // Get donor's profile from normalized tables
    $stmt = $conn->prepare("
        SELECT u.name, bg.blood_type as blood_group, d.id as donor_id
        FROM users u
        JOIN donors d ON u.id = d.user_id
        LEFT JOIN blood_groups bg ON d.blood_group_id = bg.id
        WHERE u.id = ?
    ");
    $stmt->execute([$donorId]);
    $donor = $stmt->fetch();

    if (!$donor) {
        ob_end_clean();
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donor not found']);
        exit;
    }
    
    $donorRecordId = $donor['donor_id'];

    // Count completed donations using donor_id (donors.id)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM donations WHERE donor_id = ? AND status = 'completed'");
    $stmt->execute([$donorRecordId]);
    $result = $stmt->fetch();
    $totalDonations = (int) $result['total'];

    // Verify donor has enough donations for this tier
    if ($totalDonations < $requiredDonations) {
        ob_end_clean();
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => "You need {$requiredDonations} donations to unlock the {$tierName} certificate. You have {$totalDonations}."
        ]);
        exit;
    }

    // Get the date when they reached this milestone (date of Nth completed donation)
    $stmt = $conn->prepare("
        SELECT completed_at 
        FROM donations 
        WHERE donor_id = ? AND status = 'completed' 
        ORDER BY completed_at ASC 
        LIMIT 1 OFFSET ?
    ");
    $stmt->execute([$donorId, $requiredDonations - 1]);
    $milestoneRow = $stmt->fetch();
    $milestoneDate = $milestoneRow ? date('F j, Y', strtotime($milestoneRow['completed_at'])) : date('F j, Y');

    // Generate certificate ID
    $certId = 'ACH-' . strtoupper(substr($tierName, 0, 3)) . '-' . $donorId . '-' . date('Y');

    // Generate HTML certificate
    $certificateHtml = generateAchievementCertificateHtml(
        $donor['name'],
        $donor['blood_group'],
        $tierName,
        $requiredDonations,
        $totalDonations,
        $milestoneDate,
        $certId
    );

    // Clear output buffer before sending file
    ob_end_clean();

    // Set headers for file download
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="certificate-' . strtolower($tierName) . '-milestone.html"');
    header('Content-Length: ' . strlen($certificateHtml));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    echo $certificateHtml;
    exit;

} catch (PDOException $e) {
    ob_end_clean();
    error_log("Achievement Certificate Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate certificate']);
    exit;
}

/**
 * Generate HTML achievement certificate content
 */
function generateAchievementCertificateHtml($donorName, $bloodGroup, $tierName, $requiredDonations, $totalDonations, $milestoneDate, $certId) {
    $currentYear = date('Y');
    
    // Pre-calculate plural suffix for HEREDOC compatibility
    $donationPlural = $requiredDonations > 1 ? 's' : '';
    
    // Tier colors
    $tierColors = [
        'Bronze' => ['primary' => '#cd7f32', 'secondary' => '#b8860b', 'bg' => '#fef3e2'],
        'Silver' => ['primary' => '#c0c0c0', 'secondary' => '#a8a8a8', 'bg' => '#f5f5f5'],
        'Gold' => ['primary' => '#ffd700', 'secondary' => '#daa520', 'bg' => '#fffef0'],
        'Platinum' => ['primary' => '#e5e4e2', 'secondary' => '#b4b4b4', 'bg' => '#f8f8f8'],
        'Diamond' => ['primary' => '#b9f2ff', 'secondary' => '#4fc3f7', 'bg' => '#f0faff'],
    ];
    
    $colors = $tierColors[$tierName] ?? $tierColors['Bronze'];
    
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$tierName} Achievement Certificate - BloodConnect</title>
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
            border: 8px double {$colors['primary']};
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
            border: 2px solid {$colors['bg']};
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
        
        .badge {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, {$colors['primary']}, {$colors['secondary']});
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .badge svg {
            width: 60px;
            height: 60px;
            fill: white;
        }
        
        .title {
            font-size: 36px;
            color: {$colors['secondary']};
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
            color: {$colors['secondary']};
            font-family: 'Brush Script MT', cursive, Georgia, serif;
            margin-bottom: 25px;
            border-bottom: 2px solid {$colors['bg']};
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
        
        .achievement-box {
            background: {$colors['bg']};
            border: 2px solid {$colors['primary']};
            border-radius: 12px;
            padding: 20px;
            max-width: 400px;
            margin: 0 auto 30px;
        }
        
        .achievement-title {
            font-size: 24px;
            color: {$colors['secondary']};
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .achievement-desc {
            font-size: 14px;
            color: #6b7280;
        }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: {$colors['secondary']};
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
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
            
            <div class="badge">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                </svg>
            </div>
            
            <h1 class="title">{$tierName} Donor</h1>
            <p class="subtitle">Achievement Certificate</p>
        </div>
        
        <div class="content">
            <p class="presented-to">This certificate is proudly awarded to</p>
            <h2 class="donor-name">{$donorName}</h2>
            
            <p class="message">
                In recognition of your extraordinary commitment to saving lives through blood donation.
                Your dedication and generosity have made a profound impact on countless lives in our community.
            </p>
            
            <div class="achievement-box">
                <div class="achievement-title">🏆 {$tierName} Milestone Achieved</div>
                <div class="achievement-desc">Successfully completed {$requiredDonations} blood donation{$donationPlural}</div>
            </div>
            
            <div class="stats">
                <div class="stat">
                    <div class="stat-value">{$totalDonations}</div>
                    <div class="stat-label">Total Donations</div>
                </div>
                <div class="stat">
                    <div class="stat-value">{$bloodGroup}</div>
                    <div class="stat-label">Blood Type</div>
                </div>
                <div class="stat">
                    <div class="stat-value">{$requiredDonations}+</div>
                    <div class="stat-label">Milestone</div>
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
                Achieved: {$milestoneDate}<br>
                © {$currentYear} BloodConnect
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}
