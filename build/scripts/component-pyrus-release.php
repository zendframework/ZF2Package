#!/usr/bin/env php -dphar.readonly=0
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

chdir(ROOT . '/public');

$php = $_SERVER["_"];

$di = new DirectoryIterator(ROOT . '/packages/component-pyrus/'); // change this to actual public directory
foreach ($di as $file) {
    if ($file->isDot()) {
        continue;
    }
    script_run_command($php . ' ' . ROOT . '/build/scripts/pyrus.phar scs-release ' . $file->getPathname());
}
