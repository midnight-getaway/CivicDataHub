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
    <section aria-label="Search section">
      <div class="container">
        <div class="search-placeholder">Search</div>
      </div>
    </section>

    <section id="dashboards" aria-label="Dashboard shortcuts">
      <div class="container dashboard-grid">
        <a class="dashboard-tile" href="#">Dashboard</a>
        <a class="dashboard-tile" href="#">Dashboard</a>
        <a class="dashboard-tile" href="#">Dashboard</a>
        <a class="dashboard-tile" href="#">Dashboard</a>
      </div>
    </section>

    <section id="overview" aria-label="Data and summary">
      <div class="container content-grid">
        <div class="graphic-placeholder" role="img" aria-label="Graphic and data visualization placeholder">
          Graphic/Data Visualization
        </div>

        <div class="insight-column">
          <p class="insight-text">
            Explore trusted public data, compare trends, and surface key community needs.
          </p>
          <div class="datapoint-box">Datapoint</div>
          <div class="datapoint-box">Datapoint</div>
        </div>
      </div>
    </section>
  </main>
</body>

</html>
