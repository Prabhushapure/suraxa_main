<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication check and ensure user is authenticated first
require_once 'includes/auth.php';
requireAuth();

// Include database connection
require_once 'includes/db_connect.php';

// Include reports utilities
require_once 'reports-utils.php';

$pageTitle = "User Reports";

// Get parameters from the reports.php form
$programParam = isset($_GET['p']) ? $_GET['p'] : [];
$startDate = isset($_GET['sd']) ? $_GET['sd'] : '';
$endDate = isset($_GET['ed']) ? $_GET['ed'] : '';
$nameFilter = isset($_GET['name']) ? $_GET['name'] : '';

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1

// Set number of records per page
$page_size = 20;
$offset = $page_size * ($page - 1);

// Handle program selection
$selectedPrograms = [];
$programNames = [];
$isAllPrograms = false;

if ($programParam === 'all') {
    // All programs selected
    $isAllPrograms = true;
    $sql = "SELECT ProgramID, ProgramName FROM program ORDER BY ProgramName ASC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $selectedPrograms[] = $row['ProgramID'];
            $programNames[$row['ProgramID']] = $row['ProgramName'];
        }
        $stmt->close();
    }
} else if (!empty($programParam) && is_array($programParam)) {
    // Specific programs selected
    $selectedPrograms = $programParam;
    $placeholders = str_repeat('?,', count($selectedPrograms) - 1) . '?';
    $sql = "SELECT ProgramID, ProgramName FROM program WHERE ProgramID IN ($placeholders) ORDER BY ProgramName ASC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(str_repeat('s', count($selectedPrograms)), ...$selectedPrograms);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $programNames[$row['ProgramID']] = $row['ProgramName'];
        }
        $stmt->close();
    }
}

// Generate report data with pagination
$reportData = [];
$totalRecords = 0;
if (!empty($selectedPrograms)) {
    // Get total count for pagination
    $totalRecords = getReportCount($selectedPrograms, $startDate, $endDate, $nameFilter, $conn, $isAllPrograms);
    
    // Get paginated data
    $reportData = generateUserReport($selectedPrograms, $startDate, $endDate, $nameFilter, $conn, $isAllPrograms, $page_size, $offset);
}

