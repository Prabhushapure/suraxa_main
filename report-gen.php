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

$pageTitle = "User Reports";

// Get parameters from the reports.php form
$programParam = isset($_GET['p']) ? $_GET['p'] : [];
$startDate = isset($_GET['sd']) ? $_GET['sd'] : '';
$endDate = isset($_GET['ed']) ? $_GET['ed'] : '';

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
        
        .coming-soon {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            margin: 50px 0;
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
                                <i class="fas fa-arrow-left"></i> Back to Reports
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
                            <h4 class="mb-4"><i class="fas fa-chart-bar"></i> Generated Report</h4>
                            
                            <div class="coming-soon">
                                <i class="fas fa-tools fa-3x mb-3"></i>
                                <h5>Report Implementation Coming Soon</h5>
                                <p>The report generation functionality will be implemented here.</p>
                                <p class="text-muted">
                                    This page received the following parameters:
                                    <br><strong>Programs:</strong> <?php echo $isAllPrograms ? 'all (' . count($selectedPrograms) . ' programs)' : implode(', ', $selectedPrograms); ?>
                                    <br><strong>Start Date:</strong> <?php echo $startDate; ?>
                                    <br><strong>End Date:</strong> <?php echo $endDate; ?>
                                </p>
                            </div>
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
</body>
</html> 