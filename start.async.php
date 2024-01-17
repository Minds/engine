<?php
/**
 * This is will boot an async server, powered by https://reactphp.org/
 */
require_once(dirname(__FILE__) . "/start.php");

error_reporting(E_ALL);

$router = new Minds\Core\Router();

$http = new React\Http\HttpServer(function (Psr\Http\Message\ServerRequestInterface $request) use ($router) {
    $response = $router->handleRequest($request);
    return $response;
});

$socket = new React\Socket\SocketServer('0.0.0.0:9001');
$http->listen($socket);

echo "Listening on port 9001" .  PHP_EOL;

$http->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    var_dump($e);
});
