#!/usr/bin/env php
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

$composers = array();

// create packages.json
$di = new DirectoryIterator(ROOT . '/packages/component-composer'); // change this to actual public directory
foreach ($di as $file) {
    if ($file->isDot()) {
        continue;
    }

    $zip = new ZipArchive();
    $zip->open($file->getPathname());
    $composer_index = $zip->locateName('composer.json', ZIPARCHIVE::FL_NODIR);
    if ($composer_index) {
        $fp = $zip->getStream($zip->getNameIndex($composer_index));
        $composers[$file->getBasename()] = json_decode(stream_get_contents($fp));
    }
    $zip->close();
}

var_dump(array_keys($composers));



//$autoload = '';
//
//// Dependencies
//$library_component_path = str_replace('_', '/', $package_name);
//if (file_exists($zf2_library_path . '/' . $library_component_path . '/composer.json')) {
//    $content  = file_get_contents($zf2_library_path . '/' . $library_component_path . '/composer.json');
//    $composer = json_decode($content, true);
//    if (isset($composer['target-dir'])) {
//        unset($composer['target-dir']);
//    }
//} else {
//    $composer = array();
//    $composer['name'] = strtolower(str_replace('_', '-', $package_name));
//    $composer['name'] = 'zendframework/' . $composer['name'];
//    $composer['license'] = 'BSD-3-Clause';
//    $composer['keywords'] = array('zf2', strtolower(str_replace('Zend_', '', $package_name)));
//    $composer['autoload']['psr-0'][str_replace('_', '\\', $package_name)] = $autoload;
//    $composer['require']['php'] = ">=5.3.3";
//
//    if (file_exists(ROOT . '/data/dependencies/' . $package_name . '.php')) {
//        $dependency_file = ROOT . '/data/dependencies/' . $package_name . '.php';
//    } elseif (file_exists(ROOT . '/data/dependencies/' . $package_name . '-scanned.php')) {
//        $dependency_file = ROOT . '/data/dependencies/' . $package_name . '-scanned.php';
//        $package_info = include $dependency_file;
//    }
//
//    if (isset($package_info) && $package_info) {
//        if (isset($package_info['required'])) {
//            foreach ($package_info['required'] as $dependency) {
//                $dependency_name = strtolower(str_replace('_', '-', $dependency));
//                $dependency_name = 'zendframework/' . $dependency_name;
//                $composer['require'][$dependency_name] = "$release";
//            }
//        }
//        if (isset($package_info['optional'])) {
//            foreach ($package_info['optional'] as $dependency) {
//                $dependency_name = strtolower(str_replace('_', '-', $dependency));
//                $dependency_name = 'zendframework/' . $dependency_name;
//
//                $suggest = str_replace('_', '\\', $dependency) . ' component';
//                $composer['suggest'][$dependency_name] = $suggest;
//            }
//        }
//    }
//}
//
//$composer['version'] = $release;
//
//$composer['dist']['url'] = "$composer_url/{$package_file_base_uri}{$package_file_name}";
//$composer['dist']['type'] = "zip";
//
//
//// write composer file
//$write = '<?php return ' . var_export($composer, true) . '; ? >';
//$filename = "$release.part.php";
//$path = ROOT . '/data/composer/' . $package_name. '/';
//if (!is_dir($path)) {
//    mkdir($path, 0777, true);
//}
//
//echo "Storing $path$filename" . PHP_EOL;
//file_put_contents($path.$filename, $write);