<?php
// includes/header.php
// Shared site header — include after session_start() has been called on the page.
// Outputs the full <header> with session-aware navigation.
$logged_in = isset($_SESSION['user_id']);
?>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="index.php" aria-label="Civic Data Hub home">
      <img class="brand-logo" src="assets/logo.png" alt="Civic Data Hub" />
    </a>
    <nav aria-label="Main navigation">
      <ul>
        <li><a href="index.php#dashboards">Dashboards</a></li>
        <li><a href="about.php">About Us</a></li>
        <?php if ($logged_in): ?>
          <li><a href="account.php">My Account</a></li>
          <li><a href="logout.php">Log Out</a></li>
        <?php else: ?>
          <li><a href="login.php">Log In</a></li>
          <li><a href="signup.php">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
