#!/usr/bin/env php
<?php
/**
 * Gearman worker: create new zfdeploy.phar and make it available for self-update
 *
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

ini_set('memory_limit', -1);

$worker = new GearmanWorker();
$worker->addServer();

$worker->addFunction('zfdeploy', 'zfdeploy');

while ($worker->work()) {
    if ($worker->returnCode() !== GEARMAN_SUCCESS) {
        printf("[%s] (%s) %s\n", date('Y-m-d H:i:s'), $worker->returnCode(), $worker->error());
    }
}

/**
 * Rebuild the phar file.
 * 
 * @param mixed $job 
 * @return void
 */
function zfdeploy($job)
{
    $version = $job->workload();
    $appDir  = dirname(__DIR__);

    // Normalize version; only need first 8 chars of sha1
    if (preg_match('/^[a-f0-9]{32}$/i', $version)) {
        $version = substr($version, 0, 8);
    }

    // chdir /var/local/zf-deploy
    chdir('/var/local/zf-deploy');

    // git fetch origin
    exec('/usr/local/bin/git fetch origin', $output, $return);
    if (0 !== $return) {
        $job->sendFail();
        return false;
    }

    // git checkout $version
    exec(sprintf('/usr/local/bin/git checkout %s', $version), $output, $return);
    if (0 !== $return) {
        $job->sendFail();
        return false;
    }

    // Replace version constant
    $deployClass = file_get_contents('src/Deploy.php');
    $deployClass = preg_replace('/(\s+VERSION = \')([^\']+)\';/', '$1@package_version@\';', $deployClass);
    file_put_contents('src/Deploy.php', $deployClass);

    // /usr/local/bin/box build
    exec(sprintf('/usr/local/bin/box build', $version), $output, $return);
    if (0 !== $return) {
        $job->sendFail();

        // cleanup
        exec('/usr/local/bin/git checkout -- src/Deploy.php');
        return false;
    }

    // cp zfdeploy.phar __DIR__ . '/../public/zf-deploy/zfdeploy-$version.phar'
    $releaseFile = sprintf('%s/public/zf-deploy/zfdeploy-%s.phar', $appDir, $version);
    if (false === copy('zfdeploy.phar', $releaseFile)) {
        $job->sendFail();

        // cleanup
        exec('/usr/local/bin/git checkout -- src/Deploy.php');
        exec('/usr/local/bin/git checkout -- zfdeploy.phar');
        return false;
    }

    // cleanup
    exec('/usr/local/bin/git checkout -- src/Deploy.php');
    exec('/usr/local/bin/git checkout -- zfdeploy.phar');
    
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
    $manifestFile = sprintf('%s/public/zf-deploy/manifest.json');
    $json = file_get_contents($manifestFile);
    $data = json_decode($json, true);
    array_shift($data);
    array_push($data, $manifest);
    $json = json_encode($data);

    // write manifest file
    file_put_contents($manifestFile, $json);

    $job->sendComplete(json_encode(array('version' => $version)));
    return true;
}
