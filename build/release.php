<?php

require 'scripts/functions.php';
require 'build-config.php';

define('ROOT', realpath(__DIR__ . '/../'));

$php = $_SERVER["_"];

script_run_command($php . ' scripts/component-composer-release.php');
script_run_command($php . ' scripts/component-pyrus-release.php');