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

// Fetch all programs for the dropdown
$programs = [];
$sql = "SELECT ProgramID, ProgramName FROM program ORDER BY ProgramName ASC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
    $stmt->close();
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
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .filter-item {
            margin-bottom: 20px;
        }
        
        .filter-item label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .dropdown-checkboxes {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 10px;
            max-height: 200px;
            overflow-y: auto;
            background-color: white;
        }
        
        .dropdown-checkboxes .form-check {
            margin-bottom: 8px;
        }
        
        .dropdown-checkboxes .form-check:last-child {
            margin-bottom: 0;
        }
        
        .report-description {
            background-color: #e7f3ff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        
        .generate-btn {
            font-size: 1.1rem;
            padding: 12px 30px;
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
                        </div>
                    </div>
                </div>

                <!-- Report Description -->
                <div class="card">
                    <div class="card-body">
                        <div class="report-description">
                            <h5><i class="fas fa-info-circle"></i> Report Description</h5>
                            <p class="mb-0">
                                <!-- Description will be filled out later -->
                                This report provides comprehensive analytics and insights of all the users and their completion status for the selected programs, within the specified date range. 
                                <br>Use the filters below to customize your report parameters.
                            </p>
                        </div>

                        <!-- Filters Section -->
                        <div class="filter-section">
                            <h4 class="mb-4"><i class="fas fa-filter"></i> Report Filters</h4>
                            
                            <form id="reportForm">
                                <div class="row">
                                    <!-- Program Selection -->
                                    <div class="col-md-6">
                                        <div class="filter-item">
                                            <label for="programSelection">Select Programs:</label>
                                            <div class="dropdown-checkboxes" id="programSelection">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleAllPrograms()">
                                                    <label class="form-check-label" for="selectAll">
                                                        <strong>Select All</strong>
                                                    </label>
                                                </div>
                                                <hr class="my-2">
                                                <?php foreach ($programs as $program): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input program-checkbox" type="checkbox" 
                                                           value="<?php echo htmlspecialchars($program['ProgramID']); ?>" 
                                                           id="program_<?php echo htmlspecialchars($program['ProgramID']); ?>"
                                                           name="programs[]" onchange="updateSelectAll()">
                                                    <label class="form-check-label" for="program_<?php echo htmlspecialchars($program['ProgramID']); ?>">
                                                        <?php echo htmlspecialchars($program['ProgramName']); ?>
                                                    </label>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Date Filters -->
                                    <div class="col-md-6">
                                        <!-- Start Date -->
                                        <div class="filter-item">
                                            <label for="startDate">Start Date:</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="startDate" name="startDate">
                                                <div class="input-group-text d-flex align-items-center">
                                                    <input class="form-check-input me-2" type="checkbox" id="startDateNA" onchange="toggleDateInput('startDate', 'startDateNA')">
                                                    <label class="form-check-label" for="startDateNA">NA</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- End Date -->
                                        <div class="filter-item">
                                            <label for="endDate">End Date:</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="endDate" name="endDate">
                                                <div class="input-group-text d-flex align-items-center">
                                                    <input class="form-check-input me-2" type="checkbox" id="endDateNA" onchange="toggleDateInput('endDate', 'endDateNA')">
                                                    <label class="form-check-label" for="endDateNA">NA</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Generate Report Button -->
                        <div class="text-center">
                            <button type="button" class="btn btn-success generate-btn" onclick="generateReport()">
                                <i class="fas fa-chart-line"></i> Generate User Report
                            </button>
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
        function toggleAllPrograms() {
            const selectAll = document.getElementById('selectAll');
            const programCheckboxes = document.querySelectorAll('.program-checkbox');
            
            programCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        function updateSelectAll() {
            const programCheckboxes = document.querySelectorAll('.program-checkbox');
            const selectAll = document.getElementById('selectAll');
            
            const allChecked = Array.from(programCheckboxes).every(checkbox => checkbox.checked);
            const someChecked = Array.from(programCheckboxes).some(checkbox => checkbox.checked);
            
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;
        }

        function toggleDateInput(dateInputId, checkboxId) {
            const dateInput = document.getElementById(dateInputId);
            const checkbox = document.getElementById(checkboxId);
            
            if (checkbox.checked) {
                dateInput.disabled = true;
                dateInput.value = '';
            } else {
                dateInput.disabled = false;
            }
        }

        function generateReport() {
            // Get selected programs
            const selectedPrograms = [];
            const programCheckboxes = document.querySelectorAll('.program-checkbox:checked');
            const totalPrograms = document.querySelectorAll('.program-checkbox').length;
            
            programCheckboxes.forEach(checkbox => {
                selectedPrograms.push(checkbox.value);
            });

            // Get dates
            const startDate = document.getElementById('startDateNA').checked ? 'NA' : document.getElementById('startDate').value;
            const endDate = document.getElementById('endDateNA').checked ? 'NA' : document.getElementById('endDate').value;

            // Validate selections
            if (selectedPrograms.length === 0) {
                alert('Please select at least one program.');
                return;
            }

            // Build query parameters
            const params = new URLSearchParams();
            
            // Check if all programs are selected
            if (selectedPrograms.length === totalPrograms) {
                params.append('p', 'all');
            } else {
                selectedPrograms.forEach(programId => {
                    params.append('p[]', programId);
                });
            }
            
            params.append('sd', startDate);
            params.append('ed', endDate);

            // Navigate to report generation page
            window.location.href = 'report-gen.php?' + params.toString();
        }

        // Initialize page
        $(document).ready(function() {
            // Set default dates (optional)
            // document.getElementById('startDate').valueAsDate = new Date();
            // document.getElementById('endDate').valueAsDate = new Date();
        });
    </script>
</body>
</html> 