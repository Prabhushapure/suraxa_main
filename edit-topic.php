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

$topic_id = $_GET['id'] ?? null;
if (!$topic_id) {
    header('Location: manage-topic.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic_name = $_POST['topic_name'];
    $topic_description = $_POST['topic_description'];

    $sql = "UPDATE topics SET topic_name = ?, topic_description = ? WHERE topic_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $topic_name, $topic_description, $topic_id);

    if ($stmt->execute()) {
        $_SESSION['msg'] = 'Topic updated successfully';
        header('Location: manage-topic.php');
        exit;
    } else {
        $error = "Error updating topic: " . $conn->error;
    }
}

// Fetch topic details
$sql = "SELECT topic_name, topic_description FROM topics WHERE topic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $topic_id);
$stmt->execute();
$result = $stmt->get_result();
$topic = $result->fetch_assoc();

if (!$topic) {
    header('Location: manage-topic.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Topic - Suraxa Admin</title>
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
            <div id="header-placeholder" data-title="Edit Topic"></div>

            <div class="container-fluid content">
                <h1 class="mt-2">Edit Topic</h1>
                
                <div class="row mt-4">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Topic Details</h5>
                                <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                                <form method="POST" class="mt-3">
                                    <div class="mb-3">
                                        <label for="topic_name" class="form-label">Topic Name</label>
                                        <input type="text" class="form-control" id="topic_name" name="topic_name" value="<?php echo htmlspecialchars($topic['topic_name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="topic_description" class="form-label">Topic Description</label>
                                        <textarea class="form-control" id="topic_description" name="topic_description" rows="3"><?php echo htmlspecialchars($topic['topic_description']); ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Topic</button>
                                    <a href="manage-topic.php" class="btn btn-secondary">Cancel</a>
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
</body>
</html>