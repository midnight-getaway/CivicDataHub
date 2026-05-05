<?php
/**
 * signup.php — Registration page for creating new Civic Data Hub user accounts.
 *
 * Dependencies: Session support, db_connect.php, shared header include.
 * Data sources: users table.
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

// Start session so this page can share auth-aware navigation.
session_start();
// Load PDO connection and DB configuration state.
require_once 'db_connect.php';

$error = '';
$success = '';

// Handle registration form submission and validation flow.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!$db_configured) {
        $error = 'Database is not configured yet. Please check back later.';
    } else {
        // Check for existing account before inserting a new user row.
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            // Hash password before storage and insert the new account.
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

            if ($insert->execute([$username, $email, $hashed])) {
                $success = 'Account created successfully! You can now <a href="login.php">log in</a>.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Civic Data Hub | Sign Up</title>
    <link rel="icon" href="assets/favicon.ico" sizes="any" />
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
    <link rel="icon" type="image/png" href="assets/favicon.png" />
    <link rel="apple-touch-icon" href="assets/favicon.png" />
    <link rel="stylesheet" href="styles.css?v=1" />
  </head>
  <body>
    <?php require 'includes/header.php'; ?>

    <main class="auth-layout">
      <section class="auth-section">
        <div class="container">
          <div class="card">
            <h1>Create an Account</h1>
            <p class="muted-text">Join Civic Data Hub to explore and save custom data views.</p>

            <?php if ($error): ?>
              <div class="msg error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
              <div class="msg success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="signup.php">
              <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username"
                       value="<?php echo htmlspecialchars($username ?? ''); ?>" required />
              </div>

              <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email"
                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required />
              </div>

              <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a password (min 6 chars)" required />
              </div>

              <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required />
              </div>

              <button type="submit" class="btn">Sign Up</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
          </div>
        </div>
      </section>
    </main>
  </body>
</html>
