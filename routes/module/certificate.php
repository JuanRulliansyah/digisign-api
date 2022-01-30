<?php

$router->post('certificate', 'CertificateController@createCertificate');
$router->get('certificate', 'CertificateController@list');
$router->get('certificate/delete/{id}/', 'CertificateController@delete');