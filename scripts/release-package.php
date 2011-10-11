#!/usr/bin/env php -dphar.readonly=0
<?php

define('ROOT', realpath(__DIR__ . '/../'));

// check that we have a package name
if (!isset($_SERVER['argv'][1]) || !preg_match('#\w+_\w+#', $_SERVER['argv'][1])) {
    script_exit(__FILE__ . ' expects a package name as the only argument');
} else {
    $package_name = $_SERVER['argv'][1];
}

// did you copy config.ini.orig to the proper place?
if (!file_exists(ROOT . '/data/packager.ini')) {
    script_exit('packager.ini is required');
}

// load the ini
$ini = parse_ini_file(ROOT . '/data/packager.ini');

// does it look ok?
if (!file_exists($ini['zf2_path'] . '/library')) {
    script_exit('The path to the ZF2 repo does not look correct.');
}

// @todo