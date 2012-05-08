#!/usr/bin/env php -dphar.readonly=0
<?php
if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__ . '/../'));
}
require_once ROOT . '/vendor/Composer/Json/JsonFile.php';

// did you copy config.ini.orig to the proper place?
if (!file_exists(__DIR__ . '/../data/packager.ini')) {
    script_exit('packager.ini is required');
}

// load the ini
$ini = parse_ini_file(__DIR__ . '/../data/packager.ini');

// create repositories.json from all files in data/composer/Zend*
echo PHP_EOL. "Merging package info" . PHP_EOL;
$repositories = array();

$path = ROOT . '/data/composer';
$list = glob($path.'/Zend*', GLOB_ONLYDIR);
foreach ($list as $dirname) {
    $name = basename($dirname);
    $name = strtolower(substr($name, 5));
    $name = 'zendframework/'.$name;
    echo "\tadding $name" . PHP_EOL;;

    $versions = glob($dirname . '/*.array.php');

    foreach ($versions as $versionFile) {
        $version = basename($versionFile, '.array.php');
        $contents = include $versionFile;
        $repositories['packages'][$name][$version] = $contents;
    }
}

$filename = ROOT . '/public/packages.json';
$jsonFile = new Composer\Json\JsonFile($filename);
echo "Storing $filename" . PHP_EOL;
echo "All done." . PHP_EOL;
$jsonFile->write($repositories);

function script_exit($reason) {
    echo $reason;
    echo PHP_EOL . PHP_EOL;
    exit -1;
}
