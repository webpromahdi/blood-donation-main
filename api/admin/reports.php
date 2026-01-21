<?php
/**
 * Admin Reports API
 * GET /api/admin/reports.php
 * 
 * Query Parameters:
 * - action: chart_data | donors | hospitals | requests | donations | monthly_summary | download
 * - start_date: YYYY-MM-DD (optional)
 * - end_date: YYYY-MM-DD (optional)
 * - blood_group: A+, A-, B+, etc. (optional)
 * - status: pending, approved, completed, etc. (optional)
 * - format: csv | pdf (for downloads)
 * - report_type: donor | hospital | request | donation | monthly (for downloads)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

requireAuth(['admin']);

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get query parameters
$action = $_GET['action'] ?? 'chart_data';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$bloodGroup = $_GET['blood_group'] ?? null;
$status = $_GET['status'] ?? null;
$format = $_GET['format'] ?? 'json';
$reportType = $_GET['report_type'] ?? 'donor';

try {
    switch ($action) {
        case 'chart_data':
            echo json_encode(getChartData($conn, $startDate, $endDate));
            break;
        case 'donors':
            echo json_encode(getDonorReport($conn, $startDate, $endDate, $bloodGroup, $status));
            break;
        case 'hospitals':
            echo json_encode(getHospitalReport($conn, $startDate, $endDate, $status));
            break;
        case 'requests':
            echo json_encode(getRequestsReport($conn, $startDate, $endDate, $bloodGroup, $status));
            break;
        case 'donations':
            echo json_encode(getDonationsReport($conn, $startDate, $endDate, $bloodGroup, $status));
            break;
        case 'monthly_summary':
            echo json_encode(getMonthlySummary($conn, $startDate, $endDate));
            break;
        case 'download':
            handleDownload($conn, $reportType, $format, $startDate, $endDate, $bloodGroup, $status);
            break;
        case 'custom':
            echo json_encode(getCustomReport($conn, $reportType, $startDate, $endDate, $bloodGroup, $status));
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Reports API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate report']);
}

/**
 * Get chart data for dashboard visualizations
 */
