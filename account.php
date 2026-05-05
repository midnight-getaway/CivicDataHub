<?php
/**
 * account.php — User account page showing profile details and saved dashboard views.
 *
 * Dependencies: Session support, db_connect.php, includes/header.php.
 * Data sources: users, saved_views tables.
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

// Start session to enforce account access permissions.
session_start();
// Load PDO connection and DB configuration state.
require_once 'db_connect.php';

// Redirect unauthenticated visitors to login.
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
    // Fetch profile metadata for the current authenticated user.
    $stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();

    if ($row) {
        $username   = htmlspecialchars($row['username']);
        $email      = htmlspecialchars($row['email']);
        $created_at = date('F j, Y', strtotime($row['created_at']));
    }

    // Fetch saved dashboard view presets for account display.
    $views_stmt = $pdo->prepare("SELECT id, view_name, dashboard_name, dashboard_url, filters, created_at FROM saved_views WHERE user_id = ? ORDER BY created_at DESC");
    $views_stmt->execute([$_SESSION['user_id']]);
    $saved_views = $views_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Civic Data Hub | My Account</title>
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
              <div class="msg error">Database currently disabled...</div>
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
                </div>
              </div>
            <?php else: ?>
              <div class="views-grid">
                <?php foreach ($saved_views as $view):
                  $filters = json_decode($view['filters'], true);
                  $query_str = http_build_query($filters);
                  $view_link = $view['dashboard_url'] . '?' . $query_str;
                ?>
                  <div class="view-tile">
                    <div class="view-tile-info">
                      <h3 class="view-tile-name"><a href="<?php echo htmlspecialchars($view_link); ?>"><?php echo htmlspecialchars($view['view_name']); ?></a></h3>
                      <p class="view-tile-dash"><?php echo htmlspecialchars($view['dashboard_name']); ?></p>
                      <p class="view-tile-date">Saved <?php echo date('M j, Y', strtotime($view['created_at'])); ?></p>
                    </div>
                    <button class="btn btn-danger delete-view-btn" data-id="<?php echo $view['id']; ?>">Delete</button>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </section>
    </main>
    
    <script>
      /**
       * account.php — Saved view deletion handlers.
       * Charts: None.
       * Filters: Delete action per saved view tile.
       * Dependencies: fetch API and api/delete_view.php.
       */
      // Bind a click handler to each delete button in saved view cards.
      document.querySelectorAll('.delete-view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          if (!confirm("Are you sure you want to delete this view?")) return;
          
          const viewId = this.getAttribute('data-id');
          // Call API endpoint to remove the selected saved view from the database.
          fetch('api/delete_view.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ view_id: viewId })
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              this.closest('.view-tile').remove();
              // Check if empty
              if (document.querySelectorAll('.view-tile').length === 0) {
                location.reload(); // Reload to show empty state
              }
            } else {
              alert("Error deleting view: " + (data.error || "Unknown error"));
            }
          })
          .catch(err => {
            console.error(err);
            alert("An error occurred while deleting.");
          });
        });
      });
    </script>
  </body>
</html>
