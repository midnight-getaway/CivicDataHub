<?php
/**
 * api/dashboard3.php — JSON API for Health & Wellbeing dashboard.
 *
 * GET parameters:
 *   county_id       (required) — county id from counties table
 *   year            (optional) — default 2023 (for CDC PLACES cross-sectional)
 *   measures        (optional) — comma-separated measure_ids (default: all curated)
 *   compare_ids     (optional) — comma-separated county ids for overlay
 *   ranking_measure (optional) — measure_id for the ranking chart (default: DIABETES)
 */

header('Content-Type: application/json');

require __DIR__ . '/../db_connect.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database not available']);
    exit;
}

$county_id = (int) ($_GET['county_id'] ?? 0);
$year      = (int) ($_GET['year'] ?? 2023);
$compare_ids = [];

if (!empty($_GET['compare_ids'])) {
    $compare_ids = array_filter(array_map('intval', explode(',', $_GET['compare_ids'])));
}

$all_measures = ['DIABETES', 'DEPRESSION', 'MHLTH', 'OBESITY', 'DISABILITY', 'CSMOKING'];
$measures = $all_measures;
if (!empty($_GET['measures'])) {
    $req_measures = explode(',', $_GET['measures']);
    $measures = array_intersect($req_measures, $all_measures);
}
if (empty($measures)) {
    $measures = $all_measures;
}

$ranking_measure = $_GET['ranking_measure'] ?? 'DIABETES';
if (!in_array($ranking_measure, $all_measures)) {
    $ranking_measure = 'DIABETES';
}

if ($county_id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'county_id is required']);
    exit;
}

// Ensure the county exists
$stmt = $pdo->prepare("SELECT county_name, fips_code FROM counties WHERE id = ?");
$stmt->execute([$county_id]);
$county_info = $stmt->fetch();

if (!$county_info) {
    http_response_code(404);
    echo json_encode(['error' => 'County not found']);
    exit;
}

$county_name = $county_info['county_name'];
$fips        = $county_info['fips_code'];

// --- Helper: fetch CDC PLACES health measures for a county ---
function getHealthMeasures($pdo, $cid, $yr, $measure_ids) {
    $in_clause = str_repeat('?,', count($measure_ids) - 1) . '?';
    $params = array_merge([$cid, $yr], $measure_ids);
    
    $stmt = $pdo->prepare("
        SELECT measure_id, measure_name, data_value
        FROM health_places
        WHERE county_id = ? AND year = ? AND measure_id IN ($in_clause)
        ORDER BY FIELD(measure_id, $in_clause)
    ");
    // Duplicate measure_ids for ORDER BY FIELD
    $stmt->execute(array_merge($params, $measure_ids));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Helper: fetch Opioid trend for a county ---
function getOpioidTrend($pdo, $cid) {
    $stmt = $pdo->prepare("
        SELECT year, deaths
        FROM opioid_data
        WHERE county_id = ? AND year >= 2010
        ORDER BY year ASC
    ");
    $stmt->execute([$cid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $years = [];
    $deaths = [];
    foreach ($rows as $r) {
        $years[] = (int) $r['year'];
        $deaths[] = (int) $r['deaths'];
    }
    return ['years' => $years, 'deaths' => $deaths];
}


// 1. Health Measures (Primary County)
$health_measures = getHealthMeasures($pdo, $county_id, $year, $measures);

// 2. Opioid Trend (Primary County)
$opioid_trend = getOpioidTrend($pdo, $county_id);

// 3. Comparisons
$comparisons = [];
foreach ($compare_ids as $cid) {
    $stmt = $pdo->prepare("SELECT county_name FROM counties WHERE id = ?");
    $stmt->execute([$cid]);
    $c_info = $stmt->fetch();
    if (!$c_info) continue;
    
    $comparisons[] = [
        'county_id' => $cid,
        'county' => $c_info['county_name'],
        'health_measures' => getHealthMeasures($pdo, $cid, $year, $measures),
        'opioid_trend' => getOpioidTrend($pdo, $cid)
    ];
}

// 4. Ranking Data (All Counties for the specified measure)
$stmt = $pdo->prepare("
    SELECT c.county_name, h.data_value
    FROM health_places h
    JOIN counties c ON c.id = h.county_id
    WHERE h.year = ? AND h.measure_id = ?
    ORDER BY h.data_value DESC
");
$stmt->execute([$year, $ranking_measure]);
$ranking_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$ranking = [];
foreach ($ranking_rows as $r) {
    $ranking[] = [
        'county' => $r['county_name'],
        'value' => (float) $r['data_value']
    ];
}

// 5. Stat Cards
// Top Risk
$top_risk_name = 'N/A';
$top_risk_val = -1;
$disability_rate = 'N/A';

$all_county_measures = getHealthMeasures($pdo, $county_id, $year, $all_measures);
foreach ($all_county_measures as $m) {
    $val = (float) $m['data_value'];
    if ($val > $top_risk_val) {
        $top_risk_val = $val;
        $top_risk_name = $m['measure_name'];
    }
    if ($m['measure_id'] === 'DISABILITY') {
        $disability_rate = $val . '%';
    }
}

// Opioid Mortality
$latest_opioid_deaths = 'N/A';
if (!empty($opioid_trend['deaths'])) {
    $latest_opioid_deaths = end($opioid_trend['deaths']);
}

$stat_cards = [
    'top_risk_name' => $top_risk_name,
    'top_risk_value' => $top_risk_val > -1 ? $top_risk_val . '%' : 'N/A',
    'opioid_mortality' => $latest_opioid_deaths,
    'disability_rate' => $disability_rate
];

// --- Output ---
echo json_encode([
    'county'          => $county_name,
    'fips'            => $fips,
    'year'            => $year,
    'health_measures' => $health_measures,
    'opioid_trend'    => $opioid_trend,
    'comparisons'     => $comparisons,
    'ranking'         => $ranking,
    'stat_cards'      => $stat_cards
]);
