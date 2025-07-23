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
    <title>Create Content - Suraxa Admin</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <!-- Dropzone for file uploads -->
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar placeholder -->
        <div id="sidebar-placeholder"></div>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Header placeholder -->
            <div id="header-placeholder" data-title="Create Content"></div>

            <div class="container-fluid content">
                <h1 class="mt-2">Create Content</h1>
                
                <div class="row mt-4">
                    <!-- Hidden Content Type Selection - Default to Folder -->
                    <input type="hidden" name="contentType" id="contentType" value="folder">
                    
                    <!-- File Upload Section (Hidden) -->
                    <div class="col-md-12 mb-4" id="fileUploadSection" style="display: none;">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <span class="badge bg-primary me-2">Step 1</span>
                                    Upload File
                                </h5>
                                <div class="mt-3">
                                    <form action="upload-utils.php" class="dropzone" id="fileUploadDropzone">
                                        <div class="dz-message" data-dz-message>
                                            <span>Drop files here or click to upload</span>
                                            <span class="note">(Files will be uploaded to the library)</span>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Folder Upload Section -->
                    <div class="col-md-12 mb-4" id="folderUploadSectionNew">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <span class="badge bg-primary me-2">Step 1</span>
                                    Upload Folder
                                </h5>
                                <div class="mt-3">
                                    <div class="mb-3">
                                        <label for="folderUpload" class="form-label">Select a Folder to Upload</label>
                                        <input type="file" class="form-control" id="folderUpload" webkitdirectory directory multiple>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Details Section -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <span class="badge bg-primary me-2">Step 2</span>
                                    Content Details
                                </h5>
                                <form id="contentDetailsForm" class="mt-3">
                                    <div class="mb-3">
                                        <label for="contentName" class="form-label">Content Name</label>
                                        <input type="text" class="form-control" id="contentName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="contentDescription" class="form-label">Content Description</label>
                                        <textarea class="form-control" id="contentDescription" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="contentLabel" class="form-label">Content Label</label>
                                        <input type="text" class="form-control" id="contentLabel">
                                    </div>
                                    <div class="mb-3">
                                        <label for="contentCategory" class="form-label">Content Category</label>
                                        <select class="form-select" id="contentCategory">
                                            <option value="">Select a category</option>
                                            <option value="PDF">PDF</option>
                                            <option value="Scenario">Scenario</option>
                                            <option value="Video">Video</option>
                                            <option value="PPE">PPE</option>
                                            <option value="Quiz">Quiz</option>
                                            <option value="Step Video">Step Video</option>
                                            <option value="Picture">Picture</option>
                                            <option value="Custom">Custom</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Create Content</button>
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
    <!-- Dropzone JS -->
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <script src="js/components.js"></script>
    <script src="js/main.js"></script>
    
    <script>
        // Initialize Dropzone
        Dropzone.autoDiscover = false;
        
        $(document).ready(function() {
            // Initialize dropzone
            const myDropzone = new Dropzone("#fileUploadDropzone", {
                url: "upload-utils.php",
                paramName: "file",
                maxFilesize: 50, // MB
                acceptedFiles: ".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.gif,.mp4,.mp3,.zip",
                addRemoveLinks: true,
                dictRemoveFile: "Remove",
                init: function() {
                    this.on("success", function(file, response) {
                        console.log("File uploaded successfully", response);
                    });
                    this.on("error", function(file, errorMessage) {
                        console.error("Error uploading file", errorMessage);
                    });
                }
            });
            
            // Default to folder upload
            $('#fileUploadSection').hide();
            $('#folderUploadSectionNew').show();
            
            // Handle form submission
            $('#contentDetailsForm').submit(function(e) {
                e.preventDefault();
                
                // Get form data
                const contentType = $('#contentType').val();
                const contentName = $('#contentName').val();
                const contentDescription = $('#contentDescription').val();
                const contentLabel = $('#contentLabel').val();
                const contentCategory = $('#contentCategory').val();
                const folderFiles = $('#folderUpload').prop('files');

                let formData = new FormData();
                formData.append('action', 'createContent');
                formData.append('contentType', contentType);
                formData.append('name', contentName);
                formData.append('description', contentDescription);
                formData.append('label', contentLabel);
                formData.append('category', contentCategory);

                if (folderFiles.length > 0) {
                    // Append all files from the selected folder
                    for (let i = 0; i < folderFiles.length; i++) {
                        formData.append('files[]', folderFiles[i]);
                        formData.append('files_webkitRelativePath[]', folderFiles[i].webkitRelativePath);
                    }
                }

                // Submit data to server
                $.ajax({
                    url: 'data-utils.php',
                    type: 'POST',
                    data: formData,
                    processData: false, // Prevent jQuery from automatically transforming the data into a query string
                    contentType: false, // Tell jQuery not to set contentType
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert('Content created successfully!');
                                // Reset form
                                $('#contentDetailsForm')[0].reset();
                                $('#contentCategory').val(''); // Reset dropdown
                                if (contentType === 'file') {
                                    myDropzone.removeAllFiles();
                                }
                            } else {
                                alert('Error: ' + result.message);
                            }
                        } catch (e) {
                            alert('Error processing response');
                            console.error(e);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    }
                });
            });
            
            // Function to load folders for dropdown
            function loadFolders() {
                $.ajax({
                    url: 'data-utils.php',
                    type: 'POST',
                    data: {
                        action: 'getFolders'
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                const folderSelect = $('#selectedFolder');
                                folderSelect.empty();
                                
                                // Add default option
                                folderSelect.append('<option value="">Select a folder</option>');
                                
                                // Add folders to dropdown
                                result.folders.forEach(function(folder) {
                                    folderSelect.append(`<option value="${folder.id}">${folder.name}</option>`);
                                });
                            } else {
                                console.error('Error loading folders:', result.message);
                                $('#selectedFolder').html('<option value="">Error loading folders</option>');
                            }
                        } catch (e) {
                            console.error('Error processing folder response:', e);
                            $('#selectedFolder').html('<option value="">Error loading folders</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error loading folders:', error);
                        $('#selectedFolder').html('<option value="">Error loading folders</option>');
                    }
                });
            }
        });
    </script>
</body>
</html>