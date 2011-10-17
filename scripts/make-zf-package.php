#!/usr/bin/env php -dphar.readonly=0
<?php

define('ROOT', realpath(__DIR__ . '/../'));

$package_name = 'ZendFramework';

// did you copy config.ini.orig to the proper place?
if (!file_exists(ROOT . '/data/packager.ini')) {
    script_exit('packager.ini is required');
}

// load the ini
$ini = parse_ini_file(ROOT . '/data/packager.ini');

// Do we have a zf2_path?
if (!file_exists($ini['zf2_path'] . '/library')) {
    script_exit('The path to the ZF2 repo does not look correct.');
}

$zf2_path = rtrim($ini['zf2_path'], '\\/');

// Do we have a release version?
if (!isset($ini['release'])) {
    script_exit('The proper release is required.');
}

$release       = $ini['release'];
$package_name .= '-' . $release;

// Create working directory
$work_path = ROOT . '/packages/working/' . $package_name;
if (file_exists($work_path)) {
    script_run_command('rm -Rf ' . $work_path);
}
script_run_command('mkdir -p ' . $work_path);

// Copy files to working directory
$paths = array(
    'README.txt',
    'README-GIT.txt',
    'README-DEV.txt',
    'INSTALL.txt',
    'LICENSE.txt',
    'bin/',
    'demos',
    'library/',
    'resources/',
    'tests',
);
foreach ($paths as $file)  {
    $origin_path = $zf2_path  . '/' . $file;
    $copy_path   = $work_path . '/' . $file;
    script_run_command('cp -a ' . $origin_path . ' ' . $copy_path);
}

// Create archives
chdir(ROOT . '/packages/working');
script_run_command('zip -r '  . $package_name . '.zip ' . $package_name);
script_run_command('tar czf ' . $package_name . '.tgz ' . $package_name);

// Create release directory
$release_dir = ROOT . '/public/releases/' . $package_name;
if (!file_exists($release_dir)) {
    script_run_command('mkdir -p ' . $release_dir);
}

// Move packages to release directory
foreach (array('zip', 'tgz') as $type) {
    script_run_command('mv ' . $package_name . '.' . $type . ' ' . $release_dir . '/');
}

function script_run_command($command) {
    echo 'Running: ' . $command . PHP_EOL;
    return shell_exec($command);
}

function script_exit($reason) {
    echo $reason;
    echo PHP_EOL . PHP_EOL;
    exit -1;
}
