#!/usr/bin/env php -dphar.readonly=0
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

$release = isset($argv[1]) ? $argv[1] : false;
if (!$release) {
    script_exit('No release specified');
}

$apidoc_package     = ROOT . '/packages/apidoc/ZendFramework-' . $release . '-apidoc.zip';
$apidoc_release_tmp = ROOT . '/public/docs/ZendFramework-' . $release . '/tmp';
$apidoc_release_dir = ROOT . '/public/docs/ZendFramework-' . $release . '/apidoc';
mkdir($apidoc_release_tmp, 0777, true);
script_run_command('unzip ' . $apidoc_package . ' -d ' . $apidoc_release_tmp);
script_run_command('mv ' . $apidoc_release_tmp . '/ZendFramework-' . $release . ' ' . $apidoc_release_dir);

$apidoc_packages    = ROOT . '/packages/apidoc/ZendFramework-' . $release . '-apidoc.*';
$apidoc_release_dir = ROOT . '/public/releases/ZendFramework-' . $release . '/';
script_run_command('cp -a ' . $apidoc_packages . ' ' . $apidoc_release_dir);

