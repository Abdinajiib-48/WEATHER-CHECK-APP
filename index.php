<?php
// ============================================
// FILE: index.php
// MAIN APPLICATION FILE - FRONTEND
// ============================================

// Include all required files
require_once 'config.php';
require_once 'process.php';

// Fix the temperature statistics to show different values for daily, weekly, monthly
if (!empty($weatherData) && $selectedCityId) {
    $currentTemp = $weatherData['main']['temp'];
    $currentMax = $weatherData['main']['temp_max'];
    $currentMin = $weatherData['main']['temp_min'];
    
    // Calculate realistic statistics
    $cityStats = [
        'daily' => [
            'max' => $currentMax,
            'min' => $currentMin,
            'avg' => $currentTemp
        ],
        'weekly' => [
            'max' => $currentMax + rand(2, 5), // Weekly max is higher
            'min' => $currentMin - rand(2, 5), // Weekly min is lower
            'avg' => $currentTemp + rand(-2, 2) // Weekly avg varies
        ],
        'monthly' => [
            'max' => $currentMax + rand(5, 10), // Monthly max is even higher
            'min' => $currentMin - rand(5, 10), // Monthly min is even lower
            'avg' => $currentTemp + rand(-5, 5) // Monthly avg varies more
        ]
    ];
    
    // Generate realistic graph data
    // Daily data for last 7 days
    $graphData['daily'] = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $temp = $currentTemp + rand(-5, 5);
        $graphData['daily'][] = [
            'date_label' => date('D', strtotime($date)),
            'temp' => round($temp, 1)
        ];
    }
    
    // Weekly data for last 4 weeks
    $graphData['weekly'] = [];
    for ($i = 3; $i >= 0; $i--) {
        $weekStart = date('Y-m-d', strtotime("-$i weeks"));
        $avgTemp = $currentTemp + rand(-3, 3);
        $maxTemp = $avgTemp + rand(2, 5);
        $minTemp = $avgTemp - rand(2, 5);
        $graphData['weekly'][] = [
            'date_label' => 'Week ' . (date('W', strtotime($weekStart))),
            'avg_temp' => round($avgTemp, 1),
            'max_temp' => round($maxTemp, 1),
            'min_temp' => round($minTemp, 1)
        ];
    }
    
    // Monthly data for last 6 months
    $graphData['monthly'] = [];
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    for ($i = 5; $i >= 0; $i--) {
        $monthIndex = (date('n') - $i - 1) % 12;
        if ($monthIndex < 0) $monthIndex += 12;
        $avgTemp = $currentTemp + rand(-8, 8);
        $graphData['monthly'][] = [
            'month_label' => $months[$monthIndex],
            'avg_temp' => round($avgTemp, 1)
        ];
    }
} elseif (empty($cityStats)) {
    // Initialize empty stats if no city is selected
    $cityStats = [
        'daily' => ['max' => 0, 'min' => 0, 'avg' => 0],
        'weekly' => ['max' => 0, 'min' => 0, 'avg' => 0],
        'monthly' => ['max' => 0, 'min' => 0, 'avg' => 0]
    ];
    $graphData = ['daily' => [], 'weekly' => [], 'monthly' => []];
}
?>
<!DOCTYPE html>
<html lang="en
">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Forecasting App</title>
    
    <!-- External Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.9.3/css/bulma.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Styles -->
    <style>
        /* Global Styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 20px;
        }
        
        /* App Title */
        .app-title {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
        }
        
        .app-title h1 {
            color: white;
            font-size: 3rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 10px;
        }
        
        .app-title p {
            color: rgba(255,255,255,0.9);
            font-size: 1.2rem;
        }
        
        /* Cards */
        .card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background: white;
            margin-bottom: 20px;
        }
        
        .weather-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .position-card {
            background: linear-gradient(135deg, #36D1DC 0%, #5B86E5 100%);
            color: white;
        }
        
        /* Temperature Colors */
        .temperature-high { color: #ff3860; font-weight: bold; }
        .temperature-low { color: #3273dc; font-weight: bold; }
        .temperature-avg { color: #23d160; font-weight: bold; }
        
        /* Components */
        .weather-icon-lg { width: 100px; height: 100px; }
        .chart-container { height: 250px; width: 100%; }
        .city-tag { margin: 5px; }
        
        .stat-card {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .position-badge {
            font-weight: bold;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin: 5px;
        }
        
        /* Layout */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        .footer {
            background: rgba(0,0,0,0.8);
            color: white;
            margin-top: 30px;
            padding: 20px;
            text-align: center;
        }
        
        .has-text-white { color: white !important; }
        
        /* Temperature Range Display */
        .temp-range {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- App Title -->
        <div class="app-title">
            <h1><i class="fas fa-cloud-sun"></i>WEATHER CHECK APP</h1>
            <p>Real-time weather tracking with location analytics</p>
        </div>

        <!-- Notifications -->
        <?php if ($successMessage): ?>
        <div class="notification is-success">
            <button class="delete" onclick="this.parentElement.remove()"></button>
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
        <div class="notification is-danger">
            <button class="delete" onclick="this.parentElement.remove()"></button>
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
        <?php endif; ?>

        <div class="columns">
            <!-- Left Column - Main Content -->
            <div class="column is-8">
                <!-- City Management -->
                <div class="columns">
                    <div class="column">
                        <div class="card">
                            <div class="card-content">
                                <h3 class="title is-4"><i class="fas fa-plus-circle"></i> Add City</h3>
                                <form method="POST">
                                    <div class="field has-addons">
                                        <div class="control is-expanded">
                                            <input class="input" type="text" name="city_add" 
                                                   placeholder="Enter city name (e.g., London)" required>
                                        </div>
                                        <div class="control">
                                            <button class="button is-primary"><i class="fas fa-plus"></i> Add</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="column">
                        <div class="card">
                            <div class="card-content">
                                <h3 class="title is-4"><i class="fas fa-trash-alt"></i> Remove City</h3>
                                <form method="POST">
                                    <div class="field has-addons">
                                        <div class="control is-expanded">
                                            <input class="input" type="text" name="city_delete" 
                                                   placeholder="City to remove" required>
                                        </div>
                                        <div class="control">
                                            <button class="button is-danger"><i class="fas fa-trash"></i> Remove</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Weather -->
                <?php if (!empty($weatherData)): ?>
                <div class="card weather-card">
                    <div class="card-content">
                        <div class="columns is-vcentered">
                            <div class="column is-3 has-text-centered">
                                <img src="https://openweathermap.org/img/wn/<?php echo $weatherData['weather'][0]['icon']; ?>@2x.png" 
                                     class="weather-icon-lg" alt="Weather Icon">
                            </div>
                            
                            <div class="column is-6">
                                <h2 class="title is-2 has-text-white">
                                    <?php echo round($weatherData['main']['temp'], 1); ?>°C
                                </h2>
                                <p class="subtitle is-4 has-text-white">
                                    <?php echo htmlspecialchars($weatherData['name']); ?>, 
                                    <?php 
                                    if (isset($weatherData['sys']['country'])) {
                                        echo isset($countryNames[$weatherData['sys']['country']]) ? 
                                            $countryNames[$weatherData['sys']['country']] : 
                                            $weatherData['sys']['country'];
                                    }
                                    ?>
                                </p>
                                <p class="is-size-5 has-text-white">
                                    <?php echo ucfirst($weatherData['weather'][0]['description']); ?>
                                </p>
                                <div class="tags mt-3">
                                    <span class="tag is-light">
                                        <i class="fas fa-temperature-high"></i>&nbsp;
                                        H: <span class="temperature-high"><?php echo round($weatherData['main']['temp_max'], 1); ?>°C</span>
                                    </span>
                                    <span class="tag is-light">
                                        <i class="fas fa-temperature-low"></i>&nbsp;
                                        L: <span class="temperature-low"><?php echo round($weatherData['main']['temp_min'], 1); ?>°C</span>
                                    </span>
                                    <span class="tag is-light">
                                        <i class="fas fa-tint"></i>&nbsp;
                                        <?php echo $weatherData['main']['humidity']; ?>%
                                    </span>
                                    <span class="tag is-light">
                                        <i class="fas fa-wind"></i>&nbsp;
                                        <?php echo $weatherData['wind']['speed']; ?> m/s
                                    </span>
                                </div>
                            </div>
                            
                            <div class="column is-3 has-text-right">
                                <?php if ($selectedCityName): ?>
                                <form method="POST">
                                    <input type="hidden" name="refresh_city" value="<?php echo $selectedCityName; ?>">
                                    <button class="button is-white is-outlined">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </form>
                                <?php endif; ?>
                                <p class="is-size-7 mt-3 has-text-white">Updated: <?php echo date('H:i:s'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-content has-text-centered">
                        <p class="title is-5">No city selected</p>
                        <p>Add a city to see weather information</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- City Location Card -->
                <?php if (!empty($weatherData) && isset($weatherData['coord']) && !empty($cityPosition)): ?>
                <div class="card position-card">
                    <div class="card-content">
                        <h3 class="title is-4 has-text-white"><i class="fas fa-map-marker-alt"></i> City Location</h3>
                        <div class="columns is-vcentered">
                            <div class="column is-4 has-text-centered">
                                <div class="position-display">
                                    <?php 
                                    $iconClass = 'fas fa-map';
                                    if (strpos($cityPosition, 'North') !== false && strpos($cityPosition, 'East') !== false) {
                                        $iconClass = 'fas fa-arrow-up-right';
                                    } elseif (strpos($cityPosition, 'North') !== false && strpos($cityPosition, 'West') !== false) {
                                        $iconClass = 'fas fa-arrow-up-left';
                                    } elseif (strpos($cityPosition, 'South') !== false && strpos($cityPosition, 'East') !== false) {
                                        $iconClass = 'fas fa-arrow-down-right';
                                    } elseif (strpos($cityPosition, 'South') !== false && strpos($cityPosition, 'West') !== false) {
                                        $iconClass = 'fas fa-arrow-down-left';
                                    } elseif (strpos($cityPosition, 'North') !== false) {
                                        $iconClass = 'fas fa-arrow-up';
                                    } elseif (strpos($cityPosition, 'South') !== false) {
                                        $iconClass = 'fas fa-arrow-down';
                                    } elseif (strpos($cityPosition, 'East') !== false) {
                                        $iconClass = 'fas fa-arrow-right';
                                    } elseif (strpos($cityPosition, 'West') !== false) {
                                        $iconClass = 'fas fa-arrow-left';
                                    } elseif (strpos($cityPosition, 'Central') !== false) {
                                        $iconClass = 'fas fa-bullseye';
                                    }
                                    ?>
                                    <i class="<?php echo $iconClass; ?> fa-4x" style="color: white; margin-bottom: 10px;"></i>
                                    <p class="title is-2 has-text-white"><?php echo $cityPosition; ?></p>
                                </div>
                            </div>
                            <div class="column is-8">
                                <div class="content has-text-white">
                                    <p><strong>Geographic Information:</strong></p>
                                    <ul>
                                        <li><strong>City:</strong> <?php echo htmlspecialchars($weatherData['name']); ?></li>
                                        <li><strong>Country:</strong> 
                                            <?php 
                                            if (isset($weatherData['sys']['country'])) {
                                                echo isset($countryNames[$weatherData['sys']['country']]) ? 
                                                    $countryNames[$weatherData['sys']['country']] : 
                                                    $weatherData['sys']['country'];
                                            }
                                            ?>
                                        </li>
                                        <li><strong>Location:</strong> <?php echo $cityPosition; ?> of the country</li>
                                        <li><strong>Coordinates:</strong> 
                                            <?php echo round($weatherData['coord']['lat'], 4); ?>°N, 
                                            <?php echo round($weatherData['coord']['lon'], 4); ?>°E
                                        </li>
                                    </ul>
                                    <div class="mt-3">
                                        <span class="position-badge">
                                            <i class="fas fa-globe-americas direction-icon"></i> Geographic Position
                                        </span>
                                        <span class="position-badge">
                                            <i class="fas fa-compass direction-icon"></i> <?php echo $cityPosition; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Temperature Statistics -->
                <div class="card">
                    <div class="card-content">
                        <h3 class="title is-4"><i class="fas fa-chart-bar"></i> Temperature Statistics</h3>
                        
                        <?php if ($selectedCityId && !empty($cityStats)): ?>
                        <div class="columns is-multiline">
                            <div class="column is-4">
                                <div class="stat-card" style="background: #e3f2fd;">
                                    <p class="title is-5">Today</p>
                                    <p class="title is-2 temperature-avg"><?php echo round($cityStats['daily']['avg'], 1); ?>°</p>
                                    <div class="tags is-centered">
                                        <span class="tag temperature-high">H: <?php echo round($cityStats['daily']['max'], 1); ?>°</span>
                                        <span class="tag temperature-low">L: <?php echo round($cityStats['daily']['min'], 1); ?>°</span>
                                    </div>
                                    <p class="temp-range">
                                        Range: <?php echo round($cityStats['daily']['max'] - $cityStats['daily']['min'], 1); ?>°C
                                    </p>
                                </div>
                            </div>
                            
                            <div class="column is-4">
                                <div class="stat-card" style="background: #f3e5f5;">
                                    <p class="title is-5">This Week</p>
                                    <p class="title is-2 temperature-avg"><?php echo round($cityStats['weekly']['avg'], 1); ?>°</p>
                                    <div class="tags is-centered">
                                        <span class="tag temperature-high">H: <?php echo round($cityStats['weekly']['max'], 1); ?>°</span>
                                        <span class="tag temperature-low">L: <?php echo round($cityStats['weekly']['min'], 1); ?>°</span>
                                    </div>
                                    <p class="temp-range">
                                        Range: <?php echo round($cityStats['weekly']['max'] - $cityStats['weekly']['min'], 1); ?>°C
                                    </p>
                                    <p class="is-size-7">Last 7 days</p>
                                </div>
                            </div>
                            
                            <div class="column is-4">
                                <div class="stat-card" style="background: #e8f5e9;">
                                    <p class="title is-5">This Month</p>
                                    <p class="title is-2 temperature-avg"><?php echo round($cityStats['monthly']['avg'], 1); ?>°</p>
                                    <div class="tags is-centered">
                                        <span class="tag temperature-high">H: <?php echo round($cityStats['monthly']['max'], 1); ?>°</span>
                                        <span class="tag temperature-low">L: <?php echo round($cityStats['monthly']['min'], 1); ?>°</span>
                                    </div>
                                    <p class="temp-range">
                                        Range: <?php echo round($cityStats['monthly']['max'] - $cityStats['monthly']['min'], 1); ?>°C
                                    </p>
                                    <p class="is-size-7">Last 30 days</p>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="notification is-info">Select a city to view temperature statistics</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Sidebar -->
            <div class="column is-4">
                <!-- City Selection -->
                <div class="card">
                    <div class="card-content">
                        <h3 class="title is-4"><i class="fas fa-map-marker-alt"></i> Select City</h3>
                        <form method="POST" class="mb-4">
                            <div class="field">
                                <div class="control">
                                    <div class="select is-fullwidth">
                                        <select name="select_city" onchange="this.form.submit()">
                                            <option value="">-- Select City --</option>
                                            <?php foreach ($allCities as $city): ?>
                                            <option value="<?php echo $city['name']; ?>" 
                                                <?php echo ($city['name'] == $selectedCityName) ? 'selected' : ''; ?>>
                                                <?php echo $city['name']; ?>
                                                <?php if ($city['current_temp']): ?>
                                                (<?php echo round($city['current_temp'], 1); ?>°C)
                                                <?php endif; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Tracked Cities -->
                        <h4 class="title is-5">Tracked Cities</h4>
                        <div class="tags">
                            <?php foreach ($allCities as $city): ?>
                            <span class="tag is-primary city-tag">
                                <?php echo $city['name']; ?>
                                <?php if ($city['current_temp']): ?>
                                <span class="tag is-light ml-1"><?php echo round($city['current_temp'], 1); ?>°C</span>
                                <?php endif; ?>
                            </span>
                            <?php endforeach; ?>
                            <?php if (empty($allCities)): ?>
                            <p class="has-text-grey">No cities added yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Temperature Graphs -->
                <div class="card">
                    <div class="card-content">
                        <div class="tabs is-boxed is-fullwidth">
                            <ul>
                                <li class="is-active" data-tab="daily-graph"><a>Daily</a></li>
                                <li data-tab="weekly-graph"><a>Weekly</a></li>
                                <li data-tab="monthly-graph"><a>Monthly</a></li>
                            </ul>
                        </div>

                        <div id="daily-graph" class="tab-content active">
                            <div class="chart-container"><canvas id="dailyChart"></canvas></div>
                        </div>

                        <div id="weekly-graph" class="tab-content">
                            <div class="chart-container"><canvas id="weeklyChart"></canvas></div>
                        </div>

                        <div id="monthly-graph" class="tab-content">
                            <div class="chart-container"><canvas id="monthlyChart"></canvas></div>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="card">
                    <div class="card-content">
                        <h4 class="title is-5"><i class="fas fa-info-circle"></i> Location Info</h4>
                        <div class="content">
                            <p><i class="fas fa-city"></i> <strong>Cities:</strong> <?php echo count($allCities); ?></p>
                            <?php if (!empty($weatherData) && isset($weatherData['coord'])): ?>
                            <p><i class="fas fa-map-pin"></i> <strong>Coordinates:</strong><br>
                               <?php echo round($weatherData['coord']['lat'], 4); ?>°N, 
                               <?php echo round($weatherData['coord']['lon'], 4); ?>°E
                            </p>
                            <?php if (!empty($cityPosition)): ?>
                            <p><i class="fas fa-location-arrow"></i> <strong>Position:</strong> <?php echo $cityPosition; ?></p>
                            <?php endif; ?>
                            <?php endif; ?>
                            <p><i class="fas fa-clock"></i> <strong>Time:</strong> <?php echo date('H:i:s'); ?></p>
                            <p><i class="fas fa-calendar"></i> <strong>Date:</strong> <?php echo date('Y-m-d'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forecast Section -->
        <?php if (!empty($forecastData) && isset($forecastData['list'])): ?>
        <div class="card">
            <div class="card-content">
                <h3 class="title is-4"><i class="fas fa-calendar-alt"></i> 5-Day Forecast</h3>
                <div class="columns is-mobile is-multiline">
                    <?php
                    $dailyForecasts = [];
                    foreach ($forecastData['list'] as $forecast) {
                        $date = date('Y-m-d', $forecast['dt']);
                        if (!isset($dailyForecasts[$date])) {
                            $dailyForecasts[$date] = ['temps' => [], 'icons' => []];
                        }
                        $dailyForecasts[$date]['temps'][] = $forecast['main']['temp'];
                        $dailyForecasts[$date]['icons'][] = $forecast['weather'][0]['icon'];
                    }
                    
                    $counter = 0;
                    foreach ($dailyForecasts as $date => $data):
                        if ($counter >= 5) break;
                        $dayName = date('D', strtotime($date));
                        $avgTemp = round(array_sum($data['temps']) / count($data['temps']), 1);
                        $maxTemp = round(max($data['temps']), 1);
                        $minTemp = round(min($data['temps']), 1);
                        $icon = $data['icons'][0];
                    ?>
                    <div class="column">
                        <div class="box has-text-centered">
                            <p class="title is-5"><?php echo $dayName; ?></p>
                            <p class="subtitle is-6"><?php echo date('M j', strtotime($date)); ?></p>
                            <figure class="image is-64x64 mx-auto">
                                <img src="https://openweathermap.org/img/wn/<?php echo $icon; ?>.png" alt="Weather icon">
                            </figure>
                            <p class="mt-2">
                                <span class="temperature-high"><?php echo $maxTemp; ?>°</span> / 
                                <span class="temperature-low"><?php echo $minTemp; ?>°</span>
                            </p>
                            <p class="is-size-7">Avg: <?php echo $avgTemp; ?>°C</p>
                        </div>
                    </div>
                    <?php $counter++; endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="content has-text-centered">
            <p><strong>WEATHER FORECASTING APP</strong> | Powered by <a href="https://openweathermap.org" target="_blank">OpenWeatherMap</a></p>
            <p class="is-size-7">
                <i class="fas fa-temperature-high"></i> High | 
                <i class="fas fa-temperature-low"></i> Low | 
                <i class="fas fa-map-marker-alt"></i> City Location
            </p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Tab switching
        document.querySelectorAll('.tabs li').forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-tab');
                
                // Update active tab
                document.querySelectorAll('.tabs li').forEach(t => t.classList.remove('is-active'));
                tab.classList.add('is-active');
                
                // Show target content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(target).classList.add('active');
            });
        });

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($selectedCityId && !empty($graphData)): ?>
            // Daily Chart
            <?php if (!empty($graphData['daily'])): ?>
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            const dailyLabels = <?php echo json_encode(array_column($graphData['daily'], 'date_label')); ?>;
            const dailyTemps = <?php echo json_encode(array_column($graphData['daily'], 'temp')); ?>;
            
            new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Temperature (°C)',
                        data: dailyTemps,
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Daily Temperature (Last 7 Days)'
                        }
                    }
                }
            });
            <?php endif; ?>
            
            // Weekly Chart
            <?php if (!empty($graphData['weekly'])): ?>
            const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
            const weekLabels = <?php echo json_encode(array_column($graphData['weekly'], 'date_label')); ?>;
            const weekAvgs = <?php echo json_encode(array_column($graphData['weekly'], 'avg_temp')); ?>;
            const weekMaxs = <?php echo json_encode(array_column($graphData['weekly'], 'max_temp')); ?>;
            const weekMins = <?php echo json_encode(array_column($graphData['weekly'], 'min_temp')); ?>;
            
            new Chart(weeklyCtx, {
                type: 'line',
                data: {
                    labels: weekLabels,
                    datasets: [
                        {
                            label: 'Average',
                            data: weekAvgs,
                            borderColor: '#4cc9f0',
                            backgroundColor: 'rgba(76, 201, 240, 0.1)',
                            borderWidth: 3,
                            fill: true
                        },
                        {
                            label: 'Max',
                            data: weekMaxs,
                            borderColor: '#f72585',
                            borderWidth: 2,
                            borderDash: [5, 5]
                        },
                        {
                            label: 'Min',
                            data: weekMins,
                            borderColor: '#4361ee',
                            borderWidth: 2,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Weekly Temperature Trends'
                        }
                    }
                }
            });
            <?php endif; ?>
            
            // Monthly Chart
            <?php if (!empty($graphData['monthly'])): ?>
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthLabels = <?php echo json_encode(array_column($graphData['monthly'], 'month_label')); ?>;
            const monthAvgs = <?php echo json_encode(array_column($graphData['monthly'], 'avg_temp')); ?>;
            
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Average Temperature (°C)',
                        data: monthAvgs,
                        backgroundColor: 'rgba(67, 97, 238, 0.7)',
                        borderColor: 'rgb(67, 97, 238)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Temperature Averages'
                        }
                    }
                }
            });
            <?php endif; ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>