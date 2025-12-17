1. Prerequisites
PHP 7.4+

MySQL 5.7+

Apache/Nginx web server

OpenWeatherMap API key (free from https://openweathermap.org/api)

2. Installation
Save the code as weather_app.php

Place it in your web server directory (e.g., htdocs/weather_app/)

Update database credentials and API key in the CONFIGURATION section

Access via: http://localhost/weather_app/weather_app.php

3. Database Setup
The app auto-creates database and tables on first run. Just ensure:

MySQL is running

Database user has create privileges

📱 How to Use
Add Your First City
Type a city name in "Add City" box (e.g., "London")

Click "Add" - app fetches weather and saves to database

City appears in "Tracked Cities" list

View Weather Information
Select a city from dropdown or tracked cities list

View current weather: Temperature, conditions, humidity, wind

Check statistics: Daily, weekly, monthly temperature ranges

See forecast: 5-day outlook with high/low temperatures

Explore graphs: Switch between daily, weekly, monthly tabs

Manage Cities
Add: Enter name → Add button

Remove: Enter exact name → Remove button

Refresh: Click Refresh button in weather card

🛠️ Technologies Used
Backend:

PHP 7.4+ (Complete logic in one file)

MySQL (Auto-configured database)

OpenWeatherMap API (Weather data)

Frontend:

HTML5/CSS3 (Responsive design)

Bulma CSS Framework (Modern UI components)

Chart.js (Interactive graphs)

Font Awesome (Icons)

JavaScript (Client-side interactivity)