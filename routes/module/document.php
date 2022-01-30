<?php

$router->post('document', 'DocumentController@create');
$router->get('document', 'DocumentController@list');
$router->get('document/{id}/', 'DocumentController@detail');
$router->get('document/delete/{id}/', 'DocumentController@delete');
$router->get('document/pdf/{id}/', 'DocumentController@documentPdf');