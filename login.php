<?php
session_start();
require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!$db_configured) {
        $error = 'Database is not configured yet. Please check back later.';
    } else {
        // Look up user by username or email
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $row = $stmt->fetch();

        if ($row) {
            if (password_verify($password, $row['password'])) {
                // Login successful — set session variables
                $_SESSION['user_id']  = $row['id'];
                $_SESSION['username'] = $row['username'];
                header("Location: index.php");
                exit;
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'No account found with that username or email.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Log In | Civic Data Hub</title>
    <link rel="icon" href="assets/favicon.ico" sizes="any" />
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
    <link rel="icon" type="image/png" href="assets/favicon.png" />
    <link rel="apple-touch-icon" href="assets/favicon.png" />
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body>
    <?php require 'includes/header.php'; ?>

    <main>
      <section class="auth-section">
        <div class="container">
          <div class="card">
            <h1>Log In</h1>
            <p class="muted-text">Welcome back — sign in to access your dashboards.</p>

            <?php if ($error): ?>
              <div class="msg error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="login.php">
              <div class="form-group">
                <label for="login">Username or Email</label>
                <input type="text" id="login" name="login" placeholder="Enter your username or email"
                       value="<?php echo htmlspecialchars($login ?? ''); ?>" required />
              </div>

              <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required />
              </div>

              <button type="submit" class="btn">Log In</button>
            </form>

            <p class="auth-switch">Don't have an account? <a href="signup.php">Sign up</a></p>
          </div>
        </div>
      </section>
    </main>
  </body>
</html>
