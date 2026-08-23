<?php
// ============================================================
// browse_teams.php - Advanced Search, Filtering & Browsing (Feature 4)
// Allows students to search and filter research/thesis opportunities
// using multi-criteria database queries and favorite/shortlist posts.
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

$user_id = $_SESSION['user_id'];

// ---- HANDLE FAVORITE / SHORTLIST TOGGLE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fav_action'])) {
    $target_team_id = (int)$_POST['team_id'];
    $redirect_url = $_POST['redirect_url'] ?? 'browse_teams.php';

    if ($_POST['fav_action'] === 'favorite') {
        $favStmt = $pdo->prepare("INSERT IGNORE INTO saved_posts (user_id, team_id) VALUES (:uid, :tid)");
        $favStmt->execute([':uid' => $user_id, ':tid' => $target_team_id]);
    } elseif ($_POST['fav_action'] === 'unfavorite') {
        $unfavStmt = $pdo->prepare("DELETE FROM saved_posts WHERE user_id = :uid AND team_id = :tid");
        $unfavStmt->execute([':uid' => $user_id, ':tid' => $target_team_id]);
    }

    header("Location: " . $redirect_url);
    exit();
}

// ---- FETCH DYNAMIC FILTER CHOICES FROM DATABASE ----
$domainsStmt = $pdo->query("SELECT DISTINCT domain FROM team_posts WHERE domain != '' ORDER BY domain ASC");
$available_domains = $domainsStmt->fetchAll(PDO::FETCH_COLUMN);

$supervisorsStmt = $pdo->query("SELECT DISTINCT preferred_supervisor FROM team_posts WHERE preferred_supervisor IS NOT NULL AND preferred_supervisor != '' ORDER BY preferred_supervisor ASC");
$available_supervisors = $supervisorsStmt->fetchAll(PDO::FETCH_COLUMN);

// ---- READ FILTER INPUTS FROM GET REQUEST ----
$keyword          = trim($_GET['keyword'] ?? '');
$filter_domain    = trim($_GET['domain'] ?? '');
$filter_tag       = trim($_GET['tag'] ?? '');
$filter_supervisor= trim($_GET['supervisor'] ?? '');
$filter_semester  = trim($_GET['semester'] ?? '');
$filter_cgpa      = trim($_GET['cgpa'] ?? '');
$filter_status    = trim($_GET['status'] ?? 'open'); // Default to open posts
$available_seats  = isset($_GET['available_seats']) && $_GET['available_seats'] === '1';
$saved_only       = isset($_GET['saved_only']) && $_GET['saved_only'] === '1';

// ---- BUILD SAFE PARAMETERIZED DATABASE QUERY ----
$where_clauses = ["1=1"];
$params = [];

// 1. Status Filter (default: open)
if ($filter_status === 'open') {
    $where_clauses[] = "tp.status = 'open'";
} elseif ($filter_status === 'closed') {
    $where_clauses[] = "tp.status = 'closed'";
}
// If $filter_status === 'all', no status restriction applied

// 2. Keyword Search (searches title, abstract, tags, and required_skills)
if ($keyword !== '') {
    $where_clauses[] = "(tp.title LIKE :kw OR tp.abstract LIKE :kw OR tp.tags LIKE :kw OR tp.required_skills LIKE :kw)";
    $params[':kw'] = '%' . $keyword . '%';
}

// 3. Domain Filter
if ($filter_domain !== '') {
    $where_clauses[] = "tp.domain = :domain";
    $params[':domain'] = $filter_domain;
}

// 4. Tag Filter
if ($filter_tag !== '') {
    $where_clauses[] = "tp.tags LIKE :tag";
    $params[':tag'] = '%' . $filter_tag . '%';
}

// 5. Supervisor Filter
if ($filter_supervisor !== '') {
    $where_clauses[] = "tp.preferred_supervisor LIKE :supervisor";
    $params[':supervisor'] = '%' . $filter_supervisor . '%';
}

// 6. Eligible Semester Filter
if ($filter_semester !== '' && ctype_digit($filter_semester)) {
    $where_clauses[] = "(FIND_IN_SET(:sem, REPLACE(tp.eligible_semesters, ' ', '')) > 0 OR tp.eligible_semesters IS NULL OR tp.eligible_semesters = '')";
    $params[':sem'] = (int)$filter_semester;
}