// Calculate total pages
$totalPages = ceil($totalRecords / $page_size);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Suraxa Admin</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    
    <style>
        .parameter-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .parameter-item {
            margin-bottom: 15px;
        }
        
        .parameter-item strong {
            color: #495057;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .parameter-item p {
            color: #212529;
            font-size: 1rem;
            margin-top: 5px;
            margin-bottom: 0;
        }
        
        .report-content {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            border: 1px solid #dee2e6;
            min-height: 400px;
        }
        
        .report-table-container {
            overflow-x: auto;
            max-width: 100%;
        }
        
        .report-table {
            min-width: 100px;
            white-space: nowrap;
        }
        
        .report-table td,
        .report-table th {
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
            max-width: 150px;
        }
        
        .report-table td:first-child,
        .report-table th:first-child {
            max-width: 100px;
        }
        
        /* Name column - reduced width */
        .report-table td:nth-child(2),
        .report-table th:nth-child(2) {
            max-width: 120px;
        }
        
        /* Email column - reduced width */
        .report-table td:nth-child(3),
        .report-table th:nth-child(3) {
            max-width: 140px;
        }
        
        /* Program Name column - increased width */
        .report-table td:nth-child(4),
        .report-table th:nth-child(4) {
            max-width: 250px;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar placeholder -->
        <div id="sidebar-placeholder"></div>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Header placeholder -->
            <div id="header-placeholder" data-title="<?php echo $pageTitle; ?>"></div>

            <div class="container-fluid content">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="mt-2"><?php echo $pageTitle; ?></h1>
                            <a href="reports.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Edit filters
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <!-- Report Parameters Display -->
                        <div class="parameter-section">
                            <h5 class="mb-4"><i class="fas fa-cog"></i> Report Parameters</h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="parameter-item">
                                        <strong>Selected Programs:</strong>
                                        <?php if ($isAllPrograms): ?>
                                            <p><i class="fas fa-check-circle text-success"></i> <strong>All Programs Selected</strong> (<?php echo count($programNames); ?> programs)</p>
                                        <?php elseif (!empty($programNames)): ?>
                                            <?php foreach ($programNames as $programId => $programName): ?>
                                                <p><i class="fas fa-check-circle text-success"></i> <?php echo htmlspecialchars($programName); ?></p>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No programs selected</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="parameter-item">
                                        <strong>Start Date:</strong>
                                        <p><?php echo $startDate === 'NA' || empty($startDate) ? '<span class="text-muted">Not specified</span>' : htmlspecialchars($startDate); ?></p>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="parameter-item">
                                        <strong>End Date:</strong>
                                        <p><?php echo $endDate === 'NA' || empty($endDate) ? '<span class="text-muted">Not specified</span>' : htmlspecialchars($endDate); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Content Area -->
                        <div class="report-content">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="mb-0"><i class="fas fa-chart-bar"></i> Generated Report</h4>
                                <?php if (!empty($selectedPrograms)): ?>
                                    <button type="button" 
                                            class="btn btn-success btn-lg" 
                                            onclick="downloadFullReport()"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="This report contains more user fields. After download, open report in Excel/Sheets"
                                            <?php echo empty($reportData) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-download"></i> Download Full Report
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($selectedPrograms)): ?>
                                <!-- Search Bar -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="input-group" style="width: 50%;">
                                            <input type="text" class="form-control" id="searchName" 
                                                   placeholder="Search by name..." 
                                                   value="<?php echo htmlspecialchars($nameFilter); ?>">
                                            <button class="btn btn-primary" type="button" onclick="filterByName()">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Report Table -->
                                <div class="table-responsive report-table-container">
                                    <table class="table table-striped table-hover report-table">
                                        <thead>
                                            <tr>
                                                <th>User ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Program Name</th>
                                                <th>Score</th>
                                                <th>Status</th>
                                                <th>Result</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($reportData)): ?>
                                                <?php foreach ($reportData as $row): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['UserID']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['UserName']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['LoginID']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['ProgramName']); ?></td>
                                                        <td>
                                                            <?php 
                                                            if ($row['ScorePercentage'] !== null) {
                                                                echo number_format($row['ScorePercentage'], 1) . '%';
                                                            } else {
                                                                echo '<span class="text-muted">-</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $status = $row['ProgramStatus'] ?? 'Unknown';
                                                            $badgeClass = '';
                                                            
                                                            switch ($status) {
                                                                case 'Complete':
                                                                    $badgeClass = 'badge bg-success';
                                                                    break;
                                                                case 'In-Progress':
                                                                    $badgeClass = 'badge bg-warning text-dark';
                                                                    break;
                                                                case 'Not Started':
                                                                    $badgeClass = 'badge bg-secondary';
                                                                    break;
                                                                default:
                                                                    $badgeClass = 'badge bg-light text-dark';
                                                            }
                                                            ?>
                                                            <span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $result = $row['ProgramResult'] ?? 'Unknown';
                                                            $resultClass = '';
                                                            
                                                            switch ($result) {
                                                                case 'Pass':
                                                                    $resultClass = 'text-success fw-bold';
                                                                    break;
                                                                case 'Fail':
                                                                    $resultClass = 'text-danger fw-bold';
                                                                    break;
                                                                case 'NA':
                                                                    $resultClass = 'text-muted';
                                                                    break;
                                                                default:
                                                                    $resultClass = 'text-muted';
                                                            }
                                                            ?>
                                                            <span class="<?php echo $resultClass; ?>"><?php echo htmlspecialchars($result); ?></span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['StartDate']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['EndDate']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted">
                                                        <i class="fas fa-info-circle"></i> No data found for the selected criteria.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($totalRecords > 0): ?>
                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <div>
                                            <button class="btn btn-secondary" onclick="changePage(<?php echo $page - 1; ?>)" 
                                                    <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </button>
                                            <button class="btn btn-secondary ms-2" onclick="changePage(<?php echo $page + 1; ?>)"
                                                    <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>
                                                Next <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                        <div class="text-muted">
                                            Showing <?php echo (($page - 1) * $page_size) + 1; ?> to <?php echo min($page * $page_size, $totalRecords); ?> of <?php echo $totalRecords; ?> records
                                            (Page <?php echo $page; ?> of <?php echo $totalPages; ?>)
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                                    <h5>No Programs Selected</h5>
                                    <p>Please select at least one program to generate a report.</p>
                                    <a href="reports.php" class="btn btn-primary">
                                        <i class="fas fa-arrow-left"></i> Back to Reports
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/components.js"></script>
    <script src="js/main.js"></script>

    <script>
        // Initialize tooltips when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        function filterByName() {
            const searchValue = document.getElementById('searchName').value.trim();
            let url = new URL(window.location.href);
            
            // Reset to page 1 when searching
            url.searchParams.delete('page');
            
            if (searchValue) {
                url.searchParams.set('name', searchValue);
            } else {
                url.searchParams.delete('name');
            }
            
            window.location.href = url.toString();
        }

        function changePage(newPage) {
            if (newPage < 1) return;
            
            let url = new URL(window.location.href);
            url.searchParams.set('page', newPage);
            window.location.href = url.toString();
        }

        // Handle enter key in search box
        document.getElementById('searchName')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterByName();
            }
        });

        function downloadFullReport() {
            // Check if button is disabled
            const btn = event.target.closest('button');
            if (btn.disabled) {
                return;
            }
            
            // Show loading state
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            btn.disabled = true;

            // Get current URL parameters (excluding name filter)
            let url = new URL(window.location.href);
            const params = new URLSearchParams();
            
            // Keep program and date filters, but remove name filter
            if (url.searchParams.get('p')) {
                params.set('p', url.searchParams.get('p'));
            }
            if (url.searchParams.get('sd')) {
                params.set('sd', url.searchParams.get('sd'));
            }
            if (url.searchParams.get('ed')) {
                params.set('ed', url.searchParams.get('ed'));
            }
            params.set('download_csv', '1');

            // Create download URL
            const downloadUrl = 'reports-utils.php?' + params.toString();

            // Create temporary link and trigger download
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = 'user_report.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Restore button state
            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }, 2000);
        }
    </script>
</body>
</html> 