<?php

require_once __DIR__ . '/../vendor/autoload.php';

define('TESTING', true);
define('TEST_TEMP_DIR', sys_get_temp_dir() . '/newtron_tests');

if (!is_dir(TEST_TEMP_DIR)) {
  mkdir(TEST_TEMP_DIR, 0777, true);
}

register_shutdown_function(function() {
  if (is_dir(TEST_TEMP_DIR)) {
    exec('rm -rf ' . escapeshellarg(TEST_TEMP_DIR));
  }
});
