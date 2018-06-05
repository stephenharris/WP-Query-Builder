<?php

require_once(dirname(__DIR__) . '/vendor/autoload.php');

//Load the test library...
$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';
echo "Using WordPress test library at ". $_tests_dir . PHP_EOL;

require $_tests_dir . '/includes/bootstrap.php';
