<?php

$router->post('position-letter', 'LetterController@create');
$router->get('position-letter', 'LetterController@list');
$router->get('position-letter-general', 'LetterController@listGeneral');
$router->get('position-letter/{id}/', 'LetterController@detail');
$router->patch('position-letter/{id}/', 'LetterController@update');
$router->get('position-letter/delete/{id}/', 'LetterController@delete');