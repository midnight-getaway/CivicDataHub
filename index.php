<?php
session_start();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Civic Data Hub | Home</title>
  <link rel="icon" href="assets/favicon.ico" sizes="any" />
  <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
  <link rel="icon" type="image/png" href="assets/favicon.png" />
  <link rel="apple-touch-icon" href="assets/favicon.png" />
  <link rel="stylesheet" href="styles.css" />
</head>

<body>
  <?php require 'includes/header.php'; ?>

  <main class="home-layout">
    <section id="dashboards" aria-label="Dashboard shortcuts">
      <div class="container dashboard-grid">
        <a class="dashboard-tile" href="dashboard1.php">Economic Hardship</a>
        <a class="dashboard-tile" href="dashboard2.php">Housing &amp; Homelessness</a>
        <a class="dashboard-tile" href="dashboard3.php">Health &amp; Wellbeing</a>
        <a class="dashboard-tile" href="#">Education &amp; Youth</a>
      </div>
    </section>

    <section id="overview" aria-label="Data and summary">
      <div class="container content-grid">
        <div class="graphic-placeholder" role="img" aria-label="Graphic and data visualization placeholder">
          Select a dashboard above to begin exploring data.
        </div>

        <div class="insight-column">
          <p class="insight-text">
            Explore trusted public data, compare trends, and surface key community needs.
          </p>
          <div class="datapoint-box"><span class="datapoint-label">NYS Counties Covered</span><span class="datapoint-value">62</span></div>
          <div class="datapoint-box"><span class="datapoint-label">Data Sources</span><span class="datapoint-value">4</span></div>
        </div>
      </div>
    </section>
  </main>
</body>

</html>
