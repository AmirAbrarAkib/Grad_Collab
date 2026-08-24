<?php
// ============================================================
// browse_teams.php - Explore Open Research Opportunities
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

// Fetch current student profile for match calculation
$pStmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = :uid");
$pStmt->execute([':uid' => $user_id]);
$my_profile = $pStmt->fetch(PDO::FETCH_ASSOC);

// Search and filter parameters
$search = trim($_GET['q'] ?? '');
$filter_domain = trim($_GET['domain'] ?? '');

// Build query
$sql = "
    SELECT tp.*, u.name AS leader_name, u.email AS leader_email
    FROM team_posts tp
    JOIN users u ON u.user_id = tp.leader_id
    WHERE tp.status = 'open'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (tp.title LIKE :search OR tp.abstract LIKE :search OR tp.domain LIKE :search OR tp.tags LIKE :search OR tp.required_skills LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($filter_domain)) {
    $sql .= " AND tp.domain = :domain";
    $params[':domain'] = $filter_domain;
}

$sql .= " ORDER BY tp.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique domains for filter dropdown
$dStmt = $pdo->query("SELECT DISTINCT domain FROM team_posts WHERE status = 'open' ORDER BY domain ASC");
$all_domains = $dStmt->fetchAll(PDO::FETCH_COLUMN);

// Also fetch posts the student has applied to, so we can display status indicators
$appStmt = $pdo->prepare("SELECT team_id, status FROM team_applications WHERE student_id = :sid");
$appStmt->execute([':sid' => $user_id]);
$my_applied_posts = [];
foreach ($appStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $my_applied_posts[$row['team_id']] = $row['status'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Research Opportunities - Grad Collab</title>
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
            <a href="browse_teams.php" class="sidebar-link active" id="nav-explore">
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

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 class="page-title" style="margin:0;">Explore Research Opportunities</h2>
                <p style="color:#6B7280; margin-top:4px; font-size:14px;">
                    Find research and thesis projects that match your academic profile, domain interests, and technical skills.
                </p>
            </div>
            <a href="create_team.php" class="btn btn-primary">Post Research Opportunity</a>
        </div>

        <?php if (!$my_profile): ?>
        <div class="profile-alert" style="margin-bottom:24px;">
            <div class="profile-alert-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="profile-alert-body">
                <strong>Set up your profile to see accurate match scores!</strong>
                <p>Complete your CGPA, semester, skills, and domains to get personalized compatibility recommendations.</p>
            </div>
            <a href="edit_profile.php" class="profile-alert-btn">Set Up Profile &rarr;</a>
        </div>
        <?php endif; ?>

        <!-- Search & Filter Bar -->
        <div class="card" style="margin-bottom:24px; padding:16px 20px;">
            <form method="GET" action="browse_teams.php" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                <div style="flex:2; min-width:220px;">
                    <input type="text" name="q" class="form-control" placeholder="Search by title, domain, skills, tags..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div style="flex:1; min-width:180px;">
                    <select name="domain" class="form-control">
                        <option value="">-- All Domains --</option>
                        <?php foreach ($all_domains as $dom): ?>
                            <option value="<?php echo htmlspecialchars($dom); ?>" <?php echo ($filter_domain === $dom) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dom); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="padding:10px 18px;">Search</button>
                <?php if (!empty($search) || !empty($filter_domain)): ?>
                    <a href="browse_teams.php" class="btn btn-secondary" style="padding:10px 16px;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Research Posts List -->
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <h3>No research opportunities found</h3>
                <p>Try clearing your search filters or create a new research opportunity of your own.</p>
                <a href="create_team.php" class="btn btn-primary">Post Research Opportunity</a>
            </div>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($posts as $post): 
                    $is_my_post = ((int)$post['leader_id'] === $user_id);
                    $match_data = calculate_match_score($my_profile, $post);
                    $applied_status = $my_applied_posts[$post['team_id']] ?? null;
                ?>
                <div class="post-card">
                    <div class="post-card-header">
                        <div>
                            <span class="post-card-domain"><?php echo htmlspecialchars($post['domain']); ?></span>
                            <h3 class="post-card-title" style="margin-top:4px;">
                                <a href="view_team.php?id=<?php echo (int)$post['team_id']; ?>" style="color:inherit; text-decoration:none;">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                        </div>

                        <!-- Match Score Badge -->
                        <?php if (!$is_my_post): ?>
                            <div class="match-badge-pill badge-<?php echo $match_data['badge_class']; ?>" title="<?php echo $match_data['level']; ?>">
                                <span class="match-score-val"><?php echo $match_data['score']; ?>%</span>
                                <span class="match-score-txt">Match</span>
                            </div>
                        <?php else: ?>
                            <span class="badge badge-tag" style="background:#EDE9FE; color:#6B46C1; font-weight:600;">Your Post</span>
                        <?php endif; ?>
                    </div>

                    <p class="post-card-abstract">
                        <?php
                        $abstract = htmlspecialchars($post['abstract']);
                        echo (strlen($abstract) > 160) ? substr($abstract, 0, 160) . '...' : $abstract;
                        ?>
                    </p>

                    <!-- Tags & Required Skills -->
                    <div style="margin-bottom:12px; display:flex; flex-wrap:wrap; gap:4px;">
                        <?php if (!empty($post['required_skills'])): 
                            $skills = array_map('trim', explode(',', $post['required_skills']));
                            foreach (array_slice($skills, 0, 3) as $sk): if ($sk !== ''): ?>
                                <span class="badge badge-tag" style="background:#EEF2FF; color:#4F46E5; font-size:11px;">Skill: <?php echo htmlspecialchars($sk); ?></span>
                            <?php endif; endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($post['tags'])): 
                            $tags = array_map('trim', explode(',', $post['tags']));
                            foreach (array_slice($tags, 0, 2) as $tg): if ($tg !== ''): ?>
                                <span class="badge badge-tag" style="font-size:11px;">#<?php echo htmlspecialchars($tg); ?></span>
                            <?php endif; endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="post-card-meta">
                        <span><strong>Leader:</strong> <?php echo htmlspecialchars($post['leader_name']); ?></span>
                        <span><strong>Need:</strong> <?php echo (int)$post['required_teammates']; ?> student(s)</span>
                        <span><strong>Min CGPA:</strong> <?php echo number_format((float)$post['minimum_cgpa'], 2); ?></span>
                        <span><strong>Deadline:</strong> <?php echo date('d M Y', strtotime($post['deadline'])); ?></span>
                    </div>

                    <div class="post-card-actions" style="margin-top:14px; display:flex; justify-content:space-between; align-items:center;">
                        <a href="view_team.php?id=<?php echo (int)$post['team_id']; ?>" class="btn btn-primary btn-sm">
                            View Details &amp; Apply
                        </a>

                        <?php if ($applied_status): ?>
                            <span class="badge badge-<?php echo $applied_status; ?>" style="font-size:12px;">
                                Applied: <?php echo ucfirst($applied_status); ?>
                            </span>
                        <?php endif; ?>
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
