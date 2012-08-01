<?php

require 'scripts/functions.php';
require 'build-config.php';

define('ROOT', realpath(__DIR__ . '/../'));

chdir(ROOT . '/packages/working/');

$php = $_SERVER["_"];

// download sources
echo 'Finding Sources ...' . PHP_EOL;
foreach ($components as $component_info) {
    $name = $component_info[2];
    $url = 'https://github.com/zendframework/' . $component_info[1] . '/tarball/release-' . $component_info[2];
    if (!file_exists(ROOT . '/packages/working/' . $component_info[1])) {
        script_run_command('mkdir ' . $component_info[1]);
        chdir(__DIR__ . '/../packages/working/' . $component_info[1]);
        echo 'Downloading and unpacking ' . $component_info[1] . PHP_EOL;
        script_run_command('wget -O ' . $component_info[1] . '.tar.gz ' . $url);
        script_run_command('tar xzf ' . $component_info[1] . '.tar.gz --strip 1');
        script_run_command('rm ' . $component_info[1] . '.tar.gz');
    }
    chdir(ROOT . '/packages/working/');
}

// build individual pear/pyrus package
foreach ($components as $component_info) {

    if (isset($component_info[3]['PYRUS'])) {
        echo 'Building Pyrus package for ' . $component_info[0] . ' ... ' . PHP_EOL;        
        $command =
            $php . ' -dphar.readonly=0 '
            . __DIR__ . '/scripts/component-pyrus-build.php '
            . $component_info[0] . '-' . $component_info[2] . ' '
            . ROOT . '/packages/working/' . $component_info[1] . ' '
            . escapeshellarg($component_info[3]['PYRUS']);
        script_run_command($command);
    }

    // reset path
    chdir(ROOT . '/packages/working/');
}

// build individual composer package
foreach ($components as $component_info) {

    if (isset($component_info[3]['COMPOSER'])) {
        echo 'Building Composer package for ' . $component_info[0] . ' ... ' . PHP_EOL;
        $command =
            $php . ' -dphar.readonly=0 '
                . __DIR__ . '/scripts/component-composer-build.php '
                . $component_info[0] . '-' . $component_info[2] . ' '
                . ROOT . '/packages/working/' . $component_info[1] . ' '
                . escapeshellarg($component_info[3]['COMPOSER']);
        script_run_command($command);
    }

    // reset path
    chdir(ROOT . '/packages/working/');
}

// building documentation
foreach ($components as $component_info) {

    if (in_array('MANUAL', $component_info[3])) {
        echo 'Building Documentation package for' . $component_info[0] . ' ... ' . PHP_EOL;
        $command =
            $php . ' -dphar.readonly=0 '
                . __DIR__ . '/scripts/manual-build.php '
                . $component_info[0] . '-' . $component_info[2] . ' '
                . ROOT . '/packages/working/' . $component_info[1] . ' ';
        script_run_command($command);
    }

    // reset path
    chdir(ROOT . '/packages/working/');
}

// build API docs
foreach ($components as $component_info) {

    if (isset($component_info[3]['APIDOC'])) {
        echo 'Building Apidoc package for' . $component_info[0] . ' ... ' . PHP_EOL;
        $command =
            $php . ' -dphar.readonly=0 '
                . __DIR__ . '/scripts/apidoc-build.php '
                . $component_info[0] . '-' . $component_info[2] . ' '
                . ROOT . '/packages/working/' . $component_info[1] . ' '
                . escapeshellarg($component_info[3]['APIDOC']);
        script_run_command($command);
    }

    // reset path
    chdir(ROOT . '/packages/working/');
}
