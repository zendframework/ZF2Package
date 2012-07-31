#!/usr/bin/env php
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

// get information from CLI
$package_name_full = $_SERVER['argv'][1];
$package_source = $_SERVER['argv'][2];
$package_filter = (isset($_SERVER['argv'][3])) ? trim($_SERVER['argv'][3], '/') : '*';

list($package_name, $package_release) = preg_split('#-#', $package_name_full);

chdir(ROOT . '/packages/component-composer');

script_run_command('rm -Rf ' . ROOT . '/packages/component-composer/' . $package_name . '*');

mkdir(ROOT . '/packages/component-composer/' . $package_name);

chdir($package_source);
script_run_command('tar -cf - ' . $package_filter . ' | tar -C ' . ROOT . '/packages/component-composer/' . $package_name . ' -xf -');
chdir(ROOT . '/packages/component-composer/' . $package_name);

script_run_command('cp -a ' . __DIR__ . '/composer-templates/* ./');

$package_file_name = "{$package_name}-{$package_release}.zip";
script_run_command('zip -rq ' . ROOT . '/packages/component-composer/' . $package_file_name . ' .');
script_run_command('rm -Rf ' . ROOT . '/packages/component-composer/' . $package_name . '/');

