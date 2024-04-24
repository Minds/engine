<?php

use Zend\Diactoros\ServerRequestFactory;

require_once(dirname(__FILE__) . "/start.php");

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$router = new Minds\Core\Router();
$router->route(ServerRequestFactory::fromGlobals());
