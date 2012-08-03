#!/usr/bin/env php
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

$default_data = [
    'type'         => 'library',
    'homepage'     => 'http://packages.zendframework.com/',
    'repositories' => [
        'type' => 'composer',
        'url'  => 'http://packages.zendframework.com/',
    ],
];

$composers = [];

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

    $composer_content = array_merge($default_data, $composer_content);

    if (!isset($composer_content['dist'])) {
        $composer_content['dist'] = [
            'url'  => 'http://packages.zendframework.com/composer/' . $file->getFilename(),
            'type' => 'zip',
        ];
    }

    $composers[$file->getBasename()] = $composer_content;
    $zip->close();
}

$zf2_metapackage_template = [
    'name'    => 'zendframework/zf2',
    'type'    => 'metapackage',
    'require' => [],
];
$zf2_metapackage = [];

$packages = [];
foreach ($composers as $filename => $composer) {
    if (!isset($packages[$composer['name']])) {
        $packages[$composer['name']] = array();
    }

    // check some needed info
    if (!isset($composer['version'])) {
        list($filename_name, $filename_version) = preg_split('#-#', pathinfo($filename)['filename'], 2);
        $composer['version'] = $filename_version;
    }
    if (!isset($composer['repositories'])) {
        $composer['repositories'] = array('type' => 'composer', 'url' => 'http://packages.zendframework.com/');
    }
    if (!isset($composer['type'])) {
        $composer['type'] = 'library';
    }
    $packages[$composer['name']][$composer['version']] = $composer;

    if (!preg_match('#^zendframework/zend-#', $composer['name'])) {
        continue;
    }

    if (!isset($zf2_metapackage[$composer['version']])) {
        $zf2_metapackage[$composer['version']] = $zf2_metapackage_template;
        $zf2_metapackage[$composer['version']]['version'] = $composer['version'];
    }
    $zf2_metapackage[$composer['version']]['require'][$composer['name']] = "self.version";
}

$packages['zendframework/zf2'] = $zf2_metapackage;
$packages = array('packages' => $packages);

file_put_contents(ROOT . '/public/packages.json', json_encode($packages, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
