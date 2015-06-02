<?php // @codingStandardsIgnoreFile
$packagesJson = $argv[1];
$packagesBase = isset($argv[2]) ? $argv[2] : 'https://packages.zendframework.com/';
$path         = 'packages.json';
do {
    $json = getJson($path, $packagesBase);
    $packages = json_decode($json);

    if (! isset($packages->includes)) {
        break;
    }

    $data = (array) $packages->includes;
    $path = key($data);
} while ($path);

// If an include was discovered, and the package base is a local filesystem 
// (i.e., PRODUCTION!), then we need to rewrite the source.url for each package 
// to reflect the GitHub url instead of the local filesystem.
if ($path && $path !== 'packages.json' && ! preg_match('#^https?://#', $packagesBase)) {
    // Rewrite contents
    $changes = false;
    foreach ($packages->packages as $name => $package) {
        foreach ($package as $version => $metadata) {
            if (! isset($metadata->source)) {
                continue;
            }

            if (! isset($metadata->source->url)) {
                continue;
            }

            // Rewrite local URIs for source code packages to github URIs
            $metadata->source->url = preg_replace(
                '#^/var/local/framework/components/([^.].*?)/\.git$#s',
                'https://github.com/$1.git',
                $metadata->source->url
            );
            $changes = true;
        }
    }

    if ($changes) {
        $json     = json_encode($packages, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        $dataPath = $packagesBase . '/' . $path;
        file_put_contents($dataPath, $json);
    }
}

// Cache the discovered/calculated JSON for use with creating distribution 
// packages.
file_put_contents($packagesJson, $json);
exit(0);

/**
 * Retrieve the JSON contents from a URI
 */
function getJson($path, $baseUrl)
{
    $url    = sprintf('%s/%s', rtrim($baseUrl, '/'), $path);
    $scheme = parse_url($baseUrl, PHP_URL_SCHEME);

    // No scheme === filesystem.
    if (! $scheme) {
        return file_get_contents($url);
    }

    // Remote, so use cURL
    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $json = curl_exec($ch);
    curl_close($ch);
    return $json;
}
