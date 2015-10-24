<?php
header('Content-type: application/json');
header("access-control-allow-origin: *");
include_once "domain.php";
include_once "helper.php";

if(isset($_GET['from']) && isset($_GET['to']))
{
$from = $_GET['from'];
$to = $_GET['to'];
$urlFrom=$domain."/v1/stops/$from?with_stop_sequences=true";
$urlTo=$domain."/v1/stops/$to?with_stop_sequences=true";

//  Initiate curl
$chFrom = localCurl($urlFrom);
$chTo = localCurl($urlTo);

// Execute
$resultFrom=curl_exec($chFrom);
$resultTo=curl_exec($chTo);
// Closing
curl_close($chFrom);
curl_close($chTo);

$response  = array();
// Will dump a beauty json :3
$stopFrom = json_decode($resultFrom, true);
$stopTo = json_decode($resultTo, true);
$travel = 0;
$searchedStop = array();
foreach ($stopFrom['data']['routes'] as $key => $routeFrom) {
	foreach ($routeFrom['trips'] as $key => $tripFrom) {
		foreach ($tripFrom['stop_sequences'] as $key => $stopSeqFrom) {
			// 1: buscar el destino en las stop sequence del origen
			if($stopSeqFrom['stop_id'] == $to){
				$fromSequence = -1;
				foreach ($tripFrom['stop_sequences'] as $key => $fromSequenceOnTrip) {
					if($fromSequenceOnTrip['stop_id'] == $stopFrom['data']['id'])
						$fromSequence = $fromSequenceOnTrip['sequence'];
				}
				$travel += 1;
				//echo '<label style="padding-left: 10px;">Travel('.$travel.') '.$routeFrom['name'].': '.$stopFrom['data']['name'].' - '.$stopTo['data']['name'].'</label><br>';
				$total = $fromSequence > $stopSeqFrom['sequence'] ? $fromSequence - $stopSeqFrom['sequence'] : $stopSeqFrom['sequence'] - $fromSequence;
				array_push($response, loadResponse(
					1, 
					$travel, 
					array($routeFrom), 
					array($stopSeqFrom['trip_id']), 
					array($stopFrom), 
					array($stopTo), 
					array($fromSequence), 
					array($stopSeqFrom['sequence']), 
					$total));
			}
		}
		if(count($response) == 0){
			foreach ($tripFrom['stop_sequences'] as $key => $stopSeqFrom) {
				foreach ($stopTo['data']['routes'] as $key => $routeTo) {
					foreach ($routeTo['trips'] as $key => $tripTo) {
						foreach ($tripTo['stop_sequences'] as $key => $stopSeqTo) {
							if(!in_array($stopSeqFrom['stop_id'], $searchedStop) // un from stop que no ha sido buscado anteriormente
							  && $stopSeqFrom['stop_id'] == $stopSeqTo['stop_id'] // un from stop que este en stop sequences de To
							  && $stopSeqFrom['stop_id'] != $stopFrom['data']['id']) { // un from stop que sea diferente al del origen 
								array_push($searchedStop, $stopSeqFrom['stop_id']);
	 							// 2: buscar cada uno de los stop sequence del origen el los stop sequence del destino
								$chStop = localCurl($domain."/v1/stops/".$stopSeqTo['stop_id']);
								$toStopObj=json_decode(curl_exec($chStop), true);
								curl_close($chStop);
								$travel += 1;
								//echo '<label style="padding-left: 10px;">Travel('.$travel.') '.$routeFrom['name'].': '.$stopFrom['data']['name'].' - '.$toStopObj['data']['name'].'</label><br>';	
								//echo '<label style="padding-left: 10px;">Travel('.$travel.') '.$routeTo['name'].': '.$toStopObj['data']['name'].' - '.$stopTo['data']['name'].'</label><br>';
								$fromSequence = -1;
								$toSequence = -1;
								foreach ($tripFrom['stop_sequences'] as $key => $fromSequenceOnTrip) {
									if($fromSequenceOnTrip['stop_id'] == $stopFrom['data']['id'])
										$fromSequence = $fromSequenceOnTrip['sequence'];
								}
								foreach ($tripTo['stop_sequences'] as $key => $toSequenceOnTrip) {
									if($toSequenceOnTrip['stop_id'] == $stopTo['data']['id'])
										$toSequence = $toSequenceOnTrip['sequence'];
								}
								$total = ($fromSequence > $stopSeqFrom['sequence'] ? $fromSequence - $stopSeqFrom['sequence'] : $stopSeqFrom['sequence'] - $fromSequence) 
								+ ($stopSeqTo['sequence'] > $toSequence ? $stopSeqTo['sequence'] - $toSequence : $toSequence - $stopSeqTo['sequence']);
								array_push($response, loadResponse(
									2, 
									$travel, 
									array($routeFrom, $routeTo), 
									array($stopSeqFrom['trip_id'],$stopSeqTo['trip_id']), 
									array($stopFrom, $toStopObj), 
									array($toStopObj,$stopTo), 
									array($fromSequence, $stopSeqTo['sequence']), 
									array($stopSeqFrom['sequence'],$toSequence), 
									$total));
							}
						}
					}
				}
			}
		}
	}
}

/* 
 * sino obtuvo resultados
 */
 
/* sino obtuvo resultados
 * 3: desplegar todos los stop sequence de los stop sequence del origen y buscar el destino ahi
 * 
 * al ir encontrando el destino en los stop sequence poner el resultado en un array resultante
 * transformarlo en json y retornarlo
 */
 $travelIndex = 1;
 usort($response, 'sortByTotal');
 /*foreach ($response as $key => $travel) {
 	echo json_encode($travel).'<br><br>';
	echo '*****************************<br>';
 }*/
echo json_encode($response);
}
function sortByTotal($a, $b) {
    return $a['total'] - $b['total'];
}

function loadResponse($transfers, $travel, $routes, $trips, $froms, $tos, $seqFroms, $seqTos, $total){
	$route_names = array();
	$trip_ids = array();
	$urls = array();
	$from_ids = array();
	$from_names = array();
	$to_ids = array();
	$to_names = array();
	$seq_froms = array();
	$seq_tos = array();
	
	for ($i=0; $i < count($routes); $i++) { 
		array_push($route_names, $routes[$i]['name']);
		array_push($trip_ids, $trips[$i]);
		array_push($urls, $routes[$i]['url']);
		array_push($from_ids, $froms[$i]['data']['id']);
		array_push($from_names, $froms[$i]['data']['name']);
		array_push($to_ids, $tos[$i]['data']['id']);
		array_push($to_names, $tos[$i]['data']['name']);
		array_push($seq_froms, $seqFroms[$i]);
		array_push($seq_tos, $seqTos[$i]);
	}	
	return array('travel' => $travel, 
		'route_names' => $route_names,
		'urls' => $urls,
		'trip_ids' => $trip_ids,
		'from_ids' => $from_ids, 
		'from_names' => $from_names,
		'to_ids' => $to_ids,
		'to_names' => $to_names,
		'seq_froms' => $seq_froms,
		'seq_tos' => $seq_tos,
		'total' => $total);
}
?>
