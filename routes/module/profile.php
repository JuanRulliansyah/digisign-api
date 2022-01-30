<?php

$router->post('profile', 'ProfileController@create');
$router->get('profile-list', 'ProfileController@list');
$router->get('profile-general-list', 'ProfileController@generalList');
$router->get('profile/{id}/', 'ProfileController@detail');
$router->patch('profile/{id}/', 'ProfileController@update');
$router->get('profile/delete/{id}/', 'ProfileController@delete');
$router->get('profile-requirement/', 'ProfileController@requirement');