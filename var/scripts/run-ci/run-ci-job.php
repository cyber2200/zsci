<?php

require_once('qa-common.inc');
$pskName = 'admin';
$psk = 'f4a03936a0b99ba24b9b9fcd896e78bb001bca3afd8cc72e96634bc40bdc36fd';

$action ='runCiJob';
$method='POST';

$params = array('jobId' => $_GET['jobId']);

$host = 'http://localhost:10081/ZendServer';
$webapi_req = new webapi_request_exec($host, $action, $pskName, $psk);
$webapi_req->prep_client($method, $params);

print $webapi_req->exec_request();