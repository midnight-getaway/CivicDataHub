<?php
session_start();
require_once 'db_connect.php';

// Redirect to login if not signed in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$email = '';
$created_at = '';
$saved_views = [];

if ($db_configured) {
    // Fetch account details
    $stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $username   = htmlspecialchars($row['username']);
        $email      = htmlspecialchars($row['email']);
        $created_at = date('F j, Y', strtotime($row['created_at']));
    }
    $stmt->close();

    // TODO: Fetch saved data views once the saved_views table exists
    // $views_stmt = $conn->prepare("SELECT id, view_name, dataset, created_at FROM saved_views WHERE user_id = ? ORDER BY created_at DESC");
    // $views_stmt->bind_param("i", $_SESSION['user_id']);
    // $views_stmt->execute();
    // $views_result = $views_stmt->get_result();
    // while ($view = $views_result->fetch_assoc()) {
    //     $saved_views[] = $view;
    // }
    // $views_stmt->close();
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Account | Civic Data Hub</title>
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
            <li><a href="about.html">About Us</a></li>
            <li><a href="account.php">My Account</a></li>
            <li><a href="logout.php">Log Out</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <main>
      <section class="account-section">
        <div class="container">

          <!-- Account Info Card -->
          <div class="account-card">
            <div class="account-header">
              <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
              <div>
                <h1><?php echo $username; ?></h1>
                <p class="account-role">Member</p>
              </div>
            </div>

            <div class="account-details">
              <h2>Account Details</h2>

              <?php if (!$db_configured): ?>
                <div class="form-message error">Database is not configured yet — account details will appear here once connected.</div>
              <?php endif; ?>

              <div class="detail-row">
                <span class="detail-label">Username</span>
                <span class="detail-value"><?php echo $username; ?></span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Email</span>
                <span class="detail-value"><?php echo $email ?: '—'; ?></span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Member Since</span>
                <span class="detail-value"><?php echo $created_at ?: '—'; ?></span>
              </div>
            </div>

            <div class="account-actions">
              <a href="logout.php" class="btn-submit btn-logout">Log Out</a>
            </div>
          </div>

          <!-- Saved Data Views -->
          <div class="saved-views-card">
            <h2>Saved Data Views</h2>
            <p class="saved-views-subtitle">Your saved dashboard configurations and filtered data views appear here.</p>

            <?php if (!$db_configured): ?>
              <div class="form-message error">Database is not configured yet — saved views will load once connected.</div>
            <?php elseif (empty($saved_views)): ?>
              <div class="empty-state">
                <p>You haven't saved any data views yet.</p>
                <a href="index.html#dashboards" class="btn-submit">Explore Dashboards</a>
              </div>
            <?php else: ?>
              <div class="views-grid">
                <?php foreach ($saved_views as $view): ?>
                  <div class="view-tile">
                    <h3><?php echo htmlspecialchars($view['view_name']); ?></h3>
                    <p class="view-dataset"><?php echo htmlspecialchars($view['dataset']); ?></p>
                    <p class="view-date">Saved <?php echo date('M j, Y', strtotime($view['created_at'])); ?></p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </section>
    </main>
  </body>
</html>
