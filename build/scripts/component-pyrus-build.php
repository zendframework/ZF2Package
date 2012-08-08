#!/usr/bin/env php -dphar.readonly=0
<?php

define('ROOT', realpath(__DIR__ . '/../../'));

require_once 'functions.php';

// get information from CLI
$package_name_full = $_SERVER['argv'][1];
$package_source = $_SERVER['argv'][2];
$package_filter = (isset($_SERVER['argv'][3])) ? trim($_SERVER['argv'][3], '/') : null;

list($package_name, $package_release) = preg_split('#-#', $package_name_full);

$pyrus_path = $_SERVER['_'] . ' -dphar.readonly=0 ' . __DIR__ . '/pyrus.phar';
$channel = 'packages.zendframework.com';

if (strpos(shell_exec($pyrus_path . ' list-channels'), $channel) === false) {
    script_exit('The channel ' . $channel . ' is not in your channel list');
}

if (ini_get('phar.readonly') == 'On') {
    script_exit('To build with phar, PHP must have phar.readonly=0');
}

if (file_exists(ROOT . '/packages/component-pyrus/' . $package_name)) {
    script_run_command('rm -Rf ' . ROOT . '/packages/component-pyrus/' . $package_name);
}

chdir(ROOT . '/packages/component-pyrus/');

$command = $pyrus_path . ' generate-pear2 ' . $package_name . ' ' . $channel;
script_run_command($command);

chdir(ROOT . '/packages/component-pyrus/' . $package_name);

script_run_command('rm -Rf ./src/');

chdir($package_source);

if ($package_filter === 'metadata-package') {
    script_run_command('cp -R ' . ROOT . '/build/scripts/pyrus-meta-templates/* ' . ROOT . '/packages/component-pyrus/' . $package_name);
} else {
    script_run_command('tar -cf - ' . $package_filter . ' | tar -C ' . ROOT . '/packages/component-pyrus/' . $package_name . ' -xf -');
}

chdir(ROOT . '/packages/component-pyrus/' . $package_name);

if (file_exists('./library')) {
    rename('./library', './src');
}

$command = 'mv RELEASE-0.1.0 RELEASE-' . $package_release;
$output = script_run_command($command);

$command = 'mv API-0.1.0 API-' . $package_release;
$output = script_run_command($command);

$command = 'rm package_compatible.xml';
$output = script_run_command($command);

$command = 'rm stub.php';
$output = script_run_command($command);

$command = 'rm -Rf tests/ www/ example/ docs/ data/';
$output = script_run_command($command);

$file_replacements = array();
$file_replacements['{PACKAGE_NAME}'] = $package_name;
$file_replacements['{PACKAGE_RELEASE}'] = $package_release;
$file_replacements['{PACKAGE_REQUIRE_DEPENDENCIES}'] = null;


$composers = glob_recursive('composer.json');
if ($composers) {
    $composer_file = $composers[0];

    $content  = file_get_contents($composer_file);
    $composer = json_decode($content, true);
    $package_info = array('required' => array(), 'optional' => array());
    foreach($composer['require'] as $dep => $version) {
        $vendor = $name = $dep;
        if (strpos($dep, '/')) {
            list($vendor, $name) = explode('/', $dep, 2);
        }
        if ($vendor == 'zendframework') {
            $package_info['required'][] = composer_name_to_package_name($name);
        }
    }
    if (isset($composer['suggest'])) {
        foreach($composer['suggest'] as $dep => $version) {
            $vendor = $name = $dep;
            if (strpos($dep, '/')) {
                list($vendor, $name) = explode('/', $dep, 2);
            }
            if ($vendor == 'zendframework') {
                $package_info['optional'][] = composer_name_to_package_name($name);
            }
        }
    }

    $packagexmlsetup_content = '<?php' . PHP_EOL;
    if (isset($package_info['required'])) {
        foreach ($package_info['required'] as $dependency) {
            $file_replacements['{PACKAGE_REQUIRE_DEPENDENCIES}'] .= 'require_once \'' . $dependency . '-' . trim($package_release) . '.phar\';' . "\n";
            $file_replacements['{PACKAGE_DEPENDENCY}'] = trim($dependency);
            $packagexmlsetup_content .= apply_replacements(file_get_contents(ROOT . '/build/scripts/pyrus-templates/packagexmlsetup-required.php'), $file_replacements);
        }
    }
    if (isset($package_info['optional'])) {
        foreach ($package_info['optional'] as $dependency) {
            $file_replacements['{PACKAGE_DEPENDENCY}'] = trim($dependency);
            $packagexmlsetup_content .= apply_replacements(file_get_contents(ROOT . '/build/scripts/pyrus-templates/packagexmlsetup-optional.php'), $file_replacements);
        }
    }
    echo 'Writing: packagexmlsetup.php' . PHP_EOL;
    file_put_contents('packagexmlsetup.php', $packagexmlsetup_content);
}

if ($package_filter !== 'metadata-package') {
    echo 'Writing: stub.php' . PHP_EOL;
    file_put_contents('stub.php', '<?php' . "\n" . trim(apply_replacements(file_get_contents(ROOT . '/build/scripts/pyrus-templates/stub.php'), $file_replacements)));
} 

script_run_command($pyrus_path . ' make');
script_run_command($pyrus_path . ' package -p');

script_run_command('rm -Rf ' . ROOT . '/packages/component-pyrus/' . $package_name . '-' . $package_release . '.*');

script_run_command('mv ' . $package_name . '-' . $package_release . '.* ' . ROOT . '/packages/component-pyrus/');
chdir(ROOT . '/packages/component-pyrus/' . $package_name);
script_run_command('rm -Rf ' . ROOT . '/packages/component-pyrus/' . $package_name);

