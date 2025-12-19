<?php
// ============================================
// FILE: database.php
// DATABASE FUNCTIONS
// ============================================

require_once 'config.php';

/**
 * Establishes database connection
 */
function getDBConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query($sql)) {
        die("Error creating database: " . $conn->error);
    }
    
    $conn->select_db(DB_NAME);
    return $conn;
}

/**
 * Initializes database tables
 */
function initDatabase($conn) {
    // Cities table
    $sql = "CREATE TABLE IF NOT EXISTS cities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        country_code VARCHAR(10),
        latitude DECIMAL(9,6),
        longitude DECIMAL(9,6),
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating cities table: " . $conn->error);
    }
    
    // Weather data table
    $sql = "CREATE TABLE IF NOT EXISTS weather_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        city_id INT NOT NULL,
        date DATE NOT NULL,
        current_temp DECIMAL(5,2),
        temp_max DECIMAL(5,2),
        temp_min DECIMAL(5,2),
        humidity INT,
        pressure INT,
        wind_speed DECIMAL(5,2),
        weather_main VARCHAR(50),
        weather_description VARCHAR(100),
        weather_icon VARCHAR(10),
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_city_date (city_id, date),
        FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating weather_data table: " . $conn->error);
    }
    
    // Temperature stats table
    $sql = "CREATE TABLE IF NOT EXISTS temperature_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        city_id INT NOT NULL,
        period_type ENUM('daily', 'weekly', 'monthly') NOT NULL,
        avg_temp DECIMAL(5,2),
        max_temp DECIMAL(5,2),
        min_temp DECIMAL(5,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating temperature_stats table: " . $conn->error);
    }
}

/**
 * Adds or updates a city in database
 */
function addOrUpdateCity($conn, $cityName, $countryCode = null, $latitude = null, $longitude = null) {
    $cityName = trim($cityName);
    
    // Check if city exists
    $checkStmt = $conn->prepare("SELECT id FROM cities WHERE name = ?");
    $checkStmt->bind_param("s", $cityName);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        // Update existing city
        $checkStmt->bind_result($cityId);
        $checkStmt->fetch();
        $checkStmt->close();
        
        $updateStmt = $conn->prepare("UPDATE cities SET last_updated = CURRENT_TIMESTAMP, latitude = ?, longitude = ? WHERE id = ?");
        $updateStmt->bind_param("ddi", $latitude, $longitude, $cityId);
        $updateStmt->execute();
        $updateStmt->close();
        
        return $cityId;
    } else {
        // Insert new city
        $checkStmt->close();
        $stmt = $conn->prepare("INSERT INTO cities (name, country_code, latitude, longitude) VALUES (?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("ssdd", $cityName, $countryCode, $latitude, $longitude);
            $stmt->execute();
            $cityId = $stmt->insert_id;
            $stmt->close();
            return $cityId;
        }
    }
    return false;
}

/**
 * Deletes a city from database
 */
function deleteCity($conn, $cityName) {
    $cityName = trim($cityName);
    $stmt = $conn->prepare("DELETE FROM cities WHERE name = ?");
    if ($stmt) {
        $stmt->bind_param("s", $cityName);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }
    return false;
}

/**
 * Retrieves all cities from database
 */
function getAllCities($conn) {
    $cities = [];
    $sql = "SELECT c.*, 
                   (SELECT current_temp FROM weather_data 
                    WHERE city_id = c.id 
                    ORDER BY recorded_at DESC LIMIT 1) as current_temp,
                   (SELECT weather_icon FROM weather_data 
                    WHERE city_id = c.id 
                    ORDER BY recorded_at DESC LIMIT 1) as weather_icon
            FROM cities c
            ORDER BY c.name ASC";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cities[] = $row;
        }
    }
    return $cities;
}

/**
 * Saves weather data to database
 */
