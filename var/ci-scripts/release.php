<?php
function curl_custom_postfields($ch, array $assoc = array(), array $files = array(), $headers) {
	$disallow = array("\0", "\"", "\r", "\n");
	foreach ($assoc as $k => $v) {
		if (! is_array($v)) {
			$k = str_replace($disallow, "_", $k);
			$body[] = implode("\r\n", array(
				"Content-Disposition: form-data; name=\"{$k}\"",
				"",
				filter_var($v), 
			));
		} else {
			foreach ($v as $k1 => $v1) {
				$k1 = str_replace($disallow, "_", $k1);
				$body[] = implode("\r\n", array(
					"Content-Disposition: form-data; name=\"{$k}[{$k1}]\"", // Weird part
					"",
					filter_var($v1),
				));
			}
		}
	}
	
	foreach ($files as $k => $v) {
		switch (true) {
			case false === $v = realpath(filter_var($v)):
			case !is_file($v):
			case !is_readable($v):
			continue; // or return false, throw new InvalidArgumentException
		}
		$data = file_get_contents($v);
		$v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
		$k = str_replace($disallow, "_", $k);
		$v = str_replace($disallow, "_", $v);
		$body[] = implode("\r\n", array(
			"Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
			"Content-Type: application/octet-stream",
			"",
			$data, 
		));
	}

	// generate safe boundary 
	do {
		$boundary = "---------------------" . md5(mt_rand() . microtime());
	} while (preg_grep("/{$boundary}/", $body));

	// add boundary for each parameters
	array_walk($body, function (&$part) use ($boundary) {
		$part = "--{$boundary}\r\n{$part}";
	});

	// add final boundary
	$body[] = "--{$boundary}--";
	$body[] = "";

	$postFields = implode("\r\n", $body);
	return @curl_setopt_array($ch, array(
		CURLOPT_POST       => true,
		CURLOPT_POSTFIELDS => $postFields,
		CURLOPT_HTTPHEADER => array_merge(array("Expect: 100-continue", "Content-Type: multipart/form-data; boundary={$boundary}"), $headers),
	));
}

$date = gmdate('D, d M Y H:i:s') . ' GMT';
$host = 'admin';
$useragent = 'Zend_Http_Client/1.10';
$accept = 'application/vnd.zend.serverapi+xml;version=1.9';
$apiKey = trim(file_get_contents("/usr/local/zend/gui/vendor/Ci/apikey"));
$serverUrl = 'http://localhost:10081';
$path = '/ZendServer/Api/applicationUpdate';

$sign =  $host . '; ' . hash_hmac('sha256', $host . ":" . $path . ":" . $useragent . ":" . $date, $apiKey);

$request = $serverUrl . $path;
$ch = curl_init($request);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

$userParams = array('locale' => 'GMT', 'db_host' => '123', 'db_name' => '123', 'db_username' => '123');
$params = array('appId' => '24', 'userParams' => $userParams);
$files =  array('appPackage' => '/usr/local/zend/gui/vendor/Ci/var/tmp/repo1/wppack.zpk');
$headers = array('Host: ' . $host, 'Date: ' . $date, 'User-agent: ' . $useragent, 'Accept: ' . $accept, 'X-Zend-Signature: ' . $sign);
curl_custom_postfields($ch, $params, $files, $headers);

$output = curl_exec($ch); 
curl_close($ch);

echo htmlspecialchars($output);
exit(0);
