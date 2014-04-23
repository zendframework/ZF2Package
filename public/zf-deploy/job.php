<?php
/**
 * ZendJobQueue: Create new zfdeploy.phar and make it available for self-update
 *
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

if ($_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR']) {
    header('HTTP/1.1 403 Forbidden');
    exit(1);
}

$params = ZendJobQueue::getCurrentJobParams();
if (! isset($params['version'])) {
    ZendJobQueue::setCurrentJobStatus(ZendJobQueue::FAILED);
    exit(1);
}
if (false === zfdeploy($params['version'])) {
    ZendJobQueue::setCurrentJobStatus(ZendJobQueue::FAILED);
    exit(1);
}
ZendJobQueue::setCurrentJobStatus(ZendJobQueue::OK);
exit(0);


/**
 * Rebuild the phar file.
 * 
 * @param string $version 
 */
function zfdeploy($version)
{
    $appDir  = dirname(__DIR__);

    // chdir /var/local/zf-deploy
    $startDir = getcwd();
    chdir('/var/local/zf-deploy');

    // git fetch origin
    exec('/usr/local/bin/git fetch origin', $output, $return);
    if (0 !== $return) {
        chdir($startDir);
        return false;
    }

    // git checkout $version
    exec(sprintf('/usr/local/bin/git checkout %s', $version), $output, $return);
    if (0 !== $return) {
        chdir($startDir);
        return false;
    }

    // install dependencies
    exec('rm -Rf ./vendor', $output, $return);
    if (0 !== $return) {
        chdir($startDir);
        return false;
    }
    exec('/usr/local/bin/composer install', $output, $return);
    if (0 !== $return) {
        chdir($startDir);
        return false;
    }

    // Replace version constant
    $deployClass = file_get_contents('src/Deploy.php');
    $deployClass = preg_replace('/(\s+VERSION = \')([^\']+)\';/', '$1@package_version@\';', $deployClass);
    file_put_contents('src/Deploy.php', $deployClass);

    // /usr/local/bin/box build
    exec(sprintf('/usr/local/bin/box build', $version), $output, $return);
    if (0 !== $return) {
        // cleanup
        exec('/usr/local/bin/git checkout -- .');
        chdir($startDir);
        return false;
    }

    // cp zfdeploy.phar __DIR__ . '/../public/zf-deploy/zfdeploy-$version.phar'
    $releaseFile = sprintf('%s/public/zf-deploy/zfdeploy-%s.phar', $appDir, $version);
    if (false === copy('zfdeploy.phar', $releaseFile)) {
        // cleanup
        exec('/usr/local/bin/git checkout -- .');
        chdir($startDir);
        return false;
    }

    // cleanup
    exec('/usr/local/bin/git checkout -- .');
    
    // $hash = hash_file('sha1', __DIR__ . '/../public/zf-deploy/zfdeploy-$version.phar');
    $hash = hash_file('sha1', $releaseFile);

    // create manifest entry:
    $manifest = array(
        'name'    => 'zfdeploy.phar',
        'sha1'    => $hash,
        'url'     => sprintf('https://packages.zendframework.com/zf-deploy/zfdeploy-%s.phar', $version),
        'version' => $version,
    );

    // open manifest file, parse, array_shift, array_push manifest
    $manifestFile = sprintf('%s/public/zf-deploy/manifest.json', $appDir);
    $json = file_get_contents($manifestFile);
    $data = json_decode($json, true);
    array_shift($data);
    array_push($data, $manifest);
    $json = json_encode($data);

    // write manifest file
    file_put_contents($manifestFile, $json);

    // symlink zfdeploy.phar to new version
    chdir($appDir);
    unlink('public/zf-deploy/zfdeploy.phar');
    symlink(sprintf('public/zf-deploy/zfdeploy-%s.phar', $version), 'public/zf-deploy/zfdeploy.phar');

    chdir($startDir);
    return true;
}
