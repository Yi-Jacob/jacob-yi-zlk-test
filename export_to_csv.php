<?php
$mysqli = new mysqli("localhost", "test", "password", "test");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$result = $mysqli->query("SELECT * FROM settlements");

$fp = fopen('./settlements.csv', 'w');

// Add the headers
fputcsv($fp, ['ID', 'Company Name', 'Ticker Symbol', 'Deadline', 'Class Period', 'Settlement Fund', 'Settlement Hearing Date', 'Post URL']);

// Add the rows
while ($row = $result->fetch_assoc()) {
    fputcsv($fp, $row);
}

fclose($fp);
$mysqli->close();
?>
