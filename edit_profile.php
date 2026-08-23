<?php


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

// Always use the user_id from the session, never from the URL
$user_id = $_SESSION['user_id'];

$error   = "";
$success = "";

// ---- HANDLE FORM SUBMISSION ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve and clean form values
    $cgpa                = trim($_POST['cgpa']);
    $semester            = trim($_POST['semester']);
    $availability        = trim($_POST['availability']);
    $preferred_domains   = trim($_POST['preferred_domains']);
    $skills              = trim($_POST['skills']);
    $preferred_supervisor = trim($_POST['preferred_supervisor']);

    // ---- VALIDATION ----

    // CGPA must be a number between 0.00 and 4.00
    if ($cgpa === '' || !is_numeric($cgpa) || $cgpa < 0 || $cgpa > 4.00) {
        $error = "Please enter a valid CGPA between 0.00 and 4.00.";

    // Semester must be a whole number between 1 and 12
    } elseif ($semester === '' || !ctype_digit($semester) || (int)$semester < 1 || (int)$semester > 12) {
        $error = "Please enter a valid semester (1 to 12).";

    // Availability must be one of the allowed values
    } elseif (!in_array($availability, ['Available', 'Partially Available', 'Not Available'])) {
        $error = "Please select a valid availability option.";

    } else {

        // Round CGPA to 2 decimal places for clean storage
        $cgpa = number_format((float)$cgpa, 2, '.', '');

        // Check if a profile row already exists for this student
        $check = $pdo->prepare("SELECT profile_id FROM student_profiles WHERE user_id = :uid");
        $check->execute([':uid' => $user_id]);
        $exists = $check->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            // --- UPDATE existing profile row ---
            $stmt = $pdo->prepare(
                "UPDATE student_profiles
                 SET cgpa               = :cgpa,
                     semester           = :semester,
                     availability       = :availability,
                     preferred_domains  = :preferred_domains,
                     skills             = :skills,
                     preferred_supervisor = :preferred_supervisor,
                     updated_at         = NOW()
                 WHERE user_id = :uid"
            );
        } else {
            // --- INSERT new profile row ---
            $stmt = $pdo->prepare(
                "INSERT INTO student_profiles
                     (user_id, cgpa, semester, availability, preferred_domains, skills, preferred_supervisor, updated_at)
                 VALUES
                     (:uid, :cgpa, :semester, :availability, :preferred_domains, :skills, :preferred_supervisor, NOW())"
            );
        }

        $stmt->execute([
            ':uid'                 => $user_id,
            ':cgpa'                => $cgpa,
            ':semester'            => (int)$semester,
            ':availability'        => $availability,
            ':preferred_domains'   => $preferred_domains,
            ':skills'              => $skills,
            ':preferred_supervisor' => $preferred_supervisor
        ]);

        $success = "Profile updated successfully.";
    }
}

// ---- LOAD CURRENT PROFILE DATA (to pre-fill the form) ----
$stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Also get the user's name and email to display at the top
$uStmt = $pdo->prepare("SELECT name, email FROM users WHERE user_id = :uid");
$uStmt->execute([':uid' => $user_id]);
$user = $uStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Grad Collab</title>
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
            <h2 class="page-title" style="margin:0;">Edit Profile</h2>
        </div>

        <p class="text-muted" style="margin-bottom:24px;">
            Editing profile for: <strong><?php echo htmlspecialchars($user['name']); ?></strong>
            (<?php echo htmlspecialchars($user['email']); ?>)
        </p>

        <!-- Show error or success message -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <a href="profile.php">View your profile &rarr;</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit_profile.php">

            <!-- SECTION: Academic Information -->
            <div class="form-section">
                <h3>Academic Information</h3>

                <div class="form-group">
                    <label for="cgpa">CGPA <span class="required">*</span></label>
                    <input type="number" id="cgpa" name="cgpa" class="form-control"
                           step="0.01" min="0" max="4.00"
                           value="<?php echo htmlspecialchars($profile['cgpa'] ?? ($_POST['cgpa'] ?? '')); ?>"
                           placeholder="e.g. 3.50">
                    <span class="form-text">Between 0.00 and 4.00</span>
                </div>

                <div class="form-group">
                    <label for="semester">Current Semester <span class="required">*</span></label>
                    <select id="semester" name="semester" class="form-control">
                        <option value="">-- Select Semester --</option>
                        <?php
                        $current_sem = $profile['semester'] ?? ($_POST['semester'] ?? '');
                        for ($i = 1; $i <= 12; $i++):
                        ?>
                            <option value="<?php echo $i; ?>"
                                <?php echo ($current_sem == $i) ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- SECTION: Availability -->
            <div class="form-section">
                <h3>Availability</h3>

                <div class="form-group">
                    <label for="availability">Current Availability <span class="required">*</span></label>
                    <select id="availability" name="availability" class="form-control">
                        <?php
                        $current_av = $profile['availability'] ?? ($_POST['availability'] ?? '');
                        $av_options = ['Available', 'Partially Available', 'Not Available'];
                        foreach ($av_options as $opt):
                        ?>
                            <option value="<?php echo $opt; ?>"
                                <?php echo ($current_av === $opt) ? 'selected' : ''; ?>>
                                <?php echo $opt; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- SECTION: Research Preferences -->
            <div class="form-section">
                <h3>Research Preferences</h3>

                <div class="form-group">
                    <label for="preferred_domains">Preferred Research Domains</label>
                    <input type="text" id="preferred_domains" name="preferred_domains" class="form-control"
                           value="<?php echo htmlspecialchars($profile['preferred_domains'] ?? ($_POST['preferred_domains'] ?? '')); ?>"
                           placeholder="e.g. Database, Web Development, Cyber Security">
                    <span class="form-text">Separate multiple domains with commas</span>
                </div>

                <div class="form-group">
                    <label for="skills">Skills</label>
                    <input type="text" id="skills" name="skills" class="form-control"
                           value="<?php echo htmlspecialchars($profile['skills'] ?? ($_POST['skills'] ?? '')); ?>"
                           placeholder="e.g. PHP, MySQL, Java, HTML">
                    <span class="form-text">Separate multiple skills with commas</span>
                </div>

                <div class="form-group">
                    <label for="preferred_supervisor">Preferred Supervisor</label>
                    <input type="text" id="preferred_supervisor" name="preferred_supervisor" class="form-control"
                           value="<?php echo htmlspecialchars($profile['preferred_supervisor'] ?? ($_POST['preferred_supervisor'] ?? '')); ?>"
                           placeholder="e.g. Dr. Ahmed Hassan">
                    <span class="form-text">Enter supervisor name (leave blank if no preference)</span>
                </div>
            </div>

            <div style="margin-top:24px; padding-bottom:12px;">
                <button type="submit" class="btn btn-primary">Save Profile</button>
                <a href="profile.php" class="btn btn-secondary" style="margin-left:12px;">Cancel</a>
            </div>

        </form>
    </div><!-- end page-container -->
    </div><!-- end main-wrapper -->

    <script>
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) { toggle.addEventListener('click', () => sidebar.classList.toggle('sidebar-open')); }
    </script>
</body>
</html>
