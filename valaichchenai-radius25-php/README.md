# RajaRata DayTrail

A PHP and MySQL tourism guide and one-day itinerary planner for Anuradhapura. This standalone source project is intended to run with Apache and MySQL/MariaDB from XAMPP. It does not need Node.js, npm, Composer, or a cloud database.

## Run with XAMPP

## Run with XAMPP

1. Install XAMPP with PHP 8.1 or later and the `pdo_mysql` extension. Start **Apache** and **MySQL**.
2. Copy this whole `valaichchenai-radius25-php` folder into XAMPP's `htdocs` directory.
3. Open `http://localhost/phpmyadmin`, create a database named `valaichchenai_radius25`, select it, and import `database/valaichchenai_radius25.sql`.
4. If you used a different folder name or database account, update `APP_BASE_URL`, `DB_NAME`, `DB_USER`, and `DB_PASS` in `config/config.php`.
5. Open `http://localhost/valaichchenai-radius25-php/` in a browser.
6. For the first administrator account, open `http://localhost/valaichchenai-radius25-php/setup-admin.php` and create it. The setup page closes automatically after an administrator is created.

## Included

- Responsive place discovery with keyword search and category filters
- Place detail pages with visitor information, estimated distance, opening-time fields, and map links
- OpenStreetMap map display and Google Maps directions links
- One-day itinerary builder with pace options, nearest-next ordering, estimated drive time, finish time, and opening-hour warnings
- Optional account registration and login; signed-in visitors can save and revisit itineraries
- Administrator dashboard and place create, edit, and delete tools
- MySQL schema and sample Anuradhapura places

## Important setup notes

- The sample place list and coordinates are initial demonstration data. Distances use a configurable Anuradhapura city-centre starting point, not the student's exact registered home coordinate. Set `HOME_LAT` and `HOME_LNG` in `config/config.php` to the required starting location before assessment; the site recalculates distances and enforces the 25 km local planning radius from those coordinates.
- Opening hours, entry fees, images, and visitor rules should be checked against current official sources and updated in the administrator area. Travel times and road distances are estimates; Google Maps provides turn-by-turn directions.
- Maps, external image URLs, and Google Maps directions need an internet connection.
- The initial administrator setup is available only while the database has no administrator account. Use a strong password and remove public access to the setup page before hosting this on the internet.
- For a public deployment, configure a private database account, HTTPS, and a non-default `APP_BASE_URL`. The default XAMPP MySQL account is for local development only.

## Project structure

- `index.php`, `place.php`, `map.php`, `planner.php` — visitor pages
- `login.php`, `register.php`, `logout.php`, `my-plans.php`, `setup-admin.php` — accounts and saved plans
- `admin/` — protected place management
- `includes/` — shared database, session, security, and page helpers
- `database/Valaichchenai-Radius25.sql` — database schema and initial places
- `assets/` — responsive styling and small UI interactions


