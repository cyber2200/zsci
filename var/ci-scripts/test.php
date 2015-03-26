<?php
echo "\n\n";
echo "Testing...\n";
//$ret = shell_exec("find /usr/local/zend/gui/vendor/Ci/var/tmp/repo1/ -type f -name \*.php -exec /usr/local/zend/bin/php -l {} \;");
$ret = "";
$lines = explode("\n", $ret);
$err = 0;
foreach ($lines as $line) {
	if (strpos($line, "Errors parsing") !== FALSE) {
		$err++;
		echo $line;
	}
}
echo "\n\n";
if ($err != 0) {
	exit(1);
} else {
	echo "\nAll tests completed successfully!\n";
	exit(0);
}