<?php
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

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
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Hash the password and insert
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $username, $email, $hashed);

            if ($insert->execute()) {
                $success = 'Account created successfully! You can now <a href="login.php">log in</a>.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            $insert->close();
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up | Civic Data Hub</title>
    <link rel="icon" href="assets/favicon.ico" sizes="any" />
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
    <link rel="icon" type="image/png" href="assets/favicon.png" />
    <link rel="apple-touch-icon" href="assets/favicon.png" />
    <link rel="stylesheet" href="styles.css?v=3" />
  </head>
  <body>
    <header class="site-header">
      <div class="container nav">
        <a class="brand" href="index.html" aria-label="Civic Data Hub home">
          <img class="brand-logo" src="assets/logo.png" alt="Civic Data Hub" />
        </a>
        <nav aria-label="Main navigation">
          <ul>
            <li><a href="index.html#dashboards">Dashboards</a></li>
            <li><a href="about.html">About Us</a></li>
            <li><a href="login.php">Log In</a></li>
            <li><a href="signup.php">Sign Up</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <main>
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
