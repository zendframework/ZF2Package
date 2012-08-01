#!/usr/bin/env php
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

$composers = array();

// move composer files first
script_run_command('cp ' . ROOT . '/packages/component-composer/*.zip ' . ROOT . '/public/composer/');

// create packages.json
$di = new DirectoryIterator(ROOT . '/public/composer/');
foreach ($di as $file) {
    if ($file->isDot()) {
        continue;
    }

    $zip = new ZipArchive();
    $zip->open($file->getPathname());
    $composer_index = $zip->locateName('composer.json');
    if ($composer_index) {
        $fp = $zip->getStream($zip->getNameIndex($composer_index));
        $composers[$file->getBasename()] = json_decode(stream_get_contents($fp), true);
    }
    $zip->close();
}

$packages = array();
foreach ($composers as $composer) {
    $packages[$composer['name']] = array();
    $packages[$composer['name']][$composer['version']] = $composer;
}
file_put_contents(ROOT . '/public/packages.json', json_encode($packages, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

