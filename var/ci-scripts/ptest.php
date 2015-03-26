<?php
$i = 0;
while ($i < 30) {
	$i++;
	sleep(1);
}
file_put_contents("/tmp/rrrr", time('H:i:s'));
die();
$start = microtime(true);
$appUrl = trim(file_get_contents("/tmp/appUrl"));

$cmd = "wget -qO- -T 33333 $appUrl";
echo $cmd . "\n";
$t = shell_exec($cmd);
//echo $t;
if ($t == '') {
	echo "Fail!\n";
	exit(1);
}
$cmd = "wget -qO- -T 33333 $appUrl/?p=1";
echo $cmd . "\n";
$t = shell_exec($cmd);
//echo $t;
if ($t == '') {
	echo "Fail!\n";
	exit(1);
}
$cmd = "wget -qO- -T 33333 $appUrl/?m=201411";
echo $cmd . "\n";
$t = shell_exec($cmd);
//echo $t;
if ($t == '') {
	echo "Fail!\n";
	exit(1);
}
$cmd = "wget -qO- -T 33333 $appUrl/index.php?cat=1";
echo $cmd . "\n";
$t = shell_exec($cmd);
//echo $t;
if ($t == '') {
	echo "Fail!\n";
	exit(1);
}
$cmd = "wget -qO- -T 33333 $appUrl/wp-login.php";
echo $cmd . "\n";
$t = shell_exec($cmd);
//echo $t;
if ($t == '') {
	echo "Fail!\n";
	exit(1);
}
$end = microtime(true);
echo "\nPerformance test: " . ($end - $start) . "\n";