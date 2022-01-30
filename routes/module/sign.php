<?php

$router->post('/sign', 'SignController@sign');
$router->get('/sign/{id}/{password}', 'SignController@signGet');

// $router->get('/sign', 'SignController@createPDF');
