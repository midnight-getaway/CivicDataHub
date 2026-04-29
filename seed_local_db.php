<?php

$dbPath = __DIR__ . '/data/civicdatahub.sqlite';
$dataDir = dirname($dbPath);

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("
    CREATE TABLE IF NOT EXISTS counties (
        id INTEGER PRIMARY KEY,
        county_name TEXT NOT NULL,
        fips_code TEXT
    );

    CREATE TABLE IF NOT EXISTS economic_hardship (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        county_id INTEGER NOT NULL,
        year INTEGER NOT NULL,
        poverty_pop INTEGER NOT NULL,
        poverty_below INTEGER NOT NULL,
        median_income INTEGER NOT NULL,
        labor_force INTEGER NOT NULL,
        unemployed INTEGER NOT NULL,
        housing_cost_burden_pct REAL NOT NULL,
        FOREIGN KEY(county_id) REFERENCES counties(id)
    );

    CREATE TABLE IF NOT EXISTS snap_enrollment (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        county_id INTEGER NOT NULL,
        year INTEGER NOT NULL,
        month INTEGER NOT NULL,
        snap_persons INTEGER NOT NULL,
        ta_snap_persons INTEGER NOT NULL,
        snap_households INTEGER NOT NULL,
        FOREIGN KEY(county_id) REFERENCES counties(id)
    );

    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
");

$countStmt = $pdo->query("SELECT COUNT(*) FROM counties");
$countyCount = (int) $countStmt->fetchColumn();

if ($countyCount === 0) {
    $counties = [
        [1, 'Albany', '36001'],
        [2, 'Rensselaer', '36083'],
        [3, 'Schenectady', '36093'],
        [4, 'Saratoga', '36091'],
    ];

    $countyInsert = $pdo->prepare("INSERT INTO counties (id, county_name, fips_code) VALUES (?, ?, ?)");
    foreach ($counties as $county) {
        $countyInsert->execute($county);
    }

    $economicData = [
        1 => [
            [2019, 95000, 12825, 66500, 151000, 5600, 31.8],
            [2020, 95500, 13370, 67800, 149500, 9200, 32.4],
            [2021, 96200, 12600, 70100, 150200, 7100, 31.9],
            [2022, 97000, 11830, 73400, 152000, 5400, 30.7],
            [2023, 97800, 11440, 76100, 153100, 4900, 29.8],
            [2024, 98500, 11130, 78400, 154000, 4700, 29.1],
        ],
        2 => [
            [2019, 62000, 6940, 70100, 81000, 2900, 28.2],
            [2020, 62300, 7360, 71300, 80300, 4700, 28.9],
            [2021, 62800, 7010, 73500, 80700, 3600, 28.1],
            [2022, 63200, 6640, 75900, 81400, 2800, 27.4],
            [2023, 63700, 6430, 78200, 82000, 2500, 26.8],
            [2024, 64100, 6280, 80300, 82500, 2400, 26.3],
        ],
        3 => [
            [2019, 74000, 9540, 62800, 98000, 4100, 32.5],
            [2020, 74400, 10020, 63900, 97000, 6300, 33.1],
            [2021, 74800, 9620, 66000, 97600, 4700, 32.2],
            [2022, 75300, 9110, 68400, 98300, 3500, 31.4],
            [2023, 76000, 8740, 70900, 98900, 3200, 30.6],
            [2024, 76600, 8500, 73100, 99500, 3000, 30.0],
        ],
        4 => [
            [2019, 82000, 6230, 84200, 111000, 3200, 25.4],
            [2020, 82700, 6800, 85300, 109800, 5100, 26.2],
            [2021, 83400, 6420, 87800, 110500, 3900, 25.6],
            [2022, 84000, 6020, 90500, 111700, 3000, 24.9],
            [2023, 84700, 5800, 93400, 112600, 2700, 24.4],
            [2024, 85300, 5630, 96100, 113300, 2600, 24.0],
        ],
    ];

    $econInsert = $pdo->prepare("
        INSERT INTO economic_hardship (
            county_id, year, poverty_pop, poverty_below, median_income, labor_force, unemployed, housing_cost_burden_pct
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($economicData as $countyId => $rows) {
        foreach ($rows as $row) {
            $econInsert->execute(array_merge([$countyId], $row));
        }
    }

    $snapInsert = $pdo->prepare("
        INSERT INTO snap_enrollment (county_id, year, month, snap_persons, ta_snap_persons, snap_households)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($economicData as $countyId => $rows) {
        foreach ($rows as $row) {
            [$year] = $row;
            for ($month = 1; $month <= 12; $month++) {
                $basePersons = match ($countyId) {
                    1 => 42000,
                    2 => 19000,
                    3 => 29000,
                    default => 15000,
                };

                $trend = ($year - 2019) * 450;
                $seasonal = (($month % 4) - 1) * 55;
                $snapPersons = $basePersons + $trend + $seasonal;
                $taPersons = (int) round($snapPersons * 0.21);
                $households = (int) round($snapPersons / 1.85);

                $snapInsert->execute([$countyId, $year, $month, $snapPersons, $taPersons, $households]);
            }
        }
    }

    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    $userInsert = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $userInsert->execute(['demo', 'demo@example.com', $passwordHash]);
}

echo "Local SQLite database ready at {$dbPath}\n";
