#!/usr/bin/env php
<?php

// did you copy config.ini.orig to the proper place?
if (!file_exists(__DIR__ . '/../data/packager.ini')) {
    script_exit('packager.ini is required');
}

// load the ini
$ini = parse_ini_file(__DIR__ . '/../data/packager.ini');

$external_package_list = $ini['external_component_list'];
$external_packages = explode(',', $external_package_list);

foreach ($external_packages as $package) {
    $return_val = 0;
    $php = '/usr/bin/env php ';
    $command = $php . __DIR__ . '/make-external-composer-package.php ' . $package;
    echo 'Running: ' . $command . PHP_EOL;
    passthru($command, $return_val);
    if ($return_val === -1) {
        echo 'Something went terribly wrong' . PHP_EOL;
        exit -1;
    }
}

function script_exit($reason) {
    echo $reason;
    echo PHP_EOL . PHP_EOL;
    exit -1;
}
