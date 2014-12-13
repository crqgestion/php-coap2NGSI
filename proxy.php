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
	$id_opt=(end($opts));
	return ($id_opt->getValue());
}
function coap2NGSI_str($coap_req){
	$_ngsi=json_decode($coap_req->getPayload());
	$atributes=data2atributes($_ngsi->data);
	$id=Options2ID($coap_req->GetOptions());
	
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

$loop = React\EventLoop\Factory::create();

$server = new PhpCoap\Server\Server( $loop );

$server->receive( 5683, '[::]' );

$server->on( 'request', function( $req, $res, $handler ) {
	$ngsi=coap2NGSI_str($req);
	/*Response*/
	$http_response=send2NGSI($ngsi);
	var_dump($http_response);
	$ack=array();
	$ack['next_push']=(POST_TIME*60)-(time()%(POST_TIME*60));
	$ack['act']=[];
	
	$res->setPayload( json_encode( $ack ));
	$handler->send( $res );
});

$loop->run();

?>
