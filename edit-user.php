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

// Get user ID from URL parameter
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user data
$userData = null;
if ($userId > 0) {
    $sql = "SELECT u.*, c.CompanyName FROM user u 
            INNER JOIN companysite c ON c.CompanyID = u.CompanyID 
            WHERE u.id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        $stmt->close();
    }
}

// Redirect if user not found
if (!$userData) {
    header("Location: manage-users.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Suraxa Admin</title>
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
            <div id="header-placeholder" data-title="Edit User"></div>

            <div class="container-fluid content">
                <h1 class="mt-2">Edit User</h1>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <form id="editUserForm" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" id="userId" name="userId" value="<?php echo $userId; ?>">
                            
                            <!-- Company Information -->
                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="companyId" class="form-label fw-bold">Company Name</label>
                                    <select class="form-select" id="companyId" name="companyId" disabled required>
                                        <option value="<?php echo htmlspecialchars($userData['CompanyID']); ?>">
                                            <?php echo htmlspecialchars($userData['CompanyName']); ?> 
                                            (<?php echo htmlspecialchars($userData['CompanyID']); ?>)
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- User Information -->
                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="username" class="form-label fw-bold">User Full Name *</label>
                                    <input type="text" class="form-control" id="username" name="username" required
                                           value="<?php echo htmlspecialchars($userData['UserName']); ?>"
                                           data-bs-toggle="tooltip" data-bs-placement="right" 
                                           title="Please enter a username">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="loginId" class="form-label fw-bold">Login ID (Email) *</label>
                                    <input type="email" class="form-control" id="loginId" name="loginId" required
                                           value="<?php echo htmlspecialchars($userData['LoginID']); ?>"
                                           data-bs-toggle="tooltip" data-bs-placement="right" 
                                           title="Please enter a valid email address">
                                </div>
                            </div>

                            <!-- Password Reset Section -->
                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="resetPassword" name="resetPassword">
                                        <label class="form-check-label fw-bold" for="resetPassword">
                                            Reset User Password
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="passwordFields" style="display: none;">
                                <div class="mb-3 row">
                                    <div class="col-md-6">
                                        <label for="newPassword" class="form-label fw-bold">New Password</label>
                                        <input type="password" class="form-control" id="newPassword" name="newPassword"
                                               data-bs-toggle="tooltip" data-bs-placement="right"
                                               title="Password must be at least 6 characters long">
                                        <div class="invalid-feedback">Password must be at least 6 characters long</div>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <div class="col-md-6">
                                        <label for="confirmPassword" class="form-label fw-bold">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
                                               data-bs-toggle="tooltip" data-bs-placement="right"
                                               title="Passwords must match">
                                        <div class="invalid-feedback">Passwords do not match</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Gender *</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="genderMale" 
                                                   value="M" <?php echo ($userData['Gender'] == 'M') ? 'checked' : ''; ?> required>
                                            <label class="form-check-label" for="genderMale">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="genderFemale" 
                                                   value="F" <?php echo ($userData['Gender'] == 'F') ? 'checked' : ''; ?> required>
                                            <label class="form-check-label" for="genderFemale">Female</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="role" class="form-label fw-bold">Role</label>
                                    <input type="text" class="form-control" id="role" name="role"
                                           value="<?php echo htmlspecialchars($userData['Role']); ?>"
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
                                        <option value="Admin" <?php echo $userData['AdminAccess'] ? 'selected' : ''; ?>>Admin</option>
                                        <option value="Creator" <?php echo $userData['CreatorAccess'] ? 'selected' : ''; ?>>Creator</option>
                                        <option value="Player" <?php echo $userData['PlayerAccess'] ? 'selected' : ''; ?>>Player</option>
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
                                                while ($row = $result->fetch_assoc()) {
                                                    $selected = ($row['Organization'] === $userData['UserOrg']) ? 'selected' : '';
                                                    echo "<option value='" . htmlspecialchars($row['Organization']) . "' {$selected}>" . 
                                                         htmlspecialchars($row['Organization']) . "</option>";
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
                                                while ($row = $result->fetch_assoc()) {
                                                    $selected = ($row['Region'] === $userData['Region']) ? 'selected' : '';
                                                    echo "<option value='" . htmlspecialchars($row['Region']) . "' {$selected}>" . 
                                                         htmlspecialchars($row['Region']) . "</option>";
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
                                    <input type="text" class="form-control" id="city" name="city"
                                           value="<?php echo htmlspecialchars($userData['City']); ?>">
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    Update User
                                </button>
                                <a href="manage-users.php" class="btn btn-secondary ms-2">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmEditModal" tabindex="-1" aria-labelledby="confirmEditModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmEditModalLabel">Confirm Edit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to update this user's information?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmEditBtn">Confirm Update</button>
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

            // Password reset checkbox handling
            $('#resetPassword').change(function() {
                const isChecked = $(this).is(':checked');
                $('#passwordFields').toggle(isChecked);
                if (isChecked) {
                    $('#newPassword, #confirmPassword').prop('required', true);
                } else {
                    $('#newPassword, #confirmPassword').prop('required', false);
                    $('#newPassword, #confirmPassword').val('').removeClass('is-invalid is-valid');
                }
                validateForm();
            });

            // Password validation
            $('#newPassword, #confirmPassword').on('input', function() {
                if ($('#resetPassword').is(':checked')) {
                    validatePasswords();
                    validateForm();
                }
            });

            function validatePasswords() {
                const newPassword = $('#newPassword').val();
                const confirmPassword = $('#confirmPassword').val();
                let isValid = true;

                // Validate new password length
                if (newPassword.length < 6) {
                    $('#newPassword').removeClass('is-valid').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#newPassword').removeClass('is-invalid').addClass('is-valid');
                }

                // Validate password match
                if (confirmPassword && confirmPassword !== newPassword) {
                    $('#confirmPassword').removeClass('is-valid').addClass('is-invalid');
                    isValid = false;
                } else if (confirmPassword) {
                    $('#confirmPassword').removeClass('is-invalid').addClass('is-valid');
                }

                return isValid;
            }

            // Form validation
            const form = document.getElementById('editUserForm');
            const submitBtn = document.getElementById('submitBtn');
            const inputs = {
                username: document.getElementById('username'),
                loginId: document.getElementById('loginId'),
                userAccess: document.getElementById('userAccess')
            };

            // Track if fields have been touched
            const touchedFields = {
                username: true,
                loginId: true,
                userAccess: true
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

                // Add password validation to validateForm function
                if ($('#resetPassword').is(':checked')) {
                    isValid = isValid && validatePasswords();
                }

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

            // Add input event listeners for real-time validation
            inputs.username.addEventListener('input', function() {
                if (touchedFields.username) validateForm();
            });

            inputs.loginId.addEventListener('input', function() {
                if (touchedFields.loginId) validateForm();
            });

            // For Select2, we need to handle the change event
            $(inputs.userAccess).on('change', function() {
                touchedFields.userAccess = true;
                validateForm();
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

                // Show confirmation modal
                $('#confirmEditModal').modal('show');
            });

            // Handle confirmation modal submit
            $('#confirmEditBtn').click(function() {
                // Collect form data
                const formData = new FormData();
                formData.append('action', 'updateUser');
                formData.append('userId', $('#userId').val());
                formData.append('username', inputs.username.value);
                formData.append('loginId', inputs.loginId.value);
                formData.append('gender', $('input[name="gender"]:checked').val());
                formData.append('role', $('#role').val());
                formData.append('userAccess', JSON.stringify($('#userAccess').val()));
                formData.append('userOrg', $('#userOrg').val());
                formData.append('userRegion', $('#userRegion').val());
                formData.append('city', $('#city').val());

                // Add password data if reset is checked
                if ($('#resetPassword').is(':checked')) {
                    formData.append('resetPassword', true);
                    formData.append('newPassword', $('#newPassword').val());
                }

                // Disable submit button and show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';

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
                                alert('User updated successfully!');
                                window.location.href = 'manage-users.php';
                            } else {
                                alert('Failed to update user: ' + result.message);
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
                        alert('An error occurred while updating the user. Check browser console for details.');
                    },
                    complete: function() {
                        // Hide confirmation modal
                        $('#confirmEditModal').modal('hide');
                        
                        // Re-enable submit button and restore text
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Update User';
                    }
                });
            });

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

            // Initial validation without showing errors
            validateForm(false);
        });
    </script>
</body>
</html> 