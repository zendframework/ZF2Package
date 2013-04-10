<?php
if ($argc != 4) {
    printf("[%s] Invalid arguments (requires 3, received %d)\n", $argv[0], $argc);
    printf("Usage:\n    %s [package] [version] [packages.json]\n", $argv[0]);
    exit(1);
}
$packageName  = $argv[1];
$version      = $argv[2];
$packagesJson = $argv[3];

$packages = file_get_contents($packagesJson);
$packages = json_decode($packages);
$packages = $packages->packages;
if (!isset($packages->{$packageName})) {
    printf("No definitions found for package '%s'\n", $packageName);
    exit(1);
}
$packages = $packages->{$packageName};
if (!isset($packages->{$version})) {
    printf("%s package for version %s not found\n", $packageName, $version);
    exit(1);
}
$package = $packages->{$version}->dist->url;
$path = realpath(dirname($packagesJson)) . parse_url($package, PHP_URL_PATH);
file_put_contents('php://stdout',  $path);
