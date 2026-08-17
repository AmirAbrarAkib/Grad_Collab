<?php
// ============================================================
// login.php - User Login Page
// Checks email and password, then redirects based on role
// ============================================================

// Start the session - required before using $_SESSION
session_start();

// If the user is already logged in, redirect to their dashboard
if (isset($_SESSION['user_id'])) {
    // Redirect to the correct dashboard based on their role
    if ($_SESSION['role'] === 'student') {
        header("Location: student_dashboard.php");
    } elseif ($_SESSION['role'] === 'supervisor') {
        header("Location: supervisor_dashboard.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    }
    exit();
}

// Include the database connection
require_once 'db.php';

// Variable to hold error message
$error = "";

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get the submitted email and password
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // ---- VALIDATION ----

    // Make sure both fields are filled in
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";

    } else {

        // ---- LOOK UP THE USER BY EMAIL ----
        // Prepared statement: the :email placeholder keeps the query safe
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        // Fetch the matching row as an associative array
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ---- CHECK PASSWORD ----
        // password_verify() compares the plain-text input with the stored hash
        // It returns true if they match, false otherwise
        if ($user && password_verify($password, $user['password'])) {

            // ---- LOGIN SUCCESSFUL - SAVE DATA IN SESSION ----
            // Sessions store user data on the server side between page loads
            $_SESSION['user_id'] = $user['user_id'];  // Store the user ID
            $_SESSION['name']    = $user['name'];      // Store the user's name
            $_SESSION['email']   = $user['email'];     // Store the email
            $_SESSION['role']    = $user['role'];      // Store the role (important for access control)

            // ---- REDIRECT BASED ON ROLE ----
            // Each role goes to a different dashboard page
            if ($user['role'] === 'student') {
                header("Location: student_dashboard.php");

            } elseif ($user['role'] === 'supervisor') {
                header("Location: supervisor_dashboard.php");

            } elseif ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            }

            exit(); // Always call exit() after header redirect

        } else {
            // Wrong email or wrong password - we give a vague message on purpose
            // (We don't say "wrong password" because that confirms the email exists)
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Grad Collab</title>
    <meta name="description" content="Login to Grad Collab, the university research collaboration portal.">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body class="auth-page">

    <div class="auth-container">
        <!-- Brand -->
        <div class="auth-brand">
            <div class="auth-brand-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
            </div>
            <span class="auth-brand-name">Grad Collab</span>
        </div>

        <div class="auth-card">
            <div class="auth-card-header">
                <h1 class="auth-title">Welcome back</h1>
                <p class="auth-subtitle">Sign in to your account to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom:20px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="you@university.edu">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Enter your password">
                </div>

                <button type="submit" class="btn btn-primary auth-submit">Sign In</button>

            </form>

            <div class="auth-footer-link">
                Don't have an account? <a href="signup.php">Create one here</a>
            </div>
        </div>
    </div>

</body>
</html>
