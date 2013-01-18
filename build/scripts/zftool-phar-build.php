#!/usr/bin/env php -dphar.readonly=0
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

$packageDir    = ROOT . '/packages/zftool-phar';
$packageSource = ROOT . '/packages/working/ZFTool';
$composer      = __DIR__ . '/composer.phar';

script_run_command('rm -Rf ' . $packageDir . '/ZFTool');
script_run_command('cp -a ' . $packageSource . ' ' . $packageDir);
chdir($packageDir . '/ZFTool');

script_run_command('php -dmemory_limit=-1 -dphar.readonly=0 ' . $composer . ' install');
script_run_command('php -dmemory_limit=-1 -dphar.readonly=0 bin/create-phar');
