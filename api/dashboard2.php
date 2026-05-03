<?php
/**
 * api/dashboard2.php — JSON API that serves housing burden and homelessness trend data for dashboard2.
 *
 * Dependencies: db_connect.php, PHP PDO, Census ACS endpoint.
 * Data sources: counties and hud_pit_nys tables, U.S. Census ACS API (B25070).
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

$county_id = (int) ($_GET['county_id'] ?? 0);
$coc_number = strtoupper(trim($_GET['coc_number'] ?? 'NY-503'));
$year_start = max(2019, min(2023, (int) ($_GET['year_start'] ?? 2019)));
$year_end = max(2019, min(2023, (int) ($_GET['year_end'] ?? 2023)));
$exclude_nyc = ($_GET['exclude_nyc'] ?? '0') === '1';

if ($year_start > $year_end) {
    [$year_start, $year_end] = [$year_end, $year_start];
}

if ($county_id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'county_id is required']);
    exit;
}

$county_stmt = $pdo->prepare("SELECT county_name, fips_code FROM counties WHERE id = ?");
$county_stmt->execute([$county_id]);
$county = $county_stmt->fetch(PDO::FETCH_ASSOC);

if (!$county) {
    http_response_code(404);
    echo json_encode(['error' => 'County not found']);
    exit;
}

$county_name = $county['county_name'];
$county_fips_full = str_pad((string) $county['fips_code'], 5, '0', STR_PAD_LEFT);
$county_code = substr($county_fips_full, -3);

$api_key = 'd8484f3eb13d2ada6a0a4e9eec8c8a00eb6258e6';

/**
 * Small file cache for ACS responses to avoid repeated network calls.
 */
