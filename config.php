<?php
// ============================================
// FILE: config.php
// CONFIGURATION AND CONSTANTS
// ============================================

// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'weather_app_db');

// API Configuration
define('API_KEY', '61bef1013deac2521b5182b1a67ebac0');

// Country names mapping
$countryNames = [
    "US" => "United States", "GB" => "United Kingdom", "CA" => "Canada",
    "AU" => "Australia", "FR" => "France", "DE" => "Germany", "IT" => "Italy",
    "ES" => "Spain", "JP" => "Japan", "CN" => "China", "ET" => "Ethiopia",
    "CM" => "Cameroon", "IN" => "India", "BR" => "Brazil", "RU" => "Russia",
    "MX" => "Mexico"
];

// Start session
session_start();

// Initialize variables
$weatherData = [];
$forecastData = [];
$cityStats = [];
$graphData = [];
$allCities = [];
$selectedCityId = null;
$selectedCityName = "";
$errorMessage = "";
$successMessage = "";
$cityPosition = "";
?>