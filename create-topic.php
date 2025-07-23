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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Topic - Suraxa Admin</title>
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
            <div id="header-placeholder" data-title="Create Topic"></div>

            <div class="container-fluid content">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="mt-2">Create Topic</h1>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Topic Details Section -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <span class="badge bg-primary me-2">Step 1</span>
                                    Topic Details
                                </h5>
                                <form id="topicDetailsForm" class="mt-3">
                                    <div class="mb-3">
                                        <label for="topicName" class="form-label">Topic Name</label>
                                        <input type="text" class="form-control" id="topicName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="topicDescription" class="form-label">Topic Description</label>
                                        <textarea class="form-control" id="topicDescription" rows="3"></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Create Topic
                                        </button>
                                        <a href="manage-topic.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                    </div>
                                </form>
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
            // Handle form submission
            $('#topicDetailsForm').submit(function(e) {
                e.preventDefault();
                
                // Get form data
                const topicName = $('#topicName').val();
                const topicDescription = $('#topicDescription').val();

                // Submit data to server
                $.ajax({
                    url: 'data-utils.php',
                    type: 'POST',
                    data: {
                        action: 'createTopic',
                        name: topicName,
                        description: topicDescription
                    },
                    success: function(response) {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.success) {
                            window.location.href = 'manage-topic.php';
                        } else {
                            alert('Error creating topic: ' + result.error);
                        }
                    },
                    error: function() {
                        console.error("Invalid JSON response:", response);
                        alert('Error creating topic. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>