<?php
/**
 * dashboard3.php — Health & Wellbeing dashboard page with county comparisons and ranking views.
 *
 * Dependencies: Session support, db_connect.php, includes/header.php, Chart.js CDN.
 * Data sources: counties table (page bootstrapping), api/dashboard3.php, api/save_view.php.
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

// Start session so auth-aware actions (save view) can be gated correctly.
session_start();
session_write_close(); // Release the session lock immediately
// Load shared DB connection for county filter bootstrapping.
require_once 'db_connect.php';
$is_embed = (isset($_GET['embed']) && $_GET['embed'] === '1');

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
  <title>Civic Data Hub | Health & Wellbeing</title>
  <link rel="icon" href="assets/favicon.ico" sizes="any" />
  <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
  <link rel="icon" type="image/png" href="assets/favicon.png" />
  <link rel="apple-touch-icon" href="assets/favicon.png" />
  <link rel="stylesheet" href="styles.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>

<body class="<?= $is_embed ? 'embed-preview' : '' ?>">
  <?php if (!$is_embed) require 'includes/header.php'; ?>

  <main class="dashboard-layout">
    <div class="container">
      <h1>Health & Wellbeing Dashboard</h1>
      <p class="muted-text">Chronic disease prevalence, mental health indicators, disability rates, and opioid mortality by county.</p>

      <!-- ── Filter Bar ─────────────────────── -->
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
          <label for="year-select">Health Data Year</label>
          <select id="year-select">
            <!-- CDC PLACES data is 2023 -->
            <option value="2023" selected>2023</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="ranking-select">Rank by</label>
          <select id="ranking-select">
            <option value="DIABETES">Diabetes</option>
            <option value="DEPRESSION">Depression</option>
            <option value="MHLTH">Mental Distress</option>
            <option value="OBESITY">Obesity</option>
            <option value="DISABILITY">Any Disability</option>
            <option value="CSMOKING">Smoking</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="compare-select">Compare Opioid Trend With</label>
          <select id="compare-select">
            <option value="">None</option>
            <?php foreach ($counties as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['county_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group view-actions">
          <button id="save-view-btn" class="btn">Save View</button>
          <button id="share-view-btn" class="btn">Share this View</button>
        </div>
      </div>

      <!-- ── Stat Cards ──────────────────────── -->
      <div class="stat-cards">
        <div class="stat-card">
          <span class="stat-label">Top Health Risk</span>
          <span class="stat-value" id="stat-risk">—</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Opioid Deaths (Latest)</span>
          <span class="stat-value" id="stat-opioid">—</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Disability Prevalence</span>
          <span class="stat-value" id="stat-disability">—</span>
        </div>
      </div>

      <!-- ── Charts ──────────────────────────── -->
      <div class="chart-grid">
        <div class="chart-card">
          <h2>Health Indicators (%)</h2>
          <div class="chart-canvas-wrap"><canvas id="health-chart"></canvas></div>
        </div>
        <div class="chart-card">
          <h2>Opioid Mortality Trend</h2>
          <div class="chart-canvas-wrap"><canvas id="opioid-chart"></canvas></div>
        </div>
      </div>

      <div class="chart-grid" style="margin-top: 1.5rem; grid-template-columns: 1fr;">
        <div class="chart-card">
          <h2 id="ranking-title">County Ranking</h2>
          <div style="height: 400px; overflow-y: auto;">
            <canvas id="ranking-chart"></canvas>
          </div>
        </div>
      </div>

      <p class="muted-text" style="margin-top: 2rem; font-size: 0.8rem;">
        Sources: CDC PLACES - Local Data for Better Health; NYS Open Data - Opioid-Related Deaths by County.
      </p>
    </div>
  </main>
  <?php if ($is_embed): ?>
    <style>
      .embed-preview .dashboard-layout { padding: 0.5rem 0.6rem; }
      .embed-preview .dashboard-layout h1,
      .embed-preview .dashboard-layout > .container > .muted-text,
      .embed-preview .filter-bar,
      .embed-preview .stat-cards,
      .embed-preview .chart-grid .chart-card:nth-child(2),
      .embed-preview .chart-grid[style*="margin-top"],
      .embed-preview .dashboard-layout > .container > p.muted-text { display: none; }
      .embed-preview .chart-grid { grid-template-columns: 1fr; gap: 0; }
      .embed-preview .chart-card { border: none; box-shadow: none; padding: 0.35rem; }
      .embed-preview #health-chart { max-height: 320px; }
    </style>
  <?php endif; ?>

  <script>
    /**
     * dashboard3.php — Health dashboard client-side controller.
     * Charts: healthChart (bar), opioidChart (line), rankingChart (horizontal bar).
     * Filters: county-select, year-select, ranking-select, compare-select.
     * Dependencies: Chart.js v4, api/dashboard3.php, api/save_view.php.
     */
    // Shared chart color palette for primary and comparison series.
    const COLORS = ['#233dff', '#dc2626', '#16a34a', '#ea580c', '#8b5cf6', '#eab308'];

    // Health grouped bar chart
    const healthCtx = document.getElementById('health-chart').getContext('2d');
    const healthChart = new Chart(healthCtx, {
      type: 'bar',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Prevalence (%)' } }
        }
      }
    });

    // Opioid trend line chart
    const opioidCtx = document.getElementById('opioid-chart').getContext('2d');
    const opioidChart = new Chart(opioidCtx, {
      type: 'line',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Opioid Deaths' } }
        }
      }
    });

    // Ranking horizontal bar chart
    const rankingCtx = document.getElementById('ranking-chart').getContext('2d');
    const rankingChart = new Chart(rankingCtx, {
      type: 'bar',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y', // Horizontal bar chart
        plugins: { legend: { display: false } },
        scales: {
          x: { beginAtZero: true }
        }
      }
    });

    // Fetch API payload and redraw all stat cards/charts.
    function fetchDashboard() {
      const countyId  = document.getElementById('county-select').value;
      const year      = document.getElementById('year-select').value;
      const compareId = document.getElementById('compare-select').value;
      const rankingId = document.getElementById('ranking-select').value;

      let url = `api/dashboard3.php?county_id=${countyId}&year=${year}&ranking_measure=${rankingId}`;
      if (compareId) url += `&compare_ids=${compareId}`;

      // Update browser URL silently so users can copy-paste it
      const newUrl = new URL(window.location);
      newUrl.searchParams.set('county_id', countyId);
      newUrl.searchParams.set('year', year);
      newUrl.searchParams.set('ranking_measure', rankingId);
      if (compareId) {
        newUrl.searchParams.set('compare_ids', compareId);
      } else {
        newUrl.searchParams.delete('compare_ids');
      }
      window.history.replaceState({}, '', newUrl);

      // API call: api/dashboard3.php returns measures, trends, ranking, and stat cards.
      fetch(url)
        .then(res => res.json())
        .then(data => {
          if (data.error) { console.error(data.error); return; }
          updateStatCards(data.stat_cards);
          updateHealthChart(data);
          updateOpioidChart(data);
          updateRankingChart(data, rankingId);
        })
        .catch(err => console.error('Fetch error:', err));
    }

    // ── Update stat cards ────────────────────
    function updateStatCards(stats) {
      document.getElementById('stat-risk').textContent = stats.top_risk_name + ' (' + stats.top_risk_value + ')';
      document.getElementById('stat-opioid').textContent = stats.opioid_mortality;
      document.getElementById('stat-disability').textContent = stats.disability_rate;
    }

    // ── Update health chart ───────────────────
    function updateHealthChart(data) {
      const labels = data.health_measures.map(m => m.measure_name);
      
      const datasets = [{
        label: data.county,
        data: data.health_measures.map(m => m.data_value),
        backgroundColor: COLORS[0] + 'cc',
        borderColor: COLORS[0],
        borderWidth: 1
      }];

      if (data.comparisons) {
        data.comparisons.forEach((comp, i) => {
          datasets.push({
            label: comp.county,
            data: comp.health_measures.map(m => m.data_value),
            backgroundColor: COLORS[i + 1] + 'cc',
            borderColor: COLORS[i + 1],
            borderWidth: 1
          });
        });
      }

      healthChart.data.labels = labels;
      healthChart.data.datasets = datasets;
      healthChart.update();
    }

    // ── Update opioid chart ────────────────────
    function updateOpioidChart(data) {
      const trend = data.opioid_trend;
      
      const datasets = [{
        label: data.county,
        data: trend.deaths,
        borderColor: COLORS[0],
        backgroundColor: COLORS[0] + '33',
        tension: 0.3,
        fill: false
      }];

      if (data.comparisons) {
        data.comparisons.forEach((comp, i) => {
          datasets.push({
            label: comp.county,
            data: comp.opioid_trend.deaths,
            borderColor: COLORS[i + 1],
            borderDash: [5, 5],
            tension: 0.3,
            fill: false
          });
        });
      }

      opioidChart.data.labels = trend.years;
      opioidChart.data.datasets = datasets;
      opioidChart.update();
    }
    
    // ── Update ranking chart ───────────────────
    function updateRankingChart(data, rankingId) {
      const rankingSelect = document.getElementById('ranking-select');
      const measureName = rankingSelect.options[rankingSelect.selectedIndex].text;
      document.getElementById('ranking-title').textContent = measureName + ' by County (%)';
      
      const labels = data.ranking.map(r => r.county);
      const values = data.ranking.map(r => r.value);
      
      // Highlight the selected county
      const bgColors = labels.map(label => label === data.county ? COLORS[3] : COLORS[0] + '99');

      rankingChart.data.labels = labels;
      rankingChart.data.datasets = [{
        data: values,
        backgroundColor: bgColors
      }];
      
      // Resize container based on data length to prevent squishing
      const container = document.getElementById('ranking-chart').parentNode;
      container.style.height = (labels.length * 20) + 'px';
      
      rankingChart.update();
    }

    // ── Event listeners ──────────────────────
    document.getElementById('county-select').addEventListener('change', fetchDashboard);
    document.getElementById('year-select').addEventListener('change', fetchDashboard);
    document.getElementById('ranking-select').addEventListener('change', fetchDashboard);
    document.getElementById('compare-select').addEventListener('change', fetchDashboard);

    // Copy the current filter URL so this exact dashboard state can be shared.
    async function copyCurrentViewLink() {
      const shareUrl = window.location.href;
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(shareUrl);
        } else {
          const tempInput = document.createElement('textarea');
          tempInput.value = shareUrl;
          document.body.appendChild(tempInput);
          tempInput.select();
          document.execCommand('copy');
          document.body.removeChild(tempInput);
        }
        alert('Share link copied to clipboard.');
      } catch (err) {
        console.error('Copy failed:', err);
        prompt('Copy this link:', shareUrl);
      }
    }

    document.getElementById('share-view-btn').addEventListener('click', copyCurrentViewLink);

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
        year: document.getElementById('year-select').value,
        ranking_measure: document.getElementById('ranking-select').value,
        compare_ids: document.getElementById('compare-select').value
      };

      fetch('api/save_view.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          view_name: viewName,
          dashboard_url: 'dashboard3.php',
          dashboard_name: 'Health & Wellbeing',
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
      if (params.has('year')) document.getElementById('year-select').value = params.get('year');
      if (params.has('ranking_measure')) document.getElementById('ranking-select').value = params.get('ranking_measure');
      if (params.has('compare_ids')) document.getElementById('compare-select').value = params.get('compare_ids');
    }

    // Initial load
    initFiltersFromUrl();
    fetchDashboard();
  </script>
</body>

</html>
