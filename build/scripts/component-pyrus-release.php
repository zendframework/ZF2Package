#!/usr/bin/env php -dphar.readonly=0
<?php

define('ROOT', realpath(__DIR__ . '/../../'));

$pyrus_path = __DIR__ . '/pyrus.phar';
$files = glob(ROOT . '/packages/component-pyrus/*');

chdir(ROOT . '/public');

var_dump($files);

//foreach ($files as $file) {
//    script_run_command($pyrus_path . ' scs-release ' . $file);
//}