function saveWeatherData($conn, $cityId, $weatherData) {
    $date = date('Y-m-d');
    
    // Check for existing data
    $checkStmt = $conn->prepare("SELECT id FROM weather_data WHERE city_id = ? AND date = ?");
    $checkStmt->bind_param("is", $cityId, $date);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        // Update existing record
        $checkStmt->close();
        $stmt = $conn->prepare("UPDATE weather_data SET 
            current_temp = ?, temp_max = ?, temp_min = ?,
            humidity = ?, pressure = ?, wind_speed = ?,
            weather_main = ?, weather_description = ?, weather_icon = ?,
            recorded_at = CURRENT_TIMESTAMP
            WHERE city_id = ? AND date = ?");
        
        $stmt->bind_param("ddddiisssis", 
            $weatherData['main']['temp'],
            $weatherData['main']['temp_max'],
            $weatherData['main']['temp_min'],
            $weatherData['main']['humidity'],
            $weatherData['main']['pressure'],
            $weatherData['wind']['speed'],
            $weatherData['weather'][0]['main'],
            $weatherData['weather'][0]['description'],
            $weatherData['weather'][0]['icon'],
            $cityId,
            $date
        );
    } else {
        // Insert new record
        $checkStmt->close();
        $stmt = $conn->prepare("INSERT INTO weather_data 
            (city_id, date, current_temp, temp_max, temp_min, 
             humidity, pressure, wind_speed, weather_main, 
             weather_description, weather_icon) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("isddddiisss", 
            $cityId,
            $date,
            $weatherData['main']['temp'],
            $weatherData['main']['temp_max'],
            $weatherData['main']['temp_min'],
            $weatherData['main']['humidity'],
            $weatherData['main']['pressure'],
            $weatherData['wind']['speed'],
            $weatherData['weather'][0]['main'],
            $weatherData['weather'][0]['description'],
            $weatherData['weather'][0]['icon']
        );
    }
    
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
        return true;
    }
    
    return false;
}

/**
 * Gets temperature statistics for a city
 */
function getCityStatistics($conn, $cityId) {
    $stats = [];
    
    // Today's stats
    $sql = "SELECT 
            COALESCE(AVG(current_temp), 0) as avg_temp_daily,
            COALESCE(MAX(current_temp), 0) as max_temp_daily,
            COALESCE(MIN(current_temp), 0) as min_temp_daily
            FROM weather_data 
            WHERE city_id = ? 
            AND date = CURDATE()";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cityId);
    $stmt->execute();
    $stmt->bind_result($avg, $max, $min);
    $stmt->fetch();
    $stmt->close();
    
    $stats['daily'] = ['avg' => $avg, 'max' => $max, 'min' => $min];
    
    // Weekly stats
    $sql = "SELECT 
            COALESCE(AVG(current_temp), 0) as avg_temp_weekly,
            COALESCE(MAX(current_temp), 0) as max_temp_weekly,
            COALESCE(MIN(current_temp), 0) as min_temp_weekly
            FROM weather_data 
            WHERE city_id = ? 
            AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cityId);
    $stmt->execute();
    $stmt->bind_result($avg, $max, $min);
    $stmt->fetch();
    $stmt->close();
    
    $stats['weekly'] = ['avg' => $avg, 'max' => $max, 'min' => $min];
    
    // Monthly stats
    $sql = "SELECT 
            COALESCE(AVG(current_temp), 0) as avg_temp_monthly,
            COALESCE(MAX(current_temp), 0) as max_temp_monthly,
            COALESCE(MIN(current_temp), 0) as min_temp_monthly
            FROM weather_data 
            WHERE city_id = ? 
            AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cityId);
    $stmt->execute();
    $stmt->bind_result($avg, $max, $min);
    $stmt->fetch();
    $stmt->close();
    
    $stats['monthly'] = ['avg' => $avg, 'max' => $max, 'min' => $min];
    
    return $stats;
}

/**
 * Gets graph data for a city
 */
function getGraphData($conn, $cityId, $period = 'daily') {
    $data = [];
    
    switch ($period) {
        case 'daily':
            $sql = "SELECT DATE_FORMAT(date, '%b %d') as date_label,
                           current_temp as temp
                    FROM weather_data 
                    WHERE city_id = ? 
                    AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    ORDER BY date ASC";
            break;
            
        case 'weekly':
            $sql = "SELECT DATE_FORMAT(date, '%b %d') as date_label,
                           AVG(current_temp) as avg_temp,
                           MAX(current_temp) as max_temp,
                           MIN(current_temp) as min_temp
                    FROM weather_data 
                    WHERE city_id = ? 
                    AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY WEEK(date)
                    ORDER BY date ASC";
            break;
            
        case 'monthly':
            $sql = "SELECT DATE_FORMAT(date, '%b') as month_label,
                           AVG(current_temp) as avg_temp
                    FROM weather_data 
                    WHERE city_id = ? 
                    AND date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
                    GROUP BY MONTH(date)
                    ORDER BY date ASC";
            break;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cityId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    return $data;
}
?>