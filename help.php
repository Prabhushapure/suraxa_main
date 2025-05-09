<?php
// Include authentication check
require_once 'includes/auth.php';
// Ensure user is authenticated
requireAuth();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help - Suraxa Admin</title>
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
            <div id="header-placeholder" data-title="Help Center"></div>

            <div class="container-fluid content">
                <h1 class="mt-2">Help Center</h1>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <div style="max-width: 800px; margin: 0 auto;">
                            <video width="100%" controls autoplay style="box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 6px;">
                                <source src="assets/videos/SuraxaHelp.mp4" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-5">
                    <div class="card-header">
                        <h5>Need More Help? Contact Support,</h5>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>Email: <a href="mailto:support@suraxa.com">support@suraxa.com</a></li>
                            <li>Phone: +1 (555) 123-4567</li>
                        </ul>
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