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

// Get the leader's user_id from the session (never from the URL)
$leader_id = $_SESSION['user_id'];

$error   = "";
$success = "";
$new_team_id = null;  // Will hold the ID of the newly created post

// ---- HANDLE FORM SUBMISSION ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and trim form values
    $title               = trim($_POST['title']);
    $abstract            = trim($_POST['abstract']);
    $domain              = trim($_POST['domain']);
    $tags                = trim($_POST['tags']);
    $required_teammates  = trim($_POST['required_teammates']);
    $deadline            = trim($_POST['deadline']);
    $minimum_cgpa        = trim($_POST['minimum_cgpa']);
    $eligible_semesters  = trim($_POST['eligible_semesters']);
    $required_skills     = trim($_POST['required_skills']);
    $preferred_supervisor = trim($_POST['preferred_supervisor']);

    // ---- VALIDATION ----

    if (empty($title)) {
        $error = "Please fill in the required fields. Research title is required.";

    } elseif (empty($abstract)) {
        $error = "Please fill in the required fields. Abstract is required.";

    } elseif (empty($domain)) {
        $error = "Please fill in the required fields. Research domain is required.";

    } elseif ($required_teammates === '' || !ctype_digit($required_teammates) || (int)$required_teammates < 1) {
        $error = "Please enter a valid number of required teammates (minimum 1).";

    } elseif ($minimum_cgpa === '' || !is_numeric($minimum_cgpa) || $minimum_cgpa < 0 || $minimum_cgpa > 4.00) {
        $error = "Please enter a valid minimum CGPA between 0.00 and 4.00.";

    } elseif (empty($deadline)) {
        $error = "Please select a deadline for the post.";

    } elseif (strtotime($deadline) < strtotime('today')) {
        $error = "The deadline cannot be in the past.";

    } else {

        // Round CGPA to 2 decimal places
        $minimum_cgpa = number_format((float)$minimum_cgpa, 2, '.', '');

        // Insert the new team post; leader_id comes from the session
        // Status defaults to 'open' and remaining_seats starts equal to required_teammates
        $stmt = $pdo->prepare(
            "INSERT INTO team_posts
                 (leader_id, title, abstract, domain, tags,
                  required_teammates, remaining_seats, deadline, minimum_cgpa,
                  eligible_semesters, required_skills, preferred_supervisor,
                  status, created_at)
             VALUES
                 (:leader_id, :title, :abstract, :domain, :tags,
                  :required_teammates, :remaining_seats, :deadline, :minimum_cgpa,
                  :eligible_semesters, :required_skills, :preferred_supervisor,
                  'open', NOW())"
        );

        $stmt->execute([
            ':leader_id'           => $leader_id,
            ':title'               => $title,
            ':abstract'            => $abstract,
            ':domain'              => $domain,
            ':tags'                => $tags,
            ':required_teammates'  => (int)$required_teammates,
            ':remaining_seats'     => (int)$required_teammates,
            ':deadline'            => $deadline,
            ':minimum_cgpa'        => $minimum_cgpa,
            ':eligible_semesters'  => $eligible_semesters,
            ':required_skills'     => $required_skills,
            ':preferred_supervisor' => $preferred_supervisor
        ]);

        // Get the ID of the post we just created
        $new_team_id = $pdo->lastInsertId();

        $success = "Research post created successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Research Opportunity - Grad Collab</title>
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
            <a href="create_team.php" class="sidebar-link active" id="nav-post">
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
        
        <div class="dashboard-header">
            <h2>Post a Research / Thesis Opportunity</h2>
            <p>Create an opportunity and find students interested in working with you.</p>
        </div>

        <!-- Error / success messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success && $new_team_id): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <br><br>
                <a href="view_team.php?id=<?php echo $new_team_id; ?>" class="btn btn-primary">View Post</a>
                <a href="my_teams.php" class="btn btn-secondary">My Posts</a>
            </div>
        <?php endif; ?>

        <!-- Only hide the form if the post was successfully created -->
        <?php if (!$success): ?>
        
        <!-- Form placed inside a card layout -->
        <div class="card">
            <form method="POST" action="create_team.php">

                <!-- SECTION: Research Information -->
                <div class="form-section">
                    <h3 class="section-title">Research Information</h3>

                    <div class="form-group">
                        <label for="title">Research Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                               placeholder="e.g. Smart Traffic Management System">
                    </div>

                    <div class="form-group">
                        <label for="abstract">Abstract <span class="required">*</span></label>
                        <textarea id="abstract" name="abstract" class="form-control"
                                  rows="5"
                                  placeholder="Write a short description of your proposed research or thesis..."><?php echo htmlspecialchars($_POST['abstract'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="domain">Research Domain <span class="required">*</span></label>
                        <input type="text" id="domain" name="domain" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['domain'] ?? ''); ?>"
                               placeholder="e.g. Database, Artificial Intelligence, Cyber Security">
                    </div>

                    <div class="form-group">
                        <label for="tags">Tags</label>
                        <input type="text" id="tags" name="tags" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>"
                               placeholder="e.g. MySQL, Traffic, Web Application">
                        <span class="form-text">Separate with commas</span>
                    </div>
                </div>

                <!-- SECTION: Team Requirements -->
                <div class="form-section">
                    <h3 class="section-title">Team Requirements</h3>

                    <div class="form-group">
                        <label for="required_teammates">Number of Teammates Needed <span class="required">*</span></label>
                        <input type="number" id="required_teammates" name="required_teammates" class="form-control"
                               min="1" max="20"
                               value="<?php echo htmlspecialchars($_POST['required_teammates'] ?? ''); ?>"
                               placeholder="e.g. 3">
                        <span class="form-text">How many teammates are you looking for?</span>
                    </div>
                </div>

                <!-- SECTION: Eligibility Requirements -->
                <div class="form-section">
                    <h3 class="section-title">Eligibility Requirements</h3>

                    <div class="form-group">
                        <label for="minimum_cgpa">Minimum CGPA <span class="required">*</span></label>
                        <input type="number" id="minimum_cgpa" name="minimum_cgpa" class="form-control"
                               step="0.01" min="0" max="4.00"
                               value="<?php echo htmlspecialchars($_POST['minimum_cgpa'] ?? ''); ?>"
                               placeholder="e.g. 3.00">
                    </div>

                    <div class="form-group">
                        <label for="eligible_semesters">Eligible Semester(s)</label>
                        <input type="text" id="eligible_semesters" name="eligible_semesters" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['eligible_semesters'] ?? ''); ?>"
                               placeholder="e.g. 10, 11, 12">
                        <span class="form-text">Separate multiple semesters with commas</span>
                    </div>

                    <div class="form-group">
                        <label for="required_skills">Required Skills</label>
                        <input type="text" id="required_skills" name="required_skills" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['required_skills'] ?? ''); ?>"
                               placeholder="e.g. PHP, MySQL, HTML">
                        <span class="form-text">Separate with commas</span>
                    </div>
                </div>

                <!-- SECTION: Supervision & Deadline -->
                <div class="form-section">
                    <h3 class="section-title">Supervision &amp; Deadline</h3>

                    <div class="form-group">
                        <label for="preferred_supervisor">Preferred Supervisor</label>
                        <input type="text" id="preferred_supervisor" name="preferred_supervisor" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['preferred_supervisor'] ?? ''); ?>"
                               placeholder="e.g. Dr. Ahmed Hassan (leave blank if no preference)">
                    </div>

                    <div class="form-group">
                        <label for="deadline">Application Deadline <span class="required">*</span></label>
                        <input type="date" id="deadline" name="deadline" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div style="margin-top:24px; padding-bottom:12px;">
                    <button type="submit" class="btn btn-primary">Create Research Post</button>
                    <a href="my_teams.php" class="btn btn-secondary" style="margin-left:12px;">Cancel</a>
                </div>

            </form>
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
