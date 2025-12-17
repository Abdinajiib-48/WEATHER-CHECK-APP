
<?php
// ============================================
// FILE: process.php
// FORM PROCESSING LOGIC
// ============================================

require_once 'config.php';
require_once 'database.php';
require_once 'functions.php';
require_once 'api_functions.php';

// Initialize database connection
$conn = getDBConnection();
initDatabase($conn);

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add city
    if (isset($_POST['city_add']) && !empty(trim($_POST['city_add']))) {
        $cityName = trim($_POST['city_add']);
        $weather = fetchWeatherData($cityName);
        
        if ($weather) {
            $latitude = $weather['coord']['lat'] ?? null;
            $longitude = $weather['coord']['lon'] ?? null;
            $countryCode = $weather['sys']['country'] ?? null;
            
            $cityId = addOrUpdateCity($conn, $cityName, $countryCode, $latitude, $longitude);
            
            if ($cityId) {
                saveWeatherData($conn, $cityId, $weather);
                $weatherData = $weather;
                $selectedCityId = $cityId;
                $selectedCityName = $cityName;
                
                // Get city position
                if ($latitude && $longitude && $countryCode) {
                    $cityPosition = getCoordinatesDirection($latitude, $longitude, $countryCode);
                }
                
                // Get forecast
                $forecastData = fetchForecastData($cityName);
                
                $successMessage = "City added successfully!";
            }
        } else {
            $errorMessage = "City not found. Please check the spelling.";
        }
    }
    
    // Delete city
    if (isset($_POST['city_delete']) && !empty(trim($_POST['city_delete']))) {
        $cityToDelete = trim($_POST['city_delete']);
        if (deleteCity($conn, $cityToDelete)) {
            $successMessage = "City deleted successfully!";
        } else {
            $errorMessage = "City not found in database.";
        }
    }
    
    // Select city for graphs
    if (isset($_POST['select_city']) && !empty($_POST['select_city'])) {
        $selectedCityName = $_POST['select_city'];
        $stmt = $conn->prepare("SELECT id, latitude, longitude, country_code FROM cities WHERE name = ?");
        $stmt->bind_param("s", $selectedCityName);
        $stmt->execute();
        $stmt->bind_result($selectedCityId, $cityLat, $cityLon, $cityCountry);
        $stmt->fetch();
        $stmt->close();
        
        // Get city position
        if ($cityLat && $cityLon && $cityCountry) {
            $cityPosition = getCoordinatesDirection($cityLat, $cityLon, $cityCountry);
        }
        
        // Get weather data
        if ($selectedCityId) {
            $weather = fetchWeatherData($selectedCityName);
            if ($weather) {
                $weatherData = $weather;
                saveWeatherData($conn, $selectedCityId, $weather);
                $forecastData = fetchForecastData($selectedCityName);
            }
        }
    }
    
    // Refresh city
    if (isset($_POST['refresh_city']) && !empty($_POST['refresh_city'])) {
        $cityName = $_POST['refresh_city'];
        $weather = fetchWeatherData($cityName);
        
        if ($weather) {
            $stmt = $conn->prepare("SELECT id, latitude, longitude, country_code FROM cities WHERE name = ?");
            $stmt->bind_param("s", $cityName);
            $stmt->execute();
            $stmt->bind_result($cityId, $cityLat, $cityLon, $cityCountry);
            $stmt->fetch();
            $stmt->close();
            
            if ($cityId) {
                // Update coordinates
                if (isset($weather['coord']['lat']) && isset($weather['coord']['lon'])) {
                    $updateStmt = $conn->prepare("UPDATE cities SET latitude = ?, longitude = ? WHERE id = ?");
                    $updateStmt->bind_param("ddi", $weather['coord']['lat'], $weather['coord']['lon'], $cityId);
                    $updateStmt->execute();


$updateStmt->close();
                    
                    $cityLat = $weather['coord']['lat'];
                    $cityLon = $weather['coord']['lon'];
                }
                
                // Get city position
                if ($cityLat && $cityLon && $cityCountry) {
                    $cityPosition = getCoordinatesDirection($cityLat, $cityLon, $cityCountry);
                }
                
                saveWeatherData($conn, $cityId, $weather);
                $weatherData = $weather;
                $selectedCityId = $cityId;
                $selectedCityName = $cityName;
                $forecastData = fetchForecastData($cityName);
                
                $successMessage = "Weather data refreshed!";
            }
        }
    }
}

// Get all cities
$allCities = getAllCities($conn);

// Get data for selected city
if ($selectedCityId) {
    $cityStats = getCityStatistics($conn, $selectedCityId);
    $graphData['daily'] = getGraphData($conn, $selectedCityId, 'daily');
    $graphData['weekly'] = getGraphData($conn, $selectedCityId, 'weekly');
    $graphData['monthly'] = getGraphData($conn, $selectedCityId, 'monthly');
}

// Close database connection
$conn->close();
?>