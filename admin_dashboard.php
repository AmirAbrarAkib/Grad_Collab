<?php

session_start();

// If the user is not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Only admin role can access this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Total users by role
$stmt = $pdo->prepare("SELECT role, COUNT(*) AS total FROM users GROUP BY role");
$stmt->execute();
$role_counts = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $role_counts[$row['role']] = $row['total'];
}
$student_count    = $role_counts['student']    ?? 0;
$supervisor_count = $role_counts['supervisor'] ?? 0;

// Total team posts
$stmt2 = $pdo->prepare("SELECT COUNT(*) AS total FROM team_posts");
$stmt2->execute();
$post_count = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Grad Collab</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Navigation bar -->
    <div class="topnav">
        <span class="topnav-brand">Grad Collab</span>
        <a href="admin_dashboard.php" class="active">Dashboard</a>
        <a href="logout.php" class="nav-logout">Logout</a>
    </div>

    <div class="page-container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <p style="color:#555; margin-bottom:24px;">
            Logged in as: <strong>Admin</strong> &nbsp;&middot;&nbsp; <?php echo htmlspecialchars($_SESSION['email']); ?>
        </p>

        <!-- System overview cards -->
        <div class="dash-grid">
            <div class="dash-card">
                <h4>Students</h4>
                <p><strong><?php echo (int)$student_count; ?></strong> registered student(s) on the platform.</p>
                <span class="status-badge status-open">Registered</span>
            </div>

            <div class="dash-card">
                <h4>Supervisors</h4>
                <p><strong><?php echo (int)$supervisor_count; ?></strong> supervisor(s) registered on the platform.</p>
                <span class="status-badge status-open">Registered</span>
            </div>

            <div class="dash-card">
                <h4>Research Posts</h4>
                <p><strong><?php echo (int)$post_count; ?></strong> total team post(s) created by students.</p>
                <span class="status-badge status-open">Total</span>
            </div>
        </div>

        <div class="info-card" style="margin-top:24px; border-left:4px solid #2c3e50;">
            <p style="margin:0;">
                <strong>Admin features coming soon</strong> — user management, role assignment, system settings, and more.
            </p>
        </div>

    </div><!-- end page-container -->

</body>
</html>
