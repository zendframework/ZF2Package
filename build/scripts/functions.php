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
