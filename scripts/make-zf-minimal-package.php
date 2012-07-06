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
    'README.md',
    'README-GIT.md',
    'README-DEV.md',
    'INSTALL.md',
    'LICENSE.txt',
    'composer.json',
    'bin',
    'resources',
    'vendor',
);
foreach ($paths as $file)  {
    $origin_path = $zf2_path  . '/' . $file;
    $copy_path   = $work_path . '/' . $file;
    script_run_command('cp -a ' . $origin_path . ' ' . $copy_path);
}

// Create library and sync in components
if (!file_exists($work_path . '/library/Zend')) {
    script_run_command('mkdir -p ' . $work_path . '/library/Zend');
}
$components = explode(', ', $ini['package_list']);
foreach ($components as $component) {
    $component = str_replace('Zend_', '', $component);
    if (false === strstr($component, '_')) {
        $origin = $zf2_path . '/library/Zend/' . $component;
        if (!is_dir($origin)) {
            $origin .= '.php';
            if (!file_exists($origin)) {
                echo "UNABLE TO FIND directory or file associated with component '$component'\n";
                continue;
            }
        }
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

// Create archives
chdir(ROOT . '/packages/working');
script_run_command('zip -r '  . $package_name . '-minimal.zip ' . $package_name);
script_run_command('tar czf ' . $package_name . '-minimal.tgz ' . $package_name);

// Create release directory
$release_dir = ROOT . '/public/releases/' . $package_name;
if (!file_exists($release_dir)) {
    script_run_command('mkdir -p ' . $release_dir);
}

// Move packages to release directory
foreach (array('zip', 'tgz') as $type) {
    script_run_command('mv ' . $package_name . '-minimal.' . $type . ' ' . $release_dir . '/');
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

