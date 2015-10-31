<?php
if(isset($_GET['from']) && isset($_GET['to']))
{
$from = $_GET['from'];
$to = $_GET['to'];
$urlFrom="http://54.174.147.117:3000/v1/stops/$from?with_stop_sequence=true";
$urlTo="http://54.174.147.117:3000/v1/stops/$to?with_stop_sequence=true";

//  Initiate curl
$chFrom = curl_init();
// Disable SSL verification
curl_setopt($chFrom, CURLOPT_SSL_VERIFYPEER, false);
// Will return the response, if false it print the response
curl_setopt($chFrom, CURLOPT_RETURNTRANSFER, true);
// Set the url
curl_setopt($chFrom, CURLOPT_URL,$urlFrom);

//  Initiate curl
$chTo = curl_init();
// Disable SSL verification
curl_setopt($chTo, CURLOPT_SSL_VERIFYPEER, false);
// Will return the response, if false it print the response
curl_setopt($chTo, CURLOPT_RETURNTRANSFER, true);
// Set the url
curl_setopt($chTo, CURLOPT_URL,$urlTo);

// Execute
$resultFrom=curl_exec($chFrom);
$resultTo=curl_exec($chTo);
// Closing
curl_close($chFrom);
curl_close($chTo);

// Will dump a beauty json :3
$stopFrom = json_decode($resultFrom, true);
$stopTo = json_decode($resultTo, true);
echo '<p>***************FROM:</p><p>'.print_r($stopFrom['data']['routes']).'</p>';
echo '***************TO:</br>'.print_r($stopTo['data']['routes']);
}
?>
