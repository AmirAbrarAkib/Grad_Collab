<?php
// ============================================================
// student_dashboard.php - Student Dashboard
// ============================================================

session_start();

// If the user is not logged in, send them to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Make sure only students can access this page
if ($_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Check if the student has filled in their profile yet
$stmt = $pdo->prepare("SELECT profile_id FROM student_profiles WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$has_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Count the student's research posts
$stmt2 = $pdo->prepare("SELECT COUNT(*) AS total FROM team_posts WHERE leader_id = :uid");
$stmt2->execute([':uid' => $user_id]);
$post_count = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];

// Count active (open) posts
$stmt3 = $pdo->prepare("SELECT COUNT(*) AS total FROM team_posts WHERE leader_id = :uid AND status = 'open'");
$stmt3->execute([':uid' => $user_id]);
$active_count = $stmt3->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Grad Collab</title>
    <meta name="description" content="Grad Collab student dashboard - manage your research posts and profile.">
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

            <a href="student_dashboard.php" class="sidebar-link active" id="nav-dashboard">
                <span class="sidebar-link-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </span>
                Dashboard
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
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>
        </header>

        <!-- Page content -->
        <main class="page-main">

            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! &#x1F44B;</h1>
                    <p class="welcome-sub">Here's what's happening with your research journey.</p>
                </div>
                <div class="welcome-illustration" aria-hidden="true">
                    <svg width="130" height="100" viewBox="0 0 130 100" fill="none">
                        <ellipse cx="65" cy="92" rx="48" ry="7" fill="rgba(107,70,193,0.12)"/>
                        <rect x="25" y="28" width="62" height="56" rx="6" fill="#6B46C1" opacity="0.2"/>
                        <rect x="18" y="20" width="62" height="56" rx="6" fill="#6B46C1" opacity="0.35"/>
                        <rect x="12" y="14" width="62" height="56" rx="6" fill="#6B46C1"/>
                        <rect x="20" y="24" width="28" height="3" rx="1.5" fill="white" opacity="0.8"/>
                        <rect x="20" y="31" width="46" height="2.5" rx="1.25" fill="white" opacity="0.55"/>
                        <rect x="20" y="38" width="38" height="2.5" rx="1.25" fill="white" opacity="0.55"/>
                        <rect x="20" y="45" width="42" height="2.5" rx="1.25" fill="white" opacity="0.45"/>
                        <rect x="20" y="52" width="34" height="2.5" rx="1.25" fill="white" opacity="0.4"/>
                        <circle cx="102" cy="28" r="20" fill="#10B981" opacity="0.18"/>
                        <circle cx="102" cy="28" r="14" fill="#10B981" opacity="0.4"/>
                        <path d="M95 28l5 5 9-9" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-card-icon stat-icon-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    <div class="stat-card-body">
                        <div class="stat-card-value"><?php echo (int)$post_count; ?></div>
                        <div class="stat-card-label">Research Posts</div>
                        <div class="stat-card-sub">Total posts you've created</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon stat-icon-green">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                    <div class="stat-card-body">
                        <div class="stat-card-value"><?php echo (int)$active_count; ?></div>
                        <div class="stat-card-label">Active Posts</div>
                        <div class="stat-card-sub">Currently open posts</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon stat-icon-amber">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="stat-card-body">
                        <div class="stat-card-value"><?php echo max(0, (int)$post_count - (int)$active_count); ?></div>
                        <div class="stat-card-label">Drafts</div>
                        <div class="stat-card-sub">Saved as drafts</div>
                    </div>
                </div>
            </div>

            <?php if (!$has_profile): ?>
            <!-- Profile Incomplete Alert -->
            <div class="profile-alert">
                <div class="profile-alert-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div class="profile-alert-body">
                    <strong>Your research profile is incomplete.</strong>
                    <p>Complete your profile to help others find you and to get better match suggestions.</p>
                </div>
                <a href="edit_profile.php" class="profile-alert-btn">Set up your profile now &rarr;</a>
            </div>
            <?php endif; ?>

            <!-- Quick Action Cards -->
            <div class="action-cards">

                <a href="profile.php" class="action-card" id="action-profile">
                    <div class="action-card-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="action-card-body">
                        <h3 class="action-card-title">My Profile</h3>
                        <p class="action-card-desc">View and update your academic information and research preferences.</p>
                    </div>
                    <div class="action-card-right">
                        <span class="action-card-cta">Go to Profile</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </div>
                </a>

                <a href="create_team.php" class="action-card" id="action-create">
                    <div class="action-card-icon action-icon-primary">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <line x1="9" y1="15" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <div class="action-card-body">
                        <h3 class="action-card-title">Post a Research Opportunity</h3>
                        <p class="action-card-desc">Create a new thesis or research team post and look for teammates.</p>
                    </div>
                    <div class="action-card-right">
                        <span class="action-card-cta">Create Post</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </div>
                </a>

                <a href="my_teams.php" class="action-card" id="action-myposts">
                    <div class="action-card-icon action-icon-indigo">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="8" height="10" rx="2"/>
                            <rect x="14" y="3" width="8" height="10" rx="2"/>
                            <path d="M9 17H5a2 2 0 0 0-2 2v3"/>
                            <path d="M19 17h-4a2 2 0 0 0-2 2v3"/>
                        </svg>
                    </div>
                    <div class="action-card-body">
                        <h3 class="action-card-title">My Research Posts</h3>
                        <p class="action-card-desc">You have <?php echo (int)$post_count; ?> post(s). View, edit, or close them.</p>
                    </div>
                    <div class="action-card-right">
                        <span class="action-card-cta">My Posts</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </div>
                </a>

            </div><!-- end action-cards -->

            <footer class="page-footer">
                <span>&copy; 2024 Grad Collab. All rights reserved.</span>
                <span>Built for students, by students. &#x1F49C;</span>
            </footer>

        </main>
    </div><!-- end main-wrapper -->

    <script>
        // Mobile sidebar toggle
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
