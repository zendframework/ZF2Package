#!/usr/bin/env php -dphar.readonly=0
<?php

define('ROOT', realpath(__DIR__ . '/../'));

// check that we have a package name
if (!isset($_SERVER['argv'][1])) {
    script_exit(__FILE__ . ' expects a package name as the only argument');
}

$package_name = $_SERVER['argv'][1];

// did you copy config.ini.orig to the proper place?
if (!file_exists(ROOT . '/data/packager.ini')) {
    script_exit('packager.ini is required');
}

// load the ini
$ini = parse_ini_file(ROOT . '/data/packager.ini');

// got milk? i mean - pyrus?
if (!file_exists($ini['pyrus_path'])) {
    script_exit('The path to pyrus does not look correct.');
}

$pyrus_path = $ini['pyrus_path'];

$suffix = ($ini['with_phar']) ? '{phar}' : '{tgz}';

$files = glob(ROOT . '/packages/working/' . $package_name . '/' . $package_name . '-*.' . $suffix, GLOB_BRACE);
if (!$files) {
    script_exit('Did not find a file to release for ' . $package_name);
}
$file = $files[0];

chdir(ROOT . '/public');

script_run_command($pyrus_path . ' scs-release ' . $file);


function script_run_command($command) {
    echo 'Running: ' . $command . PHP_EOL;
    return shell_exec($command);
}

function script_exit($reason) {
    echo $reason;
    echo PHP_EOL . PHP_EOL;
    exit -1;
}
