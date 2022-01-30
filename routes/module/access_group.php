<?php

$router->post('access-group', 'GroupController@create');
$router->get('access-group', 'GroupController@list');
$router->get('access-group/{id}/', 'GroupController@detail');
$router->patch('access-group/{id}/', 'GroupController@update');
$router->get('access-group/delete/{id}/', 'GroupController@delete');
