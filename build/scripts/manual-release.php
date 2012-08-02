#!/usr/bin/env php -dphar.readonly=0
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

$release = isset($argv[1]) ? $argv[1] : false;
if (!$release) {
    script_exit('No release specified');
}

$manual_package     = ROOT . '/packages/manual/ZendFramework-' . $release . '-manual-en.zip';
$manual_release_dir = ROOT . '/public/docs/ZendFramework-' . $release . '/manual/en';
mkdir($manual_release_dir, 0777, true);
script_run_command('unzip ' . $manual_package . ' -d ' . $manual_release_dir);

$manual_packages    = ROOT . '/packages/manual/ZendFramework-' . $release . '-manual-en.*';
$manual_release_dir = ROOT . '/public/releases/ZendFramework-' . $release . '/';
if (!is_dir($manual_release_dir)) {
    mkdir($manual_release_dir, 0777, true);
}
script_run_command('cp -a ' . $manual_packages . ' ' . $manual_release_dir);
