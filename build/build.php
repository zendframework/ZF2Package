<?php

include 'scripts/functions.php';
$config = include 'build-config.php';


chdir(__DIR__ . '/../packages/working/');

// download sources
echo 'Finding Sources ...' . PHP_EOL;
foreach ($config as $item) {
    $name = $item[2];
    $url = 'https://github.com/zendframework/' . $item[1] . '/tarball/release-' . $item[2];
    if (!file_exists(__DIR__ . '/../packages/working/' . $item[1])) {
        script_run_command('mkdir ' . $item[1]);
        chdir(__DIR__ . '/../packages/working/' . $item[1]);
        echo 'Downloading and unpacking ' . $item[1] . PHP_EOL;
        script_run_command('wget -O ' . $item[1] . '.tar.gz ' . $url);
        script_run_command('tar xzf ' . $item[1] . '.tar.gz --strip 1');
    }
    chdir(__DIR__ . '/../packages/working/');
}

// build individual pear/pyrus package
foreach ($config as $item) {

    if (in_array('PYRUS', $item[3])) {
        echo 'Building Pyrus package for' . $item[0] . ' ... ' . PHP_EOL;        
    }

    // reset path
    chdir(__DIR__ . '/../packages/working/');
}

// build individual composer package
foreach ($config as $item) {

    if (in_array('COMPOSER', $item[3])) {
        echo 'Building Composer package for' . $item[0] . ' ... ' . PHP_EOL;        
    }

    // reset path
    chdir(__DIR__ . '/../packages/working/');
}

// building documentation
foreach ($config as $item) {

    if (in_array('DOC', $item[3])) {
        echo 'Building Documentation package for' . $item[0] . ' ... ' . PHP_EOL;        
    }

    // reset path
    chdir(__DIR__ . '/../packages/working/');
}

// build API docs
foreach ($config as $item) {

    if (in_array('APIDOC', $item[3])) {
        echo 'Building Apidoc package for' . $item[0] . ' ... ' . PHP_EOL;        
    }

    // reset path
    chdir(__DIR__ . '/../packages/working/');
}
