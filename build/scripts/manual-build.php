#!/usr/bin/env php -dphar.readonly=0
<?php

define('ROOT', realpath(__DIR__ . '/../../'));

include 'functions.php';

// get information from CLI
$package_name_full = $_SERVER['argv'][1];
$package_source = $_SERVER['argv'][2];
$package_filter = (isset($_SERVER['argv'][3])) ? trim($_SERVER['argv'][3], '/') : null;

list($package_name, $package_release) = preg_split('#-#', $package_name_full);

script_run_command('rm -Rf ' . ROOT . '/packages/manual/' . $package_name_full . '/');
mkdir(ROOT . '/packages/manual/' . $package_name_full);
script_run_command('cp -R ' .  $package_source . '/docs/ ' . ROOT . '/packages/manual/' . $package_name_full . '/');

chdir(ROOT . '/packages/manual/' . $package_name_full . '/docs');
script_run_command('make html');

chdir(ROOT . '/packages/manual/' . $package_name_full . '/docs/_build/html/');
script_run_command('zip -rq ' . ROOT . '/packages/manual/' . $package_name_full . '-manual-en.zip .');
chdir(ROOT . '/packages/manual/');

script_run_command('tar -czf ' . ROOT . '/packages/manual/' . $package_name_full . '-manual-en.tar.gz '
    . '-C ' . ROOT . '/packages/manual/' . $package_name_full . '/docs/_build/html .'
);

script_run_command('rm -Rf ' . ROOT . '/packages/manual/' . $package_name_full . '/');