function getChartData($conn, $startDate, $endDate) {
    $result = [
        'success' => true,
        'monthly_trends' => [],
        'blood_type_distribution' => [],
        'donations_vs_requests' => []
    ];
    
    // Monthly donation trends (last 12 months)
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            DATE_FORMAT(created_at, '%b %Y') as month_label,
            COUNT(*) as count
        FROM donations
        WHERE created_at BETWEEN :start_date AND :end_date
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59']);
    $donationsByMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly requests
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            DATE_FORMAT(created_at, '%b %Y') as month_label,
            COUNT(*) as count
        FROM blood_requests
        WHERE created_at BETWEEN :start_date AND :end_date
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59']);
    $requestsByMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build combined monthly data
    $monthlyData = [];
    
    // Generate all months in range
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = new DateInterval('P1M');
    $period = new DatePeriod($start, $interval, $end->modify('+1 month'));
    
    foreach ($period as $date) {
        $key = $date->format('Y-m');
        $monthlyData[$key] = [
            'month' => $key,
            'month_label' => $date->format('M Y'),
            'donations' => 0,
            'requests' => 0
        ];
    }
    
    // Fill in donations data
    foreach ($donationsByMonth as $row) {
        if (isset($monthlyData[$row['month']])) {
            $monthlyData[$row['month']]['donations'] = (int)$row['count'];
        }
    }
    
    // Fill in requests data
    foreach ($requestsByMonth as $row) {
        if (isset($monthlyData[$row['month']])) {
            $monthlyData[$row['month']]['requests'] = (int)$row['count'];
        }
    }
    
    $result['monthly_trends'] = array_values($monthlyData);
    
    // Blood type distribution
    $stmt = $conn->query("
        SELECT 
            blood_group,
            COUNT(*) as count
        FROM users 
        WHERE role = 'donor' AND blood_group IS NOT NULL AND blood_group != ''
        GROUP BY blood_group
        ORDER BY count DESC
    ");
    $bloodTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $colors = [
        'O+' => '#EF4444', 'O-' => '#F87171',
        'A+' => '#3B82F6', 'A-' => '#60A5FA',
        'B+' => '#10B981', 'B-' => '#34D399',
        'AB+' => '#8B5CF6', 'AB-' => '#A78BFA'
    ];
    
    foreach ($bloodTypes as $bt) {
        $result['blood_type_distribution'][] = [
            'name' => $bt['blood_group'],
            'value' => (int)$bt['count'],
            'color' => $colors[$bt['blood_group']] ?? '#6B7280'
        ];
    }
    
    // Summary stats
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donations WHERE created_at BETWEEN :start_date AND :end_date");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59']);
    $result['total_donations'] = (int)$stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blood_requests WHERE created_at BETWEEN :start_date AND :end_date");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59']);
    $result['total_requests'] = (int)$stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donations WHERE status = 'completed' AND created_at BETWEEN :start_date AND :end_date");
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59']);
    $result['completed_donations'] = (int)$stmt->fetch()['count'];
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'donor' AND status = 'approved'");
    $result['active_donors'] = (int)$stmt->fetch()['count'];
    
    // Success rate
    if ($result['total_donations'] > 0) {
        $result['success_rate'] = round(($result['completed_donations'] / $result['total_donations']) * 100, 1);
    } else {
        $result['success_rate'] = 0;
    }
    
    // Lives saved estimate (3 lives per donation)
    $result['lives_saved'] = $result['completed_donations'] * 3;
    
    return $result;
}

/**
 * Get donor report data
 */
function getDonorReport($conn, $startDate, $endDate, $bloodGroup = null, $status = null) {
    $params = ['start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59'];
    
    $where = "WHERE u.role = 'donor' AND u.created_at BETWEEN :start_date AND :end_date";
    
    if ($bloodGroup) {
        $where .= " AND u.blood_group = :blood_group";
        $params['blood_group'] = $bloodGroup;
    }
    
    if ($status) {
        $where .= " AND u.status = :status";
        $params['status'] = $status;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.phone,
            u.blood_group,
            u.city,
            u.status,
            u.created_at,
            COUNT(d.id) as total_donations,
            SUM(CASE WHEN d.status = 'completed' THEN 1 ELSE 0 END) as completed_donations
        FROM users u
        LEFT JOIN donations d ON u.id = d.donor_id
        $where
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute($params);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Summary stats
    $totalDonors = count($donors);
    $activeDonors = count(array_filter($donors, fn($d) => $d['status'] === 'approved'));
    $totalDonations = array_sum(array_column($donors, 'completed_donations'));
    
    return [
        'success' => true,
        'summary' => [
            'total_donors' => $totalDonors,
            'active_donors' => $activeDonors,
            'total_donations' => $totalDonations,
            'avg_donations_per_donor' => $activeDonors > 0 ? round($totalDonations / $activeDonors, 1) : 0
        ],
        'data' => $donors
    ];
}

/**
 * Get hospital report data
 */
function getHospitalReport($conn, $startDate, $endDate, $status = null) {
    $params = ['start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59'];
    
    $where = "WHERE u.role = 'hospital' AND u.created_at BETWEEN :start_date AND :end_date";
    
    if ($status) {
        $where .= " AND u.status = :status";
        $params['status'] = $status;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.phone,
            u.city,
            u.hospital_address,
            u.registration_number,
            u.status,
            u.created_at,
            COUNT(br.id) as total_requests,
            SUM(CASE WHEN br.status = 'completed' THEN 1 ELSE 0 END) as fulfilled_requests
        FROM users u
        LEFT JOIN blood_requests br ON u.id = br.requester_id AND br.requester_type = 'hospital'
        $where
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute($params);
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalHospitals = count($hospitals);
    $activeHospitals = count(array_filter($hospitals, fn($h) => $h['status'] === 'approved'));
    $totalRequests = array_sum(array_column($hospitals, 'total_requests'));
    $fulfilledRequests = array_sum(array_column($hospitals, 'fulfilled_requests'));
    
    return [
        'success' => true,
        'summary' => [
            'total_hospitals' => $totalHospitals,
            'active_hospitals' => $activeHospitals,
            'total_requests' => $totalRequests,
            'fulfilled_requests' => $fulfilledRequests,
            'fulfillment_rate' => $totalRequests > 0 ? round(($fulfilledRequests / $totalRequests) * 100, 1) : 0
        ],
        'data' => $hospitals
    ];
}

/**
 * Get blood requests report data
 */
function getRequestsReport($conn, $startDate, $endDate, $bloodGroup = null, $status = null) {
    $params = ['start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59'];
    
    $where = "WHERE br.created_at BETWEEN :start_date AND :end_date";
    
    if ($bloodGroup) {
        $where .= " AND br.blood_type = :blood_group";
        $params['blood_group'] = $bloodGroup;
    }
    
    if ($status) {
        $where .= " AND br.status = :status";
        $params['status'] = $status;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            br.id,
            br.request_code,
            br.patient_name,
            br.blood_type,
            br.quantity,
            br.urgency,
            br.status,
            br.hospital_name,
            br.city,
            br.required_date,
            br.created_at,
            u.name as requester_name
        FROM blood_requests br
        LEFT JOIN users u ON br.requester_id = u.id
        $where
        ORDER BY br.created_at DESC
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Summary by status
    $statusSummary = [];
    foreach ($requests as $r) {
        $s = $r['status'];
        if (!isset($statusSummary[$s])) {
            $statusSummary[$s] = 0;
        }
        $statusSummary[$s]++;
    }
    
    // Summary by blood type
    $bloodTypeSummary = [];
    foreach ($requests as $r) {
        $bt = $r['blood_type'];
        if (!isset($bloodTypeSummary[$bt])) {
            $bloodTypeSummary[$bt] = 0;
        }
        $bloodTypeSummary[$bt]++;
    }
    
    return [
        'success' => true,
        'summary' => [
            'total_requests' => count($requests),
            'by_status' => $statusSummary,
            'by_blood_type' => $bloodTypeSummary,
            'emergency_count' => count(array_filter($requests, fn($r) => $r['urgency'] === 'emergency'))
        ],
        'data' => $requests
    ];
}

/**
 * Get donations report data
 */
function getDonationsReport($conn, $startDate, $endDate, $bloodGroup = null, $status = null) {
    $params = ['start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59'];
    
    $where = "WHERE d.created_at BETWEEN :start_date AND :end_date";
    
    if ($bloodGroup) {
        $where .= " AND br.blood_type = :blood_group";
        $params['blood_group'] = $bloodGroup;
    }
    
    if ($status) {
        $where .= " AND d.status = :status";
        $params['status'] = $status;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            d.id,
            d.status,
            d.created_at,
            d.completed_at,
            donor.name as donor_name,
            donor.blood_group as donor_blood_type,
            donor.city as donor_city,
            br.request_code,
            br.blood_type as requested_blood_type,
            br.hospital_name,
            br.urgency
        FROM donations d
        JOIN users donor ON d.donor_id = donor.id
        JOIN blood_requests br ON d.request_id = br.id
        $where
        ORDER BY d.created_at DESC
    ");
    $stmt->execute($params);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Summary
    $completed = count(array_filter($donations, fn($d) => $d['status'] === 'completed'));
    $cancelled = count(array_filter($donations, fn($d) => $d['status'] === 'cancelled'));
    $inProgress = count(array_filter($donations, fn($d) => in_array($d['status'], ['accepted', 'on_the_way', 'reached'])));
    
    return [
        'success' => true,
        'summary' => [
            'total_donations' => count($donations),
            'completed' => $completed,
            'cancelled' => $cancelled,
            'in_progress' => $inProgress,
            'success_rate' => count($donations) > 0 ? round(($completed / count($donations)) * 100, 1) : 0
        ],
        'data' => $donations
    ];
}

/**
 * Get monthly summary report
 */
function getMonthlySummary($conn, $startDate, $endDate) {
    // Current month stats
    $currentMonth = date('Y-m');
    $lastMonth = date('Y-m', strtotime('-1 month'));
    
    // Donations this month
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donations WHERE DATE_FORMAT(created_at, '%Y-%m') = :month");
    $stmt->execute(['month' => $currentMonth]);
    $donationsThisMonth = (int)$stmt->fetch()['count'];
    
    $stmt->execute(['month' => $lastMonth]);
    $donationsLastMonth = (int)$stmt->fetch()['count'];
    
    // Requests this month
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blood_requests WHERE DATE_FORMAT(created_at, '%Y-%m') = :month");
    $stmt->execute(['month' => $currentMonth]);
    $requestsThisMonth = (int)$stmt->fetch()['count'];
    
    $stmt->execute(['month' => $lastMonth]);
    $requestsLastMonth = (int)$stmt->fetch()['count'];
    
    // New donors this month
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'donor' AND DATE_FORMAT(created_at, '%Y-%m') = :month");
    $stmt->execute(['month' => $currentMonth]);
    $newDonorsThisMonth = (int)$stmt->fetch()['count'];
    
    // Completed donations this month
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM donations WHERE status = 'completed' AND DATE_FORMAT(completed_at, '%Y-%m') = :month");
    $stmt->execute(['month' => $currentMonth]);
    $completedThisMonth = (int)$stmt->fetch()['count'];
    
    // Blood type demand
    $stmt = $conn->prepare("
        SELECT blood_type, COUNT(*) as count 
        FROM blood_requests 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = :month
        GROUP BY blood_type
        ORDER BY count DESC
    ");
    $stmt->execute(['month' => $currentMonth]);
    $bloodTypeDemand = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top hospitals by requests
    $stmt = $conn->prepare("
        SELECT u.name, COUNT(br.id) as request_count
        FROM blood_requests br
        JOIN users u ON br.requester_id = u.id AND br.requester_type = 'hospital'
        WHERE DATE_FORMAT(br.created_at, '%Y-%m') = :month
        GROUP BY u.id
        ORDER BY request_count DESC
        LIMIT 5
    ");
    $stmt->execute(['month' => $currentMonth]);
    $topHospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'month' => date('F Y'),
        'summary' => [
            'donations_this_month' => $donationsThisMonth,
            'donations_change' => $donationsLastMonth > 0 ? round((($donationsThisMonth - $donationsLastMonth) / $donationsLastMonth) * 100, 1) : 0,
            'requests_this_month' => $requestsThisMonth,
            'requests_change' => $requestsLastMonth > 0 ? round((($requestsThisMonth - $requestsLastMonth) / $requestsLastMonth) * 100, 1) : 0,
            'new_donors' => $newDonorsThisMonth,
            'completed_donations' => $completedThisMonth,
            'lives_saved' => $completedThisMonth * 3
        ],
        'blood_type_demand' => $bloodTypeDemand,
        'top_hospitals' => $topHospitals
    ];
}

/**
 * Get custom report data
 */
function getCustomReport($conn, $reportType, $startDate, $endDate, $bloodGroup = null, $status = null) {
    switch ($reportType) {
        case 'donor':
        case 'Donor Report':
            return getDonorReport($conn, $startDate, $endDate, $bloodGroup, $status);
        case 'hospital':
        case 'Hospital Report':
            return getHospitalReport($conn, $startDate, $endDate, $status);
        case 'request':
        case 'Request History':
            return getRequestsReport($conn, $startDate, $endDate, $bloodGroup, $status);
        case 'donation':
        case 'Blood Inventory':
            return getDonationsReport($conn, $startDate, $endDate, $bloodGroup, $status);
        default:
            return getDonorReport($conn, $startDate, $endDate, $bloodGroup, $status);
    }
}

/**
 * Handle file downloads (CSV/PDF)
 */
function handleDownload($conn, $reportType, $format, $startDate, $endDate, $bloodGroup, $status) {
    // Get report data based on type
    switch ($reportType) {
        case 'donor':
            $report = getDonorReport($conn, $startDate, $endDate, $bloodGroup, $status);
            $filename = 'donor_report';
            break;
        case 'hospital':
            $report = getHospitalReport($conn, $startDate, $endDate, $status);
            $filename = 'hospital_report';
            break;
        case 'monthly':
            $report = getMonthlySummary($conn, $startDate, $endDate);
            $filename = 'monthly_summary';
            break;
        case 'request':
            $report = getRequestsReport($conn, $startDate, $endDate, $bloodGroup, $status);
            $filename = 'requests_report';
            break;
        case 'donation':
            $report = getDonationsReport($conn, $startDate, $endDate, $bloodGroup, $status);
            $filename = 'donations_report';
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid report type']);
            return;
    }
    
    $filename .= '_' . date('Y-m-d');
    
    if ($format === 'csv') {
        generateCSV($report, $filename, $reportType);
    } else {
        // For PDF, return JSON data and let frontend handle rendering
        // Since we're not using external PHP PDF libraries
        echo json_encode([
            'success' => true,
            'format' => 'pdf_data',
            'filename' => $filename . '.pdf',
            'report' => $report,
            'generated_at' => date('Y-m-d H:i:s'),
            'date_range' => ['start' => $startDate, 'end' => $endDate]
        ]);
    }
}

/**
 * Generate CSV file
 */
function generateCSV($report, $filename, $reportType) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if ($reportType === 'monthly') {
        // Monthly summary format
        fputcsv($output, ['Monthly Summary Report']);
        fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['Metric', 'Value']);
        
        foreach ($report['summary'] as $key => $value) {
            fputcsv($output, [ucwords(str_replace('_', ' ', $key)), $value]);
        }
        
        fputcsv($output, []);
        fputcsv($output, ['Blood Type Demand']);
        fputcsv($output, ['Blood Type', 'Count']);
        foreach ($report['blood_type_demand'] as $bt) {
            fputcsv($output, [$bt['blood_type'], $bt['count']]);
        }
        
        fputcsv($output, []);
        fputcsv($output, ['Top Hospitals']);
        fputcsv($output, ['Hospital', 'Requests']);
        foreach ($report['top_hospitals'] as $h) {
            fputcsv($output, [$h['name'], $h['request_count']]);
        }
    } else {
        // Standard data report
        if (!empty($report['data'])) {
            // Headers
            fputcsv($output, array_keys($report['data'][0]));
            
            // Data rows
            foreach ($report['data'] as $row) {
                fputcsv($output, $row);
            }
        }
    }
    
    fclose($output);
    exit;
}
