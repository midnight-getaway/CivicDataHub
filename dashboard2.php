<?php
session_start();
require_once 'db_connect.php';

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
  <title>Housing &amp; Homelessness | Civic Data Hub</title>
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
      <h1>Housing &amp; Homelessness Dashboard</h1>
      <p class="muted-text">Housing affordability by county alongside fixed regional homelessness trends for Capital Region CoC-518.</p>

      <div class="filter-bar">
        <div class="filter-group">
          <label for="county-select">County (Housing Section Only)</label>
          <select id="county-select">
            <?php foreach ($counties as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $c['county_name'] === 'Albany' ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['county_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group">
          <label for="year-start">From Year</label>
          <input id="year-start" type="range" min="2019" max="2024" value="2019" />
          <span class="muted-text" id="year-start-label">2019</span>
        </div>

        <div class="filter-group">
          <label for="year-end">To Year</label>
          <input id="year-end" type="range" min="2019" max="2024" value="2024" />
          <span class="muted-text" id="year-end-label">2024</span>
        </div>

        <div class="filter-group">
          <label for="shelter-toggle">Shelter View</label>
          <select id="shelter-toggle">
            <option value="total" selected>Total</option>
            <option value="sheltered">Sheltered Only</option>
            <option value="unsheltered">Unsheltered Only</option>
          </select>
        </div>
      </div>

      <div class="stat-cards">
        <div class="stat-card">
          <span class="stat-label">Housing Cost Burden (Latest Year)</span>
          <span class="stat-value" id="stat-housing-burden">-</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">CoC-518 Total PIT (Latest Year)</span>
          <span class="stat-value" id="stat-coc-total">-</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Year-over-Year Change (CoC-518)</span>
          <span class="stat-value" id="stat-coc-yoy">-</span>
        </div>
      </div>

      <section class="chart-card" style="margin-bottom: 1.5rem;">
        <h2>Housing Affordability (County-Filterable)</h2>
        <p class="muted-text">ACS 5-year estimate: percent of households spending 30%+ of income on housing.</p>
        <canvas id="housing-trend-chart"></canvas>
      </section>

      <section class="chart-card" style="margin-bottom: 1.5rem;">
        <h2>Homelessness (Fixed to Capital Region CoC-518)</h2>
        <p class="muted-text">Data shown for Capital Region CoC-518 - not filterable by county.</p>
        <div class="chart-grid">
          <div class="chart-card">
            <h2>Sheltered vs Unsheltered PIT Counts</h2>
            <canvas id="homeless-grouped-chart"></canvas>
          </div>
          <div class="chart-card">
            <h2>Total PIT Trend: CoC-518 vs NYS</h2>
            <canvas id="pit-trend-chart"></canvas>
          </div>
        </div>
      </section>

      <p class="muted-text" style="margin-top: 2rem; font-size: 0.8rem;">
        Sources: U.S. Census Bureau ACS 5-Year Estimates (housing cost burden by county); HUD CoC Homeless Populations and Subpopulations Reports (CoC-518 PIT counts); HUD AHAR (New York State context).
      </p>
    </div>
  </main>

  <script>
    const housingCtx = document.getElementById('housing-trend-chart').getContext('2d');
    const groupedCtx = document.getElementById('homeless-grouped-chart').getContext('2d');
    const pitTrendCtx = document.getElementById('pit-trend-chart').getContext('2d');

    const housingTrendChart = new Chart(housingCtx, {
      type: 'line',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: {
          y: { beginAtZero: false, title: { display: true, text: 'Percent of households (30%+ cost burden)' } }
        }
      }
    });

    const homelessGroupedChart = new Chart(groupedCtx, {
      type: 'bar',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
      }
    });

    const pitTrendChart = new Chart(pitTrendCtx, {
      type: 'line',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: false } }
      }
    });

    function getRange() {
      const startInput = document.getElementById('year-start');
      const endInput = document.getElementById('year-end');

      let start = parseInt(startInput.value, 10);
      let end = parseInt(endInput.value, 10);

      if (start > end) {
        if (document.activeElement === startInput) {
          end = start;
          endInput.value = String(end);
        } else {
          start = end;
          startInput.value = String(start);
        }
      }

      document.getElementById('year-start-label').textContent = String(start);
      document.getElementById('year-end-label').textContent = String(end);
      return { start, end };
    }

    function fetchDashboard2() {
      const countyId = document.getElementById('county-select').value;
      const shelterView = document.getElementById('shelter-toggle').value;
      const range = getRange();
      const url = `api/dashboard2.php?county_id=${countyId}&year_start=${range.start}&year_end=${range.end}`;

      fetch(url)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            console.error(data.error);
            return;
          }
          updateStatCards(data.stat_cards);
          updateHousingTrend(data.housing);
          updateGroupedHomeless(data.homelessness, shelterView);
          updatePitTrend(data.homelessness);
        })
        .catch(err => console.error('Fetch error:', err));
    }

    function updateStatCards(stats) {
      document.getElementById('stat-housing-burden').textContent = `${stats.housing_cost_burden}%`;
      document.getElementById('stat-coc-total').textContent = stats.coc_total_pit.toLocaleString();

      const sign = stats.coc_yoy_change > 0 ? '+' : '';
      document.getElementById('stat-coc-yoy').textContent = `${sign}${stats.coc_yoy_change.toLocaleString()} (${sign}${stats.coc_yoy_pct}%)`;
    }

    function updateHousingTrend(housing) {
      housingTrendChart.data.labels = housing.years;
      housingTrendChart.data.datasets = [{
        label: `${housing.county} County`,
        data: housing.cost_burden_rate,
        borderColor: '#233dff',
        backgroundColor: '#233dff33',
        tension: 0.3,
        fill: false
      }];
      housingTrendChart.update();
    }

    function updateGroupedHomeless(homelessness, shelterView) {
      homelessGroupedChart.data.labels = homelessness.years;

      if (shelterView === 'sheltered') {
        homelessGroupedChart.data.datasets = [{
          label: 'Sheltered',
          data: homelessness.sheltered,
          backgroundColor: '#16a34a99'
        }];
      } else if (shelterView === 'unsheltered') {
        homelessGroupedChart.data.datasets = [{
          label: 'Unsheltered',
          data: homelessness.unsheltered,
          backgroundColor: '#dc262699'
        }];
      } else {
        homelessGroupedChart.data.datasets = [
          {
            label: 'Sheltered',
            data: homelessness.sheltered,
            backgroundColor: '#16a34a99'
          },
          {
            label: 'Unsheltered',
            data: homelessness.unsheltered,
            backgroundColor: '#dc262699'
          }
        ];
      }

      homelessGroupedChart.update();
    }

    function updatePitTrend(homelessness) {
      pitTrendChart.data.labels = homelessness.years;
      pitTrendChart.data.datasets = [
        {
          label: 'Capital Region CoC-518',
          data: homelessness.total_coc,
          borderColor: '#233dff',
          backgroundColor: '#233dff22',
          tension: 0.25,
          fill: false
        },
        {
          label: 'New York State (AHAR)',
          data: homelessness.total_nys,
          borderColor: '#ea580c',
          backgroundColor: '#ea580c22',
          borderDash: [6, 4],
          tension: 0.25,
          fill: false
        }
      ];
      pitTrendChart.update();
    }

    document.getElementById('county-select').addEventListener('change', fetchDashboard2);
    document.getElementById('year-start').addEventListener('input', fetchDashboard2);
    document.getElementById('year-end').addEventListener('input', fetchDashboard2);
    document.getElementById('shelter-toggle').addEventListener('change', fetchDashboard2);

    fetchDashboard2();
  </script>
</body>

</html>
