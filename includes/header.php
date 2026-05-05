<?php
/**
 * includes/header.php — Shared site header and primary navigation markup.
 *
 * Dependencies: Requires an active PHP session from the parent page.
 * Data sources: None.
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

// Toggle auth-specific nav items based on session state.
$logged_in = isset($_SESSION['user_id']);
?>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="index.php" aria-label="Civic Data Hub home">
      <img class="brand-logo" src="assets/logo.png" alt="Civic Data Hub" />
    </a>
    <button class="nav-toggle" id="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav-links" aria-label="Toggle navigation menu">
      <span></span><span></span><span></span>
    </button>
    <nav aria-label="Main navigation">
      <ul id="site-nav-links">
        <li class="dashboards-menu">
          <span class="dashboards-label">Dashboards</span>
          <ul class="dashboards-dropdown" aria-label="Dashboard links">
            <li><a href="dashboard1.php">Economic Hardship</a></li>
            <li><a href="dashboard2.php">Housing &amp; Homelessness</a></li>
            <li><a href="dashboard3.php">Health &amp; Wellbeing</a></li>
          </ul>
        </li>
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
<script>
  /**
   * includes/header.php — Mobile navigation toggle behavior.
   * Last updated: 2026-05-05
   * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
   */
  (function () {
    const toggleBtn = document.getElementById('nav-toggle');
    const navLinks = document.getElementById('site-nav-links');
    if (!toggleBtn || !navLinks) return;

    // Expand/collapse the mobile menu when the hamburger button is clicked.
    toggleBtn.addEventListener('click', function () {
      const expanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', String(!expanded));
      navLinks.classList.toggle('open', !expanded);
    });
  })();
</script>
