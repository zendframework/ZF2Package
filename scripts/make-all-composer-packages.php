#!/usr/bin/env php
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

$package_list = $ini['package_list'];

$packages = explode(',', $package_list);

foreach ($packages as $package) {
    $return_val = 0;
    $php = '/usr/bin/env php ';
    $command = $php . __DIR__ . '/make-composer-package.php ' . $package;
    echo 'Running: ' . $command . PHP_EOL;
    passthru($command, $return_val);
    if ($return_val === -1) {
        echo 'Something went terribly wrong' . PHP_EOL;
        exit -1;
    }
}

// now create repositories.json
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
//file_put_contents($filename, json_encode($repositories));


function script_exit($reason) {
    echo $reason;
    echo PHP_EOL . PHP_EOL;
    exit -1;
}
