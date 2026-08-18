<?php

// Start the session so we can check if user is already logged in
session_start();

// If the user is already logged in, send them to their dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include the database connection file
require_once 'db.php';

// Variables to hold error and success messages
$error   = "";
$success = "";

// Check if the form was submitted (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form values and trim extra spaces
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = $_POST['role'];

    // ---- VALIDATION ----

    // Check that all fields are filled in
    if (empty($name) || empty($email) || empty($password) || empty($confirm) || empty($role)) {
        $error = "Please fill in all fields.";

    // Check that the email is in a valid format (e.g. user@example.com)
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    // Check that passwords match
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";

    // Check that password is at least 6 characters long
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";

    // Make sure the user did not somehow submit 'admin' as the role
    } elseif ($role !== 'student' && $role !== 'supervisor') {
        $error = "Invalid role selected.";

    } else {

        // ---- CHECK IF EMAIL ALREADY EXISTS ----
        // We use a prepared statement to safely query the database
        // The :email placeholder prevents SQL injection
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->rowCount() > 0) {
            // Email already in the database
            $error = "An account with that email already exists.";

        } else {

            // ---- HASH THE PASSWORD ----
            // We NEVER store plain-text passwords
            // password_hash() uses the bcrypt algorithm by default
            // The result is a secure 60-character hash
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // ---- INSERT NEW USER INTO DATABASE ----
            // Again using a prepared statement with placeholders
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role)
                 VALUES (:name, :email, :password, :role)"
            );

            $stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $hashed_password,
                ':role'     => $role
            ]);

            // Show a success message and provide a link to login
            $success = "Account created successfully! You can now <a href='login.php'>login here</a>.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Grad Collab</title>
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
                <h1 class="auth-title">Create an account</h1>
                <p class="auth-subtitle">Join Grad Collab to find research teammates</p>
            </div>

            <!-- Show error message if there is one -->
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom:20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Show success message if registration worked -->
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom:20px;"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Signup form - only show if registration was not yet successful -->
            <?php if (!$success): ?>
            <form method="POST" action="signup.php">

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <!-- value keeps the typed name if form is re-shown after error -->
                    <input type="text" id="name" name="name" class="form-control"
                           value="<?php echo htmlspecialchars($name ?? ''); ?>"
                           placeholder="Your full name">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           placeholder="you@university.edu">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="At least 6 characters">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                           placeholder="Repeat your password">
                </div>

                <div class="form-group">
                    <label for="role">Register As</label>
                    <!-- Only Student and Supervisor are available to the public -->
                    <select id="role" name="role" class="form-control">
                        <option value="">-- Select Role --</option>
                        <option value="student"
                            <?php echo (isset($role) && $role === 'student') ? 'selected' : ''; ?>>
                            Student
                        </option>
                        <option value="supervisor"
                            <?php echo (isset($role) && $role === 'supervisor') ? 'selected' : ''; ?>>
                            Supervisor
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">Create Account</button>

            </form>
            <?php endif; ?>

            <div class="auth-footer-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </div>
    </div>

</body>
</html>
