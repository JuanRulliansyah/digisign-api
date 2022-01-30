<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('verify/{validate_id}/', 'DocumentController@verify');

$router->group(['prefix' => 'api'], function () use ($router) 
{
    $route_files = glob(base_path() . '/routes/module/*.php');
    foreach ($route_files as $file) {
        require($file);
    } 
});