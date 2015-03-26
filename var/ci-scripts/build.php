<?php
echo "Building...\n";
$t = '';
$t .= shell_exec("cd /usr/local/zend/gui/vendor/Ci/var/tmp/repo1; /usr/local/zend/bin/zdpack pack wppack");
echo $t . "\n";
exit(0);