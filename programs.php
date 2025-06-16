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

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1

// Set number of records per page
$page_size = 20;
$offset = $page_size * ($page - 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programs List - Suraxa Admin</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar placeholder -->
        <div id="sidebar-placeholder"></div>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Header placeholder -->
            <div id="header-placeholder" data-title="Programs List"></div>

            <div class="container-fluid content">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="mt-2">Programs List</h1>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php
                        // Get total count of programs for pagination calculation
                        $totalPrograms = 0;
                        $countSql = "SELECT COUNT(*) as total FROM program";
                        if ($countStmt = $conn->prepare($countSql)) {
                            $countStmt->execute();
                            $countResult = $countStmt->get_result();
                            $countRow = $countResult->fetch_assoc();
                            $totalPrograms = $countRow['total'];
                            $countStmt->close();
                        }
                        
                        // Calculate total pages
                        $totalPages = ceil($totalPrograms / $page_size);
                        ?>

                        <!-- Programs Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Program Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Prepare the SQL query with pagination
                                    $sql = "SELECT ProgramID, ProgramName, CreatedDate 
                                           FROM program 
                                           ORDER BY CreatedDate DESC LIMIT ? OFFSET ?";
                                    
                                    if ($stmt = $conn->prepare($sql)) {
                                        $stmt->bind_param("ii", $page_size, $offset);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['ProgramName']) . "</td>";
                                            echo "<td>
                                                    <button class='btn btn-sm btn-primary' onclick='viewDashboard(\"" . $row['ProgramID'] . "\")' data-bs-toggle='tooltip' data-bs-placement='top' title='View program dashboard'>
                                                        <i class='fas fa-chart-bar'></i> View Dashboard
                                                    </button>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                        $stmt->close();
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
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
                                Page <?php echo $page; ?> of <?php echo $totalPages; ?>
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

    <script>
        $(document).ready(function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        function changePage(newPage) {
            if (newPage < 1) return;
            
            let url = new URL(window.location.href);
            url.searchParams.set('page', newPage);
            window.location.href = url.toString();
        }

        function viewDashboard(programId) {
            // For now, this function does nothing as requested
            // You can later implement navigation to dashboard
            console.log('View dashboard for program ID:', programId);
            // Example: window.location.href = 'program-dashboard.php?id=' + programId;
        }
    </script>
</body>
</html> 