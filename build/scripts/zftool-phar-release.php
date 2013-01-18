#!/usr/bin/env php -dphar.readonly=0
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

$zftoolPhar  = ROOT . '/packages/zftool-phar/ZFTool/bin/zftool.phar';
$releasePath = ROOT . '/public/zftool.phar';

if (!file_exists($zftoolPhar)) {
    exit(0);
}

script_run_command('cp -a ' . $zftoolPhar . ' ' . $releasePath);
