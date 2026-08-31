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

// ---- HANDLE FAVORITE / SHORTLIST ACTION ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fav_action'])) {
    if ($_POST['fav_action'] === 'favorite') {
        $favStmt = $pdo->prepare("INSERT IGNORE INTO saved_posts (user_id, team_id) VALUES (:uid, :tid)");
        $favStmt->execute([':uid' => $user_id, ':tid' => $team_id]);
    } elseif ($_POST['fav_action'] === 'unfavorite') {
        $unfavStmt = $pdo->prepare("DELETE FROM saved_posts WHERE user_id = :uid AND team_id = :tid");
        $unfavStmt->execute([':uid' => $user_id, ':tid' => $team_id]);
    }
    header("Location: view_team.php?id=" . $team_id);
    exit();
}

// Fetch the team post along with the leader's name and saved state
$stmt = $pdo->prepare(
    "SELECT tp.*, u.name AS leader_name, u.email AS leader_email,
            (sp.save_id IS NOT NULL) AS is_saved
     FROM team_posts tp
     JOIN users u ON u.user_id = tp.leader_id
     LEFT JOIN saved_posts sp ON sp.team_id = tp.team_id AND sp.user_id = :uid
     WHERE tp.team_id = :tid"
);
$stmt->execute([':tid' => $team_id, ':uid' => $user_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

// If the post doesn't exist, redirect back
if (!$post) {
    header("Location: browse_teams.php");
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

        <!-- Navigation links -->
        <p style="margin-bottom:16px; display: flex; gap: 16px;">
            <a href="browse_teams.php">&larr; Back to Browse Opportunities</a>
            <?php if ($is_leader): ?>
                <span class="text-muted">|</span>
                <a href="my_teams.php">My Posts</a>
            <?php endif; ?>
        </p>

        <!-- Post title, status badge and favorite button -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="margin:0 0 6px 0;"><?php echo htmlspecialchars($post['title']); ?></h2>
                <div class="post-card-domain"><?php echo htmlspecialchars($post['domain']); ?></div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="badge badge-<?php echo $post['status']; ?>" style="font-size:0.95em; padding:6px 14px;">
                    <?php echo ucfirst(htmlspecialchars($post['status'])); ?>
                </span>

                <!-- Favorite / Shortlist Button -->
                <form method="POST" action="view_team.php?id=<?php echo $team_id; ?>" style="display: inline;">
                    <?php if ($post['is_saved']): ?>
                        <input type="hidden" name="fav_action" value="unfavorite">
                        <button type="submit" class="btn btn-sm" style="background: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; gap: 6px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#D97706" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            Shortlisted
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="fav_action" value="favorite">
                        <button type="submit" class="btn btn-secondary btn-sm" style="gap: 6px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            Shortlist Post
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Leader action buttons — only the leader sees these -->
        <?php if ($is_leader): ?>
        <div style="display:flex; gap:10px; margin-bottom:20px;">
            <a href="edit_team.php?id=<?php echo $team_id; ?>" class="btn btn-secondary btn-sm">Edit Post</a>
            
            <?php if ($post['status'] === 'open'): ?>
            <form method="POST" action="my_teams.php" style="display:inline;"
                  onsubmit="return confirm('Close this post? It will no longer appear as open.');">
                <input type="hidden" name="action" value="close">
                <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                <button type="submit" class="btn btn-danger btn-sm">Close Post</button>
            </form>
            <?php else: ?>
            <form method="POST" action="my_teams.php" style="display:inline;"
                  onsubmit="return confirm('Reopen this post? It will accept applicants again.');">
                <input type="hidden" name="action" value="reopen">
                <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                <button type="submit" class="btn btn-primary btn-sm">Reopen Post</button>
            </form>
            <?php endif; ?>
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
                            $tags = array_map('trim', explode(',', $post['tags']));
                            foreach ($tags as $tag) {
                                if ($tag !== '') {
                                    echo '<span class="badge badge-tag" style="margin-right:6px;">' . htmlspecialchars($tag) . '</span>';
                                }
                            }
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

        <!-- Team Requirements & Capacity -->
        <div class="info-card">
            <h3>Team Requirements &amp; Capacity</h3>
            <table class="info-table">
                <tr>
                    <th>Teammates Needed</th>
                    <td><?php echo (int)$post['required_teammates']; ?></td>
                </tr>
                <tr>
                    <th>Remaining Available Seats</th>
                    <td>
                        <strong style="color: <?php echo ($post['remaining_seats'] > 0) ? '#059669' : '#DC2626'; ?>;">
                            <?php echo (int)($post['remaining_seats'] ?? $post['required_teammates']); ?>
                        </strong>
                        <?php if (($post['remaining_seats'] ?? $post['required_teammates']) == 0): ?>
                            <span class="badge badge-closed" style="margin-left:8px;">Team Full</span>
                        <?php endif; ?>
                    </td>
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
