<?php
/**
 * dashboard2.php — Housing & Homelessness dashboard with county housing burden and regional homelessness trends.
 *
 * Dependencies: Session support, db_connect.php, includes/header.php, Chart.js CDN.
 * Data sources: counties table (page bootstrapping), api/dashboard2.php, api/save_view.php.
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

// Start session (determine save view behavior), release session lock
session_start();
session_write_close();
// Load shared DB connection for county filter bootstrapping.
require_once 'db_connect.php';
$is_embed = (isset($_GET['embed']) && $_GET['embed'] === '1');

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
  <title>Civic Data Hub | Housing & Homelessness</title>
  <link rel="icon" href="assets/favicon.ico" sizes="any" />
  <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png" />
  <link rel="icon" type="image/png" href="assets/favicon.png" />
  <link rel="apple-touch-icon" href="assets/favicon.png" />
  <link rel="stylesheet" href="styles.css?v=1" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>

<body class="<?= $is_embed ? 'embed-preview' : '' ?>">
  <?php if (!$is_embed) require 'includes/header.php'; ?>

  <main class="dashboard-layout">
    <div class="container">
      <h1>Housing & Homelessness Dashboard</h1>
      <p class="muted-text">Housing affordability by county alongside regional homelessness trends for New York State.</p>

      <section class="dashboard-section">
        <h2 class="section-title">Housing Affordability</h2>
        <p class="muted-text">Shows the share of households spending more than 30% of their income on housing costs. Filter by county and year range.</p>

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
            <label for="housing-year-start">Year Range (From)</label>
            <select id="housing-year-start">
              <?php for ($y = 2019; $y <= 2023; $y++): ?>
                <option value="<?= $y ?>"><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="filter-group">
            <label for="housing-year-end">Year Range (To)</label>
            <select id="housing-year-end">
              <?php for ($y = 2019; $y <= 2023; $y++): ?>
                <option value="<?= $y ?>" <?= $y === 2023 ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="filter-group view-actions">
            <button class="btn save-view-btn">Save View</button>
            <button class="btn share-view-btn">Share this View</button>
          </div>
        </div>

        <div class="stat-cards section-stat-cards-single">
          <div class="stat-card">
            <span class="stat-label">Housing Cost Burden (30%+)
              <span class="help-icon" data-tooltip="The share of households in the selected county spending more than 30% of their income on housing costs. Source: U.S. Census Bureau ACS 5-Year Estimates." aria-label="Housing cost burden definition" tabindex="0">ⓘ</span>
            </span>
            <span class="stat-value" id="stat-housing-rate">—</span>
          </div>
        </div>

        <div class="chart-grid">
          <div class="chart-card">
            <h2>Households Spending 30%+ of Income on Housing</h2>
            <p class="muted-text">By county — U.S. Census Bureau ACS 5-Year Estimate</p>
            <div class="chart-canvas-wrap"><canvas id="housing-chart"></canvas></div>
          </div>
          <div class="chart-card">
            <h2>How does this county compare?</h2>
            <p class="muted-text">Among all 62 NYS counties, latest available year</p>
            <div class="compare-rank" id="housing-rank-text">Ranks — of 62 — higher number = lower burden</div>
            <div class="compare-subtext" id="housing-rank-subtext">for housing cost burden in —</div>

            <div class="mini-stat-grid">
              <div class="mini-stat">
                <span class="mini-stat-label" id="housing-county-label">Selected county</span>
                <span class="mini-stat-value" id="housing-county-rate">—</span>
              </div>
              <div class="mini-stat">
                <span class="mini-stat-label">NYS Average</span>
                <span class="mini-stat-value" id="housing-nys-rate">—</span>
              </div>
            </div>
            <div class="mini-stat-note" id="housing-vs-avg-note">—</div>

            <div class="distribution-wrap">
              <div class="distribution-track">
                <div class="distribution-marker" id="housing-distribution-marker" aria-hidden="true"></div>
              </div>
              <div class="distribution-labels">
                <span id="housing-distribution-min">—</span>
                <span id="housing-distribution-max">—</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="dashboard-section">
        <h2 class="section-title">Homelessness by Region</h2>
        <p class="muted-text">
          Annual counts of people experiencing homelessness, collected by region. Select a region and year range to explore trends.
          <span class="help-icon" data-tooltip="Homelessness data is collected by region, not individual county. Each region is a federally designated planning area where local organizations coordinate shelter and housing services together. New York State has 24 of these regions." aria-label="Homelessness region definition" tabindex="0">ⓘ</span>
        </p>

        <div class="filter-bar">
          <div class="filter-group">
            <label for="coc-select">Region</label>
            <select id="coc-select">
              <option value="NY-503" selected>Albany City & County</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="homeless-year-start">Year Range (From)</label>
            <select id="homeless-year-start">
              <?php for ($y = 2019; $y <= 2023; $y++): ?>
                <option value="<?= $y ?>"><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="filter-group">
            <label for="homeless-year-end">Year Range (To)</label>
            <select id="homeless-year-end">
              <?php for ($y = 2019; $y <= 2023; $y++): ?>
                <option value="<?= $y ?>" <?= $y === 2023 ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="filter-group">
            <label for="homeless-view">Show:</label>
            <select id="homeless-view">
              <option value="total" selected>Total</option>
              <option value="sheltered">Sheltered only</option>
              <option value="unsheltered">Unsheltered only</option>
            </select>
          </div>

          <div class="filter-group view-actions">
            <button class="btn save-view-btn">Save View</button>
            <button class="btn share-view-btn">Share this View</button>
          </div>

        </div>

        <div class="stat-cards section-stat-cards-two">
          <div class="stat-card">
            <span class="stat-label">People Experiencing Homelessness — Selected Region (Latest Year)
              <span class="help-icon" data-tooltip="Total people counted on a single night in January during the annual federal homeless survey for this region." aria-label="Total homeless definition" tabindex="0">ⓘ</span>
            </span>
            <span class="stat-value" id="stat-homeless-total">—</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Change from Prior Year — Selected Region
              <span class="help-icon" data-tooltip="Change from the previous year's annual count. This is a one-night snapshot taken each January, not a full-year total." aria-label="Year over year change definition" tabindex="0">ⓘ</span>
            </span>
            <span class="stat-value" id="stat-yoy-change">—</span>
          </div>
        </div>

        <div class="chart-grid">
          <div class="chart-card">
            <h2 id="shelter-title">Sheltered vs. Unsheltered</h2>
            <p class="muted-text">Annual count for selected region</p>
            <div class="chart-canvas-wrap"><canvas id="shelter-chart"></canvas></div>
          </div>
          <div class="chart-card">
            <h2>Who is experiencing homelessness?</h2>
            <p class="muted-text" id="subpop-year-label">Selected region — latest year in range</p>
            <div class="subpop-grid">
              <div class="stat-card subpop-card">
                <span class="stat-label">Chronically Homeless
                  <span class="help-icon" data-tooltip="People who have been continuously homeless for at least a year, or repeatedly homeless, and have a disability." aria-label="Chronically homeless definition" tabindex="0">ⓘ</span>
                </span>
                <span class="stat-value" id="subpop-chronically">—</span>
              </div>
              <div class="stat-card subpop-card">
                <span class="stat-label">Homeless Veterans
                  <span class="help-icon" data-tooltip="Veterans experiencing homelessness, regardless of shelter status." aria-label="Homeless veterans definition" tabindex="0">ⓘ</span>
                </span>
                <span class="stat-value" id="subpop-veterans">—</span>
              </div>
              <div class="stat-card subpop-card">
                <span class="stat-label">Unaccompanied Youth
                  <span class="help-icon" data-tooltip="Young people under 25 experiencing homelessness without a parent or guardian." aria-label="Unaccompanied youth definition" tabindex="0">ⓘ</span>
                </span>
                <span class="stat-value" id="subpop-youth">—</span>
              </div>
              <div class="stat-card subpop-card">
                <span class="stat-label">People in Families
                  <span class="help-icon" data-tooltip="Adults and children experiencing homelessness as part of a family unit." aria-label="People in families definition" tabindex="0">ⓘ</span>
                </span>
                <span class="stat-value" id="subpop-families">—</span>
              </div>
            </div>
            <p class="muted-text" style="font-size:0.78rem; margin-top:0.6rem;">Subgroups are not mutually exclusive and do not sum to the total.</p>
          </div>
        </div>

        <div class="chart-grid" style="margin-top:1.5rem; grid-template-columns: 1fr;">
          <div class="chart-card">
            <h2>Total Homeless Population Over Time</h2>
            <p class="muted-text" id="trend-dynamic-subtitle">Indexed to 2019 baseline — shows percent change, not raw counts. Hover for actual numbers.</p>
            <div class="chart-canvas-wrap"><canvas id="trend-chart"></canvas></div>
          </div>
        </div>

        <div class="chart-grid" style="margin-top:1.5rem; grid-template-columns: 1fr;">
          <div class="chart-card">
            <h2 id="ranking-title">All NYS Regions Ranked by Total Homeless</h2>
            <p class="muted-text">Average annual count across selected year range</p>
            <div id="d2-ranking-container" style="max-height: 600px; overflow-y: auto; overflow-x: hidden;">
              <div id="d2-ranking-sizer" style="position: relative; width: 100%;"><canvas id="ranking-chart"></canvas></div>
            </div>
            <label for="exclude-nyc" class="ranking-toggle">
              <input type="checkbox" id="exclude-nyc" />
              Exclude New York City (its counts are much larger than other regions and may compress the chart scale)
            </label>
          </div>
        </div>
      </section>
      <p class="muted-text" style="margin-top: 1.25rem; font-size: 0.8rem;">
        Sources: U.S. Census Bureau ACS 5-Year Estimates (B25070); HUD Continuum of Care (CoC) Homeless Populations and Subpopulations Reports (PIT), New York State regions.
      </p>
    </div>
  </main>
  <?php if ($is_embed): ?>
    <style>
      .embed-preview .dashboard-layout { padding: 0.45rem 0.55rem; }
      .embed-preview .dashboard-layout h1,
      .embed-preview .dashboard-layout > .container > .muted-text,
      .embed-preview .dashboard-section:first-child,
      .embed-preview .dashboard-section .filter-bar,
      .embed-preview .dashboard-section .stat-cards,
      .embed-preview .dashboard-section .chart-grid:first-of-type,
      .embed-preview .dashboard-section .chart-grid:nth-of-type(3),
      .embed-preview .dashboard-section .chart-grid:nth-of-type(4),
      .embed-preview .dashboard-layout > .container > p.muted-text { display: none; }
      .embed-preview .dashboard-section {
        margin-top: 0;
        padding: 0;
        border: none;
        background: transparent;
      }
      .embed-preview .dashboard-section .section-title,
      .embed-preview .dashboard-section > .muted-text { display: none; }
      .embed-preview .dashboard-section .chart-grid { grid-template-columns: 1fr; gap: 0; margin-top: 0 !important; }
      .embed-preview .chart-card { border: none; box-shadow: none; padding: 0.3rem; }
      .embed-preview #trend-chart { max-height: 320px; }
    </style>
  <?php endif; ?>

  <script>
    /**
     * dashboard2.php — Housing/Homelessness dashboard client-side controller.
     * Charts: housingChart (line), shelterChart (stacked bar), trendChart (indexed line), rankingChart (horizontal bar).
     * Filters: county-select, housing-year-start/end, coc-select, homeless-year-start/end, homeless-view, exclude-nyc.
     * Dependencies: Chart.js v4, api/dashboard2.php, api/save_view.php.
     */
    const COLORS = ['#233dff', '#dc2626', '#16a34a', '#ea580c'];

    const housingChart = new Chart(document.getElementById('housing-chart').getContext('2d'), {
      type: 'line',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Rate (%)' } }
        }
      }
    });

    const shelterChart = new Chart(document.getElementById('shelter-chart').getContext('2d'), {
      type: 'bar',
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } },
        scales: {
          x: { stacked: true },
          y: { stacked: true, beginAtZero: true }
        }
      }
    });

    const trendChart = new Chart(document.getElementById('trend-chart').getContext('2d'), {
      type: 'line',
      plugins: [{
        id: 'trendAnnotations',
        beforeDatasetsDraw(chart) {
          const { ctx, chartArea, scales } = chart;
          if (!chartArea || !scales?.x || !scales?.y) return;

          const xScale = scales.x;
          const yScale = scales.y;
          const labels = chart.data.labels || [];
          const idx2020 = labels.findIndex(y => Number(y) === 2020);
          const idx2021 = labels.findIndex(y => Number(y) === 2021);

          ctx.save();

          // COVID-19 vertical band (2020-2021)
          if (idx2020 !== -1 && idx2021 !== -1) {
            const xStart = xScale.getPixelForValue(idx2020) - 14;
            const xEnd = xScale.getPixelForValue(idx2021) + 14;
            ctx.fillStyle = 'rgba(107, 114, 128, 0.12)';
            ctx.fillRect(xStart, chartArea.top, xEnd - xStart, chartArea.bottom - chartArea.top);

            ctx.fillStyle = '#6b7280';
            ctx.font = '12px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('COVID-19 period', (xStart + xEnd) / 2, chartArea.top + 14);
          }

          // 2019 baseline reference line at index 100
          const y100 = yScale.getPixelForValue(100);
          if (Number.isFinite(y100)) {
            ctx.strokeStyle = '#9ca3af';
            ctx.lineWidth = 1;
            ctx.setLineDash([4, 4]);
            ctx.beginPath();
            ctx.moveTo(chartArea.left, y100);
            ctx.lineTo(chartArea.right, y100);
            ctx.stroke();
            ctx.setLineDash([]);
          }

          // Fill the area between region and NYS lines, segment by segment.
          const regionMeta = chart.getDatasetMeta(0);
          const nysMeta = chart.getDatasetMeta(1);
          if (!regionMeta?.data?.length || !nysMeta?.data?.length) {
            ctx.restore();
            return;
          }

          const regionData = chart.data.datasets[0]?.data || [];
          const nysData = chart.data.datasets[1]?.data || [];
          const segCount = Math.min(regionMeta.data.length, nysMeta.data.length) - 1;

          for (let i = 0; i < segCount; i++) {
            const r1 = regionMeta.data[i];
            const r2 = regionMeta.data[i + 1];
            const n1 = nysMeta.data[i];
            const n2 = nysMeta.data[i + 1];
            if (!r1 || !r2 || !n1 || !n2) continue;

            const avgGap = (((Number(regionData[i]) || 0) - (Number(nysData[i]) || 0))
              + ((Number(regionData[i + 1]) || 0) - (Number(nysData[i + 1]) || 0))) / 2;

            ctx.beginPath();
            ctx.moveTo(r1.x, r1.y);
            ctx.lineTo(r2.x, r2.y);
            ctx.lineTo(n2.x, n2.y);
            ctx.lineTo(n1.x, n1.y);
            ctx.closePath();
            ctx.fillStyle = avgGap >= 0 ? 'rgba(220, 80, 60, 0.15)' : 'rgba(60, 160, 120, 0.15)';
            ctx.fill();
          }

          ctx.restore();
        },
        afterDatasetsDraw(chart) {
          const { ctx, chartArea } = chart;
          const labels = chart.data.labels || [];
          const regionDs = chart.data.datasets[0] || {};
          const nysDs = chart.data.datasets[1] || {};
          const regionVals = regionDs.data || [];
          const nysVals = nysDs.data || [];
          if (!labels.length || !regionVals.length || !nysVals.length) return;

          const idx = labels.length - 1;
          const year = labels[idx];
          const regionChange = Math.round((Number(regionVals[idx]) || 100) - 100);
          const nysChange = Math.round((Number(nysVals[idx]) || 100) - 100);
          const regionName = regionDs.pointLabel || regionDs.label || 'Selected Region';

          const regionText = `${year}: ${regionName} ${regionChange >= 0 ? '+' : ''}${regionChange}%`;
          const middleText = ' · ';
          const nysText = `New York State ${nysChange >= 0 ? '+' : ''}${nysChange}% from 2019 baseline`;

          ctx.save();
          ctx.font = '12px sans-serif';
          ctx.textBaseline = 'top';

          const totalWidth =
            ctx.measureText(regionText).width +
            ctx.measureText(middleText).width +
            ctx.measureText(nysText).width;

          let x = chartArea.right - totalWidth - 6;
          if (x < chartArea.left + 6) x = chartArea.left + 6;
          const y = chartArea.top + 6;

          ctx.fillStyle = regionDs.borderColor || '#16a34a';
          ctx.fillText(regionText, x, y);
          x += ctx.measureText(regionText).width;

          ctx.fillStyle = '#4b5563';
          ctx.fillText(middleText, x, y);
          x += ctx.measureText(middleText).width;

          ctx.fillStyle = nysDs.borderColor || '#ea580c';
          ctx.fillText(nysText, x, y);

          ctx.restore();
        }
      }],
      data: { labels: [], datasets: [] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: true },
          tooltip: {
            callbacks: {
              title(items) {
                return `Year: ${items[0].label}`;
              },
              label(context) {
                const ds = context.dataset || {};
                const indexed = Number(context.parsed.y || 0).toFixed(1);
                const raw = ds.rawValues && ds.rawValues[context.dataIndex] != null
                  ? Number(ds.rawValues[context.dataIndex]).toLocaleString()
                  : '—';
                const label = ds.pointLabel || ds.label || 'Series';
                return `${label}: ${indexed} (${raw} people)`;
              },
              afterBody(items) {
                if (!items || !items.length) return '';
                const i = items[0].dataIndex;
                const dsRegion = items[0].chart.data.datasets[0] || {};
                const dsNys = items[0].chart.data.datasets[1] || {};
                const regionName = dsRegion.pointLabel || dsRegion.label || 'Selected region';
                const regionVal = Number((dsRegion.data || [])[i] || 0);
                const nysVal = Number((dsNys.data || [])[i] || 0);
                const gap = regionVal - nysVal;
                const direction = gap >= 0 ? 'above' : 'below';
                return `Gap: ${regionName} is ${Math.abs(gap).toFixed(1)} points ${direction} NYS baseline`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: false,
            title: { display: true, text: 'Change from 2019 (indexed)' }
          }
        }
      }
    });
    let trendBandState = { regionName: 'Selected region', latestGap: 0 };

    // Ranking chart — destroyed and recreated on each load so explicit pixel
    // dimensions can be set, bypassing any CSS height overrides.
    let rankingChart = null;

    function numberOrDash(value) {
      return (value === null || value === undefined || Number.isNaN(value)) ? '—' : value;
    }

    function formatSigned(n) {
      if (n === null || n === undefined || Number.isNaN(n)) return '—';
      if (n > 0) return '+' + n.toLocaleString();
      return n.toLocaleString();
    }

    function loadCocOptions(options, selectedCode) {
      const select = document.getElementById('coc-select');
      if (!Array.isArray(options) || options.length === 0) return;

      const previousValue = selectedCode || select.value;
      select.innerHTML = '';

      options.forEach(opt => {
        const option = document.createElement('option');
        option.value = opt.coc_number;
        option.textContent = String(opt.coc_name || '').replace(/\s+CoC$/i, '');
        if (opt.coc_number === previousValue) {
          option.selected = true;
        }
        select.appendChild(option);
      });
    }

    function updateStatCards(stats) {
      document.getElementById('stat-housing-rate').textContent = numberOrDash(stats.housing_rate_30_plus) === '—'
        ? '—'
        : `${stats.housing_rate_30_plus}%`;
      document.getElementById('stat-homeless-total').textContent = numberOrDash(stats.latest_total_homeless) === '—'
        ? '—'
        : Number(stats.latest_total_homeless).toLocaleString();
      document.getElementById('stat-yoy-change').textContent = formatSigned(stats.yoy_total_change);
    }

    function updateHousingContextPanel(data) {
      const ctx = data.housing_context || {};
      const countyName = data.county || 'Selected county';
      const countyRate = Number(ctx.county_rate);
      const nysAvg = Number(ctx.nys_avg_rate);
      const rank = ctx.county_rank;
      const total = ctx.total_counties || 62;
      const latestYear = ctx.latest_year || '—';

      document.getElementById('housing-rank-text').textContent =
        (rank ? `Ranks ${rank} of ${total}` : `Ranks — of ${total}`) + ' — higher number = lower burden';
      document.getElementById('housing-rank-subtext').textContent = `for housing cost burden in ${latestYear}`;

      document.getElementById('housing-county-label').textContent = countyName;
      document.getElementById('housing-county-rate').textContent = Number.isFinite(countyRate) ? `${countyRate.toFixed(1)}%` : '—';
      document.getElementById('housing-nys-rate').textContent = Number.isFinite(nysAvg) ? `${nysAvg.toFixed(1)}%` : '—';

      const diff = Number.isFinite(countyRate) && Number.isFinite(nysAvg) ? countyRate - nysAvg : null;
      const note = document.getElementById('housing-vs-avg-note');
      if (diff === null) {
        note.textContent = '—';
        note.style.color = '#6b7280';
      } else if (diff > 0) {
        note.textContent = `↑ ${Math.abs(diff).toFixed(1)} pts above NYS average`;
        note.style.color = '#b91c1c';
      } else if (diff < 0) {
        note.textContent = `↓ ${Math.abs(diff).toFixed(1)} pts below NYS average`;
        note.style.color = '#047857';
      } else {
        note.textContent = 'At NYS average';
        note.style.color = '#6b7280';
      }

      document.getElementById('housing-distribution-min').textContent =
        Number.isFinite(Number(ctx.nys_min_rate)) ? `${Number(ctx.nys_min_rate).toFixed(1)}%` : '—';
      document.getElementById('housing-distribution-max').textContent =
        Number.isFinite(Number(ctx.nys_max_rate)) ? `${Number(ctx.nys_max_rate).toFixed(1)}%` : '—';

      const marker = document.getElementById('housing-distribution-marker');
      const pos = Number(ctx.distribution_position_pct);
      marker.style.left = Number.isFinite(pos) ? `${Math.max(0, Math.min(100, pos))}%` : '0%';
    }

    function updateHousingChart(data) {
      housingChart.data.labels = data.housing.years;
      housingChart.data.datasets = [{
        label: `${data.county} County`,
        data: data.housing.rate_30_plus,
        borderColor: COLORS[0],
        backgroundColor: COLORS[0] + '33',
        tension: 0.3,
        fill: false
      }];
      housingChart.update();
    }

    function updateShelterChart(data) {
      const years = data.homelessness.years;
      const view = document.getElementById('homeless-view').value;
      const plainName = String(data.homelessness.selected_coc_name || '').replace(/\s+CoC$/i, '');

      document.getElementById('shelter-title').textContent =
        `Sheltered vs. Unsheltered — ${plainName}`;

      shelterChart.data.labels = years;
      if (view === 'sheltered') {
        shelterChart.data.datasets = [{
          label: 'Sheltered',
          data: data.homelessness.sheltered,
          backgroundColor: '#233dff99'
        }];
      } else if (view === 'unsheltered') {
        shelterChart.data.datasets = [{
          label: 'Unsheltered',
          data: data.homelessness.unsheltered,
          backgroundColor: '#dc262699'
        }];
      } else {
        shelterChart.data.datasets = [
          {
            label: 'Sheltered',
            data: data.homelessness.sheltered,
            backgroundColor: '#233dff99'
          },
          {
            label: 'Unsheltered',
            data: data.homelessness.unsheltered,
            backgroundColor: '#dc262699'
          }
        ];
      }
      shelterChart.update();
    }

    function updateSubpopulationPanel(data) {
      const sub = (data.homelessness && data.homelessness.latest_subpop) ? data.homelessness.latest_subpop : {};
      const fmt = (v) => Number.isFinite(Number(v)) ? Number(v).toLocaleString() : '—';
      document.getElementById('subpop-year-label').textContent = `Selected region — latest year in range (${sub.year || '—'})`;
      document.getElementById('subpop-chronically').textContent = fmt(sub.chronically_homeless);
      document.getElementById('subpop-veterans').textContent = fmt(sub.homeless_veterans);
      document.getElementById('subpop-youth').textContent = fmt(sub.homeless_youth_under25);
      document.getElementById('subpop-families').textContent = fmt(sub.homeless_people_in_families);
    }

    function updateTrendChart(data) {
      const years = data.homelessness.years || [];
      const regionRaw = (data.homelessness.total || []).map(v => Number(v) || 0);
      const stateRaw = (data.homelessness.state_total || []).map(v => Number(v) || 0);
      const plainName = String(data.homelessness.selected_coc_name || '').replace(/\s+CoC$/i, '');

      const baseRegion = regionRaw[0] > 0 ? regionRaw[0] : 1;
      const baseState = stateRaw[0] > 0 ? stateRaw[0] : 1;
      const regionIndexed = regionRaw.map(v => Number(((v / baseRegion) * 100).toFixed(1)));
      const stateIndexed = stateRaw.map(v => Number(((v / baseState) * 100).toFixed(1)));

      const allIndexed = [...regionIndexed, ...stateIndexed].filter(v => Number.isFinite(v));
      const minIndexed = allIndexed.length ? Math.min(...allIndexed) : 95;
      const maxIndexed = allIndexed.length ? Math.max(...allIndexed) : 105;
      const pad = Math.max(2, (maxIndexed - minIndexed) * 0.1);
      trendChart.options.scales.y.min = minIndexed - pad;
      trendChart.options.scales.y.max = maxIndexed + pad;

      const latestYear = years.length ? years[years.length - 1] : 'latest year';
      const latestGap = (regionIndexed.length && stateIndexed.length)
        ? regionIndexed[regionIndexed.length - 1] - stateIndexed[stateIndexed.length - 1]
        : 0;
      trendBandState = { regionName: plainName, latestGap };
      const subtitle = document.getElementById('trend-dynamic-subtitle');
      if (subtitle) {
        subtitle.textContent = latestGap >= 0
          ? `In ${latestYear}, ${plainName} is growing faster than the New York State trend.`
          : `In ${latestYear}, ${plainName} is growing slower than the New York State trend.`;
      }

      trendChart.data.labels = years;
      trendChart.data.datasets = [
        {
          label: plainName,
          pointLabel: plainName,
          data: regionIndexed,
          rawValues: regionRaw,
          borderColor: COLORS[2],
          backgroundColor: COLORS[2] + '33',
          tension: 0.3,
          fill: false
        },
        {
          label: 'New York State Total',
          pointLabel: 'New York State',
          data: stateIndexed,
          rawValues: stateRaw,
          borderColor: COLORS[3],
          borderDash: [5, 5],
          tension: 0.3,
          fill: false
        }
      ];
      trendChart.update();
    }

    function updateRankingChart(data) {
      const labels = data.ranking.map(r => r.coc_number);
      const values = data.ranking.map(r => r.total_homeless);
      const selected = data.homelessness.selected_coc_number;
      const bgColors = labels.map(coc => coc === selected ? COLORS[3] : COLORS[0] + '99');

      const rowHeight = window.innerWidth <= 600 ? 28 : 32;
      const computedHeight = Math.max(labels.length * rowHeight, 400);

      if (rankingChart) rankingChart.destroy();

      const canvas = document.getElementById('ranking-chart');
      const sizer = document.getElementById('d2-ranking-sizer');
      const containerWidth = sizer.offsetWidth || 600;
      canvas.width  = containerWidth;
      canvas.height = computedHeight;
      sizer.style.height = computedHeight + 'px';

      rankingChart = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: { labels, datasets: [{ data: values, backgroundColor: bgColors }] },
        options: {
          responsive: false,
          maintainAspectRatio: false,
          indexAxis: 'y',
          animation: { duration: 400 },
          plugins: { legend: { display: false } },
          scales: { x: { beginAtZero: true } }
        }
      });
    }

    function fetchDashboard() {
      const countyId = document.getElementById('county-select').value;
      const cocNumber = document.getElementById('coc-select').value;
      const yearStart = document.getElementById('housing-year-start').value;
      const yearEnd = document.getElementById('housing-year-end').value;
      const homelessView = document.getElementById('homeless-view').value;
      const excludeNyc = document.getElementById('exclude-nyc').checked ? '1' : '0';

      const url = `api/dashboard2.php?county_id=${countyId}&coc_number=${encodeURIComponent(cocNumber)}&year_start=${yearStart}&year_end=${yearEnd}&exclude_nyc=${excludeNyc}`;

      // Update browser URL silently so users can copy-paste it
      const newUrl = new URL(window.location);
      newUrl.searchParams.set('county_id', countyId);
      newUrl.searchParams.set('coc_number', cocNumber);
      newUrl.searchParams.set('year_start', yearStart);
      newUrl.searchParams.set('year_end', yearEnd);
      newUrl.searchParams.set('homeless_view', homelessView);
      if (excludeNyc === '1') {
        newUrl.searchParams.set('exclude_nyc', '1');
      } else {
        newUrl.searchParams.delete('exclude_nyc');
      }
      window.history.replaceState({}, '', newUrl);

      fetch(url)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            console.error(data.error);
            return;
          }
          loadCocOptions(data.coc_options, data.homelessness.selected_coc_number);
          updateStatCards(data.stat_cards);
          updateHousingContextPanel(data);
          updateHousingChart(data);
          updateShelterChart(data);
          updateSubpopulationPanel(data);
          updateTrendChart(data);
          updateRankingChart(data);
        })
        .catch(err => console.error('Fetch error:', err));
    }

    document.getElementById('county-select').addEventListener('change', fetchDashboard);
    document.getElementById('coc-select').addEventListener('change', fetchDashboard);
    document.getElementById('housing-year-start').addEventListener('change', () => {
      document.getElementById('homeless-year-start').value = document.getElementById('housing-year-start').value;
      fetchDashboard();
    });
    document.getElementById('housing-year-end').addEventListener('change', () => {
      document.getElementById('homeless-year-end').value = document.getElementById('housing-year-end').value;
      fetchDashboard();
    });
    document.getElementById('homeless-year-start').addEventListener('change', () => {
      document.getElementById('housing-year-start').value = document.getElementById('homeless-year-start').value;
      fetchDashboard();
    });
    document.getElementById('homeless-year-end').addEventListener('change', () => {
      document.getElementById('housing-year-end').value = document.getElementById('homeless-year-end').value;
      fetchDashboard();
    });
    document.getElementById('homeless-view').addEventListener('change', fetchDashboard);
    document.getElementById('exclude-nyc').addEventListener('change', fetchDashboard);

    // Save view logic
    function handleSaveView() {
      <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = 'login.php';
        return;
      <?php endif; ?>

      const viewName = prompt("Enter a name for this saved view:");
      if (!viewName) return;

      const filters = {
        county_id: document.getElementById('county-select').value,
        coc_number: document.getElementById('coc-select').value,
        year_start: document.getElementById('housing-year-start').value,
        year_end: document.getElementById('housing-year-end').value,
        homeless_view: document.getElementById('homeless-view').value,
        exclude_nyc: document.getElementById('exclude-nyc').checked ? '1' : '0'
      };

      fetch('api/save_view.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          view_name: viewName,
          dashboard_url: 'dashboard2.php',
          dashboard_name: 'Housing & Homelessness',
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
    }

    document.querySelectorAll('.save-view-btn').forEach(btn => {
      btn.addEventListener('click', handleSaveView);
    });

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

    document.querySelectorAll('.share-view-btn').forEach(btn => {
      btn.addEventListener('click', copyCurrentViewLink);
    });

    // Initialize filters from URL parameters
    function initFiltersFromUrl() {
      const params = new URLSearchParams(window.location.search);
      if (params.has('county_id')) document.getElementById('county-select').value = params.get('county_id');
      if (params.has('coc_number')) {
        const cocSelect = document.getElementById('coc-select');
        const cocValue = params.get('coc_number');
        if (![...cocSelect.options].some(o => o.value === cocValue)) {
          const temp = document.createElement('option');
          temp.value = cocValue;
          temp.textContent = cocValue;
          cocSelect.appendChild(temp);
        }
        cocSelect.value = cocValue;
      }
      if (params.has('year_start')) {
        document.getElementById('housing-year-start').value = params.get('year_start');
        document.getElementById('homeless-year-start').value = params.get('year_start');
      }
      if (params.has('year_end')) {
        document.getElementById('housing-year-end').value = params.get('year_end');
        document.getElementById('homeless-year-end').value = params.get('year_end');
      }
      if (params.has('homeless_view')) document.getElementById('homeless-view').value = params.get('homeless_view');
      if (params.has('exclude_nyc')) document.getElementById('exclude-nyc').checked = params.get('exclude_nyc') === '1';
    }

    function setupHelpTooltips() {
      const floating = document.createElement('div');
      floating.className = 'floating-tooltip';
      document.body.appendChild(floating);

      function showTooltip(el) {
        const text = el.getAttribute('data-tooltip');
        if (!text) return;
        floating.textContent = text;
        floating.classList.add('visible');
        const rect = el.getBoundingClientRect();
        floating.style.left = `${rect.left + rect.width / 2}px`;
        floating.style.top = `${rect.bottom + 10}px`;
      }

      function hideTooltip() {
        floating.classList.remove('visible');
      }

      document.querySelectorAll('.help-icon[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', () => showTooltip(el));
        el.addEventListener('focus', () => showTooltip(el));
        el.addEventListener('click', (e) => {
          e.preventDefault();
          if (floating.classList.contains('visible')) {
            hideTooltip();
          } else {
            showTooltip(el);
          }
        });
        el.addEventListener('mouseleave', hideTooltip);
        el.addEventListener('blur', hideTooltip);
      });

      document.addEventListener('click', (e) => {
        if (!e.target.closest('.help-icon')) hideTooltip();
      });
    }

    function setupTrendBandTooltip() {
      const canvas = document.getElementById('trend-chart');
      const tip = document.createElement('div');
      tip.className = 'floating-tooltip';
      document.body.appendChild(tip);

      function hide() {
        tip.classList.remove('visible');
      }

      canvas.addEventListener('mousemove', (e) => {
        const chart = trendChart;
        const regionMeta = chart.getDatasetMeta(0);
        const nysMeta = chart.getDatasetMeta(1);
        if (!regionMeta?.data?.length || !nysMeta?.data?.length) {
          hide();
          return;
        }

        const rect = canvas.getBoundingClientRect();
        const mx = e.clientX - rect.left;
        const my = e.clientY - rect.top;

        let inBand = false;
        let segmentGap = 0;
        const count = Math.min(regionMeta.data.length, nysMeta.data.length) - 1;
        for (let i = 0; i < count; i++) {
          const r1 = regionMeta.data[i];
          const r2 = regionMeta.data[i + 1];
          const n1 = nysMeta.data[i];
          const n2 = nysMeta.data[i + 1];
          if (!r1 || !r2 || !n1 || !n2) continue;

          const minX = Math.min(r1.x, r2.x);
          const maxX = Math.max(r1.x, r2.x);
          if (mx < minX || mx > maxX) continue;

          const t = (mx - r1.x) / ((r2.x - r1.x) || 1);
          const yRegion = r1.y + (r2.y - r1.y) * t;
          const yNys = n1.y + (n2.y - n1.y) * t;
          const top = Math.min(yRegion, yNys);
          const bottom = Math.max(yRegion, yNys);

          if (my >= top && my <= bottom) {
            const regionVal = Number((chart.data.datasets[0].data || [])[i] || 0);
            const nysVal = Number((chart.data.datasets[1].data || [])[i] || 0);
            segmentGap = regionVal - nysVal;
            inBand = true;
            break;
          }
        }

        if (!inBand) {
          hide();
          return;
        }

        tip.textContent = segmentGap >= 0
          ? `${trendBandState.regionName} homelessness has grown faster than the New York State average since 2019.`
          : `${trendBandState.regionName} homelessness has grown more slowly than the New York State average since 2019.`;
        tip.style.left = `${e.clientX + 8}px`;
        tip.style.top = `${e.clientY + 12}px`;
        tip.classList.add('visible');
      });

      canvas.addEventListener('mouseleave', hide);
    }

    setupHelpTooltips();
    setupTrendBandTooltip();
    initFiltersFromUrl();
    fetchDashboard();
  </script>
</body>

</html>