function fetchJsonWithCache($url, $cache_ttl_seconds = 43200) {
    $cache_dir = __DIR__ . '/../data/cache';
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0777, true);
    }

    $cache_file = $cache_dir . '/acs_' . sha1($url) . '.json';
    if (is_file($cache_file) && (time() - filemtime($cache_file) <= $cache_ttl_seconds)) {
        $cached = @file_get_contents($cache_file);
        $decoded = json_decode($cached, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    $resp = @file_get_contents($url);
    if ($resp === false) {
        return null;
    }

    $decoded = json_decode($resp, true);
    if (!is_array($decoded)) {
        return null;
    }

    @file_put_contents($cache_file, $resp);
    return $decoded;
}

$housing_years = [];
$housing_rates = [];
$housing_values = [];

for ($year = $year_start; $year <= $year_end; $year++) {
    $url = "https://api.census.gov/data/{$year}/acs/acs5"
        . "?get=NAME,B25070_001E,B25070_007E,B25070_008E,B25070_009E,B25070_010E"
        . "&for=county:{$county_code}&in=state:36&key={$api_key}";

    $json = fetchJsonWithCache($url);
    if (!is_array($json) || count($json) < 2) {
        continue;
    }

    $row = $json[1];
    $total = (float) ($row[1] ?? 0);
    $burdened = (float) ($row[2] ?? 0) + (float) ($row[3] ?? 0) + (float) ($row[4] ?? 0) + (float) ($row[5] ?? 0);
    $rate = $total > 0 ? round(($burdened / $total) * 100, 1) : 0.0;

    $housing_years[] = $year;
    $housing_rates[] = $rate;
    $housing_values[] = [
        'year' => $year,
        'total_renter_households' => (int) $total,
        'cost_burdened_households' => (int) $burdened,
        'rate_30_plus' => $rate
    ];
}

// Housing county context panel (latest year in selected range, statewide comparison)
$latest_housing_year = !empty($housing_years) ? end($housing_years) : $year_end;
$county_rank = null;
$county_rate_latest = null;
$nys_avg_rate = null;
$nys_min_rate = null;
$nys_max_rate = null;
$distribution_position_pct = null;

try {
    $url_all = "https://api.census.gov/data/{$latest_housing_year}/acs/acs5"
        . "?get=NAME,B25070_001E,B25070_007E,B25070_008E,B25070_009E,B25070_010E"
        . "&for=county:*&in=state:36&key={$api_key}";
    $json_all = fetchJsonWithCache($url_all);
    $all_rates = [];

    if (is_array($json_all) && count($json_all) > 1) {
        for ($i = 1; $i < count($json_all); $i++) {
            $row = $json_all[$i];
            $name = (string) ($row[0] ?? '');
            $total = (float) ($row[1] ?? 0);
            if ($name === '' || $total <= 0) {
                continue;
            }
            $burdened = (float) ($row[2] ?? 0) + (float) ($row[3] ?? 0) + (float) ($row[4] ?? 0) + (float) ($row[5] ?? 0);
            $rate = round(($burdened / $total) * 100, 1);
            $all_rates[] = [
                'county_name' => preg_replace('/ County, New York$/', '', $name),
                'county_code' => str_pad((string) ($row[7] ?? ''), 3, '0', STR_PAD_LEFT),
                'rate' => $rate
            ];
        }
    }

    if (!empty($all_rates)) {
        usort($all_rates, function ($a, $b) {
            return $b['rate'] <=> $a['rate'];
        });

        $values = array_map(fn($r) => $r['rate'], $all_rates);
        $nys_avg_rate = round(array_sum($values) / count($values), 1);
        $nys_min_rate = min($values);
        $nys_max_rate = max($values);

        foreach ($all_rates as $i => $r) {
            if ($r['county_code'] === $county_code) {
                $county_rank = $i + 1;
                $county_rate_latest = $r['rate'];
                break;
            }
        }

        if ($county_rate_latest !== null && $nys_max_rate > $nys_min_rate) {
            $distribution_position_pct = round((($county_rate_latest - $nys_min_rate) / ($nys_max_rate - $nys_min_rate)) * 100, 1);
        } elseif ($county_rate_latest !== null) {
            $distribution_position_pct = 50.0;
        }
    }
} catch (Throwable $e) {
    // Keep dashboard responsive if statewide comparison fetch fails.
}

// CoC list (for dropdown + ranking chart)
$ranking_sql = "
    SELECT coc_number, coc_name,
           SUM(total_homeless) AS total_homeless
    FROM hud_pit_nys
    WHERE year BETWEEN ? AND ?
";
if ($exclude_nyc) {
    $ranking_sql .= " AND coc_number <> 'NY-600' ";
}
$ranking_sql .= "
    GROUP BY coc_number, coc_name
    ORDER BY total_homeless DESC
";

$ranking = [];
$coc_options = [];
try {
    $options_stmt = $pdo->query("
        SELECT DISTINCT coc_number, coc_name
        FROM hud_pit_nys
        ORDER BY coc_name
    ");
    $coc_options_rows = $options_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($coc_options_rows as $r) {
        $coc_options[] = [
            'coc_number' => $r['coc_number'],
            'coc_name' => $r['coc_name']
        ];
    }
} catch (Throwable $e) {
    // Keep dashboard responsive even if the HUD table is not yet loaded.
}

try {
    $rank_stmt = $pdo->prepare($ranking_sql);
    $rank_stmt->execute([$year_start, $year_end]);
    $ranking_rows = $rank_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ranking_rows as $r) {
        $ranking[] = [
            'coc_number' => $r['coc_number'],
            'coc_name' => $r['coc_name'],
            'total_homeless' => (int) $r['total_homeless']
        ];
    }
} catch (Throwable $e) {
    // Keep dashboard responsive even if the HUD table is not yet loaded.
}

// CoC homelessness trend for selected CoC
$trend_years = [];
$trend_total = [];
$trend_sheltered = [];
$trend_unsheltered = [];
$selected_coc_name = $coc_number;
$latest_subpop = [
    'year' => null,
    'chronically_homeless' => null,
    'homeless_veterans' => null,
    'homeless_youth_under25' => null,
    'homeless_people_in_families' => null
];

try {
    $trend_stmt = $pdo->prepare("
        SELECT year, coc_name, total_homeless, sheltered_total, unsheltered_total
        FROM hud_pit_nys
        WHERE coc_number = ? AND year BETWEEN ? AND ?
        ORDER BY year
    ");
    $trend_stmt->execute([$coc_number, $year_start, $year_end]);
    $trend_rows = $trend_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($trend_rows as $r) {
        $selected_coc_name = $r['coc_name'];
        $trend_years[] = (int) $r['year'];
        $trend_total[] = (int) $r['total_homeless'];
        $trend_sheltered[] = (int) $r['sheltered_total'];
        $trend_unsheltered[] = (int) $r['unsheltered_total'];
    }

    $subpop_stmt = $pdo->prepare("
        SELECT year, chronically_homeless, homeless_veterans, homeless_youth_under25, homeless_people_in_families
        FROM hud_pit_nys
        WHERE coc_number = ? AND year BETWEEN ? AND ?
        ORDER BY year DESC
        LIMIT 1
    ");
    $subpop_stmt->execute([$coc_number, $year_start, $year_end]);
    $subpop_row = $subpop_stmt->fetch(PDO::FETCH_ASSOC);
    if ($subpop_row) {
        $latest_subpop = [
            'year' => (int) $subpop_row['year'],
            'chronically_homeless' => (int) $subpop_row['chronically_homeless'],
            'homeless_veterans' => (int) $subpop_row['homeless_veterans'],
            'homeless_youth_under25' => (int) $subpop_row['homeless_youth_under25'],
            'homeless_people_in_families' => (int) $subpop_row['homeless_people_in_families']
        ];
    }
} catch (Throwable $e) {
    // Keep dashboard responsive even if the HUD table is not yet loaded.
}

// NYS statewide trend (sum of all CoCs)
$state_total = [];
try {
    $state_sql = "
        SELECT year, SUM(total_homeless) AS total_homeless
        FROM hud_pit_nys
        WHERE year BETWEEN ? AND ?
    ";
    $state_sql .= " GROUP BY year ORDER BY year ";

    $state_stmt = $pdo->prepare($state_sql);
    $state_stmt->execute([$year_start, $year_end]);
    $state_rows = $state_stmt->fetchAll(PDO::FETCH_ASSOC);

    $by_year = [];
    foreach ($state_rows as $r) {
        $by_year[(int) $r['year']] = (int) $r['total_homeless'];
    }
    foreach ($trend_years as $y) {
        $state_total[] = $by_year[$y] ?? 0;
    }
} catch (Throwable $e) {
    // Keep dashboard responsive even if the HUD table is not yet loaded.
}

$latest_housing_rate = !empty($housing_rates) ? end($housing_rates) : null;
$latest_total = !empty($trend_total) ? end($trend_total) : null;
$prev_total = count($trend_total) > 1 ? $trend_total[count($trend_total) - 2] : null;
$yoy_change = null;
if ($latest_total !== null && $prev_total !== null) {
    $yoy_change = $latest_total - $prev_total;
}

echo json_encode([
    'county' => $county_name,
    'county_fips' => $county_fips_full,
    'year_start' => $year_start,
    'year_end' => $year_end,
    'housing' => [
        'years' => $housing_years,
        'rate_30_plus' => $housing_rates,
        'rows' => $housing_values
    ],
    'housing_context' => [
        'latest_year' => $latest_housing_year,
        'county_rank' => $county_rank,
        'total_counties' => 62,
        'county_rate' => $county_rate_latest,
        'nys_avg_rate' => $nys_avg_rate,
        'nys_min_rate' => $nys_min_rate,
        'nys_max_rate' => $nys_max_rate,
        'distribution_position_pct' => $distribution_position_pct
    ],
    'homelessness' => [
        'selected_coc_number' => $coc_number,
        'selected_coc_name' => $selected_coc_name,
        'years' => $trend_years,
        'total' => $trend_total,
        'sheltered' => $trend_sheltered,
        'unsheltered' => $trend_unsheltered,
        'state_total' => $state_total,
        'latest_subpop' => $latest_subpop
    ],
    'ranking' => $ranking,
    'coc_options' => $coc_options,
    'stat_cards' => [
        'housing_rate_30_plus' => $latest_housing_rate,
        'latest_total_homeless' => $latest_total,
        'yoy_total_change' => $yoy_change
    ]
]);
