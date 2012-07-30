#!/usr/bin/env php -dphar.readonly=0
<?php

define('ROOT', realpath(__DIR__ . '/../'));

include 'functions.php';

$package_name = 'ZendFramework';

// did you copy config.ini.orig to the proper place?
if (!file_exists(ROOT . '/data/packager.ini')) {
    script_exit('packager.ini is required');
}

// load the ini
$ini = parse_ini_file(ROOT . '/data/packager.ini');

// Do we have a documentation_path?
if (!file_exists($ini['documentation_path'])) {
    script_exit('The path to the zf2-documentation repo does not look correct.');
}

$documentation_path = rtrim($ini['documentation_path'], '\\/');

// Do we have a release version?
if (!isset($ini['release'])) {
    script_exit('The proper release is required.');
}

$package_name .= '-' . $ini['release'];

chdir($documentation_path . '/docs');

script_run_command('make html');

chdir($documentation_path . '/docs/_build/html/');

// Create release directory
$release_dir = ROOT . '/public/releases/' . $package_name;
if (!file_exists($release_dir)) {
    script_run_command('mkdir -p ' . $release_dir);
}

// Create archives
script_run_command('zip -r ' . $release_dir . '/' . $package_name . '-manual-en.zip ./');
script_run_command('tar czf ' . $release_dir . '/' . $package_name . '-manual-en.tgz ./');

