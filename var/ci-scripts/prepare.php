<?php
echo "Preparing...\n";
$t = '';
$t .= shell_exec("rm -r /usr/local/zend/gui/vendor/Ci/var/tmp/repo1");
$t .= shell_exec("mkdir /usr/local/zend/gui/vendor/Ci/var/tmp/repo1");
$t .= shell_exec('git clone /usr/local/zend/gui/vendor/Ci/var/git/wpci01.git /usr/local/zend/gui/vendor/Ci/var/tmp/repo1 2>&1; echo $?');
echo $t . "\n";
exit(0);