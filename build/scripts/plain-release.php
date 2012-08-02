#!/usr/bin/env php -dphar.readonly=0
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

$release = isset($argv[1]) ? $argv[1] : false;
if (!$release) {
    script_exit('No release specified');
}

$package_glob = ROOT . '/packages/plain/ZendFramework-*' . $release . '.*';
$release_dir  = ROOT . '/public/releases/ZendFramework-' . $release . '';
if (!is_dir($release_dir)) {
    mkdir($release_dir, 0777, true);
}
script_run_command('cp -a ' . $package_glob . ' ' . $release_dir);
