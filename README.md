# CivicDataHub

Civic Data Hub is a PHP web application with interactive dashboards for county and regional civic data.

## Run With Docker

### Prerequisites

- Docker Desktop installed and running
- Docker Compose v2 (`docker compose`)

### Build and Start

From the project root:

```bash
APP_PORT=8081 CENSUS_API_KEY="YOUR_API_KEY" docker compose up --build -d
```

Notes:
- The app will be available at `http://localhost:8081`.
- If `8081` is in use, change `APP_PORT` (for example `APP_PORT=8082`).
- `CENSUS_API_KEY` is optional for local review in the current setup, but supported.

### Stop the App

```bash
docker compose down
```

## Test the Application

### 1) Open in Browser

- Home: `http://localhost:8081/`
- Dashboard 1: `http://localhost:8081/dashboard1.php`
- Dashboard 2: `http://localhost:8081/dashboard2.php`

### 2) Quick API Checks

Dashboard 1 sample:

```bash
curl "http://localhost:8081/api/dashboard1.php?county_id=1&year_start=2019&year_end=2023"
```

Dashboard 2 sample:

```bash
curl "http://localhost:8081/api/dashboard2.php?county_id=1&year_start=2019&year_end=2024"
```

### 3) Expected Dashboard 2 Behavior

- County dropdown affects **Housing Affordability** section only.
- Homelessness charts remain fixed to **Capital Region CoC-518**.
- Label is visible: `Data shown for Capital Region CoC-518 - not filterable by county.`
- Year range controls annual trends.
- Shelter toggle switches between total/sheltered/unsheltered view in grouped bar chart.

## Local Data Notes

- This Docker setup seeds a local SQLite database automatically on container start.
- Seeded data is for local testing/demo use.
- Demo login account:
  - Username: `demo`
  - Password: `password123`

## Useful Docker Commands

Show running services:

```bash
docker compose ps
```

View logs:

```bash
docker compose logs -f
```
