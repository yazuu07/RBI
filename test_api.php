<?php
// test_api.php - Test the citizens API
require_once 'config.php';

// Try to call the API directly
$api_url = 'http://localhost/dashboard/RBI/api/citizens.php';

echo "<h2>API Test</h2>";

// Use cURL to test the API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: " . $http_code . "<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";

if ($http_code == 200) {
    echo "✅ API is working!";
} else {
    echo "❌ API is NOT working. Check the path.";
    echo "<br>Try changing the URL to: /dashboard/RBI/api/citizens.php";
}
?>