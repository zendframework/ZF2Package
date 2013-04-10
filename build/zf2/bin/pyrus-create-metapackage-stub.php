<?php
if ($argc != 6) {
    printf("[%s] Invalid arguments (requires 4, received %d)\n", $argv[0], $argc);
    printf("Usage:\n    %s [package] [version] [composer.json] [pyrus-templates path] [build path]\n", $argv[0]);
    exit(1);
}

$package         = $argv[1];
$package_version = $argv[2];
$composer        = $argv[3];
$pyrus_templates = $argv[4];
$package_dir     = $argv[5];

require_once __DIR__ . '/functions.php';

chdir($package_dir);

$file_replacements = array();
$file_replacements['{PACKAGE_NAME}'] = $package;
$file_replacements['{PACKAGE_RELEASE}'] = $package_version;
$file_replacements['{PACKAGE_REQUIRE_DEPENDENCIES}'] = null;

$content      = file_get_contents($composer);
$composer     = json_decode($content, true);
$package_info = array('required' => array(), 'optional' => array());
foreach ($composer['replace'] as $dep => $version) {
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

$packagexmlsetup_content = '<' . '?php' . PHP_EOL;
if (isset($package_info['required'])) {
    foreach ($package_info['required'] as $dependency) {
        $file_replacements['{PACKAGE_REQUIRE_DEPENDENCIES}'] .= 'require_once \'' . $dependency . '-' . trim($package_version) . '.phar\';' . "\n";
        $file_replacements['{PACKAGE_DEPENDENCY}'] = trim($dependency);
        $packagexmlsetup_content .= apply_replacements(file_get_contents($pyrus_templates . '/packagexmlsetup-required.php'), $file_replacements);
    }
}
if (isset($package_info['optional'])) {
    foreach ($package_info['optional'] as $dependency) {
        $file_replacements['{PACKAGE_DEPENDENCY}'] = trim($dependency);
        $packagexmlsetup_content .= apply_replacements(file_get_contents($pyrus_templates . '/packagexmlsetup-optional.php'), $file_replacements);
    }
}
echo 'Writing: packagexmlsetup.php' . PHP_EOL;
file_put_contents('packagexmlsetup.php', $packagexmlsetup_content);
