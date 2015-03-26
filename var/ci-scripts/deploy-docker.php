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
echo "Trying to deploy on Docker...\n";
$date = gmdate('D, d M Y H:i:s') . ' GMT';
$host = 'admin';
$useragent = 'Zend_Http_Client/1.10';
$accept = 'application/vnd.zend.serverapi+xml;version=1.9';
$apiKey = '';
$ip = trim(file_get_contents("/tmp/dockerIp"));
$serverUrl = 'http://'. $ip .':10081';
$path = '/ZendServer/Api/applicationDeploy';

$sign =  $host . '; ' . hash_hmac('sha256', $host . ":" . $path . ":" . $useragent . ":" . $date, $apiKey);

$request = $serverUrl . $path;
echo $request . "\n";
$ch = curl_init($request);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

$appUrl = 'http://' . $ip . '/test' . rand(0, 100000000);
file_put_contents("/tmp/appUrlDocker", $appUrl);
$userParams = array('locale' => 'GMT', 'db_host' => '123', 'db_name' => '123', 'db_username' => '123');
$params = array('baseUrl' => $appUrl, 'defaultServer' => 'TRUE', 'userParams' => $userParams);
$files =  array('appPackage' => '/usr/local/zend/gui/vendor/Ci/var/tmp/repo1/wppack.zpk');
$headers = array('Host: ' . $host, 'Date: ' . $date, 'User-agent: ' . $useragent, 'Accept: ' . $accept, 'X-Zend-Signature: ' . $sign);
curl_custom_postfields($ch, $params, $files, $headers);

$output = curl_exec($ch);
$err = curl_error($ch);
$runs = 0;
// Waiting for container to be up
while ($err != '') {
	$runs++;
	if ($runs == 10) {
		echo "Too many tries..\n";
		break;
	}
	echo $err . "\n";
	echo "Trying again...\n";
	sleep(10);
	
	$sign =  $host . '; ' . hash_hmac('sha256', $host . ":" . $path . ":" . $useragent . ":" . $date, $apiKey);
	$headers = array('Host: ' . $host, 'Date: ' . $date, 'User-agent: ' . $useragent, 'Accept: ' . $accept, 'X-Zend-Signature: ' . $sign);
	curl_custom_postfields($ch, $params, $files, $headers);
	$output = curl_exec($ch);
	$err = curl_error($ch);
}

$p = xml_parser_create();
xml_parse_into_struct($p, $output, $vals, $index);
xml_parser_free($p);

$runs = 0;
// Waiting for Zend Server to be ready
while ($vals[9]['value'] == 'serverNotReady') {
	$runs++;
	if ($runs == 10) {
		echo "Too many tries..\n";
		break;
	}
	echo "Server not ready...\n";
	echo "Trying again...\n";
	sleep(10);
	
	$sign =  $host . '; ' . hash_hmac('sha256', $host . ":" . $path . ":" . $useragent . ":" . $date, $apiKey);
	$headers = array('Host: ' . $host, 'Date: ' . $date, 'User-agent: ' . $useragent, 'Accept: ' . $accept, 'X-Zend-Signature: ' . $sign);
	curl_custom_postfields($ch, $params, $files, $headers);
	$output = curl_exec($ch);
	$p = xml_parser_create();
	xml_parse_into_struct($p, $output, $vals, $index);
	xml_parser_free($p);
}
echo "\n\nServer is ready!\n\n";
$t = '';
$runs = 0;
// Waiting for API key
while (trim($t) == '') {
	$runs++;
	if ($runs == 30) {
		echo "Too many tries...\n";
		echo "Please try to deploy manually: /usr/local/zend/bin/php /usr/local/zend/gui/vendor/Ci/var/ci-scripts/deploy-docker.php\n";
		break;
	}
	echo "Trying to get API key...\n";
	sleep(20);
	$fullcontId = file_get_contents("/tmp/dockerFullContainerId");
	$command = "sudo tail /var/lib/docker/aufs/mnt/". $fullcontId ."/root/api_key";
	$t = shell_exec($command);	
	$tArr = explode("\t", $t);
	if (isset($tArr[1])) {
		$apiKey = trim($tArr[1]);
		echo "apiKey = " . $apiKey . "\n";
		$sign =  $host . '; ' . hash_hmac('sha256', $host . ":" . $path . ":" . $useragent . ":" . $date, $apiKey);
		$headers = array('Host: ' . $host, 'Date: ' . $date, 'User-agent: ' . $useragent, 'Accept: ' . $accept, 'X-Zend-Signature: ' . $sign);
		curl_custom_postfields($ch, $params, $files, $headers);
		$output = curl_exec($ch);
	}
}

curl_close($ch);
echo htmlspecialchars($output) . "\n\n";
exit(0);