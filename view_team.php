<?php
// ============================================================
// view_team.php - View Full Research Post Details & Join Requests
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
require_once 'match_helper.php';

// Ensure table exists
ensure_applications_table($pdo);

// Validate team_id
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: my_teams.php");
    exit();
}
$team_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];

// Fetch the team post along with leader details
$stmt = $pdo->prepare(
    "SELECT tp.*, u.name AS leader_name, u.email AS leader_email
     FROM team_posts tp
     JOIN users u ON u.user_id = tp.leader_id
     WHERE tp.team_id = :tid"
);
$stmt->execute([':tid' => $team_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header("Location: my_teams.php");
    exit();
}

$is_leader = ((int)$post['leader_id'] === $user_id);

$error_msg   = "";
$success_msg = "";

// Fetch current user's profile
$pStmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = :uid");
$pStmt->execute([':uid' => $user_id]);
$my_profile = $pStmt->fetch(PDO::FETCH_ASSOC);

// ============================================================
// POST ACTION: Leader Accepting / Rejecting an Application
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['accept_applicant', 'reject_applicant'])) {
    if (!$is_leader) {
        $error_msg = "Unauthorized action.";
    } else {
        $app_id     = (int)($_POST['application_id'] ?? 0);
        $new_status = ($_POST['action'] === 'accept_applicant') ? 'accepted' : 'rejected';

        // Verify that this application belongs to this team post
        $vStmt = $pdo->prepare("SELECT application_id, status FROM team_applications WHERE application_id = :aid AND team_id = :tid");
        $vStmt->execute([':aid' => $app_id, ':tid' => $team_id]);
        $target_app = $vStmt->fetch(PDO::FETCH_ASSOC);

        if ($target_app) {
            $uStmt = $pdo->prepare("UPDATE team_applications SET status = :status, updated_at = NOW() WHERE application_id = :aid");
            $uStmt->execute([':status' => $new_status, ':aid' => $app_id]);
            $success_msg = "Application status has been updated to " . ucfirst($new_status) . ".";
        } else {
            $error_msg = "Application not found or invalid.";
        }
    }
}

// ============================================================
// POST ACTION: Student Submitting a Join Request
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_team') {
    $statement = trim($_POST['message'] ?? '');

    // Check if already applied
    $checkApp = $pdo->prepare("SELECT application_id, status FROM team_applications WHERE team_id = :tid AND student_id = :sid AND status = 'pending'");
    $checkApp->execute([':tid' => $team_id, ':sid' => $user_id]);
    $pendingApp = $checkApp->fetch(PDO::FETCH_ASSOC);

    if ($is_leader) {
        $error_msg = "You cannot apply to your own research post.";
    } elseif ($post['status'] !== 'open') {
        $error_msg = "This research post is closed and no longer accepting applications.";
    } elseif (strtotime($post['deadline']) < strtotime('today')) {
        $error_msg = "The application deadline for this post has passed.";
    } elseif ($pendingApp) {
        $error_msg = "You already have a pending application for this team.";
    } elseif (empty($statement)) {
        $error_msg = "Please write a statement of interest explaining your motivation and skills.";
    } elseif (strlen($statement) < 10) {
        $error_msg = "Statement of interest must be at least 10 characters long.";
    } else {
        // Calculate match score to store with the application
        $calculated_match = calculate_match_score($my_profile, $post);
        $score_to_store   = $calculated_match['score'];

        $insertStmt = $pdo->prepare("
            INSERT INTO team_applications (team_id, student_id, message, match_score, status, applied_at)
            VALUES (:tid, :sid, :msg, :score, 'pending', NOW())
        ");
        $insertStmt->execute([
            ':tid'   => $team_id,
            ':sid'   => $user_id,
            ':msg'   => $statement,
            ':score' => $score_to_store
        ]);

        $success_msg = "Your join request has been submitted successfully!";
    }
}

// ============================================================
// DATA FETCH FOR RENDER
// ============================================================

// If student is not leader, compute their match score and get their application status
$my_match = null;
$my_application = null;
if (!$is_leader) {
    $my_match = calculate_match_score($my_profile, $post);

    $appStmt = $pdo->prepare("SELECT * FROM team_applications WHERE team_id = :tid AND student_id = :sid ORDER BY application_id DESC LIMIT 1");
    $appStmt->execute([':tid' => $team_id, ':sid' => $user_id]);
    $my_application = $appStmt->fetch(PDO::FETCH_ASSOC);
}

