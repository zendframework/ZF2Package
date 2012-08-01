<?php

require 'scripts/functions.php';
require 'build-config.php';

define('ROOT', realpath(__DIR__ . '/../'));

$php = $_SERVER["_"];

script_run_command($php . ' ' . ROOT . '/build/scripts/component-composer-release.php');
script_run_command($php . ' ' . ROOT . '/build/scripts/component-pyrus-release.php');
script_run_command($php . ' ' . ROOT . '/build/scripts/manual-release.php ' . $release);
script_run_command($php . ' ' . ROOT . '/build/scripts/apidoc-release.php ' . $release);
script_run_command($php . ' ' . ROOT . '/build/scripts/plain-release.php ' . $release);
