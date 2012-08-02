#!/usr/bin/env php
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

// get information from CLI
$package_name_full = $_SERVER['argv'][1];
$package_source = $_SERVER['argv'][2];
$package_filter = (isset($_SERVER['argv'][3])) ? trim($_SERVER['argv'][3], '/') : '*';

list($package_name, $package_release) = preg_split('#-#', $package_name_full);

chdir(ROOT . '/packages/plain');

script_run_command('rm -Rf ' . ROOT . '/packages/plain/' . $package_name_full . '/');

mkdir(ROOT . '/packages/plain/' . $package_name_full);

chdir($package_source);
script_run_command('tar -cf - ' . $package_filter . ' | tar -C ' . ROOT . '/packages/plain/' . $package_name_full . ' -xf -');
chdir(ROOT . '/packages/plain/');


script_run_command('zip -rq ' . ROOT . '/packages/plain/' . $package_name_full . '.zip ./' . $package_name_full);
script_run_command('tar czf ' . ROOT . '/packages/plain/' . $package_name_full . '.tgz ' . '-C ' . ROOT . '/packages/plain ' . $package_name_full);
script_run_command('rm -Rf ' . ROOT . '/packages/plain/' . $package_name_full . '/');
