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
