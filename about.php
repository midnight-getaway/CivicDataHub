<?php
/**
 * about.php — About page describing project goals and team members.
 *
 * Dependencies: Session support and shared header include.
 * Data sources: None.
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

// Start session for shared navigation state.
session_start();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Civic Data Hub | About</title>
  <link rel="icon" href="assets/favicon.ico" sizes="any" />
  <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
  <link rel="icon" type="image/png" href="assets/favicon.png" />
  <link rel="apple-touch-icon" href="assets/favicon.png" />
  <link rel="stylesheet" href="styles.css" />
</head>

<body>
  <!-- Include the shared site header for consistent navigation across pages. -->
  <?php require 'includes/header.php'; ?>

  <main class="about-layout">
    <!-- Constrain About page content width for improved readability. -->
    <div class="container about-container">

      <section class="about-intro">
        <!-- Primary About page title for context and page identity. -->
        <h1>About Us</h1>
        <!-- Short supporting subline to describe the project mission quickly. -->
        <p class="about-subline">Built by students. Powered by public data. Designed for the people doing the work.</p>
        <!-- Accent divider to visually separate the title block from body content. -->
        <div class="about-accent-rule" aria-hidden="true"></div>
        <!-- First body paragraph preserves existing About copy while improving scanability. -->
        <p class="about-copy">Civic Data Hub is a web-based platform designed to make New York State data more accessible,
          meaningful, and actionable. By aggregating trusted state-level datasets into interactive dashboards
          with advanced filtering, we empower users to explore trends across regions, populations, and
          timeframes, all in one place. Whether you're analyzing shifts in public health, education, housing,
          or civic infrastructure, Civic Data Hub transforms raw data into clear, shareable insights that
          support evidence-based planning and policy decisions.</p>
        <!-- Second body paragraph preserves existing About copy while reducing visual density. -->
        <p class="about-copy">This project was built by a group of
          college students as part of a Web Design and Development course project, driven by the belief that
          open data should be genuinely usable, not just available. We created Civic Data Hub to bridge the
          gap between complex government datasets and the researchers, advocates, planners, and community
          members who need them most. Users can save custom views, collaborate with peers, and share findings
          with ease, making it simpler than ever to turn data into decisions that matter.</p>
      </section>

      <!-- Team section heading introduces contributor profile cards. -->
      <h2 class="about-team-heading">Meet the Team</h2>
      <!-- Team member card grid keeps existing content and images in a cleaner visual container. -->
      <section class="about-row">
        <div class="about-column about-card team-card">
          <img src="assets/Portrait-OS.png" alt="Portrait of Owen Sim" class="portrait" />
          <div class="team-card-content">
            <h3>Owen Sim</h3>
            <p>I am a senior student at UAlbany studying cybersecurity and work part-time for New York State ITS
              in the Division of Legal Technology. I took CINF 362 to help me improve my web development
              skills. At the beginning of the semester, I only knew HTML and basic CSS, but I've greatly
              improved on those skills and have learned quite a bit of JavaScript and PHP as well. This final
              project website has been a fun but challenging experience, as I've taken on implementing user
              account creation/authentication and helping my group use ChartJS to visualize our datasets. This
              involves ensuring the PHP forms and data views can integrate with the MySQL database securely
              and efficiently, which is a great task to assess and further improve my skills.</p>
          </div>
        </div>
        <div class="about-column about-card team-card">
          <img src="assets/Portrait-KM.png" alt="Portrait of Kylie Mugrace" class="portrait" />
          <div class="team-card-content">
            <h3>Kylie Mugrace</h3>
            <p>I am an undergraduate student at UAlbany studying Informatics. When not working on my education,
              I volunteer for the Schaghticoke Fair as their IT technician. I took CINF 362 to improve my web
              development skills, especially in JavaScript and PHP, where my skills were lacking. I feel I've
              improved a lot compared to the beginning of the semester, and I'm hoping to showcase those
              skills with this final project.</p>
          </div>
        </div>
        <div class="about-column about-card team-card">
          <img src="assets/Portrait-KV.png" alt="Portrait of Keady Van Zandt" class="portrait" />
          <div class="team-card-content">
            <h3>Keady Van Zandt</h3>
            <p>I am a student at UAlbany studying Informatics with a concentration in UX/UI, and I work
              full-time as Associate Director of Data &amp; Strategic Operations at United Way of the Greater
              Capital Region. The Civic Data Hub concept grew directly from my professional experience in the
              nonprofit sector. My contributions include the user needs assessment, style guide, wireframes,
              database design, data architecture, and overall visual design of the site. CINF 362 gave me the
              opportunity to deepen my understanding of PHP, MySQL, and front-end development while applying
              those skills to work I genuinely care about. Communities thrive when their leaders have access to
              clear, credible data and the tools to act on it.</p>
          </div>
        </div>
      </section>

    </div>
  </main>
</body>

</html>
