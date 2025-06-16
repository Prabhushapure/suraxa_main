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

// Include dashboard utilities
require_once 'dashboard-utils.php';

// Get program ID from URL parameter
$programID = isset($_GET['programID']) ? $_GET['programID'] : '';

if (empty($programID)) {
    // Redirect back to programs list if no program ID provided
    header('Location: programs.php');
    exit;
}

// Fetch program details
$programDetails = null;
$sql = "SELECT ProgramID, ProgramName, CreatedDate, ProgramAudiance, 
               ProgramLearningMode, ProgramDuration, ProgramDescription, ProgramLearningOutcome
        FROM program 
        WHERE ProgramID = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $programID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $programDetails = $result->fetch_assoc();
    } else {
        // Program not found, show alert and redirect back to programs list
        echo '<script>
            alert("Invalid Program ID. The program you are looking for does not exist.");
            window.location.href = "programs.php";
        </script>';
        exit;
    }
    $stmt->close();
} else {
    // Database error, show alert and redirect back to programs list
    echo '<script>
        alert("Database error occurred. Please try again later.");
        window.location.href = "programs.php";
    </script>';
    exit;
}

$pageTitle = htmlspecialchars($programDetails['ProgramName']) . " - Dashboard";

// Get program statistics
$programStats = getProgramStats($programID, $conn);
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
    
    <!-- Google Charts -->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
        google.charts.load('current', {'packages': ['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            var data = google.visualization.arrayToDataTable([
                ['Status', 'Count'],
                ['Pass', <?php echo $programStats['pass']; ?>],
                ['Fail', <?php echo $programStats['fail']; ?>],
                ['In-Progress', <?php echo $programStats['inProgress']; ?>],
                ['Not Started', <?php echo $programStats['notStarted']; ?>]
            ]);

            var options = {
                title: 'Program Status Distribution',
                width: 700,
                height: 500,
                is3D: true,
                colors: ['#28a745', '#dc3545', '#ffc107', '#6c757d'],
                backgroundColor: 'transparent',
                titleTextStyle: {
                    fontSize: 22,
                    color: '#495057'
                },
                legend: {
                    position: 'right',
                    textStyle: {
                        fontSize: 14
                    }
                }
            };

            var chart = new google.visualization.PieChart(document.getElementById('piechart'));
            chart.draw(data, options);
        }
    </script>
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
                            <a href="programs.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Programs
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <!-- Program Details - Left Column (4/12 width) -->
                            <div class="col-md-4">
                                <h4 class="mb-4">Program Details</h4>
                                
                                <div class="program-details">
                                    <div class="detail-item mb-3">
                                        <strong>Program ID:</strong>
                                        <p class="mb-0"><?php echo htmlspecialchars($programDetails['ProgramID']); ?></p>
                                    </div>
                                                                        
                                    <div class="detail-item mb-3">
                                        <strong>Program Description:</strong>
                                        <p class="mb-0"><?php echo htmlspecialchars($programDetails['ProgramDescription'] ?? 'No description available'); ?></p>
                                    </div>
                                    
                                    <div class="detail-item mb-3">
                                        <strong>Program Audience:</strong>
                                        <p class="mb-0"><?php echo htmlspecialchars($programDetails['ProgramAudiance'] ?? 'Not specified'); ?></p>
                                    </div>
                                    
                                    <div class="detail-item mb-3">
                                        <strong>Learning Outcome:</strong>
                                        <p class="mb-0"><?php echo htmlspecialchars($programDetails['ProgramLearningOutcome'] ?? 'Not specified'); ?></p>
                                    </div>
                                    
                                    <div class="detail-item mb-3">
                                        <strong>Created Date:</strong>
                                        <p class="mb-0"><?php echo date('F j, Y', strtotime($programDetails['CreatedDate'])); ?></p>
                                    </div>                                    
                                </div>
                            </div>
                            
                            <!-- Chart Area - Right Column (8/12 width) -->
                            <div class="col-md-8">
                                <div class="chart-container">
                                    <h4 class="mb-4">Program Analytics</h4>
                                    
                                    <!-- Number of users invited -->
                                    <div class="alert alert-info mb-4">
                                        <i class="fas fa-users"></i>
                                        <strong>Number of users invited:</strong> <?php echo $programStats['invited']; ?>
                                    </div>
                                    
                                    <!-- Statistics Summary -->
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body text-center">
                                                    <h5 style="color: white; font-weight: bold; font-size: 1.8rem;"><?php echo $programStats['pass']; ?></h5>
                                                    <small style="color: white; font-weight: bold; font-size: 0.9rem;">Passed</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-danger text-white">
                                                <div class="card-body text-center">
                                                    <h5 style="color: white; font-weight: bold; font-size: 1.8rem;"><?php echo $programStats['fail']; ?></h5>
                                                    <small style="color: white; font-weight: bold; font-size: 0.9rem;">Failed</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-warning text-white">
                                                <div class="card-body text-center">
                                                    <h5 style="color: white; font-weight: bold; font-size: 1.8rem;"><?php echo $programStats['inProgress']; ?></h5>
                                                    <small style="color: white; font-weight: bold; font-size: 0.9rem;">In Progress</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-secondary text-white">
                                                <div class="card-body text-center">
                                                    <h5 style="color: white; font-weight: bold; font-size: 1.8rem;"><?php echo $programStats['notStarted']; ?></h5>
                                                    <small style="color: white; font-weight: bold; font-size: 0.9rem;">Not Started</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 3D Pie Chart -->
                                    <div class="d-flex justify-content-center">
                                        <div id="piechart"></div>
                                    </div>
                                </div>
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

    <style>
        .program-details .detail-item {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .program-details .detail-item strong {
            color: #495057;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .program-details .detail-item p {
            color: #212529;
            font-size: 1rem;
            margin-top: 5px;
        }
        
        .chart-container {
            height: 100%;
        }
    </style>
</body>
</html> 