// If user is leader, fetch all applicants for this team
$applicants = [];
$accepted_count = 0;
if ($is_leader) {
    $appsQuery = $pdo->prepare("
        SELECT ta.*, u.name AS applicant_name, u.email AS applicant_email,
               sp.cgpa, sp.semester, sp.availability, sp.skills, sp.preferred_domains, sp.preferred_supervisor
        FROM team_applications ta
        JOIN users u ON u.user_id = ta.student_id
        LEFT JOIN student_profiles sp ON sp.user_id = ta.student_id
        WHERE ta.team_id = :tid
        ORDER BY ta.applied_at DESC
    ");
    $appsQuery->execute([':tid' => $team_id]);
    $applicants = $appsQuery->fetchAll(PDO::FETCH_ASSOC);

    foreach ($applicants as $app) {
        if ($app['status'] === 'accepted') {
            $accepted_count++;
        }
    }
}
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

        <!-- Navigation back links -->
        <p style="margin-bottom:16px;">
            <?php if ($is_leader): ?>
                <a href="my_teams.php">&larr; Back to My Posts</a>
            <?php else: ?>
                <a href="browse_teams.php">&larr; Back to Explore Teams</a>
            <?php endif; ?>
        </p>

        <!-- Alerts -->
        <?php if ($error_msg): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        <?php if ($success_msg): ?>
            <div class="alert alert-success" style="margin-bottom:20px;"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <!-- Post Title Header -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
            <div>
                <span class="badge badge-tag" style="margin-bottom:8px; font-size:13px;"><?php echo htmlspecialchars($post['domain']); ?></span>
                <h2 style="margin:0; font-size:24px; color:#1F2937;"><?php echo htmlspecialchars($post['title']); ?></h2>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="status-badge status-<?php echo $post['status']; ?>" style="font-size:0.95em; padding:5px 14px;">
                    <?php echo ucfirst(htmlspecialchars($post['status'])); ?>
                </span>
                <?php if ($is_leader && $post['status'] === 'open'): ?>
                    <a href="edit_team.php?id=<?php echo $team_id; ?>" class="btn btn-secondary btn-sm">Edit Post</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$is_leader && $my_match): ?>
        <!-- ============================================================
             MATCH SCORE CARD (For Students Viewing)
             ============================================================ -->
        <div class="match-score-card <?php echo $my_match['badge_class']; ?>" style="margin-bottom:24px;">
            <div class="match-score-header">
                <div class="match-score-circle">
                    <span class="match-score-number"><?php echo $my_match['score']; ?>%</span>
                    <span class="match-score-label">Match</span>
                </div>
                <div class="match-score-info">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <h3 style="margin:0; font-size:18px; color:var(--text-main);">Your Compatibility Score: <?php echo $my_match['level']; ?></h3>
                        <span class="badge badge-<?php echo $my_match['badge_class']; ?>"><?php echo $my_match['score']; ?>/100 pts</span>
                    </div>
                    <p style="margin:4px 0 0; font-size:13px; color:var(--text-muted);">
                        Based on your profile CGPA, semester, technical skills, domain preferences, and availability.
                    </p>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" id="toggleBreakdownBtn" style="align-self:center;">
                    View Match Breakdown
                </button>
            </div>

            <!-- Breakdown Accordion Panel -->
            <div id="matchBreakdownPanel" class="match-breakdown-grid" style="display:none; margin-top:16px; border-top:1px solid rgba(0,0,0,0.06); padding-top:16px;">
                <?php foreach ($my_match['breakdown'] as $key => $factor): ?>
                <div class="breakdown-item">
                    <div class="breakdown-item-header">
                        <span class="breakdown-name"><?php echo htmlspecialchars($factor['name']); ?></span>
                        <span class="breakdown-pts"><strong><?php echo $factor['score']; ?></strong> / <?php echo $factor['max']; ?> pts</span>
                    </div>
                    <div class="breakdown-bar-bg">
                        <div class="breakdown-bar-fill" style="width: <?php echo ($factor['max'] > 0 ? round(($factor['score'] / $factor['max']) * 100) : 0); ?>%;"></div>
                    </div>
                    <div class="breakdown-status"><?php echo htmlspecialchars($factor['status']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============================================================
             MAIN POST DETAILS
             ============================================================ -->
        <div class="card" style="margin-bottom:24px;">
            <h3 class="section-title">Research Description</h3>
            <p style="color:#374151; line-height:1.7; font-size:15px; white-space:pre-line;">
                <?php echo htmlspecialchars($post['abstract']); ?>
            </p>

            <?php if (!empty($post['tags'])): ?>
            <div style="margin-top:16px; padding-top:14px; border-top:1px solid #F3F4F6;">
                <strong style="font-size:13px; color:#6B7280; text-transform:uppercase; letter-spacing:0.05em; display:block; margin-bottom:8px;">Topics &amp; Tags:</strong>
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
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px; margin-bottom:24px;">
            
            <!-- Requirements Card -->
            <div class="card">
                <h3 class="section-title">Requirements &amp; Eligibility</h3>
                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label">Teammates Needed</div>
                        <div class="info-value"><strong><?php echo (int)$post['required_teammates']; ?></strong> student(s)</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Minimum CGPA</div>
                        <div class="info-value"><?php echo number_format((float)$post['minimum_cgpa'], 2); ?> / 4.00</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Eligible Semesters</div>
                        <div class="info-value">
                            <?php echo !empty($post['eligible_semesters']) ? htmlspecialchars($post['eligible_semesters']) : '<span class="text-muted">Open to all</span>'; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Required Skills</div>
                        <div class="info-value">
                            <?php echo !empty($post['required_skills']) ? htmlspecialchars($post['required_skills']) : '<span class="text-muted">None specified</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supervision & Timeline Card -->
            <div class="card">
                <h3 class="section-title">Supervision &amp; Details</h3>
                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label">Preferred Supervisor</div>
                        <div class="info-value">
                            <?php echo !empty($post['preferred_supervisor']) ? htmlspecialchars($post['preferred_supervisor']) : '<span class="text-muted">No preference</span>'; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Application Deadline</div>
                        <div class="info-value">
                            <strong><?php echo date('d M Y', strtotime($post['deadline'])); ?></strong>
                            <?php if (strtotime($post['deadline']) < strtotime('today')): ?>
                                <span class="badge badge-danger" style="margin-left:6px;">Expired</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Team Leader</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($post['leader_name']); ?>
                            (<?php echo htmlspecialchars($post['leader_email']); ?>)
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Posted Date</div>
                        <div class="info-value"><?php echo date('d M Y', strtotime($post['created_at'])); ?></div>
                    </div>
                </div>
            </div>

        </div>

        <?php if (!$is_leader): ?>
        <!-- ============================================================
             JOIN REQUEST / APPLICATION SECTION (Non-Leader)
             ============================================================ -->
        <div class="card" id="apply-section">
            <h3 class="section-title">Join this Research Team</h3>

            <?php if ($my_application): ?>
                <!-- Already Applied -->
                <div class="app-status-box app-status-<?php echo $my_application['status']; ?>">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                        <div>
                            <span class="badge badge-<?php echo $my_application['status']; ?>" style="font-size:13px; padding:4px 12px;">
                                Application Status: <?php echo ucfirst($my_application['status']); ?>
                            </span>
                            <span style="font-size:13px; color:#6B7280; margin-left:8px;">
                                Applied on <?php echo date('d M Y, h:i A', strtotime($my_application['applied_at'])); ?>
                            </span>
                        </div>
                        <span class="badge badge-tag">Match Score at Apply: <?php echo (int)$my_application['match_score']; ?>%</span>
                    </div>

                    <?php if ($my_application['status'] === 'pending'): ?>
                        <p style="color:#92400E; margin-bottom:12px;">
                            Your join request has been sent to <strong><?php echo htmlspecialchars($post['leader_name']); ?></strong> and is currently pending review.
                        </p>
                    <?php elseif ($my_application['status'] === 'accepted'): ?>
                        <p style="color:#065F46; font-weight:600; margin-bottom:12px;">
                            🎉 Congratulations! You have been accepted as a teammate on this project. You can contact your team leader at <a href="mailto:<?php echo htmlspecialchars($post['leader_email']); ?>"><?php echo htmlspecialchars($post['leader_email']); ?></a>.
                        </p>
                    <?php elseif ($my_application['status'] === 'rejected'): ?>
                        <p style="color:#991B1B; margin-bottom:12px;">
                            Your join request was not accepted by the team leader for this opportunity.
                        </p>
                    <?php endif; ?>

                    <div style="background:#FFFFFF; border:1px solid #E5E7EB; border-radius:8px; padding:14px;">
                        <strong style="font-size:13px; color:#4B5563; display:block; margin-bottom:6px;">Your Statement of Interest:</strong>
                        <p style="margin:0; color:#1F2937; font-size:14px; line-height:1.5;">
                            <?php echo nl2br(htmlspecialchars($my_application['message'])); ?>
                        </p>
                    </div>
                </div>

            <?php elseif ($post['status'] !== 'open'): ?>
                <div class="alert alert-danger">
                    This research opportunity is currently closed and is not accepting new applications.
                </div>
            <?php elseif (strtotime($post['deadline']) < strtotime('today')): ?>
                <div class="alert alert-danger">
                    The application deadline for this post was <?php echo date('d M Y', strtotime($post['deadline'])); ?> and has passed.
                </div>
            <?php else: ?>
                <!-- Application Form -->
                <form method="POST" action="view_team.php?id=<?php echo $team_id; ?>#apply-section">
                    <input type="hidden" name="action" value="apply_team">

                    <p style="color:#4B5563; margin-bottom:16px;">
                        Interested in collaborating on this research? Introduce yourself and tell the team leader why you would be a great fit.
                    </p>

                    <div class="form-group">
                        <label for="message">Statement of Interest / Message <span class="required">*</span></label>
                        <textarea id="message" name="message" class="form-control" rows="4"
                                  placeholder="Introduce yourself, mention your relevant background, skills, and why you are interested in this research project..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        <span class="form-text">Explain your skills, availability, and motivation (minimum 10 characters).</span>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top:10px;">
                        Submit Join Request
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($is_leader): ?>
        <!-- ============================================================
             LEADER APPLICANTS MANAGEMENT SECTION
             ============================================================ -->
        <div class="card" id="applicants-section">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
                <div>
                    <h3 class="section-title" style="margin:0;">Applicants &amp; Join Requests</h3>
                    <span style="font-size:13px; color:#6B7280;">
                        Review student applications, inspect match scores, and accept or reject candidates.
                    </span>
                </div>
                <div style="background:var(--primary-bg); border:1px solid var(--primary-bg-mid); padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; color:var(--primary);">
                    Accepted: <?php echo $accepted_count; ?> / <?php echo (int)$post['required_teammates']; ?> needed
                </div>
            </div>

            <?php if (empty($applicants)): ?>
                <div class="empty-state" style="padding:32px 16px;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <h4>No applications yet</h4>
                    <p style="color:#6B7280; font-size:14px;">When students apply to join your research team, their details and match scores will appear here.</p>
                </div>
            <?php else: ?>
                <div class="applicants-list">
                    <?php foreach ($applicants as $applicant): 
                        // Compute live match score breakdown for leader inspection
                        $app_profile = [
                            'cgpa' => $applicant['cgpa'],
                            'semester' => $applicant['semester'],
                            'availability' => $applicant['availability'],
                            'skills' => $applicant['skills'],
                            'preferred_domains' => $applicant['preferred_domains'],
                            'preferred_supervisor' => $applicant['preferred_supervisor']
                        ];
                        $app_match = calculate_match_score($applicant['cgpa'] !== null ? $app_profile : null, $post);
                    ?>
                    <div class="applicant-card applicant-card-<?php echo $applicant['status']; ?>">
                        <div class="applicant-card-header">
                            <div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <h4 class="applicant-name"><?php echo htmlspecialchars($applicant['applicant_name']); ?></h4>
                                    <span class="badge badge-<?php echo $applicant['status']; ?>">
                                        <?php echo ucfirst($applicant['status']); ?>
                                    </span>
                                </div>
                                <span class="applicant-email"><?php echo htmlspecialchars($applicant['applicant_email']); ?></span>
                            </div>
                            
                            <div class="applicant-match-badge badge-<?php echo $app_match['badge_class']; ?>">
                                <span style="font-size:16px; font-weight:700;"><?php echo $app_match['score']; ?>%</span>
                                <span style="font-size:11px; text-transform:uppercase;"><?php echo $app_match['level']; ?></span>
                            </div>
                        </div>

                        <!-- Academic Snapshot -->
                        <div class="applicant-stats-row">
                            <span><strong>CGPA:</strong> <?php echo $applicant['cgpa'] !== null ? htmlspecialchars($applicant['cgpa']) : 'N/A'; ?></span>
                            <span><strong>Semester:</strong> <?php echo $applicant['semester'] !== null ? 'Sem ' . htmlspecialchars($applicant['semester']) : 'N/A'; ?></span>
                            <span><strong>Availability:</strong> <?php echo $applicant['availability'] !== null ? htmlspecialchars($applicant['availability']) : 'N/A'; ?></span>
                            <span><strong>Applied:</strong> <?php echo date('d M Y', strtotime($applicant['applied_at'])); ?></span>
                        </div>

                        <?php if (!empty($applicant['skills'])): ?>
                        <div style="margin-bottom:10px; font-size:13px;">
                            <strong style="color:#4B5563;">Skills:</strong>
                            <?php 
                            $app_skills = array_map('trim', explode(',', $applicant['skills']));
                            foreach ($app_skills as $s): if ($s !== ''): ?>
                                <span class="badge badge-tag" style="font-size:11px;"><?php echo htmlspecialchars($s); ?></span>
                            <?php endif; endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Statement of Interest -->
                        <div class="applicant-message-box">
                            <strong style="font-size:12px; text-transform:uppercase; color:#6B7280; display:block; margin-bottom:4px;">Statement of Interest:</strong>
                            <p style="margin:0; font-size:14px; color:#1F2937; line-height:1.5;">
                                <?php echo nl2br(htmlspecialchars($applicant['message'])); ?>
                            </p>
                        </div>

                        <!-- Action Buttons (Accept / Reject) -->
                        <div class="applicant-card-actions">
                            <?php if ($applicant['status'] === 'pending'): ?>
                                <form method="POST" action="view_team.php?id=<?php echo $team_id; ?>#applicants-section" style="display:inline;" onsubmit="return confirm('Accept this applicant into your team?');">
                                    <input type="hidden" name="action" value="accept_applicant">
                                    <input type="hidden" name="application_id" value="<?php echo (int)$applicant['application_id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Accept Applicant</button>
                                </form>
                                <form method="POST" action="view_team.php?id=<?php echo $team_id; ?>#applicants-section" style="display:inline;" onsubmit="return confirm('Reject this application?');">
                                    <input type="hidden" name="action" value="reject_applicant">
                                    <input type="hidden" name="application_id" value="<?php echo (int)$applicant['application_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            <?php elseif ($applicant['status'] === 'accepted'): ?>
                                <span style="font-size:13px; color:#065F46; font-weight:600;">✓ Accepted Teammate</span>
                                <form method="POST" action="view_team.php?id=<?php echo $team_id; ?>#applicants-section" style="display:inline;" onsubmit="return confirm('Change status back to rejected?');">
                                    <input type="hidden" name="action" value="reject_applicant">
                                    <input type="hidden" name="application_id" value="<?php echo (int)$applicant['application_id']; ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm" style="color:#DC2626;">Remove / Reject</button>
                                </form>
                            <?php elseif ($applicant['status'] === 'rejected'): ?>
                                <span style="font-size:13px; color:#991B1B;">✕ Rejected</span>
                                <form method="POST" action="view_team.php?id=<?php echo $team_id; ?>#applicants-section" style="display:inline;" onsubmit="return confirm('Accept this applicant now?');">
                                    <input type="hidden" name="action" value="accept_applicant">
                                    <input type="hidden" name="application_id" value="<?php echo (int)$applicant['application_id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Accept Instead</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- end page-container -->
    </div><!-- end main-wrapper -->

    <script>
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (toggle && sidebar) { toggle.addEventListener('click', () => sidebar.classList.toggle('sidebar-open')); }

        // Match breakdown accordion toggle
        const toggleBreakdownBtn = document.getElementById('toggleBreakdownBtn');
        const matchBreakdownPanel = document.getElementById('matchBreakdownPanel');
        if (toggleBreakdownBtn && matchBreakdownPanel) {
            toggleBreakdownBtn.addEventListener('click', () => {
                if (matchBreakdownPanel.style.display === 'none' || matchBreakdownPanel.style.display === '') {
                    matchBreakdownPanel.style.display = 'grid';
                    toggleBreakdownBtn.innerText = 'Hide Match Breakdown';
                } else {
                    matchBreakdownPanel.style.display = 'none';
                    toggleBreakdownBtn.innerText = 'View Match Breakdown';
                }
            });
        }
    </script>
</body>
</html>
