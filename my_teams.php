<?php
// ============================================================
// my_teams.php - My Research Posts (Feature 3 & 4)
// Shows all research/thesis posts created by the logged-in student
// ============================================================

session_start();

// Only logged-in students may access this page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';
require_once 'match_helper.php';

ensure_applications_table($pdo);

// Get the logged-in student's user_id from the session
$user_id = (int)$_SESSION['user_id'];

// ---- HANDLE CLOSE POST ACTION ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'close') {

    $team_id = (int)$_POST['team_id'];

    // Verify leader_id = session user_id in the WHERE clause
    $stmt = $pdo->prepare(
        "UPDATE team_posts SET status = 'closed' WHERE team_id = :tid AND leader_id = :uid"
    );
    $stmt->execute([':tid' => $team_id, ':uid' => $user_id]);

    header("Location: my_teams.php?closed=1");
    exit();
}

// ---- FETCH THIS STUDENT'S POSTS ----
$stmt = $pdo->prepare(
    "SELECT team_id, title, abstract, domain, required_teammates, minimum_cgpa,
            eligible_semesters, deadline, preferred_supervisor, tags, status, created_at
     FROM team_posts
     WHERE leader_id = :uid
     ORDER BY created_at DESC"
);
$stmt->execute([':uid' => $user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch application counts for this student's posts
$appCountStmt = $pdo->prepare("
    SELECT ta.team_id,
           SUM(CASE WHEN ta.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
           SUM(CASE WHEN ta.status = 'accepted' THEN 1 ELSE 0 END) AS accepted_count,
           COUNT(*) AS total_count
    FROM team_applications ta
    JOIN team_posts tp ON tp.team_id = ta.team_id
    WHERE tp.leader_id = :uid
    GROUP BY ta.team_id
");
$appCountStmt->execute([':uid' => $user_id]);
$app_counts = [];
foreach ($appCountStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $app_counts[$row['team_id']] = $row;
}

$closed_msg = isset($_GET['closed']) ? "Post has been closed." : "";
$edit_msg   = isset($_GET['updated']) ? "Post updated successfully." : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Research Posts - Grad Collab</title>
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
            <a href="browse_teams.php" class="sidebar-link" id="nav-explore">
                <span class="sidebar-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
                Explore Teams
            </a>
            <a href="my_applications.php" class="sidebar-link" id="nav-applications">
                <span class="sidebar-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></span>
                My Applications
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
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 class="page-title" style="margin:0;">My Research Posts</h2>
                <p style="color:#6B7280; font-size:14px; margin-top:4px;">Manage your opportunities and review join requests from prospective teammates.</p>
            </div>
            <a href="create_team.php" class="btn btn-primary">Create Post</a>
        </div>

        <!-- Status messages -->
        <?php if ($closed_msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($closed_msg); ?></div>
        <?php endif; ?>
        <?php if ($edit_msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($edit_msg); ?></div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            
            <!-- Empty state -->
            <div class="empty-state">
                <h3>No research posts yet</h3>
                <p>You haven't created any research opportunities.</p>
                <a href="create_team.php" class="btn btn-primary">Create Research Post</a>
            </div>

        <?php else: ?>

            <!-- Post Cards -->
            <?php foreach ($posts as $post): 
                $tid = $post['team_id'];
                $pending_count = (int)($app_counts[$tid]['pending_count'] ?? 0);
                $accepted_count = (int)($app_counts[$tid]['accepted_count'] ?? 0);
            ?>
            <div class="post-card">
                <div class="post-card-header">
                    <div>
                        <div class="post-card-domain"><?php echo htmlspecialchars($post['domain']); ?></div>
                        <h3 class="post-card-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <?php if ($pending_count > 0): ?>
                            <span class="badge badge-pending" style="font-size:12px; font-weight:600;">
                                <?php echo $pending_count; ?> Pending Request<?php echo $pending_count > 1 ? 's' : ''; ?>
                            </span>
                        <?php endif; ?>
                        <span class="badge badge-<?php echo $post['status']; ?>">
                            <?php echo ucfirst(htmlspecialchars($post['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <p class="post-card-abstract">
                    <?php 
                    $abstract = htmlspecialchars($post['abstract']);
                    echo (strlen($abstract) > 150) ? substr($abstract, 0, 150) . '...' : $abstract;
                    ?>
                </p>

                <?php if (!empty($post['tags'])): ?>
                <div style="margin-bottom: 12px;">
                    <?php 
                    $tags = array_map('trim', explode(',', $post['tags']));
                    foreach ($tags as $tag): 
                        if ($tag !== ''):
                    ?>
                        <span class="badge badge-tag"><?php echo htmlspecialchars($tag); ?></span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                <?php endif; ?>

                <div class="post-card-meta">
                    <span><strong>Teammates:</strong> <?php echo $accepted_count; ?> / <?php echo (int)$post['required_teammates']; ?> accepted</span>
                    <span><strong>Min CGPA:</strong> <?php echo number_format((float)$post['minimum_cgpa'], 2); ?></span>
                    <span><strong>Deadline:</strong> <?php echo date('d M Y', strtotime($post['deadline'])); ?></span>
                </div>

                <div class="post-card-actions">
                    <a href="view_team.php?id=<?php echo (int)$post['team_id']; ?>" class="btn btn-primary btn-sm">
                        View Details &amp; Applicants <?php if ($pending_count > 0): ?>(<?php echo $pending_count; ?>)<?php endif; ?>
                    </a>

                    <?php if ($post['status'] === 'open'): ?>
                        <a href="edit_team.php?id=<?php echo (int)$post['team_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                        
                        <!-- Close the post using an inline form -->
                        <form method="POST" action="my_teams.php" class="inline-form" onsubmit="return confirm('Close this post? It will no longer accept new join requests.');">
                            <input type="hidden" name="action" value="close">
                            <input type="hidden" name="team_id" value="<?php echo (int)$post['team_id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Close Post</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div><!-- end page-container -->
    </div><!-- end main-wrapper -->

    <script>
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) { toggle.addEventListener('click', () => sidebar.classList.toggle('sidebar-open')); }
    </script>
</body>
</html>
