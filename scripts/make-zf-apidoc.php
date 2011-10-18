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

// got docblox?
if (!file_exists($ini['docblox_path'])) {
    script_exit('The path to docblox does not look correct.');
}

$docblox_path = $ini['docblox_path'];

// Create working directory
$work_path = ROOT . '/packages/working/' . $package_name;
if (file_exists($work_path)) {
    script_run_command('rm -Rf ' . $work_path);
}
script_run_command('mkdir -p ' . $work_path . '/manual');

// Create library and sync in components
if (!file_exists($work_path . '/library/Zend')) {
    script_run_command('mkdir -p ' . $work_path . '/library/Zend');
}
$components = explode(', ', $ini['package_list']);
foreach ($components as $component) {
    $component = str_replace('Zend_', '', $component);
    if (false === strstr($component, '_')) {
        $origin = $zf2_path . '/library/Zend/' . $component;
        $target = $work_path . '/library/Zend/';
        script_run_command('cp -a ' . $origin . ' ' . $target);
        continue;
    }

    $subcomponents = explode('_', $component);
    $component     = array_pop($subcomponents);
    $subpath       = implode('/', $subcomponents);
    $target        = $work_path . '/library/Zend/' . $subpath;
    if (!file_exists($target)) {
        script_run_command('mkdir -p ' . $target);
    }
    $origin = $zf2_path . '/library/Zend/' . $subpath . '/' . $component;
    script_run_command('cp -a ' . $origin . ' ' . $target);
}

// Create API documentation
script_run_command('mkdir -p ' . $work_path . '/documentation/apidoc/core');
chdir($work_path);
script_run_command($docblox_path . ' run -d library -t documentation/apidoc/core --title "Zend Framework 2.0 API Documentation" --template zend -p');

// Remove source tree
script_run_command('rm -Rf ' . $work_path . '/library');

// Create archives
chdir(dirname($work_path));
script_run_command('zip -r '  . $package_name . '-apidoc.zip ' . $package_name);
script_run_command('tar czf ' . $package_name . '-apidoc.tgz ' . $package_name);

// Create release directory
$release_dir = ROOT . '/public/releases/' . $package_name;
if (!file_exists($release_dir)) {
    script_run_command('mkdir -p ' . $release_dir);
}

// Move packages to release directory
foreach (array('zip', 'tgz') as $type) {
    script_run_command('mv ' . $package_name . '-apidoc.' . $type . ' ' . $release_dir . '/');
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


