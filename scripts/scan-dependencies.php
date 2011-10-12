#!/usr/bin/env php
<?php

include __DIR__ . '/includes/DependencyScanner.php';

define('ROOT', realpath(__DIR__ . '/../'));

// check that we have a package name
if (!isset($_SERVER['argv'][1]) || !preg_match('#\w+_\w+#', $_SERVER['argv'][1])) {
    script_exit(__FILE__ . ' expects a package name as the only argument');
} else {
    $package_name = $_SERVER['argv'][1];
}

// did you copy config.ini.orig to the proper place?
if (!file_exists(ROOT . '/data/packager.ini')) {
    script_exit('packager.ini is required');
}

// load the ini
$ini = parse_ini_file(ROOT . '/data/packager.ini');

// does it look ok?
if (!file_exists($ini['zf2_path'] . '/library')) {
    script_exit('The path to the ZF2 repo does not look correct.');
}

$zf2_path = rtrim($ini['zf2_path'], '\\/');
$zf2_library_path = $zf2_path . '/library';

$component_path = $zf2_library_path . '/' . str_replace('_', '/', $package_name);

if (!file_exists($component_path)) {
    echo 'Path for this component not found';
    exit(0);
}

// Directory provided
$deps = array();
$it   = new RecursiveDirectoryIterator($component_path);
foreach (new RecursiveIteratorIterator($it) as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $filePath = $file->getRealPath();
    if ('.php' != substr($filePath, -4)) {
        continue;
    }

    $deps += DependencyScanner::getForFile($filePath);
}

if (!count($deps)) {
    echo "No dependencies found\n";
    exit(0);
}

$dependencies = array('optional' => array());

foreach (array_unique($deps) as $dep) {
    if (strpos($dep, 'Zend') !== 0) continue;
    $dependencies['optional'][] = str_replace('\\', '_', $dep);
}

file_put_contents(
    ROOT . '/data/dependencies/' . $package_name . '-scanned.php',
    '<?php return ' . str_replace('\\\\', '\\', var_export($dependencies, true)) . ';' . "\n"
);
exit(0);