<?php
header('Content-type: application/json');
header("access-control-allow-origin: *");
include_once "domain.php";
include_once "helper.php";

if(isset($_GET['trip']) && isset($_GET['from']) && isset($_GET['to'])){
	$from = $_GET['from'];
	$to = $_GET['to'];	
	$trip = $_GET['trip'];
	$tripUrl=$domain."/v1/trips/$trip";
	
	$chTrip = localCurl($tripUrl);
	$tripsResult=curl_exec($chTrip);
	
	curl_close($chTrip);
	
	$trips = json_decode($tripsResult, true);
	
	$stop_sequences = $trips['data']['stop_sequences'];
	$response = array();
	$fromSeq = -1;
	$toSeq = -1;
	foreach ($stop_sequences as $key => $value) {
		// obten las secuencias de from y to
		if($from == $value['stop']['id'])
			$fromSeq = $value['sequence'];
		if($to == $value['stop']['id'])
			$toSeq = $value['sequence'];
	}
	for ($i=$fromSeq<$toSeq?$fromSeq:$toSeq; $i <= ($fromSeq<$toSeq?$toSeq:$fromSeq) - ($fromSeq>$toSeq?$toSeq:$fromSeq) + 1; $i++) { 
		array_push($response, $stop_sequences[$i]);
	}
	
	// si el from es mayor que el to entonces invertir el arreglo de stops
	if($fromSeq > $toSeq)
		$response = array_reverse($response);
	echo json_encode($response);
}
?>