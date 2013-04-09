<?php
if ($argc != 3) {
    printf("[%s] Invalid arguments (requires 2, received %d)\n", $argv[0], $argc);
    printf("Usage:\n    %s [version] [packages.json]\n", $argv[0]);
    exit(1);
}
$version      = $argv[1];
$packagesJson = $argv[2];

$packages = file_get_contents($packagesJson);
$packages = json_decode($packages);
$packages = $packages->packages;
$zfPackages = $packages->{'zendframework/zendframework'};
if (!isset($zfPackages->{$version})) {
    printf("Package for Zend Framework version '%' not found\n", $version);
    exit(1);
}
$package = $zfPackages->{$version}->dist->url;
$path = realpath(dirname($packagesJson)) . parse_url($package, PHP_URL_PATH);
file_put_contents('php://stdout',  $path);
