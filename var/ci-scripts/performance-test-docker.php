<?php
sleep(150);
$start = microtime(true);
$appUrl = trim(file_get_contents("/tmp/appUrlDocker"));
//$appUrl = $appUrl . '/index.php';
$t = shell_exec("wget -qO- http://localhost");
//echo $t;
$cmd = "wget -qO- $appUrl";
echo $cmd . "\n";
$t = shell_exec($cmd);
echo $t;
if ($t == '') {
	echo "Fail!\n";
	exit(1);
}
$cmd = "wget -qO- $appUrl/?p=1";
echo $cmd . "\n";
$t = shell_exec($cmd);
echo $t;
if ($t == '') {
	echo "Fail!\n";
	exit(1);
}
$cmd = "wget -qO- $appUrl/?m=201411";
echo $cmd . "\n";
$t = shell_exec($cmd);
echo $t;
if ($t == '') {
	echo "Fail!\n";
	exit(1);
}
$cmd = "wget -qO- $appUrl/index.php?cat=1";
echo $cmd . "\n";
$t = shell_exec($cmd);
echo $t;
if ($t == '') {
	echo "Fail!\n";
	exit(1);
}
$cmd = "wget -qO- $appUrl/wp-login.php";
echo $cmd . "\n";
$t = shell_exec($cmd);
echo $t;
if ($t == '') {
	echo "Fail!\n";
	exit(1);
}
$end = microtime(true);
echo "\nPerformance test: " . ($end - $start) . "\n";