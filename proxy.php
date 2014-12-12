<?php
require( __DIR__ . '/vendor/autoload.php' );
$loop = React\EventLoop\Factory::create();

$server = new PhpCoap\Server\Server( $loop );

$server->receive( 5683, '[::]' );

$server->on( 'request', function( $req, $res, $handler ) {
	var_dump($req);
	$res->setPayload( json_encode( 'test' ) );
	$handler->send( $res );
});

$loop->run();

?>
