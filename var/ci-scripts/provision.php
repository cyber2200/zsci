<?php
/**
 * If you want to run docker you have to be su - so you will have to add the follow line into the visudo (sudo visudo) file:
 * zend ALL = NOPASSWD: /usr/bin/docker
 */
echo "Provisioning...\n";
$t = '';
$t .= shell_exec("whoami");
echo "\n\n** User: " . trim($t) . " **\n\n";
$t = '';
$t .= shell_exec("sudo docker run -d zend/zs8prealpha");
echo $t . "\n";
file_put_contents("/tmp/dockerFullContainerId", trim($t));
$fullContainerId = trim($t);
$t .= shell_exec("sudo docker ps");
$tArr = explode("\n", $t);
print_r($tArr);
$tArr = explode(" ", $tArr[2]);
$containerId = $tArr[0];
file_put_contents("/tmp/dockerContainerId", trim($containerId));
$t = shell_exec("sudo docker inspect --format '{{ .NetworkSettings.IPAddress }}' {$containerId}");
echo 'IP: ' .$t . "\n";
file_put_contents("/tmp/dockerIp", trim($t));
exit(0);