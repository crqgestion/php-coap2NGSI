<?php
require( __DIR__ . '/vendor/autoload.php' );
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

define ("SERVER",'http://fiware.crqgestion.es:1026/v1/updateContext');
define("POST_TIME",5); /*Minutes*/


function data2atributes($data){
	$key_attr=array(
		't'=> 'temperature',
		'h'=> 'humididy',
		'vb'=>'battery voltage',
		'al'=>'ambient light',
		'dt'=>'dht temp',
		'dh'=>'dht humidity',
		'p'=>'presence'
	);

	$atributes=array();
	foreach($data as $key => $value) {
		$atribute=array();
		$atribute['name']=$key_attr[$key];
		$atribute['type']='float';
		$atribute['value']=floatval($value);
		array_push($atributes,$atribute);
	}
	return json_encode($atributes);
}
function Options2ID($opts){	
	$id_opt=( end( explode('/',$opts) ) );
	return (intval($id_opt));
}
function coap2NGSI_str($coap_req,$uri){
	$atributes=data2atributes($coap_req->data);
	$id=Options2ID($uri);
	
	$obj=("{
		\"contextElements\": [
				{
						\"type\": \"Sensor\",
						\"isPattern\": \"false\",
						\"id\": $id,
						\"attributes\": $atributes
				}
		],
		\"updateAction\": \"APPEND\"
	}");

	return (json_decode($obj));/*As string*/
}
function send2NGSI ($data_str){
	$http_client = new GuzzleHttp\Client();
	try{
		$response = $http_client->post(SERVER , 
			[
				'body' => json_encode($data_str),
				'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json']
			]
		);
	}catch (RequestException $e) {
		return null;
	}
	return ($response->json());
}
	$uri=($_SERVER['REQUEST_URI']);
	$post = file_get_contents('php://input');
	$ngsi=coap2NGSI_str(json_decode($post),$uri);
	

	/*Response*/
	$http_response=send2NGSI($ngsi);

	$ack=array();
	$ack['next_push']=(POST_TIME*60)-(time()%(POST_TIME*60));
	$ack['act']=[];
	
	
	echo json_encode($ack);


?>
