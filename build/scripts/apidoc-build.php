#!/usr/bin/env php -dphar.readonly=0
<?php

define('ROOT', realpath(__DIR__ . '/../../'));

include 'functions.php';

// get information from CLI
$package_name_full = $_SERVER['argv'][1];
$package_source = $_SERVER['argv'][2];
$package_filter = (isset($_SERVER['argv'][3])) ? trim($_SERVER['argv'][3], '/') : null;

list($package_name, $package_release) = preg_split('#-#', $package_name_full);

script_run_command('rm -Rf ' . ROOT . '/packages/apidoc/' . $package_name_full . '*');
mkdir(ROOT . '/packages/apidoc/' . $package_name_full);

copy(ROOT . '/build/scripts/phpdocumentor-template/phpdoc.xml', ROOT . '/packages/apidoc/' . $package_name_full . '.xml');

file_put_contents(
    ROOT . '/packages/apidoc/' . $package_name_full . '.xml',
    apply_replacements(
        file_get_contents(ROOT . '/packages/apidoc/' . $package_name_full . '.xml'),
        array(
            '{target}' => ROOT . '/packages/apidoc/' . $package_name_full . '/',
            '{directory}' => $package_source . '/' . $package_filter
        )
    )
);

chdir(ROOT . '/packages/apidoc/');
script_run_command('php -dmemory_limit=-1 ' . ROOT . '/build/scripts/phpDocumentor.phar -c ' . ROOT . '/packages/apidoc/' . $package_name_full . '.xml');

script_run_command('zip -rq ' . ROOT . '/packages/apidoc/' . $package_name_full . '-apidoc.zip ' . $package_name_full);
script_run_command('tar czf ' . ROOT . '/packages/apidoc/' . $package_name_full . '-apidoc.tgz ' . $package_name_full);
