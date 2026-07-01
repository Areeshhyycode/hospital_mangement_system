<?php
// ============================================================
// Database & app configuration
// Adjust these to match your local MySQL (XAMPP/WAMP defaults shown)
// ============================================================
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default is empty
define('DB_NAME', 'hospital_db');
define('DB_PORT', 3306);

define('APP_NAME', 'Hospital Management System');
define('CURRENCY', 'Rs.');

// Show errors while developing; turn off in production.
error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('Asia/Karachi');