// 7. Minimum CGPA Requirement Filter (Posts where student's CGPA meets the requirement)
if ($filter_cgpa !== '' && is_numeric($filter_cgpa)) {
    $cgpa_val = (float)$filter_cgpa;
    if ($cgpa_val >= 0.00 && $cgpa_val <= 4.00) {
        $where_clauses[] = "tp.minimum_cgpa <= :cgpa";
        $params[':cgpa'] = number_format($cgpa_val, 2, '.', '');
    }
}

// 8. Available Seats Filter (Remaining seats > 0)
if ($available_seats) {
    $where_clauses[] = "tp.remaining_seats > 0";
}

// 9. Shortlisted / Saved Only Filter
if ($saved_only) {
    $where_clauses[] = "sp.save_id IS NOT NULL";
}

// Construct full SQL statement with LEFT JOIN to saved_posts for bookmark state
$sql = "SELECT tp.*, u.name AS leader_name, u.email AS leader_email,
               (sp.save_id IS NOT NULL) AS is_saved
        FROM team_posts tp
        JOIN users u ON u.user_id = tp.leader_id
        LEFT JOIN saved_posts sp ON sp.team_id = tp.team_id AND sp.user_id = :current_user_id
        WHERE " . implode(" AND ", $where_clauses) . "
        ORDER BY tp.created_at DESC";

$params[':current_user_id'] = $user_id;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$result_count = count($posts);

