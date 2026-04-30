<?php
session_start();
session_write_close(); // Release the session lock immediately
require_once 'db_connect.php';

// Get all counties for the dropdown
$counties = [];
if ($pdo) {
    $counties = $pdo->query("SELECT id, county_name FROM counties ORDER BY county_name")->fetchAll();
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Economic Hardship | Civic Data Hub</title>
  <link rel="icon" href="assets/favicon.ico" sizes="any" />
  <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
  <link rel="icon" type="image/png" href="assets/favicon.png" />
  <link rel="apple-touch-icon" href="assets/favicon.png" />
  <link rel="stylesheet" href="styles.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>

<body>
  <?php require 'includes/header.php'; ?>

  <main class="dashboard-layout">
    <div class="container">
      <h1>Economic Hardship Dashboard</h1>
      <p class="muted-text">Poverty rates, median household income, unemployment, and SNAP enrollment across New York State counties.</p>

      <!-- Filter Bar -->
      <div class="filter-bar">
        <div class="filter-group">
          <label for="county-select">County</label>
          <select id="county-select">
            <?php foreach ($counties as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $c['county_name'] === 'Albany' ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['county_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group">
          <label for="year-start">From</label>
          <select id="year-start">
            <?php for ($y = 2019; $y <= 2023; $y++): ?>
              <option value="<?= $y ?>"><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="filter-group">
          <label for="year-end">To</label>
          <select id="year-end">
            <?php for ($y = 2019; $y <= 2023; $y++): ?>
              <option value="<?= $y ?>" <?= $y === 2023 ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="filter-group">
          <label for="metric-select">Metric</label>
          <select id="metric-select">
            <option value="poverty_rate">Poverty Rate (%)</option>
            <option value="median_income">Median Income ($)</option>
            <option value="unemployment_rate">Unemployment Rate (%)</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="compare-select">Compare With</label>
          <select id="compare-select">
            <option value="">None</option>
            <?php foreach ($counties as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['county_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group" style="justify-content: flex-end;">
          <button id="save-view-btn" class="btn" style="padding: 0.45rem 1rem;">Save View</button>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="stat-cards">
        <div class="stat-card">
          <span class="stat-label">Poverty Rate</span>
          <span class="stat-value" id="stat-poverty">—</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Median Income</span>
          <span class="stat-value" id="stat-income">—</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Unemployment</span>
          <span class="stat-value" id="stat-unemployment">—</span>
        </div>
      </div>

      <!-- Charts -->
      <div class="chart-grid">
        <div class="chart-card">
          <h2>Trend Over Time</h2>
          <canvas id="trend-chart"></canvas>
        </div>
        <div class="chart-card">
          <h2>SNAP Enrollment</h2>
          <canvas id="snap-chart"></canvas>
        </div>
      </div>

      <p class="muted-text" style="margin-top: 2rem; font-size: 0.8rem;">
        Sources: U.S. Census Bureau ACS 5-Year Estimates (2019–2023); NYS OTDA SNAP Caseload Statistics via data.ny.gov.
      </p>
    </div>
  </main>

  <script>
    // Chart.js setup
    const COLORS = ['#233dff', '#dc2626', '#16a34a', '#ea580c'];

    // Trend line chart
    const trendCtx = document.getElementById('trend-chart').getContext('2d');
    const trendChart = new Chart(trendCtx, {
      type: 'line',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: {
          y: { beginAtZero: false }
        }
      }
    });

    // SNAP stacked bar chart
    const snapCtx = document.getElementById('snap-chart').getContext('2d');
    const snapChart = new Chart(snapCtx, {
      type: 'bar',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: {
          x: { stacked: true },
          y: { stacked: true, beginAtZero: true }
        }
      }
    });

    // Data fetching
    function fetchDashboard() {
      const countyId  = document.getElementById('county-select').value;
      const yearStart = document.getElementById('year-start').value;
      const yearEnd   = document.getElementById('year-end').value;
      const metric    = document.getElementById('metric-select').value;
      const compareId = document.getElementById('compare-select').value;

      let url = `api/dashboard1.php?county_id=${countyId}&year_start=${yearStart}&year_end=${yearEnd}`;
      if (compareId) url += `&compare_ids=${compareId}`;

      // Update browser URL silently so users can copy-paste it
      const newUrl = new URL(window.location);
      newUrl.searchParams.set('county_id', countyId);
      newUrl.searchParams.set('year_start', yearStart);
      newUrl.searchParams.set('year_end', yearEnd);
      newUrl.searchParams.set('metric', metric);
      if (compareId) {
        newUrl.searchParams.set('compare_ids', compareId);
      } else {
        newUrl.searchParams.delete('compare_ids');
      }
      window.history.replaceState({}, '', newUrl);

      fetch(url)
        .then(res => res.json())
        .then(data => {
          if (data.error) { console.error(data.error); return; }
          updateStatCards(data.stat_cards);
          updateTrendChart(data, metric);
          updateSnapChart(data);
        })
        .catch(err => console.error('Fetch error:', err));
    }

    // Update stat cards
    function updateStatCards(stats) {
      document.getElementById('stat-poverty').textContent = stats.poverty_rate + '%';
      document.getElementById('stat-income').textContent = '$' + stats.median_income.toLocaleString();
      document.getElementById('stat-unemployment').textContent = stats.unemployment_rate + '%';
    }

    // Update trend chart
    function updateTrendChart(data, metric) {
      const labels = {
        poverty_rate: 'Poverty Rate (%)',
        median_income: 'Median Household Income ($)',
        unemployment_rate: 'Unemployment Rate (%)'
      };

      trendChart.data.labels = data.years;
      trendChart.data.datasets = [{
        label: data.county,
        data: data[metric],
        borderColor: COLORS[0],
        backgroundColor: COLORS[0] + '33',
        tension: 0.3,
        fill: false
      }];

      // Add comparison counties
      if (data.comparisons) {
        data.comparisons.forEach((comp, i) => {
          trendChart.data.datasets.push({
            label: comp.county,
            data: comp[metric],
            borderColor: COLORS[i + 1],
            borderDash: [5, 5],
            tension: 0.3,
            fill: false
          });
        });
      }

      trendChart.options.scales.y.title = { display: true, text: labels[metric] };
      trendChart.update();
    }

    // Update SNAP chart
    function updateSnapChart(data) {
      const snap = data.snap;
      snapChart.data.labels = snap.years;
      snapChart.data.datasets = [
        {
          label: 'Non-TA SNAP',
          data: snap.snap_persons.map((total, i) => total - snap.ta_persons[i]),
          backgroundColor: '#233dff99'
        },
        {
          label: 'Temporary Assistance',
          data: snap.ta_persons,
          backgroundColor: '#dc262699'
        }
      ];
      snapChart.update();
    }

    // Event listeners
    document.getElementById('county-select').addEventListener('change', fetchDashboard);
    document.getElementById('year-start').addEventListener('change', fetchDashboard);
    document.getElementById('year-end').addEventListener('change', fetchDashboard);
    document.getElementById('metric-select').addEventListener('change', fetchDashboard);
    document.getElementById('compare-select').addEventListener('change', fetchDashboard);

    // Save view logic
    document.getElementById('save-view-btn').addEventListener('click', () => {
      <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
      <?php endif; ?>

      const viewName = prompt("Enter a name for this saved view:");
      if (!viewName) return;

      const filters = {
        county_id: document.getElementById('county-select').value,
        year_start: document.getElementById('year-start').value,
        year_end: document.getElementById('year-end').value,
        metric: document.getElementById('metric-select').value,
        compare_ids: document.getElementById('compare-select').value
      };

      fetch('api/save_view.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          view_name: viewName,
          dashboard_url: 'dashboard1.php',
          dashboard_name: 'Economic Hardship',
          filters: filters
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("View saved successfully!");
        } else {
          alert("Error saving view: " + (data.error || "Unknown error"));
        }
      })
      .catch(err => {
        console.error(err);
        alert("An error occurred while saving.");
      });
    });

    // Initialize filters from URL parameters
    function initFiltersFromUrl() {
      const params = new URLSearchParams(window.location.search);
      if (params.has('county_id')) document.getElementById('county-select').value = params.get('county_id');
      if (params.has('year_start')) document.getElementById('year-start').value = params.get('year_start');
      if (params.has('year_end')) document.getElementById('year-end').value = params.get('year_end');
      if (params.has('metric')) document.getElementById('metric-select').value = params.get('metric');
      if (params.has('compare_ids')) document.getElementById('compare-select').value = params.get('compare_ids');
    }

    // Initial load
    initFiltersFromUrl();
    fetchDashboard();
  </script>
</body>

</html>
