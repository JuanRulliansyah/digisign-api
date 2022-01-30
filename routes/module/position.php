<?php

$router->post('position', 'PositionController@create');
$router->get('positions', 'PositionController@list');
$router->get('position/{id}/', 'PositionController@detail');
$router->patch('position/{id}/', 'PositionController@update');
$router->get('position/delete/{id}/', 'PositionController@delete');