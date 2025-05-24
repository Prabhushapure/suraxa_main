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

// Get new users filter
$showNewUsers = isset($_GET['filter']) && $_GET['filter'] === 'new_users';

// Set number of records per page
$page_size = 20;
$offset = $page_size * ($page - 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Suraxa Admin</title>
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
            <div id="header-placeholder" data-title="Manage Users"></div>

            <div class="container-fluid content">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="mt-2">Manage Users</h1>
                            <div>
                                <a href="add-user.php" class="btn btn-primary me-2">
                                    <i class="fas fa-plus"></i> Add New User
                                </a>
                                <button type="button" class="btn btn-success" id="sendInvitesBtn" disabled onclick="sendEmailInvites()">
                                    <i class="fas fa-envelope"></i> Send Email Invites
                                </button>
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
                                               placeholder="Search by username..." 
                                               value="<?php echo htmlspecialchars($nameFilter); ?>">
                                        <button class="btn btn-primary" type="button" onclick="filterByName()">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                    <div class="form-check form-switch bg-light rounded p-2 border d-flex align-items-center" 
                                         style="--bs-bg-opacity: .5;"
                                         data-bs-toggle="tooltip" 
                                         data-bs-placement="top" 
                                         title="Users that have not received an email invite yet">
                                        <label class="form-check-label" for="showNewUsers">Show new users only</label>
                                        <div style="width: 20px;"></div>
                                        <input class="form-check-input" 
                                               style="width: 3em; height: 1.5em; margin: 0;" 
                                               type="checkbox" 
                                               role="switch"
                                               id="showNewUsers" 
                                               <?php echo $showNewUsers ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </th>
                                        <th>Username</th>
                                        <th>Login ID</th>
                                        <th>User Role</th>
                                        <th>Org/Vendor</th>
                                        <th>Region</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Prepare the SQL query with pagination and search filter
                                    $sql = "SELECT id, UserName, LoginID, Role as UserRole, UserOrg, Region 
                                           FROM user 
                                           WHERE CompanyID = ? 
                                           AND UserName LIKE ? ";
                                    
                                    // Add status condition based on filter
                                    if ($showNewUsers) {
                                        $sql .= "AND UserStatus = 1 ";
                                    } else {
                                        $sql .= "AND UserStatus <> 0 ";
                                    }
                                    
                                    $sql .= "ORDER BY UserName LIMIT ? OFFSET ?";
                                    
                                    if ($stmt = $conn->prepare($sql)) {
                                        $searchPattern = "%$nameFilter%";
                                        $stmt->bind_param("ssii", $_SESSION['companyId'], $searchPattern, $page_size, $offset);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td><input type='checkbox' class='form-check-input user-checkbox' value='" . $row['id'] . "'></td>";
                                            echo "<td>" . htmlspecialchars($row['UserName']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['LoginID']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['UserRole']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['UserOrg']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['Region']) . "</td>";
                                            echo "<td>
                                                    <a href='edit-user.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary me-2'>
                                                        <i class='fas fa-edit'></i>
                                                    </a>
                                                    <button class='btn btn-sm btn-danger' onclick='deleteUser(" . $row['id'] . ")'>
                                                        <i class='fas fa-trash'></i>
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
                                <button class="btn btn-secondary ms-2" onclick="changePage(<?php echo $page + 1; ?>)">
                                    Next <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div class="text-muted">
                                Page <?php echo $page; ?>
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

            // Function to update send invites button state
            function updateSendInvitesButton() {
                const hasCheckedUsers = $('.user-checkbox:checked').length > 0;
                $('#sendInvitesBtn').prop('disabled', !hasCheckedUsers);
            }

            // Handle "Select All" checkbox
            $('#selectAll').change(function() {
                $('.user-checkbox').prop('checked', $(this).is(':checked'));
                updateSendInvitesButton();
            });

            // Handle individual checkboxes using event delegation
            $(document).on('change', '.user-checkbox', function() {
                const totalCheckboxes = $('.user-checkbox').length;
                const checkedCheckboxes = $('.user-checkbox:checked').length;
                $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
                updateSendInvitesButton();
            });

            // Handle new users toggle
            $('#showNewUsers').change(function() {
                let url = new URL(window.location.href);
                
                // Reset to page 1 when changing filter
                url.searchParams.delete('page');
                
                if ($(this).is(':checked')) {
                    url.searchParams.set('filter', 'new_users');
                } else {
                    url.searchParams.delete('filter');
                }
                
                window.location.href = url.toString();
            });

            // Initial button state
            updateSendInvitesButton();
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

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                $.ajax({
                    url: 'data-utils.php',
                    method: 'POST',
                    data: {
                        action: 'deleteUser',
                        userId: userId
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert('User deleted successfully');
                                location.reload();
                            } else {
                                alert('Failed to delete user: ' + result.message);
                            }
                        } catch (e) {
                            alert('An error occurred while processing the response');
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting the user');
                    }
                });
            }
        }

        function sendEmailInvites() {
            const selectedUsers = [];
            $('.user-checkbox:checked').each(function() {
                selectedUsers.push($(this).val());
            });

            if (selectedUsers.length === 0) {
                alert('Please select at least one user to send invites.');
                return;
            }

            // Show confirmation dialog using Bootstrap modal
            const confirmationHtml = `
                <div class="modal fade" id="confirmSendModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Send Invites</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to send invite emails to ${selectedUsers.length} user${selectedUsers.length > 1 ? 's' : ''}?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="proceedWithSendingInvites()">Send</button>
                            </div>
                        </div>
                    </div>
                </div>`;

            // Remove any existing modal
            $('#confirmSendModal').remove();
            
            // Add the modal to the document
            $('body').append(confirmationHtml);
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('confirmSendModal'));
            modal.show();
        }

        function proceedWithSendingInvites() {
            const selectedUsers = [];
            $('.user-checkbox:checked').each(function() {
                selectedUsers.push($(this).val());
            });

            // Hide the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmSendModal'));
            modal.hide();

            $.ajax({
                url: 'mailing-utils.php',
                method: 'POST',
                data: {
                    action: 'sendInvites',
                    userIds: selectedUsers
                },
                dataType: 'json', // Explicitly expect JSON response
                success: function(result) {
                    if (result.success) {
                        alert('Email invites sent successfully!');
                        // Uncheck all checkboxes
                        $('.user-checkbox, #selectAll').prop('checked', false);
                        updateSendInvitesButton();
                    } else {
                        alert('Failed to send invites: ' + (result.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    let errorMessage = 'Error sending invites';
                    try {
                        const response = xhr.responseText;
                        if (response) {
                            errorMessage += '\nServer response: ' + response;
                        }
                    } catch (e) {
                        errorMessage += '\nError details: ' + error;
                    }
                    alert(errorMessage);
                }
            });
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