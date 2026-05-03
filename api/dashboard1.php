<?php
/**
 * api/dashboard1.php — JSON API that serves trend, SNAP, comparison, and stat-card data for dashboard1.
 *
 * Dependencies: db_connect.php and PHP PDO.
 * Data sources: economic_hardship, snap_enrollment, counties tables.
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

header('Content-Type: application/json');

require __DIR__ . '/../db_connect.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database not available']);
    exit;
}

$county_id  = (int) ($_GET['county_id'] ?? 0);
$year_start = (int) ($_GET['year_start'] ?? 2019);
$year_end   = (int) ($_GET['year_end'] ?? 2023);
$compare_ids = [];

if (!empty($_GET['compare_ids'])) {
    $compare_ids = array_map('intval', explode(',', $_GET['compare_ids']));
}

if ($county_id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'county_id is required']);
    exit;
}

// ====================== HELPER FUNCTIONS =======================================================

// --- Fetch economic data for one county ---
function getEconomicData($pdo, $county_id, $year_start, $year_end) {
    $stmt = $pdo->prepare("
        SELECT c.county_name, c.fips_code,
               e.year, e.poverty_pop, e.poverty_below, e.median_income, e.labor_force, e.unemployed
        FROM economic_hardship e
        JOIN counties c ON c.id = e.county_id
        WHERE e.county_id = ? AND e.year BETWEEN ? AND ?
        ORDER BY e.year
    ");
    $stmt->execute([$county_id, $year_start, $year_end]);
    return $stmt->fetchAll();
}

// --- Fetch SNAP data for one county ---
function getSnapData($pdo, $county_id, $year_start, $year_end) {
    $stmt = $pdo->prepare("
        SELECT year,
               ROUND(AVG(snap_persons)) AS avg_snap_persons,
               ROUND(AVG(ta_snap_persons)) AS avg_ta_persons,
               ROUND(AVG(snap_households)) AS avg_snap_households
        FROM snap_enrollment
        WHERE county_id = ? AND year BETWEEN ? AND ?
        GROUP BY year
        ORDER BY year
    ");
    $stmt->execute([$county_id, $year_start, $year_end]);
    return $stmt->fetchAll();
}

// ====================== MAIN LOGIC ===================================================================

// --- Main county data ---
$econ_rows = getEconomicData($pdo, $county_id, $year_start, $year_end);

if (empty($econ_rows)) {
    http_response_code(404);
    echo json_encode(['error' => 'No data found for this county']);
    exit;
}

$county_name = $econ_rows[0]['county_name'];
$fips        = $econ_rows[0]['fips_code'];

$years = [];
$poverty_rate = [];
$median_income = [];
$unemployment_rate = [];

foreach ($econ_rows as $r) {
    $years[] = (int) $r['year'];
    $poverty_rate[] = $r['poverty_pop'] > 0
        ? round($r['poverty_below'] / $r['poverty_pop'] * 100, 1)
        : 0;
    $median_income[] = (int) $r['median_income'];
    $unemployment_rate[] = $r['labor_force'] > 0
        ? round($r['unemployed'] / $r['labor_force'] * 100, 1)
        : 0;
}

$snap_rows = getSnapData($pdo, $county_id, $year_start, $year_end);
$snap_years = [];
$snap_persons = [];
$ta_persons = [];

foreach ($snap_rows as $r) {
    $snap_years[] = (int) $r['year'];
    $snap_persons[] = (int) $r['avg_snap_persons'];
    $ta_persons[] = (int) $r['avg_ta_persons'];
}

// Stat cards use the most recent year
$latest = end($econ_rows);
$stat_cards = [
    'poverty_rate'      => $latest['poverty_pop'] > 0 ? round($latest['poverty_below'] / $latest['poverty_pop'] * 100, 1) : 0,
    'median_income'     => (int) $latest['median_income'],
    'unemployment_rate' => $latest['labor_force'] > 0 ? round($latest['unemployed'] / $latest['labor_force'] * 100, 1) : 0,
];

// --- Comparison counties ---
$comparisons = [];
foreach ($compare_ids as $cid) {
    $c_rows = getEconomicData($pdo, $cid, $year_start, $year_end);
    if (empty($c_rows)) continue;

    $c = [
        'county' => $c_rows[0]['county_name'],
        'years' => [],
        'poverty_rate' => [],
        'median_income' => [],
        'unemployment_rate' => [],
    ];
    foreach ($c_rows as $r) {
        $c['years'][] = (int) $r['year'];
        $c['poverty_rate'][] = $r['poverty_pop'] > 0 ? round($r['poverty_below'] / $r['poverty_pop'] * 100, 1) : 0;
        $c['median_income'][] = (int) $r['median_income'];
        $c['unemployment_rate'][] = $r['labor_force'] > 0 ? round($r['unemployed'] / $r['labor_force'] * 100, 1) : 0;
    }
    $comparisons[] = $c;
}

// --- Output ---
echo json_encode([
    'county'            => $county_name,
    'fips'              => $fips,
    'years'             => $years,
    'poverty_rate'      => $poverty_rate,
    'median_income'     => $median_income,
    'unemployment_rate' => $unemployment_rate,
    'snap' => [
        'years'        => $snap_years,
        'snap_persons' => $snap_persons,
        'ta_persons'   => $ta_persons,
    ],
    'stat_cards'        => $stat_cards,
    'comparisons'       => $comparisons,
]);
