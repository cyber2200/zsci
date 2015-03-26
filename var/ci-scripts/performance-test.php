<?php
$appUrl = trim(file_get_contents("/tmp/appUrl"));
echo "**" . $appUrl . "**\n";
$cmd = "wget -qO- $appUrl";
echo $cmd . "\n";
$t = shell_exec($cmd);
echo urlencode($t);
exit(0);