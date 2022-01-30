<?php

$router->group(['prefix' => 'auth'], function () use ($router) 
{
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
});