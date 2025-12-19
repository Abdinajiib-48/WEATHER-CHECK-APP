<?php
// ============================================
// FILE: functions.php
// UTILITY FUNCTIONS
// ============================================

require_once 'config.php';

/**
 * Determines city position in country based on coordinates
 * Returns: 'Northern', 'Southern', 'Eastern', 'Western', etc.
 */
function getCityPositionInCountry($lat, $lon, $countryCode) {
    // Country center points
    $countryCenters = [
        'US' => ['lat' => 39.8283, 'lon' => -98.5795],
        'GB' => ['lat' => 54.0, 'lon' => -2.0],
        'CA' => ['lat' => 56.1304, 'lon' => -106.3468],
        'AU' => ['lat' => -25.2744, 'lon' => 133.7751],
        'FR' => ['lat' => 46.2276, 'lon' => 2.2137],
        'DE' => ['lat' => 51.1657, 'lon' => 10.4515],
        'IT' => ['lat' => 41.8719, 'lon' => 12.5674],
        'ES' => ['lat' => 40.4637, 'lon' => -3.7492],
        'JP' => ['lat' => 36.2048, 'lon' => 138.2529],
        'CN' => ['lat' => 35.8617, 'lon' => 104.1954],
        'IN' => ['lat' => 20.5937, 'lon' => 78.9629],
        'BR' => ['lat' => -14.2350, 'lon' => -51.9253],
        'RU' => ['lat' => 61.5240, 'lon' => 105.3188],
        'MX' => ['lat' => 23.6345, 'lon' => -102.5528],
        'ET' => ['lat' => 9.1450, 'lon' => 40.4897],
        'CM' => ['lat' => 7.3697, 'lon' => 12.3547],
    ];
    
    $center = $countryCenters[$countryCode] ?? ['lat' => 0, 'lon' => 0];
    $latDiff = $lat - $center['lat'];
    $lonDiff = $lon - $center['lon'];
    
    // Latitude direction
    if (abs($latDiff) < 2) {
        $latDirection = 'Central';
    } elseif ($latDiff > 0) {
        $latDirection = 'Northern';
    } else {
        $latDirection = 'Southern';
    }
    
    // Longitude direction
    if (abs($lonDiff) < 2) {
        $lonDirection = 'Central';
    } elseif ($lonDiff > 0) {
        $lonDirection = 'Eastern';
    } else {
        $lonDirection = 'Western';
    }
    
    // Combine directions
    if ($latDirection == 'Central' && $lonDirection == 'Central') {
        return 'Central';
    } elseif ($latDirection == 'Central') {
        return $lonDirection;
    } elseif ($lonDirection == 'Central') {
        return $latDirection;
    } else {
        return $latDirection . '-' . $lonDirection;
    }
}

/**
 * Gets user-friendly location description
 */
function getCoordinatesDirection($lat, $lon, $countryCode) {
    $position = getCityPositionInCountry($lat, $lon, $countryCode);
    
    $directions = [
        'Northern' => 'Northern part',
        'Southern' => 'Southern part', 
        'Eastern' => 'Eastern part',
        'Western' => 'Western part',
        'Northern-Eastern' => 'North-Eastern part',
        'Northern-Western' => 'North-Western part',
        'Southern-Eastern' => 'South-Eastern part',
        'Southern-Western' => 'South-Western part',
        'Central' => 'Central part'
    ];
    
    return $directions[$position] ?? $position;
}
?>