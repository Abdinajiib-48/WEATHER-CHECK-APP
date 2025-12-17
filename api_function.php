<?php
// ============================================
// FILE: api_functions.php
// API FUNCTIONS
// ============================================

require_once 'config.php';

/**
 * Fetches current weather data from OpenWeatherMap API
 */
function fetchWeatherData($cityName) {
    $url = "https://api.openweathermap.org/data/2.5/weather?q=" . 
           urlencode($cityName) . "&units=metric&appid=" . API_KEY;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['cod']) && $data['cod'] == 200) {
            return $data;
        }
    }
    
    return null;
}

/**
 * Fetches forecast data from OpenWeatherMap API
 */
function fetchForecastData($cityName) {
    $url = "https://api.openweathermap.org/data/2.5/forecast?q=" . 
           urlencode($cityName) . "&units=metric&cnt=40&appid=" . API_KEY;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        return json_decode($response, true);
    }
    
    return null;
}
?>