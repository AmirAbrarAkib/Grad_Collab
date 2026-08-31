<?php
// ============================================================
// profile.php - Student Profile Page (Feature 2)
// Displays the logged-in student's profile and research preferences
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

// Get the logged-in student's user_id from the session
// We NEVER trust an ID from the URL — always use the session
$user_id = $_SESSION['user_id'];

// Fetch basic user info from the users table
$stmt = $pdo->prepare("SELECT user_id, name, email, role, created_at FROM users WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the student's research profile if it exists
$stmt2 = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = :uid");
$stmt2->execute([':uid' => $user_id]);
$profile = $stmt2->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Grad Collab</title>
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
            <a href="profile.php" class="sidebar-link active" id="nav-profile">
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
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h2 class="page-title" style="margin:0;">My Profile</h2>
            <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
        </div>

        <!-- Account Information -->
        <div class="card">
            <h3 class="section-title">Account Information</h3>
            <div class="info-list">
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['name']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Role</div>
                    <div class="info-value"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Member Since</div>
                    <div class="info-value"><?php echo date('d M Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Research Profile -->
        <div class="card">
            <h3 class="section-title">Academic &amp; Research Profile</h3>

            <?php if ($profile): ?>
                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label">CGPA</div>
                        <div class="info-value"><?php echo htmlspecialchars($profile['cgpa']); ?> / 4.00</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Current Semester</div>
                        <div class="info-value"><?php echo htmlspecialchars($profile['semester']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Availability</div>
                        <div class="info-value"><?php echo htmlspecialchars($profile['availability']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Preferred Research Domains</div>
                        <div class="info-value">
                            <?php
                            if (!empty($profile['preferred_domains'])) {
                                echo htmlspecialchars($profile['preferred_domains']);
                            } else {
                                echo '<span class="text-muted">Not set</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Skills</div>
                        <div class="info-value">
                            <?php
                            if (!empty($profile['skills'])) {
                                echo htmlspecialchars($profile['skills']);
                            } else {
                                echo '<span class="text-muted">Not set</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Preferred Supervisor</div>
                        <div class="info-value">
                            <?php
                            if (!empty($profile['preferred_supervisor'])) {
                                echo htmlspecialchars($profile['preferred_supervisor']);
                            } else {
                                echo '<span class="text-muted">Not set</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Last Updated</div>
                        <div class="info-value"><?php echo date('d M Y, h:i A', strtotime($profile['updated_at'])); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 40px 20px;">
                    <p style="margin-bottom: 12px;">You have not filled in your research profile yet.</p>
                    <a href="edit_profile.php" class="btn btn-primary">Set up your profile</a>
                </div>
            <?php endif; ?>
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
