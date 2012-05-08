#!/usr/bin/env php
<?php

// did you copy config.ini.orig to the proper place?
if (!file_exists(__DIR__ . '/../data/packager.ini')) {
    script_exit('packager.ini is required');
}

// load the ini
$ini = parse_ini_file(__DIR__ . '/../data/packager.ini');

$package_list = $ini['package_list'];

$packages = explode(',', $package_list);

foreach ($packages as $package) {
    $return_val = 0;
    $php = '/usr/bin/env php ';
    $command = $php . __DIR__ . '/make-composer-package.php ' . $package;
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
