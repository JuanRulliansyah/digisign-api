<?php

$router->post('module', 'ModuleController@create');
$router->get('module', 'ModuleController@list');
$router->get('module/user/', 'ModuleController@listUser');
$router->get('module/{id}/', 'ModuleController@detail');
$router->patch('module/{id}/', 'ModuleController@update');
$router->get('module/delete/{id}/', 'ModuleController@delete');
