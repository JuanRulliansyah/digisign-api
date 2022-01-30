<?php

$router->get('region/province', 'RegionController@listProvince');
$router->get('region/city', 'RegionController@listCity');
$router->get('region/district', 'RegionController@listDistrict');
$router->get('region/sub-district', 'RegionController@listSubDistrict');
$router->get('region/postal-code', 'RegionController@listPostalCode');