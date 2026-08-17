<?php
// ============================================================
// view_team.php - View Full Research Post Details (Feature 3)
// Shows all information for a single team post.
// The post ID comes from the URL but the student can only
// view their own posts (others can view if we add browse later).
// ============================================================

session_start();

// Must be logged in as a student
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Get the team_id from the URL and make sure it is a positive integer
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: my_teams.php");
    exit();
}
$team_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch the team post along with the leader's name from the users table
$stmt = $pdo->prepare(
    "SELECT tp.*, u.name AS leader_name, u.email AS leader_email
     FROM team_posts tp
     JOIN users u ON u.user_id = tp.leader_id
     WHERE tp.team_id = :tid"
);
$stmt->execute([':tid' => $team_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

// If the post doesn't exist, redirect back
if (!$post) {
    header("Location: my_teams.php");
    exit();
}

// Is the current student the leader of this post?
$is_leader = ($post['leader_id'] == $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - Grad Collab</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body class="app-layout">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
            </div>
            <div>
                <span class="sidebar-brand-name">Grad Collab</span>
                <span class="sidebar-brand-tagline">Research Together, Grow Together</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <span class="sidebar-nav-label">Main</span>
            <a href="student_dashboard.php" class="sidebar-link" id="nav-dashboard">
                <span class="sidebar-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></span>
                Dashboard
            </a>
            <a href="profile.php" class="sidebar-link" id="nav-profile">
                <span class="sidebar-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                My Profile
            </a>
            <a href="create_team.php" class="sidebar-link" id="nav-post">
                <span class="sidebar-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg></span>
                Post Research
            </a>
            <a href="my_teams.php" class="sidebar-link active" id="nav-myposts">
                <span class="sidebar-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="8" height="10" rx="2"/><rect x="14" y="3" width="8" height="10" rx="2"/><path d="M9 17H5a2 2 0 0 0-2 2v3"/><path d="M19 17h-4a2 2 0 0 0-2 2v3"/></svg></span>
                My Posts
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="sidebar-logout">
                <span class="sidebar-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                Logout
            </a>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
            <div class="topbar-spacer"></div>
            <div class="topbar-user">
                <div class="topbar-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                <span class="topbar-username"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
        </header>
    <div class="page-container">

        <!-- Back link -->
        <p style="margin-bottom:16px;">
            <a href="my_teams.php">&larr; Back to My Posts</a>
        </p>

        <!-- Post title and status badge side by side -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px;">
            <h2 style="margin:0;"><?php echo htmlspecialchars($post['title']); ?></h2>
            <span class="status-badge status-<?php echo $post['status']; ?>" style="font-size:0.95em; padding:5px 14px;">
                <?php echo ucfirst(htmlspecialchars($post['status'])); ?>
            </span>
        </div>

        <!-- Leader action buttons — only the leader sees these -->
        <?php if ($is_leader && $post['status'] === 'open'): ?>
        <div style="margin-bottom:18px;">
            <a href="edit_team.php?id=<?php echo $team_id; ?>" class="btn-primary">Edit Post</a>
            &nbsp;
            <form method="POST" action="my_teams.php" style="display:inline;"
                  onsubmit="return confirm('Close this post?');">
                <input type="hidden" name="action" value="close">
                <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                <button type="submit" class="btn-danger">Close Post</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Research Information -->
        <div class="info-card">
            <h3>Research Information</h3>
            <table class="info-table">
                <tr>
                    <th>Domain</th>
                    <td><?php echo htmlspecialchars($post['domain']); ?></td>
                </tr>
                <tr>
                    <th>Tags</th>
                    <td>
                        <?php
                        if (!empty($post['tags'])) {
                            echo htmlspecialchars($post['tags']);
                        } else {
                            echo '<span class="text-muted">None</span>';
                        }
                        ?>
                    </td>
                </tr>
            </table>

            <div style="margin-top:16px;">
                <strong>Abstract</strong>
                <p style="margin-top:6px; color:#444; line-height:1.6;">
                    <?php echo nl2br(htmlspecialchars($post['abstract'])); ?>
                </p>
            </div>
        </div>

        <!-- Team Requirements -->
        <div class="info-card">
            <h3>Team Requirements</h3>
            <table class="info-table">
                <tr>
                    <th>Teammates Needed</th>
                    <td><?php echo (int)$post['required_teammates']; ?></td>
                </tr>
                <tr>
                    <th>Required Skills</th>
                    <td>
                        <?php
                        if (!empty($post['required_skills'])) {
                            echo htmlspecialchars($post['required_skills']);
                        } else {
                            echo '<span class="text-muted">None specified</span>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Eligibility -->
        <div class="info-card">
            <h3>Eligibility Requirements</h3>
            <table class="info-table">
                <tr>
                    <th>Minimum CGPA</th>
                    <td><?php echo htmlspecialchars($post['minimum_cgpa']); ?> / 4.00</td>
                </tr>
                <tr>
                    <th>Eligible Semesters</th>
                    <td>
                        <?php
                        if (!empty($post['eligible_semesters'])) {
                            echo htmlspecialchars($post['eligible_semesters']);
                        } else {
                            echo '<span class="text-muted">Any semester</span>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Supervision & Deadline -->
        <div class="info-card">
            <h3>Supervision &amp; Deadline</h3>
            <table class="info-table">
                <tr>
                    <th>Preferred Supervisor</th>
                    <td>
                        <?php
                        if (!empty($post['preferred_supervisor'])) {
                            echo htmlspecialchars($post['preferred_supervisor']);
                        } else {
                            echo '<span class="text-muted">No preference</span>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Deadline</th>
                    <td><?php echo date('d M Y', strtotime($post['deadline'])); ?></td>
                </tr>
            </table>
        </div>

        <!-- Team Leader -->
        <div class="info-card">
            <h3>Team Leader</h3>
            <table class="info-table">
                <tr>
                    <th>Name</th>
                    <td><?php echo htmlspecialchars($post['leader_name']); ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?php echo htmlspecialchars($post['leader_email']); ?></td>
                </tr>
                <tr>
                    <th>Posted On</th>
                    <td><?php echo date('d M Y, h:i A', strtotime($post['created_at'])); ?></td>
                </tr>
            </table>
        </div>

    </div><!-- end page-container -->
    </div><!-- end main-wrapper -->

    <script>
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) { toggle.addEventListener('click', () => sidebar.classList.toggle('sidebar-open')); }
    </script>
</body>
</html>
