# Civic Data Hub

**Civic Data Hub** is a web-based platform that aggregates New York State data from trusted public sources into interactive dashboards to make it easier for policymakers, researchers, and community members to explore trends across regions, populations, and timeframes.

## Data Sources
The Hub currently features data from three main sources:
- **Economic Hardship:** U.S. Census Bureau (ACS 5-Year Estimates) and NYS Open Data (SNAP Caseload Statistics).
- **Housing & Homelessness:** U.S. Census Bureau (ACS Housing Cost Burden) and HUD (CoC Homeless Populations & Subpopulations Reports).
- **Health & Wellbeing:** CDC PLACES (Local Data for Better Health) and NYS Open Data (Opioid Data).

## Major Features
- **Interactive Dashboards:** Visualizes trends using responsive charts (Line, Bar, Scatter, etc.) using the Chart.js library.
- **Advanced Filtering:** Filter public data dynamically by specific NYS counties, timeframes, and specific demographics.
- **Saved Views:** Registered users can securely log in to save and manage custom data views directly to their account.

## Technology Stack
- **Frontend:** HTML5, CSS3, Vanilla JavaScript, Chart.js
- **Backend:** PHP 8.3
- **Database:** MySQL (AWS RDS)
- **Server:** Nginx

## Site Map
- `index.php` - Homepage with project overview and dashboard previews.
- `dashboard1.php` - Economic Hardship dashboard.
- `dashboard2.php` - Housing & Homelessness dashboard.
- `dashboard3.php` - Health & Wellbeing dashboard.
- `about.php` - About the project and the team.
- `account.php` - User dashboard for managing saved views.
- `login.php` / `signup.php` - User authentication.
- `api/` - Backend JSON endpoints serving dashboard data.
