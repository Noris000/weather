<?php
class WeatherAPI {
    private $apiUrl;
    private $apiKey;

    public function __construct($apiUrl, $apiKey) {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function fetchWeatherData($location, $days) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://{$this->apiUrl}/forecast.json?q={$location}&days={$days}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "X-RapidAPI-Host: {$this->apiUrl}",
                "X-RapidAPI-Key: {$this->apiKey}"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        return ['err' => $err, 'response' => $response];
    }
}

class WeatherApp {
    private $weatherAPI;

    public function __construct($apiUrl, $apiKey) {
        $this->weatherAPI = new WeatherAPI($apiUrl, $apiKey);
    }

    public function getWeatherData($location, $days) {
        return $this->weatherAPI->fetchWeatherData($location, $days);
    }
}

$apiUrl = "weatherapi-com.p.rapidapi.com";
$apiKey = "2c43607887mshe946d9968ce1a2dp15ab6cjsn55a17fcc5994";

$weatherApp = new WeatherApp($apiUrl, $apiKey);

// Initialize variables with default values
$cityName = "N/A";
$temperature = "N/A";
$realFeel = "N/A";
$chanceOfRainToday = "N/A";
$wind = "N/A";
$uvIndex = "N/A";
$forecastHours = [];
$errors = [];
$timesToDisplay = ['06:00', '09:00', '12:00', '15:00', '18:00', '21:00'];
$city = "cesis";

if (!empty($_GET["city"])) {
    $city = $_GET["city"];
}
// Fetch weather data for the entered city
$result = $weatherApp->getWeatherData($city, 3);
$err = $result['err'];
$response = $result['response'];

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    // Parse the API response data as JSON
    $data = json_decode($response, true);

    // Show Api for city
    
    // echo '<pre>';
    // print_r($data);
    // echo '</pre>';

    // Update weather data based on the newly fetched data
    if (isset($data["error"])) {
        $errors[] = "Not Found!";
    }
    if (!empty($data)) {
        $cityName = $data['location']['name'] ?? "N/A";
        $temperature = isset($data["current"]['temp_c']) ? round($data["current"]['temp_c']) . "°C" : "N/A";
        $realFeel = isset($data['current']['feelslike_c']) ? round($data['current']['feelslike_c']) . "°C" : "N/A";
        $wind = isset($data["current"]['wind_kph']) ? $data["current"]['wind_kph'] . " km/h" : "N/A";
        $uvIndex = $data["current"]['uv'] ?? "N/A";

        // Check if chance of rain data is available for today
        $chanceOfRainToday = isset($data['forecast']['forecastday'][0]['day']['daily_chance_of_rain'])
            ? $data['forecast']['forecastday'][0]['day']['daily_chance_of_rain'] . "%"
            : "N/A";

        // Get hourly forecast data
        $forecastHours = isset($data['forecast']['forecastday'][0]['hour'])
            ? $data['forecast']['forecastday'][0]['hour']
            : [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
<div class="container">
<form method="GET" action="">
    <div class="searchbar">
    <input type="text" placeholder="Search for cities" name="city" autocomplete="off">
        <button type="submit">Search</button>
    </div>
</form>
    <?php
    if (!empty($errors)) {
        foreach ($errors as $error) {
    ?>
            <div class="error"><?= $error ?></div>
    <?php
        }
        die();
    }
    ?>
    <div class="row">
        <div class="column">
            <div class="column">
                <div class="currentforecast">
                    <div class="column">
                        <span class="cityname"><?= $cityName ?></span>
                        <?php
                        // Check if chance of rain data is available for today
                        if ($data && isset($data['forecast']['forecastday'][0]['day']['daily_chance_of_rain'])) {
                            $chanceOfRainToday = $data['forecast']['forecastday'][0]['day']['daily_chance_of_rain'];
                        }
                        ?>
                        <div class="row rain-info">
                            <p>Chance of rain:</p>
                            <span class="rain-chance"><?= $chanceOfRainToday ?>%</span>
                        </div>
                        <div class="gap"></div>
                        <span class="temperature"><?= $temperature ?></span>
                    </div>
                    <div class="column right-column">
                        <img src="<?= $data["current"]["condition"]["icon"] ?>" alt="Weather Icon">
                    </div>
                </div>
                <div class='todaysforecast'>
                    <h3>TODAY'S FORECAST</h3>
                    <div class="column">
                        <div class="row">
                            <!-- time every 3 hours for today's forecast -->
                            <?php
                            foreach ($forecastHours as $hourlyData) {
                                $time = substr($hourlyData['time'], 11, 5); // Extract HH:MM from the time string
                                if (in_array($time, $timesToDisplay)) {
                                    $icon = $hourlyData['condition']['icon'];
                                    $temperature = $hourlyData['temp_c'];

                                    echo "<div class='hourly-forecast'>";
                                    echo "<span class='time'>{$time}</span>";
                                    echo "<span class='icon'><img src='{$icon}' alt='Weather Icon'></span>";
                                    echo "<span class='temperature'> {$temperature}°C</span>";
                                    echo "</div>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="airconditions">
                <h3>AIR CONDITIONS</h3>
                <div class="column">
                    <p><i class="fas fa-thermometer-half"></i> Real Feel</p>
                    <span class="realfeel"><?= $realFeel ?></span>
                    <p><i class="fas fa-cloud-showers-heavy"></i> Chance of rain</p>
                    <?php
                    // Check if chance of rain data is available for today
                    if ($data && isset($data['forecast']['forecastday'][0]['day']['daily_chance_of_rain'])) {
                        $chanceOfRainToday = $data['forecast']['forecastday'][0]['day']['daily_chance_of_rain'];
                    }
                    ?>
                    <span class="rain-chance"><?= $chanceOfRainToday ?>%</span>
                </div>
                <div class="column">
                    <p><i class="fas fa-wind"></i> Wind</p>
                    <span class="wind"><?= $wind ?></span>
                    <p><i class="fas fa-sun"></i> UV index</p>
                    <span class="uvindex"><?= $uvIndex ?></span>
                </div>
                <div class="button-container">
                    <button type="button" class="see-more-button">See more</button>
                </div>
            </div>
        </div>
        <div class="sevendayforecast">
            <h2>3-DAY FORECAST</h2>
            <div class="column">
                <?php
                if ($data && isset($data['forecast']['forecastday'])) {
                    $sevenDayForecast = $data['forecast']['forecastday'];

                    foreach ($sevenDayForecast as $dayData) {
                        $date = $dayData['date'];
                        $dayOfWeek = date('l', strtotime($date));
                        $icon = $dayData['day']['condition']['icon'];
                        $condition = $dayData['day']['condition']['text'];
                        $maxTemp = $dayData['day']['maxtemp_c'];
                        $minTemp = $dayData['day']['mintemp_c'];

                        echo "<div class='day-forecast'>";
                        echo "<p class='day'>$dayOfWeek</p>";
                        echo "<img src='$icon' alt='Weather Icon'";
                        echo "<p class='condition'>$condition</p>";
                        echo "<p class='temp'>$maxTemp / $minTemp</p>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>
    <?php
    // Debug
    // var_dump($data); // or print_r($data);
    ?>
</body>
</html>