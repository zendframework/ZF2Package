<?php

require 'scripts/functions.php';
require 'build-config.php';

define('ROOT', realpath(__DIR__ . '/../'));

chdir(ROOT . '/packages/working/');

script_run_command('rm -Rf ' . ROOT . '/packages/apidoc/Zend*');
script_run_command('rm -Rf ' . ROOT . '/packages/component-composer/Z*');
script_run_command('rm -Rf ' . ROOT . '/packages/component-pyrus/Z*');
script_run_command('rm -Rf ' . ROOT . '/packages/manual/Z*');
script_run_command('rm -Rf ' . ROOT . '/packages/plain/Z*');
script_run_command('rm -Rf ' . ROOT . '/packages/working/Z* ' . ROOT . '/packages/working/z*');