#!/bin/bash
sqlite3 /usr/local/zend/gui/vendor/Ci/var/db/ci.db ".dump" > /usr/local/zend/gui/vendor/Ci/var/db/dump.sql
mv /usr/local/zend/gui/vendor/Ci/var/db/ci.db /usr/local/zend/gui/vendor/Ci/var/db/ci_rb.db
cat /usr/local/zend/gui/vendor/Ci/var/db/dump.sql | sqlite3 /usr/local/zend/gui/vendor/Ci/var/db/ci.db
