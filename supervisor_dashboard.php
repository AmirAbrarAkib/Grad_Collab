<?php
// ============================================================
// supervisor_dashboard.php - Supervisor Dashboard
// ============================================================

session_start();

// If the user is not logged in, send them to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Make sure only supervisors can access this page
if ($_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Count how many open team posts exist across all students
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM team_posts WHERE status = 'open'");
$stmt->execute();
$open_posts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Count total students in the system
$stmt2 = $pdo->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'student'");
$stmt2->execute();
$student_count = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard - Grad Collab</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Navigation bar -->
    <div class="topnav">
        <span class="topnav-brand">Grad Collab</span>
        <a href="supervisor_dashboard.php" class="active">Dashboard</a>
        <a href="logout.php" class="nav-logout">Logout</a>
    </div>

    <div class="page-container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <p style="color:#555; margin-bottom:24px;">
            Logged in as: <strong>Supervisor</strong> &nbsp;&middot;&nbsp; <?php echo htmlspecialchars($_SESSION['email']); ?>
        </p>

        <!-- Dashboard quick-stat cards -->
        <div class="dash-grid">
            <div class="dash-card">
                <h4>Open Research Posts</h4>
                <p>There are <strong><?php echo (int)$open_posts; ?></strong> active research team post(s) from students looking for teammates.</p>
                <span class="status-badge status-open">Active</span>
            </div>

            <div class="dash-card">
                <h4>Registered Students</h4>
                <p><strong><?php echo (int)$student_count; ?></strong> student(s) are currently registered on the platform.</p>
                <span class="status-badge status-open">Live</span>
            </div>

            <div class="dash-card">
                <h4>Coming Soon</h4>
                <p>Review research requests, manage milestones, and oversee student projects.</p>
                <span class="status-badge status-closed">Pending</span>
            </div>
        </div>

    </div><!-- end page-container -->

</body>
</html>
