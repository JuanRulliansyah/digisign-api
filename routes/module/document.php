<?php

$router->post('document', 'DocumentController@create');
$router->post('document/share/send/', 'DocumentController@share');
$router->get('document', 'DocumentController@list');
$router->get('document/inbox/', 'DocumentController@inbox');
$router->get('document/outbox/', 'DocumentController@outbox');
$router->get('document/purpose/', 'DocumentController@purpose');
$router->get('document/{id}/', 'DocumentController@detail');
$router->get('document/delete/{id}/', 'DocumentController@delete');
$router->get('document/pdf/{id}/', 'DocumentController@documentPdf');
$router->get('document/share/available-user/', 'DocumentController@availableUser');
$router->get('document/share/sign-list/{id}', 'DocumentController@shareSignList');
