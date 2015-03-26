<?php
$ruleId = '15';

$date = gmdate('D, d M Y H:i:s') . ' GMT';
$host = 'admin';
$useragent = 'Zend_Http_Client/1.10';
$accept = 'application/vnd.zend.serverapi+xml;version=1.9';
$apiKey = trim(file_get_contents("/usr/local/zend/gui/vendor/Ci/apikey"));
$serverUrl = 'http://localhost:10081';
$path = '/ZendServer/Api/jobqueueRunNowRule';

$sign =  $host . '; ' . hash_hmac('sha256', $host . ":" . $path . ":" . $useragent . ":" . $date, $apiKey);



$ch = curl_init($serverUrl . $path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch,CURLOPT_HTTPHEADER,
	array(
		'Host: ' . $host,
		'Date: ' . $date,
		'User-agent: ' . $useragent,
		'Accept: ' . $accept,
		'X-Zend-Signature: ' . $sign
	)
);
curl_setopt($ch,CURLOPT_POSTFIELDS, array('ruleId' => $ruleId));
$output = curl_exec($ch); 
curl_close($ch);
echo $output;
