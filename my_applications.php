<?php
// ============================================================
// my_applications.php - My Submitted Join Requests
// ============================================================

session_start();

// Only logged-in students can access this page
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

// Ensure table exists
ensure_applications_table($pdo);

$user_id = (int)$_SESSION['user_id'];

// Fetch all applications submitted by this student
$stmt = $pdo->prepare("
    SELECT ta.*, tp.title AS post_title, tp.domain AS post_domain, tp.status AS post_status, tp.deadline,
           u.name AS leader_name, u.email AS leader_email
    FROM team_applications ta
    JOIN team_posts tp ON tp.team_id = ta.team_id
    JOIN users u ON u.user_id = tp.leader_id
    WHERE ta.student_id = :sid
    ORDER BY ta.applied_at DESC
");
$stmt->execute([':sid' => $user_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Grad Collab</title>
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
            <a href="my_applications.php" class="sidebar-link active" id="nav-applications">
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
            <a href="my_teams.php" class="sidebar-link" id="nav-myposts">
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
                <h2 class="page-title" style="margin:0;">My Applications</h2>
                <p style="color:#6B7280; margin-top:4px; font-size:14px;">Track the status of your join requests to research and thesis teams.</p>
            </div>
            <a href="browse_teams.php" class="btn btn-primary">Find More Teams</a>
        </div>

        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:14px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                <h3>No Applications Submitted Yet</h3>
                <p>You have not applied to any research opportunities yet. Explore open teams and find matching projects!</p>
                <a href="browse_teams.php" class="btn btn-primary" style="margin-top:8px;">Explore Research Teams</a>
            </div>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($applications as $app): ?>
                <div class="post-card">
                    <div class="post-card-header">
                        <div>
                            <span class="post-card-domain"><?php echo htmlspecialchars($app['post_domain']); ?></span>
                            <h3 class="post-card-title" style="margin-top:4px;">
                                <a href="view_team.php?id=<?php echo (int)$app['team_id']; ?>" style="color:inherit; text-decoration:none;">
                                    <?php echo htmlspecialchars($app['post_title']); ?>
                                </a>
                            </h3>
                        </div>
                        <span class="badge badge-<?php echo $app['status']; ?>" style="font-size:13px; padding:5px 14px;">
                            <?php echo ucfirst($app['status']); ?>
                        </span>
                    </div>

                    <!-- Statement sent -->
                    <div style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px; padding:12px; margin-bottom:14px;">
                        <strong style="font-size:12px; color:#6B7280; text-transform:uppercase; display:block; margin-bottom:4px;">Your Statement:</strong>
                        <p style="margin:0; font-size:13px; color:#374151; line-height:1.5;">
                            <?php echo nl2br(htmlspecialchars($app['message'])); ?>
                        </p>
                    </div>

                    <div class="post-card-meta">
                        <span><strong>Leader:</strong> <?php echo htmlspecialchars($app['leader_name']); ?></span>
                        <span><strong>Leader Email:</strong> <a href="mailto:<?php echo htmlspecialchars($app['leader_email']); ?>"><?php echo htmlspecialchars($app['leader_email']); ?></a></span>
                        <span><strong>Applied:</strong> <?php echo date('d M Y, h:i A', strtotime($app['applied_at'])); ?></span>
                        <span><strong>Match Score:</strong> <?php echo (int)$app['match_score']; ?>%</span>
                    </div>

                    <div class="post-card-actions" style="margin-top:14px;">
                        <a href="view_team.php?id=<?php echo (int)$app['team_id']; ?>" class="btn btn-secondary btn-sm">
                            View Research Post &amp; Updates &rarr;
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
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
