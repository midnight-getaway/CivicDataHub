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
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                // Login successful — set session variables
                $_SESSION['user_id']  = $row['id'];
                $_SESSION['username'] = $row['username'];
                header("Location: index.html");
                exit;
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'No account found with that username or email.';
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
    <title>Log In | Civic Data Hub</title>
    <link rel="icon" href="assets/favicon.ico" sizes="any" />
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
    <link rel="icon" type="image/png" href="assets/favicon.png" />
    <link rel="apple-touch-icon" href="assets/favicon.png" />
    <link rel="stylesheet" href="styles.css" />
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
            <li><a href="signup.php">Sign Up</a></li>
            <li><a href="about.html">About Us</a></li>
            <li><a href="login.php">Log In</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <main>
      <section class="auth-section">
        <div class="container">
          <div class="auth-card">
            <h1>Log In</h1>
            <p class="auth-subtitle">Welcome back — sign in to access your dashboards.</p>

            <?php if ($error): ?>
              <div class="form-message error"><?php echo $error; ?></div>
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

              <button type="submit" class="btn-submit">Log In</button>
            </form>

            <p class="auth-switch">Don't have an account? <a href="signup.php">Sign up</a></p>
          </div>
        </div>
      </section>
    </main>
  </body>
</html>
