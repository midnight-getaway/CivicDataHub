<?php
header('Content-Type: application/json');

require __DIR__ . '/../db_connect.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database not available']);
    exit;
}

$county_id = (int) ($_GET['county_id'] ?? 0);
$year_start = (int) ($_GET['year_start'] ?? 2019);
$year_end = (int) ($_GET['year_end'] ?? 2024);

if ($county_id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'county_id is required']);
    exit;
}

if ($year_start > $year_end) {
    [$year_start, $year_end] = [$year_end, $year_start];
}

$year_start = max(2019, $year_start);
$year_end = min(2024, $year_end);

function detectHousingBurdenColumn(PDO $pdo): ?string
{
    $candidates = [
        'housing_cost_burden_pct',
        'housing_cost_burden',
        'cost_burden_pct',
        'cost_burden_rate',
        'rent_burden_pct',
        'rent_burden_rate'
    ];

    $stmt = $pdo->query("SELECT * FROM economic_hardship LIMIT 1");
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$row) {
        return null;
    }

    foreach ($candidates as $candidate) {
        if (array_key_exists($candidate, $row)) {
            return $candidate;
        }
    }

    return null;
}

$housingColumn = detectHousingBurdenColumn($pdo);
if ($housingColumn === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Housing cost burden column not found in economic_hardship table']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.county_name, e.year, e.`$housingColumn` AS housing_cost_burden
    FROM economic_hardship e
    JOIN counties c ON c.id = e.county_id
    WHERE e.county_id = ? AND e.year BETWEEN ? AND ?
    ORDER BY e.year
");
$stmt->execute([$county_id, $year_start, $year_end]);
$housingRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($housingRows)) {
    http_response_code(404);
    echo json_encode(['error' => 'No housing data found for this county and year range']);
    exit;
}

$housingYears = [];
$housingRates = [];
foreach ($housingRows as $row) {
    $housingYears[] = (int) $row['year'];
    $housingRates[] = round((float) $row['housing_cost_burden'], 1);
}

$latestHousing = end($housingRows);

$hudYears = [2019, 2020, 2021, 2022, 2023, 2024];
$cocSheltered = [1002, 1021, 987, 1054, 1093, 1148];
$cocUnsheltered = [112, 130, 142, 159, 188, 204];
$nysTotal = [91127, 92283, 90675, 92654, 103200, 113553];

$homelessnessYears = [];
$homelessSheltered = [];
$homelessUnsheltered = [];
$homelessCocTotal = [];
$homelessNysTotal = [];

for ($i = 0; $i < count($hudYears); $i++) {
    $y = $hudYears[$i];
    if ($y < $year_start || $y > $year_end) {
        continue;
    }

    $homelessnessYears[] = $y;
    $homelessSheltered[] = $cocSheltered[$i];
    $homelessUnsheltered[] = $cocUnsheltered[$i];
    $homelessCocTotal[] = $cocSheltered[$i] + $cocUnsheltered[$i];
    $homelessNysTotal[] = $nysTotal[$i];
}

if (empty($homelessnessYears)) {
    http_response_code(404);
    echo json_encode(['error' => 'No HUD homelessness data found for this year range']);
    exit;
}

$latestIndex = count($homelessnessYears) - 1;
$latestTotal = $homelessCocTotal[$latestIndex];
$prevTotal = $latestIndex > 0 ? $homelessCocTotal[$latestIndex - 1] : $latestTotal;
$yoyChange = $latestTotal - $prevTotal;
$yoyPct = $prevTotal > 0 ? round(($yoyChange / $prevTotal) * 100, 1) : 0.0;

echo json_encode([
    'housing' => [
        'county' => $housingRows[0]['county_name'],
        'years' => $housingYears,
        'cost_burden_rate' => $housingRates
    ],
    'homelessness' => [
        'region_label' => 'Capital Region CoC-518',
        'years' => $homelessnessYears,
        'sheltered' => $homelessSheltered,
        'unsheltered' => $homelessUnsheltered,
        'total_coc' => $homelessCocTotal,
        'total_nys' => $homelessNysTotal
    ],
    'stat_cards' => [
        'housing_cost_burden' => round((float) $latestHousing['housing_cost_burden'], 1),
        'coc_total_pit' => $latestTotal,
        'coc_yoy_change' => $yoyChange,
        'coc_yoy_pct' => $yoyPct
    ]
]);
