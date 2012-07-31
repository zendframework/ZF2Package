#!/usr/bin/env php -dphar.readonly=0
<?php

define('ROOT', realpath(__DIR__ . '/../../'));

include 'functions.php';

// get information from CLI
$package_name_full = $_SERVER['argv'][1];
$package_source = $_SERVER['argv'][2];
$package_filter = (isset($_SERVER['argv'][3])) ? trim($_SERVER['argv'][3], '/') : null;

list($package_name, $package_release) = preg_split('#-#', $package_name_full);

chdir($package_source);
mkdir(ROOT . '/packages/manual/' . $package_name_full);
script_run_command('rm -Rf ' . ROOT . '/packages/manual/' . $package_name_full . '/');
script_run_command('cp -R docs/ ' . ROOT . '/packages/manual/' . $package_name_full . '/');
chdir(ROOT . '/packages/manual/' . $package_name_full . '/');
script_run_command('make html');

//
//
//// did you copy config.ini.orig to the proper place?
//if (!file_exists(ROOT . '/data/packager.ini')) {
//    script_exit('packager.ini is required');
//}
//
//// load the ini
//$ini = parse_ini_file(ROOT . '/data/packager.ini');
//
//// Do we have a documentation_path?
//if (!file_exists($ini['documentation_path'])) {
//    script_exit('The path to the zf2-documentation repo does not look correct.');
//}
//
//$documentation_path = rtrim($ini['documentation_path'], '\\/');
//
//// Do we have a release version?
//if (!isset($ini['release'])) {
//    script_exit('The proper release is required.');
//}
//
//$package_name .= '-' . $ini['release'];
//
//chdir($documentation_path . '/docs');
//
//script_run_command('make html');
//
//chdir($documentation_path . '/docs/_build/html/');
//
//// Create release directory
//$release_dir = ROOT . '/public/releases/' . $package_name;
//if (!file_exists($release_dir)) {
//    script_run_command('mkdir -p ' . $release_dir);
//}
//
//// Create archives
//script_run_command('zip -r ' . $release_dir . '/' . $package_name . '-manual-en.zip ./');
//script_run_command('tar czf ' . $release_dir . '/' . $package_name . '-manual-en.tgz ./');
//
