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

// Get name filter if set
$nameFilter = isset($_GET['name']) ? $_GET['name'] : '';

// Set number of records per page
$page_size = 20;
$offset = $page_size * ($page - 1);

// Get total count of all topics
$totalContent = 0;
$countSql = "SELECT COUNT(*) as total FROM topic";
if ($countResult = $conn->query($countSql)) {
    $countRow = $countResult->fetch_assoc();
    $totalContent = $countRow['total'];
    $countResult->close();
}

// Handle topic deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $deleteSql = "DELETE FROM topics WHERE topic_id = ?";
    if ($deleteStmt = $conn->prepare($deleteSql)) {
        $deleteStmt->bind_param("s", $delete_id);
        if ($deleteStmt->execute()) {
            $_SESSION['msg'] = 'Topic deleted successfully.';
        } else {
            $_SESSION['msg'] = 'Error deleting topic.';
        }
        $deleteStmt->close();
        header('Location: manage-topic.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Topics - Suraxa Admin</title>
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
            <div id="header-placeholder" data-title="Manage Topics"></div>

            <div class="container-fluid content">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="mt-2">Manage Topics</h1>
                            <div>
                                <a href="create-topic.php" class="btn btn-primary me-2">
                                    <i class="fas fa-plus"></i> Create New Topic
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <!-- Search Bar -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="input-group" style="width: 50%;">
                                        <input type="text" class="form-control" id="searchName" 
                                               placeholder="Search by topic name..." 
                                               value="<?php echo htmlspecialchars($nameFilter); ?>">
                                        <button class="btn btn-primary" type="button" onclick="filterByName()">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-light rounded p-2 border d-flex align-items-center" 
                                             style="--bs-bg-opacity: .5;">
                                            <span class="fw-bold">Total topics - <?php echo $totalContent; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Get count of filtered topics for pagination calculation
                        $filteredCountSql = "SELECT COUNT(*) as total FROM topic ";
                        
                        // Add name filter if provided
                        if (!empty($nameFilter)) {
                            $filteredCountSql .= "WHERE topic_name LIKE ? ";
                        }
                        
                        $filteredContent = 0;
                        if ($filteredCountStmt = $conn->prepare($filteredCountSql)) {
                            if (!empty($nameFilter)) {
                                $searchPattern = "%$nameFilter%";
                                $filteredCountStmt->bind_param("s", $searchPattern);
                            } 
                            $filteredCountStmt->execute();
                            $filteredCountResult = $filteredCountStmt->get_result();
                            $filteredCountRow = $filteredCountResult->fetch_assoc();
                            $filteredContent = $filteredCountRow['total'];
                            $filteredCountStmt->close();
                        }
                        
                        // Calculate total pages based on filtered results
                        $totalPages = ceil($filteredContent / $page_size);
                        ?>

                        <!-- Content Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Topic ID</th>
                                        <th>Topic Name</th>
                                        <th>Topic Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Prepare the SQL query with pagination and search filter
                                    $sql = "SELECT TopicID, TopicName, TopicDescription FROM topic ";
                                    
                                    // Add name filter if provided
                                    if (!empty($nameFilter)) {
                                        $sql .= "WHERE topic_name LIKE ? ";
                                    }
                                    
                                    $sql .= "ORDER BY TopicID DESC LIMIT ? OFFSET ?";

                                    
                                    if ($stmt = $conn->prepare($sql)) {
                                        if (!empty($nameFilter)) {
                                            $searchPattern = "%$nameFilter%";
                                            $stmt->bind_param("sii", $searchPattern, $page_size, $offset);
                                        } else {
                                            $stmt->bind_param("ii", $page_size, $offset);
                                        }
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        $sno = $offset + 1;
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $sno++ . "</td>";
                                            echo "<td>" . htmlspecialchars($row['TopicID']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['TopicName']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['TopicDescription']) . "</td>";
                                            echo "<td>
                                                    <a href='edit-topic.php?id=" . $row['TopicID'] . "' class='btn btn-sm btn-primary' data-bs-toggle='tooltip' data-bs-placement='top' title='Edit topic'>
                                                        <i class='fa fa-pencil'></i>
                                                    </a>
                                                    <button class='btn btn-sm btn-danger' onclick='deleteTopic(\"" . $row['TopicID'] . "\")' data-bs-toggle='tooltip' data-bs-placement='top' title='Delete topic'>
                                                        <i class='fa fa-trash'></i>
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

        function deleteTopic(topicId) {
            if (confirm('Are you sure you want to delete this topic?')) {
                window.location.href = 'manage-topic.php?delete_id=' + topicId;
            }
        }

        // Handle enter key in search box
        document.getElementById('searchName').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterByName();
            }
        });
    </script>
</body>
</html>