<?php

/*Setup of Structure:

I used XAMPP to host my Database server and Apache Web server

I Created a Database using phpMyAdmin called crypto_db

Followed by Creating a table called crypto_prices using the following script:
   
    CREATE TABLE crypto_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coin_id INT UNIQUE,
        name VARCHAR(50),
        symbol VARCHAR(10),
        price DECIMAL(18,8),
        market_cap DECIMAL(18,2),
        volume_24h DECIMAL(18,2),
        percent_change_1h DECIMAL(10,6),
        percent_change_24h DECIMAL(10,6),
        percent_change_7d DECIMAL(10,6),
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    Started the PHP server using command cd and navigating to Code (fetch_crypto.php)
    and adding php -S localhost:8000

    Opening my localhost the following is diplayed: Data inserted/updated successfully!
*/


$host = '127.0.0.1'; 
$dbname = 'crypto_db';
$username = 'root';   
$password = '';       

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


$apiUrl = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest';
$apiKey = '0669fe37-f45c-4056-9218-73c9781cb4aa'; 

//Setting Parameters to limit to top 50 and also changing Currency to ZAR
$parameters = [
    'start' => '1',
    'limit' => '50',
    'convert' => 'ZAR'
  ];
  
  $qs = http_build_query($parameters); // query string encode the parameters
  $request = "{$apiUrl}?{$qs}"; // create the request URL

  // Initialize cURL to fetch API data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $request);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-CMC_PRO_API_KEY: $apiKey",
    "Accept: application/json"
]);
//Setting HEADERS Above

$response = curl_exec($ch);
if (curl_errno($ch)) {
    die('Error fetching API data: ' . curl_error($ch));
}
curl_close($ch);

// Decode the JSON response
$data = json_decode($response, true);
if ($data === null || !isset($data['data'])) {
    die('Error decoding JSON or invalid API response structure.');
}

// Prepare the SQL statement for inserting/updating data
$sql = "INSERT INTO crypto_prices (coin_id, name, symbol, price, market_cap, volume_24h, percent_change_1h, percent_change_24h, percent_change_7d, last_updated)
        VALUES (:coin_id, :name, :symbol, :price, :market_cap, :volume_24h, :percent_change_1h, :percent_change_24h, :percent_change_7d, :last_updated)
        ON DUPLICATE KEY UPDATE 
            price = VALUES(price),
            market_cap = VALUES(market_cap),
            volume_24h = VALUES(volume_24h),
            percent_change_1h = VALUES(percent_change_1h),
            percent_change_24h = VALUES(percent_change_24h),
            percent_change_7d = VALUES(percent_change_7d),
            last_updated = VALUES(last_updated)";

$stmt = $pdo->prepare($sql);

// Loop through the API data and insert/update into the database
foreach ($data['data'] as $coin) {
    $coin_id = $coin['id'];
    $name = $coin['name'];
    $symbol = $coin['symbol'];
    $quoteZAR = $coin['quote']['ZAR'];
    $price = $quote['price'];
    $market_cap = $quoteZAR['market_cap'];
    $volume_24h = $quoteZAR['volume_24h'];
    $percent_change_1h = $quoteZAR['percent_change_1h'];
    $percent_change_24h = $quoteZAR['percent_change_24h'];
    $percent_change_7d = $quoteZAR['percent_change_7d'];
    $last_updated = date('Y-m-d H:i:s', strtotime($quoteZAR['last_updated']));

    $stmt->execute([
        ':coin_id' => $coin_id,
        ':name' => $name,
        ':symbol' => $symbol,
        ':price' => $price,
        ':market_cap' => $market_cap,
        ':volume_24h' => $volume_24h,
        ':percent_change_1h' => $percent_change_1h,
        ':percent_change_24h' => $percent_change_24h,
        ':percent_change_7d' => $percent_change_7d,
        ':last_updated' => $last_updated
    ]);
}

echo "Data inserted/updated successfully!";
?>
