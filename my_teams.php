<?php
// ============================================================
// my_teams.php - My Research Posts (Feature 3)
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

// Get the logged-in student's user_id from the session
$user_id = $_SESSION['user_id'];

// ---- HANDLE CLOSE / REOPEN POST ACTION ----
// Student can close or reopen their own post from this page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $team_id = (int)$_POST['team_id'];

    if ($_POST['action'] === 'close') {
        // We only close the post if it belongs to this student.
        $stmt = $pdo->prepare(
            "UPDATE team_posts SET status = 'closed' WHERE team_id = :tid AND leader_id = :uid"
        );
        $stmt->execute([':tid' => $team_id, ':uid' => $user_id]);
        header("Location: my_teams.php?closed=1");
        exit();

    } elseif ($_POST['action'] === 'reopen') {
        // Reopen post
        $stmt = $pdo->prepare(
            "UPDATE team_posts SET status = 'open' WHERE team_id = :tid AND leader_id = :uid"
        );
        $stmt->execute([':tid' => $team_id, ':uid' => $user_id]);
        header("Location: my_teams.php?reopened=1");
        exit();
    }
}

// ---- FETCH THIS STUDENT'S POSTS ----
// We filter by leader_id = the session user_id so students only see their own posts
$stmt = $pdo->prepare(
    "SELECT team_id, title, abstract, domain, required_teammates, remaining_seats, minimum_cgpa,
            eligible_semesters, deadline, preferred_supervisor, tags, status, created_at
     FROM team_posts
     WHERE leader_id = :uid
     ORDER BY created_at DESC"
);
$stmt->execute([':uid' => $user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$closed_msg   = isset($_GET['closed']) ? "Post has been closed." : "";
$reopen_msg   = isset($_GET['reopened']) ? "Post has been reopened." : "";
$edit_msg     = isset($_GET['updated']) ? "Post updated successfully." : "";
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
            <a href="browse_teams.php" class="sidebar-link" id="nav-browse">
                <span class="sidebar-link-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
                Browse Teams
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
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h2 class="page-title" style="margin:0;">My Research Posts</h2>
            <a href="create_team.php" class="btn btn-primary">Create Post</a>
        </div>

        <!-- Status messages -->
        <?php if ($closed_msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($closed_msg); ?></div>
        <?php endif; ?>
        <?php if ($reopen_msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($reopen_msg); ?></div>
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
            <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-card-header">
                    <div>
                        <div class="post-card-domain"><?php echo htmlspecialchars($post['domain']); ?></div>
                        <h3 class="post-card-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                    </div>
                    <span class="badge badge-<?php echo $post['status']; ?>">
                        <?php echo ucfirst(htmlspecialchars($post['status'])); ?>
                    </span>
                </div>
                
                <p class="post-card-abstract">
                    <?php 
                    $abstract = htmlspecialchars($post['abstract']);
                    if (strlen($abstract) > 150) {
                        echo substr($abstract, 0, 150) . '...';
                    } else {
                        echo $abstract;
                    }
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
                    <span><strong>Teammates needed:</strong> <?php echo (int)$post['required_teammates']; ?></span>
                    <span><strong>Remaining seats:</strong> <?php echo (int)($post['remaining_seats'] ?? $post['required_teammates']); ?></span>
                    <span><strong>Min CGPA:</strong> <?php echo htmlspecialchars($post['minimum_cgpa']); ?></span>
                    <span><strong>Deadline:</strong> <?php echo date('d M Y', strtotime($post['deadline'])); ?></span>
                </div>

                <div class="post-card-actions">
                    <a href="view_team.php?id=<?php echo (int)$post['team_id']; ?>" class="btn btn-secondary btn-sm">View Details</a>
                    <a href="edit_team.php?id=<?php echo (int)$post['team_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>

                    <?php if ($post['status'] === 'open'): ?>
                        <!-- Close the post using a small inline form -->
                        <form method="POST" action="my_teams.php" class="inline-form" onsubmit="return confirm('Close this post? It will no longer appear as open.');">
                            <input type="hidden" name="action" value="close">
                            <input type="hidden" name="team_id" value="<?php echo (int)$post['team_id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Close Post</button>
                        </form>
                    <?php else: ?>
                        <!-- Reopen the post -->
                        <form method="POST" action="my_teams.php" class="inline-form" onsubmit="return confirm('Reopen this post? It will accept applicants again.');">
                            <input type="hidden" name="action" value="reopen">
                            <input type="hidden" name="team_id" value="<?php echo (int)$post['team_id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Reopen Post</button>
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
