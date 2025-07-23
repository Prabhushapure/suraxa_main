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

// Get total count of all content for this company
$totalContent = 0;
$countSql = "SELECT COUNT(*) as total FROM content_metadata WHERE CompanyID = ? AND Status = 1";
if ($countStmt = $conn->prepare($countSql)) {
    $countStmt->bind_param("s", $_SESSION['companyId']);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult->fetch_assoc();
    $totalContent = $countRow['total'];
    $countStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Content - Suraxa Admin</title>
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
            <div id="header-placeholder" data-title="Manage Content"></div>

            <div class="container-fluid content">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="mt-2">Manage Content</h1>
                            <div>
                                <a href="create-content.php" class="btn btn-primary me-2">
                                    <i class="fas fa-plus"></i> Create New Content
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
                                               placeholder="Search by content name..." 
                                               value="<?php echo htmlspecialchars($nameFilter); ?>">
                                        <button class="btn btn-primary" type="button" onclick="filterByName()">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-light rounded p-2 border d-flex align-items-center" 
                                             style="--bs-bg-opacity: .5;">
                                            <span class="fw-bold">Total content - <?php echo $totalContent; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Get count of filtered content for pagination calculation
                        $filteredCountSql = "SELECT COUNT(*) as total 
                                           FROM content_metadata 
                                           WHERE CompanyID = ? 
                                           AND Status = 1 ";
                        
                        // Add name filter if provided
                        if (!empty($nameFilter)) {
                            $filteredCountSql .= "AND Title LIKE ? ";
                        }
                        
                        $filteredContent = 0;
                        if ($filteredCountStmt = $conn->prepare($filteredCountSql)) {
                            if (!empty($nameFilter)) {
                                $searchPattern = "%$nameFilter%";
                                $filteredCountStmt->bind_param("ss", $_SESSION['companyId'], $searchPattern);
                            } else {
                                $filteredCountStmt->bind_param("s", $_SESSION['companyId']);
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
                                        <th>Content ID</th>
                                        <th>Content Name</th>
                                        <th>Content Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Prepare the SQL query with pagination and search filter
                                    $sql = "SELECT cm.id, cm.ContentID, cm.Title, 
                                           CASE 
                                               WHEN cf.id IS NOT NULL THEN 'File' 
                                               WHEN cfo.id IS NOT NULL THEN 'Folder' 
                                               ELSE 'Unknown' 
                                           END as ContentType 
                                           FROM content_metadata cm 
                                           LEFT JOIN content_files cf ON cm.id = cf.metadata_id 
                                           LEFT JOIN content_folders cfo ON cm.id = cfo.metadata_id 
                                           WHERE cm.CompanyID = ? 
                                           AND cm.Status = 1 ";
                                    
                                    // Add name filter if provided
                                    if (!empty($nameFilter)) {
                                        $sql .= "AND cm.Title LIKE ? ";
                                    }
                                    
                                    $sql .= "ORDER BY cm.CreatedAt DESC LIMIT ? OFFSET ?";
                                    
                                    if ($stmt = $conn->prepare($sql)) {
                                        if (!empty($nameFilter)) {
                                            $searchPattern = "%$nameFilter%";
                                            $stmt->bind_param("ssii", $_SESSION['companyId'], $searchPattern, $page_size, $offset);
                                        } else {
                                            $stmt->bind_param("sii", $_SESSION['companyId'], $page_size, $offset);
                                        }
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        $sno = $offset + 1;
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $sno++ . "</td>";
                                            echo "<td>" . htmlspecialchars($row['ContentID']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['Title']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['ContentType']) . "</td>";
                                            echo "<td>
                                                    <a href='edit-content.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary' data-bs-toggle='tooltip' data-bs-placement='top' title='Edit content'>
                                                        <i class='fa fa-pencil'></i>
                                                    </a>
                                                    <button class='btn btn-sm btn-danger' onclick='deleteContent(" . $row['id'] . ")' data-bs-toggle='tooltip' data-bs-placement='top' title='Delete content'>
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

        function deleteContent(contentId) {
            if (confirm('Are you sure you want to delete this content?')) {
                $.ajax({
                    url: 'data-utils.php',
                    method: 'POST',
                    data: {
                        action: 'deleteContent',
                        contentId: contentId
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert('Content deleted successfully');
                                location.reload();
                            } else {
                                alert('Failed to delete content: ' + result.message);
                            }
                        } catch (e) {
                            alert('An error occurred while processing the response');
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting the content');
                    }
                });
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