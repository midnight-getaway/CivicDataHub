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
$greeting_title = 'My Account';

if ($db_configured) {
    // Fetch account details
    $stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();

    if ($row) {
        $username   = htmlspecialchars($row['username']);
        $email      = htmlspecialchars($row['email']);
        $created_at = date('F j, Y', strtotime($row['created_at']));
    }

    // TODO: Fetch saved data views once the saved_views table exists
    // $views_stmt = $pdo->prepare("SELECT id, view_name, dataset, created_at FROM saved_views WHERE user_id = ? ORDER BY created_at DESC");
    // $views_stmt->execute([$_SESSION['user_id']]);
    // $saved_views = $views_stmt->fetchAll();
}

$name_source = trim($_SESSION['username'] ?? '');
if ($db_configured && !empty($row['username'])) {
    $name_source = trim((string) $row['username']);
}
if ($name_source !== '' && strpos($name_source, '@') === false) {
    $name_parts = preg_split('/[\s._-]+/', $name_source);
    $first_name = trim((string) ($name_parts[0] ?? ''));
    if ($first_name !== '') {
        $greeting_title = 'Welcome back, ' . htmlspecialchars(ucfirst($first_name));
    }
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
    <?php require 'includes/header.php'; ?>

    <main>
      <section class="account-section">
        <div class="container">

          <!-- Account Info Card -->
          <div class="card">
            <h1 class="account-title"><?php echo $greeting_title; ?></h1>
            <p class="member-badge">Member</p>

            <h2 class="account-details-heading">Account Details</h2>

            <?php if (!$db_configured): ?>
              <div class="msg error">Database is not configured yet — account details will appear here once connected.</div>
            <?php endif; ?>

            <div class="detail-row">
              <strong>Username</strong>
              <span><?php echo $username; ?></span>
            </div>
            <div class="detail-row">
              <strong>Email</strong>
              <span><?php echo $email ?: '—'; ?></span>
            </div>
            <div class="detail-row">
              <strong>Member Since</strong>
              <span><?php echo $created_at ?: '—'; ?></span>
            </div>
            <hr class="account-divider" />

            <a href="logout.php" class="btn btn-danger">Log Out</a>
          </div>

          <!-- Saved Data Views -->
          <div class="card">
            <h2>Saved Data Views</h2>
            <p class="muted-text">Your saved dashboard configurations and filtered data views appear here.</p>

            <?php if (!$db_configured): ?>
              <div class="msg error">Database is not configured yet — saved views will load once connected.</div>
            <?php elseif (empty($saved_views)): ?>
              <div class="empty-state">
                <div class="empty-icon" aria-hidden="true">▦</div>
                <p>You haven't saved any data views yet.</p>
                <p class="muted-text account-explore-label">Explore Dashboards</p>
                <div class="account-dashboard-links">
                  <a href="dashboard1.php" class="btn">Economic Hardship</a>
                  <a href="dashboard2.php" class="btn">Housing &amp; Homelessness</a>
                  <a href="dashboard3.php" class="btn">Health &amp; Wellbeing</a>
                  <a href="#" class="btn">Education &amp; Youth</a>
                </div>
              </div>
            <?php else: ?>
              <div class="views-grid">
                <?php foreach ($saved_views as $view): ?>
                  <div class="view-tile">
                    <h3><?php echo htmlspecialchars($view['view_name']); ?></h3>
                    <p><?php echo htmlspecialchars($view['dataset']); ?></p>
                    <p>Saved <?php echo date('M j, Y', strtotime($view['created_at'])); ?></p>
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