// Current URL for redirecting back after toggling favorite
$current_url = htmlspecialchars($_SERVER['REQUEST_URI']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Opportunities - Grad Collab</title>
    <meta name="description" content="Search and browse research and thesis team opportunities.">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body class="app-layout">

    <!-- ===== LEFT SIDEBAR ===== -->
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
                <span class="sidebar-link-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </span>
                Dashboard
            </a>

            <a href="browse_teams.php" class="sidebar-link active" id="nav-browse">
                <span class="sidebar-link-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </span>
                Browse Teams
            </a>

            <a href="profile.php" class="sidebar-link" id="nav-profile">
                <span class="sidebar-link-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </span>
                My Profile
            </a>

            <a href="create_team.php" class="sidebar-link" id="nav-post">
                <span class="sidebar-link-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                    </svg>
                </span>
                Post Research
            </a>

            <a href="my_teams.php" class="sidebar-link" id="nav-myposts">
                <span class="sidebar-link-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="8" height="10" rx="2"/>
                        <rect x="14" y="3" width="8" height="10" rx="2"/>
                        <path d="M9 17H5a2 2 0 0 0-2 2v3"/>
                        <path d="M19 17h-4a2 2 0 0 0-2 2v3"/>
                    </svg>
                </span>
                My Posts
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="sidebar-logout" id="nav-logout">
                <span class="sidebar-link-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </span>
                Logout
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-wrapper">

        <!-- Top bar -->
        <header class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <div class="topbar-spacer"></div>
            <div class="topbar-user">
                <div class="topbar-avatar">
                    <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                </div>
                <span class="topbar-username"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
        </header>

        <!-- Page content -->
        <main class="page-main">

            <div class="page-header" style="margin-bottom: 24px;">
                <h1 class="page-title" style="margin-bottom: 6px;">Browse Research &amp; Thesis Teams</h1>
                <p class="text-muted" style="margin: 0;">Explore open opportunities, filter by your requirements, and shortlist potential teams.</p>
            </div>

            <!-- SEARCH & ADVANCED FILTER PANEL -->
            <div class="card" style="margin-bottom: 28px; padding: 24px;">
                <form method="GET" action="browse_teams.php" id="filterForm">
                    
                    <!-- Keyword Search Bar -->
                    <div style="margin-bottom: 20px;">
                        <label for="keyword" style="font-weight: 600; margin-bottom: 8px; display: block;">Keyword Search</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="keyword" name="keyword" class="form-control"
                                   placeholder="Search by title, abstract, tags, or required skills..."
                                   value="<?php echo htmlspecialchars($keyword); ?>">
                            <button type="submit" class="btn btn-primary" style="flex-shrink: 0; padding: 0 24px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                Search
                            </button>
                        </div>
                    </div>

                    <!-- Multi-Filter Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 20px;">
                        
                        <!-- Domain Filter -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="domain">Domain</label>
                            <select id="domain" name="domain" class="form-control">
                                <option value="">-- All Domains --</option>
                                <?php foreach ($available_domains as $d): ?>
                                    <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($filter_domain === $d) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tag Filter -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="tag">Specific Tag</label>
                            <input type="text" id="tag" name="tag" class="form-control"
                                   placeholder="e.g. AI, MySQL, React"
                                   value="<?php echo htmlspecialchars($filter_tag); ?>">
                        </div>

                        <!-- Supervisor Filter -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="supervisor">Preferred Supervisor</label>
                            <input type="text" id="supervisor" name="supervisor" class="form-control"
                                   placeholder="e.g. Dr. Ahmed"
                                   value="<?php echo htmlspecialchars($filter_supervisor); ?>">
                        </div>

                        <!-- Semester Filter -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="semester">Eligible Semester</label>
                            <select id="semester" name="semester" class="form-control">
                                <option value="">-- Any Semester --</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($filter_semester === (string)$i) ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Minimum CGPA Filter -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="cgpa">My CGPA (Matches &le; Requirement)</label>
                            <input type="number" step="0.01" min="0" max="4.00" id="cgpa" name="cgpa" class="form-control"
                                   placeholder="e.g. 3.50"
                                   value="<?php echo htmlspecialchars($filter_cgpa); ?>">
                        </div>

                        <!-- Status Filter -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="status">Post Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="open" <?php echo ($filter_status === 'open') ? 'selected' : ''; ?>>Open Opportunities Only</option>
                                <option value="all" <?php echo ($filter_status === 'all') ? 'selected' : ''; ?>>All Posts (Open &amp; Closed)</option>
                                <option value="closed" <?php echo ($filter_status === 'closed') ? 'selected' : ''; ?>>Closed Posts Only</option>
                            </select>
                        </div>

                    </div>

                    <!-- Filter Checkboxes & Action Buttons -->
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        
                        <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center;">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; margin: 0; font-weight: 500;">
                                <input type="checkbox" name="available_seats" value="1" <?php echo $available_seats ? 'checked' : ''; ?> style="width: 16px; height: 16px; accent-color: var(--primary);">
                                Only with Available Seats (&gt; 0)
                            </label>

                            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; margin: 0; font-weight: 500; color: #D97706;">
                                <input type="checkbox" name="saved_only" value="1" <?php echo $saved_only ? 'checked' : ''; ?> style="width: 16px; height: 16px; accent-color: #D97706;">
                                &#9733; My Shortlisted Posts Only
                            </label>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                            <a href="browse_teams.php" class="btn btn-secondary btn-sm">Reset All</a>
                        </div>
                    </div>

                </form>
            </div>

            <!-- RESULT COUNT & SUMMARY BAR -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 16px; font-weight: 600; margin: 0; color: var(--text-main);">
                    Found <?php echo $result_count; ?> research opportunity<?php echo ($result_count === 1) ? '' : 'ies'; ?>
                </h3>

                <?php if ($saved_only): ?>
                    <span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 13px; padding: 4px 10px;">
                        &#9733; Viewing Shortlisted Posts
                    </span>
                <?php endif; ?>
            </div>

            <!-- OPPORTUNITY FEED -->
            <?php if (empty($posts)): ?>
                <div class="empty-state" style="background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-card); padding: 48px 24px; text-align: center;">
                    <div style="width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 50%; background: var(--primary-bg); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                    <h3 style="font-size: 18px; margin-bottom: 8px;">No matching research posts found</h3>
                    <p class="text-muted" style="max-width: 480px; margin: 0 auto 20px;">
                        Try loosening your filter criteria, clearing search keywords, or selecting "All Posts" to see more results.
                    </p>
                    <a href="browse_teams.php" class="btn btn-primary btn-sm">Clear All Filters</a>
                </div>
            <?php else: ?>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card" style="position: relative;">
                            
                            <div class="post-card-header" style="padding-right: 48px;">
                                <div>
                                    <div class="post-card-domain"><?php echo htmlspecialchars($post['domain']); ?></div>
                                    <h3 class="post-card-title">
                                        <a href="view_team.php?id=<?php echo (int)$post['team_id']; ?>" style="color: var(--primary); text-decoration: none;">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h3>
                                </div>
                                <span class="badge badge-<?php echo $post['status']; ?>">
                                    <?php echo ucfirst(htmlspecialchars($post['status'])); ?>
                                </span>
                            </div>

                            <!-- Shortlist / Favorite Toggle Button -->
                            <form method="POST" action="browse_teams.php" style="position: absolute; top: 20px; right: 20px;">
                                <input type="hidden" name="team_id" value="<?php echo (int)$post['team_id']; ?>">
                                <input type="hidden" name="redirect_url" value="<?php echo $current_url; ?>">
                                
                                <?php if ($post['is_saved']): ?>
                                    <input type="hidden" name="fav_action" value="unfavorite">
                                    <button type="submit" title="Remove from shortlist" style="background: #FEF3C7; border: 1px solid #FCD34D; color: #D97706; border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition);">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#D97706" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                        </svg>
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="fav_action" value="favorite">
                                    <button type="submit" title="Shortlist / Favorite this opportunity" style="background: var(--surface); border: 1px solid var(--border-color); color: var(--text-muted); border-radius: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition);">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </form>

                            <!-- Abstract Preview -->
                            <p class="post-card-abstract">
                                <?php 
                                $abstract = htmlspecialchars($post['abstract']);
                                if (strlen($abstract) > 200) {
                                    echo substr($abstract, 0, 200) . '...';
                                } else {
                                    echo $abstract;
                                }
                                ?>
                            </p>

                            <!-- Tag Chips -->
                            <?php if (!empty($post['tags'])): ?>
                                <div style="margin-bottom: 14px;">
                                    <?php 
                                    $tags = array_map('trim', explode(',', $post['tags']));
                                    foreach ($tags as $t): 
                                        if ($t !== ''):
                                    ?>
                                        <a href="browse_teams.php?tag=<?php echo urlencode($t); ?>" class="badge badge-tag" style="text-decoration: none;">
                                            #<?php echo htmlspecialchars($t); ?>
                                        </a>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>

                            <!-- Meta Details Grid -->
                            <div class="post-card-meta">
                                <span>
                                    <strong>Available Seats:</strong> 
                                    <span style="font-weight: 600; color: <?php echo ($post['remaining_seats'] > 0) ? '#059669' : '#DC2626'; ?>;">
                                        <?php echo (int)($post['remaining_seats'] ?? $post['required_teammates']); ?> / <?php echo (int)$post['required_teammates']; ?>
                                    </span>
                                </span>
                                
                                <span><strong>Min CGPA:</strong> <?php echo htmlspecialchars($post['minimum_cgpa']); ?></span>
                                
                                <span>
                                    <strong>Eligible Semesters:</strong> 
                                    <?php echo !empty($post['eligible_semesters']) ? htmlspecialchars($post['eligible_semesters']) : 'Any'; ?>
                                </span>

                                <?php if (!empty($post['preferred_supervisor'])): ?>
                                    <span><strong>Supervisor:</strong> <?php echo htmlspecialchars($post['preferred_supervisor']); ?></span>
                                <?php endif; ?>

                                <span><strong>Deadline:</strong> <?php echo date('d M Y', strtotime($post['deadline'])); ?></span>
                            </div>

                            <!-- Footer actions & Leader info -->
                            <div class="post-card-actions" style="justify-content: space-between;">
                                <div style="font-size: 13px; color: var(--text-muted);">
                                    Posted by <strong><?php echo htmlspecialchars($post['leader_name']); ?></strong> on <?php echo date('d M Y', strtotime($post['created_at'])); ?>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="view_team.php?id=<?php echo (int)$post['team_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                    <?php if ($post['leader_id'] == $user_id): ?>
                                        <a href="edit_team.php?id=<?php echo (int)$post['team_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

            <footer class="page-footer" style="margin-top: 40px;">
                <span>&copy; 2024 Grad Collab. All rights reserved.</span>
                <span>Built for students, by students. &#x1F49C;</span>
            </footer>

        </main>
    </div>

    <script>
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('sidebar-open');
            });
        }
    </script>
</body>
</html>
