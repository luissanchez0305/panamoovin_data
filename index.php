<?php
header('Content-type: application/json');
header("access-control-allow-origin: *");
$domain = "http://test-panatrans.herokuapp.com";
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
				array_push($response, loadResponse($travel, $routeFrom, $stopSeqFrom['trip_id'], $stopFrom, $stopTo, $fromSequence, $stopSeqFrom['sequence'], $total));
			}
		}
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
							array_push($response, loadResponse($travel, $routeFrom, $stopSeqFrom['trip_id'], $stopFrom, $toStopObj, $fromSequence, $stopSeqFrom['sequence'], $total));
							array_push($response, loadResponse($travel, $routeTo, $stopSeqTo['trip_id'], $toStopObj, $stopTo, $stopSeqTo['sequence'], $toSequence, $total));						
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
 	if($travel['travel'] != $travelIndex){
 		$travelIndex = $travel['travel'];
		echo '<br>';
	}
	echo '<label style="padding-left: 10px;">Travel('.$travel['travel'].') <a href="'.$travel['url'].'" target="_blank">'.
		$travel['name'].'</a>: '.$travel['from'].' ('.$travel['seqFrom'].') - '.$travel['to'].' ('.$travel['seqTo'].') *Total: '.$travel['total'].'</label><br>';
 }*/
echo json_encode($response);
}
function sortByTotal($a, $b) {
    return $a['total'] - $b['total'];
}

function loadResponse($travel, $route, $trip_id, $from, $to, $seqFrom, $seqTo, $total){	
	return array('travel' => $travel, 
		'name' => $route['name'], 
		'trip_id' => $trip_id,
		'url' => $route['url'], 
		'from_id' => $from['data']['id'],
		'from' => $from['data']['name'], 
		'to_id' => $to['data']['id'],
		'to' => $to['data']['name'],
		'seqFrom' => $seqFrom,
		'seqTo' => $seqTo,
		'total' => $total);
}

function localCurl($url){
//  Initiate curl
$ch = curl_init();
// Disable SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// Will return the response, if false it print the response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Set the url
curl_setopt($ch, CURLOPT_URL,$url);
	
return $ch;
}
?>
