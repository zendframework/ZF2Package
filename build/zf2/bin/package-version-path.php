<?php
if ($argc != 5) {
    printf("[%s] Invalid arguments (requires 4, received %d)\n", $argv[0], $argc);
    printf("Usage:\n    %s [package] [version] [packages.json]\n", $argv[0]);
    exit(1);
}
$packageName  = $argv[1];
$version      = $argv[2];
$packagesJson = $argv[3];
$public       = $argv[4];

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
$path = realpath($public) . parse_url($package, PHP_URL_PATH);
if (!file_exists($path)) {
    system(sprintf('wget -O "%s" "%s"', $path, $package));
}
if (!file_exists($path)) {
    file_put_contents('php://stderr', "Failed to download package from packages site!\n");
    exit(1);
}
file_put_contents('php://stdout', $path);
