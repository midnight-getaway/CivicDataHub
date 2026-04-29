<?php
session_start();
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
  <title>Health & Wellbeing | Civic Data Hub</title>
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
          <canvas id="health-chart"></canvas>
        </div>
        <div class="chart-card">
          <h2>Opioid Mortality Trend</h2>
          <canvas id="opioid-chart"></canvas>
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

  <script>
    // ── Chart.js setup ───────────────────────
    const COLORS = ['#233dff', '#dc2626', '#16a34a', '#ea580c', '#8b5cf6', '#eab308'];

    // Health grouped bar chart
    const healthCtx = document.getElementById('health-chart').getContext('2d');
    const healthChart = new Chart(healthCtx, {
      type: 'bar',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
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

    // ── Data fetching ────────────────────────
    function fetchDashboard() {
      const countyId  = document.getElementById('county-select').value;
      const year      = document.getElementById('year-select').value;
      const compareId = document.getElementById('compare-select').value;
      const rankingId = document.getElementById('ranking-select').value;

      let url = `api/dashboard3.php?county_id=${countyId}&year=${year}&ranking_measure=${rankingId}`;
      if (compareId) url += `&compare_ids=${compareId}`;

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

    // Initial load
    fetchDashboard();
  </script>
</body>

</html>
