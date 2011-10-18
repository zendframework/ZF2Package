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
script_run_command('mkdir -p ' . $work_path . '/manual');

// Copy files to working directory
script_run_command('cp -a ' . $zf2_path . '/documentation/manual/en ' . $work_path . '/manual/');

if (!file_exists($work_path . '/manual/en')) {
    script_exit('Failed to sync documentation sources to packaging directory.');
}

// Execute docbook toolchain
chdir($work_path . '/manual/en');
script_run_command('autoconf');
script_run_command('./configure');
script_run_command('make');

// move HTML files
script_run_command('mkdir -p ' . $work_path . '/manual/core');
script_run_command('mv ' . $work_path . '/manual/en/html ' . $work_path . '/manual/core/en');

// Remove doc sources
chdir(dirname($work_path));
script_run_command('rm -Rf ' . $work_path . '/manual/en');

// Create archives
script_run_command('zip -r '  . $package_name . '-manual-en.zip ' . $package_name);
script_run_command('tar czf ' . $package_name . '-manual-en.tgz ' . $package_name);

// Create release directory
$release_dir = ROOT . '/public/releases/' . $package_name;
if (!file_exists($release_dir)) {
    script_run_command('mkdir -p ' . $release_dir);
}

// Move packages to release directory
foreach (array('zip', 'tgz') as $type) {
    script_run_command('mv ' . $package_name . '-manual-en.' . $type . ' ' . $release_dir . '/');
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

