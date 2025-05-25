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
    <title>Add User - Suraxa Admin</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <!-- Select2 for better multi-select -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar placeholder -->
        <div id="sidebar-placeholder"></div>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Header placeholder -->
            <div id="header-placeholder" data-title="Add User"></div>

            <div class="container-fluid content">
                <h1 class="mt-2">Add New User</h1>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <form id="addUserForm" method="POST" class="needs-validation" novalidate>
                            <!-- Company Information -->
                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="companyId" class="form-label fw-bold">Company Name</label>
                                    <select class="form-select" id="companyId" name="companyId" disabled required>
                                        <?php
                                        $sql = "SELECT CompanyID, CompanyName FROM companysite WHERE CompanyID = ?";
                                        if ($stmt = $conn->prepare($sql)) {
                                            $stmt->bind_param("s", $_SESSION['companyId']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($row['CompanyID']) . "'>" . 
                                                     htmlspecialchars($row['CompanyName']) . " (" . htmlspecialchars($row['CompanyID']) . ")" .
                                                     "</option>";
                                            }
                                            $stmt->close();
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- User Information -->
                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="username" class="form-label fw-bold">User Full Name *</label>
                                    <input type="text" class="form-control" id="username" name="username" required
                                           data-bs-toggle="tooltip" data-bs-placement="right" 
                                           title="Please enter a username">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="loginId" class="form-label fw-bold">Login ID (Email) *</label>
                                    <input type="email" class="form-control" id="loginId" name="loginId" required
                                           data-bs-toggle="tooltip" data-bs-placement="right" 
                                           title="Please enter a valid email address">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-bold">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="6" required
                                           data-bs-toggle="tooltip" data-bs-placement="right" 
                                           title="Password must be at least 6 characters long">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Gender *</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male" checked required>
                                            <label class="form-check-label" for="genderMale">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female" required>
                                            <label class="form-check-label" for="genderFemale">Female</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="role" class="form-label fw-bold">Role</label>
                                    <input type="text" class="form-control" id="role" name="role"
                                           data-bs-toggle="tooltip" data-bs-placement="right" 
                                           title="Please enter a role">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="userAccess" class="form-label d-block fw-bold">User Access *</label>
                                    <select class="form-select" id="userAccess" name="userAccess[]" multiple required
                                           data-bs-toggle="tooltip" data-bs-placement="right" 
                                           title="Please select at least one access level">
                                        <option value="Admin">Admin</option>
                                        <option value="Creator">Creator</option>
                                        <option value="Player">Player</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Organization Information -->
                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="userOrg" class="form-label fw-bold">User Organization</label>
                                    <div class="input-group">
                                        <select class="form-select" id="userOrg" name="userOrg">
                                            <option value="">Select Organization</option>
                                            <?php
                                            $sql = "SELECT Organization FROM user_organization ORDER BY Organization ASC";
                                            if ($stmt = $conn->prepare($sql)) {
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                if ($result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='" . htmlspecialchars($row['Organization']) . "'>" . 
                                                             htmlspecialchars($row['Organization']) . "</option>";
                                                    }
                                                    // If we have results, make the field required
                                                    echo "<script>document.getElementById('userOrg').required = true;</script>";
                                                    echo "<script>document.querySelector('label[for=\"userOrg\"]').innerHTML += ' *';</script>";
                                                }
                                                $stmt->close();
                                            }
                                            ?>
                                        </select>
                                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#createOrgModal">
                                            Create New
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="userRegion" class="form-label fw-bold">Region</label>
                                    <div class="input-group">
                                        <select class="form-select" id="userRegion" name="userRegion">
                                            <option value="">Select Region</option>
                                            <?php
                                            $sql = "SELECT Region FROM user_region ORDER BY Region ASC";
                                            if ($stmt = $conn->prepare($sql)) {
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                if ($result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='" . htmlspecialchars($row['Region']) . "'>" . 
                                                             htmlspecialchars($row['Region']) . "</option>";
                                                    }
                                                    // If we have results, make the field required
                                                    echo "<script>document.getElementById('userRegion').required = true;</script>";
                                                    echo "<script>document.querySelector('label[for=\"userRegion\"]').innerHTML += ' *';</script>";
                                                }
                                                $stmt->close();
                                            }
                                            ?>
                                        </select>
                                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#createRegionModal">
                                            Create New
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="city" class="form-label fw-bold">City</label>
                                    <input type="text" class="form-control" id="city" name="city">
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                    Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal for creating new organization -->
    <div class="modal fade" id="createOrgModal" tabindex="-1" aria-labelledby="createOrgModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createOrgModalLabel">Create New Organization</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createOrgForm">
                        <div class="mb-3">
                            <label for="newOrgName" class="form-label">Organization Name</label>
                            <input type="text" class="form-control" id="newOrgName" required>
                            <div class="invalid-feedback">Please enter an organization name.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitNewOrg">Create</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for creating new region -->
    <div class="modal fade" id="createRegionModal" tabindex="-1" aria-labelledby="createRegionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRegionModalLabel">Create New Region</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createRegionForm">
                        <div class="mb-3">
                            <label for="newRegionName" class="form-label">Region Name</label>
                            <input type="text" class="form-control" id="newRegionName" required>
                            <div class="invalid-feedback">Please enter a region name.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitNewRegion">Create</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/components.js"></script>
    <script src="js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'manual'
                });
            });

            // Initialize Select2 for better multi-select experience
            $('#userAccess').select2({
                placeholder: "Select access levels",
                allowClear: true,
                width: '100%'
            });

            // Form validation
            const form = document.getElementById('addUserForm');
            const submitBtn = document.getElementById('submitBtn');
            const inputs = {
                username: document.getElementById('username'),
                loginId: document.getElementById('loginId'),
                password: document.getElementById('password'),
                userAccess: document.getElementById('userAccess')
            };

            // Track if fields have been touched
            const touchedFields = {
                username: false,
                loginId: false,
                password: false,
                userAccess: false
            };

            function showValidationMessage(input, isValid, fieldName) {
                if (touchedFields[fieldName]) {
                    input.classList.remove('is-valid', 'is-invalid');
                    input.classList.add(isValid ? 'is-valid' : 'is-invalid');
                    
                    // Get the tooltip instance for this input
                    const tooltip = bootstrap.Tooltip.getInstance(input);
                    if (!isValid && document.activeElement === input) {
                        tooltip.show();
                    } else {
                        tooltip.hide();
                    }
                }
            }

            function validateEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            function validateForm(showErrors = false) {
                let isValid = true;
                
                // Username validation
                const usernameValid = inputs.username.value.trim() !== '';
                showValidationMessage(inputs.username, usernameValid, 'username');
                isValid = isValid && usernameValid;

                // Email validation
                const emailValid = validateEmail(inputs.loginId.value);
                showValidationMessage(inputs.loginId, emailValid, 'loginId');
                isValid = isValid && emailValid;

                // Password validation
                const passwordValid = inputs.password.value.length >= 6;
                showValidationMessage(inputs.password, passwordValid, 'password');
                isValid = isValid && passwordValid;

                // User Access validation
                const userAccessValid = $(inputs.userAccess).val() && $(inputs.userAccess).val().length > 0;
                if (touchedFields.userAccess) {
                    if (!userAccessValid) {
                        $(inputs.userAccess).next('.select2-container').addClass('is-invalid-select2');
                        const tooltip = bootstrap.Tooltip.getInstance(inputs.userAccess);
                        tooltip.show();
                    } else {
                        $(inputs.userAccess).next('.select2-container').removeClass('is-invalid-select2');
                        const tooltip = bootstrap.Tooltip.getInstance(inputs.userAccess);
                        tooltip.hide();
                    }
                }
                isValid = isValid && userAccessValid;

                submitBtn.disabled = !isValid;
                return isValid;
            }

            // Add focus event listeners to show tooltips when field is focused
            Object.values(inputs).forEach(input => {
                input.addEventListener('focus', function() {
                    if (touchedFields[input.id] && input.classList.contains('is-invalid')) {
                        const tooltip = bootstrap.Tooltip.getInstance(input);
                        if (tooltip) tooltip.show();
                    }
                });

                input.addEventListener('blur', function() {
                    const tooltip = bootstrap.Tooltip.getInstance(input);
                    if (tooltip) tooltip.hide();
                });
            });

            // Add blur event listeners to mark fields as touched
            inputs.username.addEventListener('blur', function() {
                touchedFields.username = true;
                validateForm();
            });

            inputs.loginId.addEventListener('blur', function() {
                touchedFields.loginId = true;
                validateForm();
            });

            inputs.password.addEventListener('blur', function() {
                touchedFields.password = true;
                validateForm();
            });

            // For Select2, we need to handle the change event
            $(inputs.userAccess).on('change', function() {
                touchedFields.userAccess = true;
                validateForm();
            });

            // Add input event listeners for real-time validation after first touch
            inputs.username.addEventListener('input', function() {
                if (touchedFields.username) validateForm();
            });

            inputs.loginId.addEventListener('input', function() {
                if (touchedFields.loginId) validateForm();
            });

            inputs.password.addEventListener('input', function() {
                if (touchedFields.password) validateForm();
            });

            // Form submission handling
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                
                // Mark all fields as touched when form is submitted
                Object.keys(touchedFields).forEach(field => touchedFields[field] = true);
                
                if (!validateForm()) {
                    form.classList.add('was-validated');
                    return;
                }

                // Collect form data
                const formData = new FormData();
                formData.append('action', 'createUser');
                formData.append('username', inputs.username.value);
                formData.append('loginId', inputs.loginId.value);
                formData.append('password', inputs.password.value);
                formData.append('gender', $('input[name="gender"]:checked').val());
                formData.append('role', $('#role').val());
                formData.append('userAccess', JSON.stringify($('#userAccess').val()));
                formData.append('userOrg', $('#userOrg').val());
                formData.append('userRegion', $('#userRegion').val());
                formData.append('city', $('#city').val());

                // Disable submit button and show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';

                // Submit form via AJAX
                $.ajax({
                    url: 'data-utils.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert(`User created successfully!\nUser ID: ${result.userId}`);
                                // Reset form
                                form.reset();
                                $('#userAccess').val(null).trigger('change');
                                // Reset validation state
                                Object.keys(touchedFields).forEach(field => touchedFields[field] = false);
                                $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                            } else {
                                alert('Failed to create user: ' + result.message);
                            }
                        } catch (e) {
                            console.error('JSON Parse Error:', e);
                            console.error('Raw Response:', response);
                            alert('Error parsing response. Check browser console for details.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        alert('An error occurred while creating the user. Check browser console for details.');
                    },
                    complete: function() {
                        // Re-enable submit button and restore text
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Create User';
                    }
                });
            });

            // Initial validation without showing errors
            validateForm(false);

            // Handle new organization creation
            $('#submitNewOrg').click(function() {
                const orgName = $('#newOrgName').val().trim();
                if (!orgName) {
                    $('#newOrgName').addClass('is-invalid');
                    return;
                }

                $.ajax({
                    url: 'data-utils.php',
                    method: 'POST',
                    data: {
                        action: 'addNewOrg',
                        orgName: orgName
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Add the new option to the select
                            $('#userOrg').append(new Option(orgName, orgName, true, true));
                            
                            // Close the modal and reset form
                            $('#createOrgModal').modal('hide');
                            $('#newOrgName').val('');
                            $('#newOrgName').removeClass('is-invalid');
                            
                            // Show success message
                            alert('Organization added successfully!');
                        } else {
                            alert(response.message || 'Failed to add organization');
                        }
                    },
                    error: function() {
                        alert('An error occurred while adding the organization');
                    }
                });
            });

            // Handle new region creation
            $('#submitNewRegion').click(function() {
                const regionName = $('#newRegionName').val().trim();
                if (!regionName) {
                    $('#newRegionName').addClass('is-invalid');
                    return;
                }

                $.ajax({
                    url: 'data-utils.php',
                    method: 'POST',
                    data: {
                        action: 'addNewRegion',
                        regionName: regionName
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Add the new option to the select
                            $('#userRegion').append(new Option(regionName, regionName, true, true));
                            
                            // Close the modal and reset form
                            $('#createRegionModal').modal('hide');
                            $('#newRegionName').val('');
                            $('#newRegionName').removeClass('is-invalid');
                            
                            // Show success message
                            alert('Region added successfully!');
                        } else {
                            alert(response.message || 'Failed to add region');
                        }
                    },
                    error: function() {
                        alert('An error occurred while adding the region');
                    }
                });
            });

            // Reset validation state when modals are hidden
            $('#createOrgModal, #createRegionModal').on('hidden.bs.modal', function() {
                $(this).find('input').removeClass('is-invalid').val('');
            });

            // Handle input validation
            $('#newOrgName, #newRegionName').on('input', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>
</body>
</html> 