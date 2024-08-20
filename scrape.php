<?php
// Required libraries
$mysqli = new mysqli("localhost", "test", "password", "test");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// URL to scrape
$url = "https://zlk.com/settlement";
$html = file_get_contents($url);

// Load HTML into DOMDocument
$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML($html);
libxml_clear_errors();

// Create a new DOMXPath object
$xpath = new DOMXPath($doc);

// XPath queries to extract data
$posts = $xpath->query("//div[contains(@class, 'sl_list_col')]");
foreach ($posts as $post) {
    // Extracting the data
    $company_name_raw = $xpath->query(".//h5[contains(@class, 'primary_color font_w7')]", $post)->item(0)->nodeValue;
    preg_match('/^(.+?) \((.+?)\)$/', trim($company_name_raw), $matches);
    $company_name = $matches[1];
    $ticker_symbol = $matches[2];

    $settlement_fund = trim($xpath->query(".//h5[contains(@class, 'sf_amount')]//span", $post)->item(0)->nodeValue);
    
    $deadline_raw = trim($xpath->query(".//p[contains(@class, 'state_c5')]//span[contains(text(), 'Deadline:')]/following-sibling::text()", $post)->item(0)->nodeValue);
    $deadline = DateTime::createFromFormat('M d, Y', $deadline_raw)->format('Y-m-d H:i:s');

    // Corrected extraction for Settlement Hearing Date
    $settlement_hearing_date_raw = trim($xpath->query(".//p[contains(span/text(), 'Settlement Hearing Date:')]")->item(0)->textContent);
    $settlement_hearing_date_raw = str_replace('Settlement Hearing Date:', '', $settlement_hearing_date_raw);
    $settlement_hearing_date = DateTime::createFromFormat('M d, Y', trim($settlement_hearing_date_raw))->format('Y-m-d H:i:s');
    
    $class_period = trim($xpath->query(".//p[contains(text(), 'Class Period:')]/span/following-sibling::text()", $post)->item(0)->nodeValue);

    $post_url = $xpath->query(".//p[@class='sl_link']/a", $post)->item(0)->getAttribute('href');

    // Prepare SQL insert statement
    $stmt = $mysqli->prepare("INSERT INTO settlements (company_name, ticker_symbol, deadline, class_period, settlement_fund, settlement_hearing_date, post_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $company_name, $ticker_symbol, $deadline, $class_period, $settlement_fund, $settlement_hearing_date, $post_url);
    $stmt->execute();
    $stmt->close();
}

$mysqli->close();
?>
