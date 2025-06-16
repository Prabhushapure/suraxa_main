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
    <title>Bulk Upload New Users - Suraxa Admin</title>
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
            <div id="header-placeholder" data-title="Bulk Upload New Users"></div>

            <div class="container-fluid content">
                <h1 class="mt-2">Bulk Upload New Users</h1>
                
                <div class="row mt-4">
                    <!-- Step 1: Download Template -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <span class="badge bg-danger me-2">Step 1</span>
                                    Download Sample File
                                </h5>
                                <p class="card-text">Download this sample file to get started with bulk user upload.</p>
                                <form method="post" action="upload-utils.php">
                                    <button type="submit" name="downloadSample" class="btn btn-primary">
                                        <i class="fas fa-download me-2"></i>Download Sample Data File
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Fill Details -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <span class="badge bg-danger me-2">Step 2</span>
                                    Fill User Details
                                </h5>
                                <p class="card-text">
                                    <ol type="1">
                                        <li>Open the file in excel or google sheets.</li>
                                        <li>First row is the headings and the second row is a sample user data row. </li>
                                        <li>Now remove the sample user row, and add in the new users and their details in each row. Each row should contain one user's details. </li>
                                        <li>For fields like Gender (M/F), PlayerAccess(1/0), Please use the value in the brackets (use the sample data row as example)</li>
                                        <li>Once completed, save the file, and Export it as .csv file. (File > Download/Export > Comma-seperated values .csv)</li>
                                    </ol>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Upload File -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <span class="badge bg-danger me-2">Step 3</span>
                                    Upload the Saved .csv File
                                </h5>
                                <p class="card-text">Click on "Choose file" below, select the .csv file that has all new users, and click on "Upload New Users".</p>

                                <form id="uploadForm" method="post" action="upload-utils.php" enctype="multipart/form-data">
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv">
                                        <button class="btn btn-primary" type="submit" name="abc">
                                            <i class="fas fa-upload me-2"></i>Upload New Users
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Output Card - Initially Hidden -->
                    <div class="col-md-12 mb-4" id="outputCard" style="display: none;">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <span class="badge bg-danger me-2">Output</span>
                                    Processing Results
                                </h5>
                                <div id="outputContent">
                                    <div class="d-flex align-items-center">
                                        <div class="spinner-border text-primary me-2" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <span>Processing your file, please wait...</span>
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

    <script>
        $(document).ready(function() {
            // File upload validation and handling
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                
                const fileInput = $('#csvFile');
                if (!fileInput.val()) {
                    alert('Please select a CSV file to upload.');
                    return false;
                }

                const fileExtension = fileInput.val().split('.').pop().toLowerCase();
                if (fileExtension !== 'csv') {
                    alert('Please upload only CSV files.');
                    return false;
                }

                // Show the output card with loading state
                $('#outputCard').show();
                $('#outputContent').html(`
                    <div class="d-flex align-items-center">
                        <div class="spinner-border text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Processing your file, please wait...</span>
                    </div>
                `);

                // // Create FormData object
                const formData = new FormData(this);
                formData.append('action', 'uploadCSV');

                // Submit form via AJAX
                $.ajax({
                    url: 'upload-utils.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            let outputHtml = '';
                            
                            if (result.success) {
                                outputHtml = `
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-check-circle me-2"></i>
                                        ${result.message}
                                    </div>`;
                            } else {
                                outputHtml = `
                                    <div class="alert alert-danger mb-0">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        Error: ${result.message}
                                    </div>`;
                            }
                            
                            $('#outputContent').html(outputHtml);
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            $('#outputContent').html(`
                                <div class="alert alert-danger mb-0">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    An error occurred while processing the file.
                                </div>`
                            );
                        }
                    },
                    error: function() {
                        $('#outputContent').html(`
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                An error occurred while uploading the file.
                            </div>`
                        );
                    }
                });
            });
        });
    </script>
</body>
</html>