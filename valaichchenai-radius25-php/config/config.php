<?php
declare(strict_types=1);

// XAMPP local defaults. Change these if your local MySQL setup differs.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'valaichchenai_radius25';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

// Folder inside htdocs. Must match the folder name exactly.
const APP_BASE_URL = '/valaichchenai-radius25-php';

// Home base for the planner: Valaichchenai, Eastern Province, Sri Lanka.
const HOME_LAT = 7.926135;
const HOME_LNG = 81.527874;
const HOME_LABEL = 'Valaichchenai town centre';
const MAX_PLACE_RADIUS_KM = 25.0;

date_default_timezone_set('Asia/Colombo');