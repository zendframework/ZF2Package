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
            $composer_content = create_composer_json_stub($file->getBasename());
        }
    }
    $composers[$file->getBasename()] = $composer_content;
    $zip->close();
}

$packages = array();
foreach ($composers as $filename => $composer) {
    if (!isset($packages[$composer['name']])) {
        $packages[$composer['name']] = array();
    }
    if (!isset($composer['version'])) {
        list($filename_name, $filename_version) = preg_split('#-#', pathinfo($filename)['filename'], 2);
        $composer['version'] = $filename_version;
    }
    $packages[$composer['name']][$composer['version']] = $composer;
}

file_put_contents(ROOT . '/public/packages.json', json_encode($packages, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

