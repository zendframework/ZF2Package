#!/usr/bin/env php
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

// get information from CLI
$package_name_full = $_SERVER['argv'][1];
$package_source = $_SERVER['argv'][2];
$package_filter = (isset($_SERVER['argv'][3])) ? trim($_SERVER['argv'][3], '/') : '*';

list($package_name, $package_release) = preg_split('#-#', $package_name_full);

chdir(ROOT . '/packages/plain');

script_run_command('rm -Rf ' . ROOT . '/packages/plain/' . $package_name_full . '/');

mkdir(ROOT . '/packages/plain/' . $package_name_full);

chdir($package_source);
script_run_command('tar -cf - ' . $package_filter . ' | tar -C ' . ROOT . '/packages/plain/' . $package_name_full . ' -xf -');
chdir(ROOT . '/packages/plain/');

script_run_command('zip -rq ' . ROOT . '/packages/plain/' . $package_name_full . '.zip ./' . $package_name_full);
script_run_command('rm -Rf ' . ROOT . '/packages/plain/' . $package_name_full . '/');

// modify package's composer.json

$zip = new ZipArchive();
$zip->open(ROOT . '/packages/plain/' . $package_name_full . '.zip');
$composer_index_in_root = $zip->locateName('composer.json');
if ($composer_index_in_root !== false) {
    $fp = $zip->getStream($zip->getNameIndex($composer_index_in_root));
    $composer_content = json_decode(stream_get_contents($fp), true);
} else {
    $composer_index_anywhere = $zip->locateName('composer.json', ZIPARCHIVE::FL_NODIR);
    if ($composer_index_anywhere) {
        $fp = $zip->getStream($zip->getNameIndex($composer_index_anywhere));
        $composer_content = json_decode(stream_get_contents($fp), true);
    } else {
        var_dump($composer_index_anywhere);
        echo 'A composer.json was not found in ' . $package_name_full . '.zip';
        exit -1;
    }
}

$composer_content['version'] = $package_release;
$composer_content['dist']['url'] = "http://packages.zendframework.com/composer/{$package_name_full}.zip";
$composer_content['dist']['type'] = "zip";

if (isset($composer_content['target-dir'])) {
    unset($composer_content['target-dir']);
}

$zip->addFromString('composer.json', json_encode($composer_content));
$zip->close();

// Build tgz file
unlink(ROOT . '/packages/plain/composer.json');
script_run_command('unzip -q ' . ROOT . '/packages/plain/' . $package_name_full . '.zip -d ' . ROOT . '/packages/plain/');
script_run_command('tar czf ' . ROOT . '/packages/plain/' . $package_name_full . '.tgz '
    . '-C ' . ROOT . '/packages/plain '
    . $package_name_full
);

script_run_command('rm -Rf ' . ROOT . '/packages/plain/' . $package_name_full . '/');
