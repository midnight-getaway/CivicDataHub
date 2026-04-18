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
            <h1><?php echo $username; ?></h1>
            <p class="muted-text">Member</p>

            <h2>Account Details</h2>

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

            <a href="logout.php" class="btn btn-danger" style="margin-top:1.5rem">Log Out</a>
          </div>

          <!-- Saved Data Views -->
          <div class="card">
            <h2>Saved Data Views</h2>
            <p class="muted-text">Your saved dashboard configurations and filtered data views appear here.</p>

            <?php if (!$db_configured): ?>
              <div class="msg error">Database is not configured yet — saved views will load once connected.</div>
            <?php elseif (empty($saved_views)): ?>
              <div class="empty-state">
                <p>You haven't saved any data views yet.</p>
                <a href="index.html#dashboards" class="btn">Explore Dashboards</a>
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
