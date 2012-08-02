<?php

function script_run_command($command) {
    $text_command = $command;
//    $text_command = str_replace($_SERVER['_'], 'php', $text_command);
//    $text_command = str_replace($_SERVER['PWD'] . '/', '', $text_command);
//    $text_command = str_replace(ROOT . '/', '../', $text_command);

    echo 'Running: ' . $text_command . PHP_EOL;
    $return_code = null;
    passthru($command, $return_code);
    if ($return_code !== 0) {
        exit('Command failed ' . PHP_EOL);
    }
}

function script_exit($reason) {
    echo $reason;
    echo PHP_EOL . PHP_EOL;
    exit(-1);
}

function apply_replacements($source, $replacements) {
    foreach ($replacements as $var => $value) {
        $source = str_replace($var, $value, $source);
    }
    return $source;
}

function glob_recursive($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

function create_composer_json_stub($filename)
{
    $composer = array();

    list($package_name, $package_release) = preg_split('#-#', pathinfo($filename)['filename'], 2);

    if (!$package_name || !$package_release) {
        script_exit('Unable to determine either package name or package release from filename "' . $filename . '"');
    }

    $composer['name'] = strtolower(str_replace('_', '-', $package_name));
    $composer['name'] = 'zendframework/' . $composer['name'];
    $composer['license'] = 'BSD-3-Clause';
    $composer['keywords'] = array('zf2', strtolower(str_replace('Zend_', '', $package_name)));
    $composer['autoload']['psr-0'][str_replace('_', '\\', $package_name)] = '';
    $composer['require']['php'] = ">=5.3.3";

    $composer['version'] = $package_release;
    $composer['dist']['url'] = "http://packages.zendframework.com/composer/{$package_name}.zip";
    $composer['dist']['type'] = "zip";

    return $composer;
}
