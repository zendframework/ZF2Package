<?php
/**
 * ZendJobQueue: Create new zfdeploy.phar and make it available for self-update
 *
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

if ($_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR']) {
    file_put_contents(sys_get_temp_dir() . '/last_job.json', json_encode(array(
        'error'       => 'Unauthorized caller',
        'remote_addr' => $_SERVER['REMOTE_ADDR'],
        'server_addr' => $_SERVER['SERVER_ADDR'],
    )));
    header('HTTP/1.1 403 Forbidden');
    exit(1);
}

$params = ZendJobQueue::getCurrentJobParams();
file_put_contents(sys_get_temp_dir() . '/last_job.json', json_encode($params));
if (! isset($params['version'])) {
    file_put_contents(sys_get_temp_dir() . '/last_job_error.json', json_encode(array(
        'error' => 'missing version parameter',
    )));
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
    $appDir  = __DIR__;

    // chdir /var/local/zf-deploy
    $startDir = getcwd();
    chdir('/var/local/zf-deploy');

    // git fetch origin
    $output = array();
    exec('/usr/local/bin/git fetch origin', $output, $return);
    if (0 !== $return) {
        file_put_contents(sys_get_temp_dir() . '/last_job_error.json', json_encode(array(
            'error' => 'Failed to retrieve changes via git',
        )));
        chdir($startDir);
        return false;
    }

    // git checkout $version
    $output = array();
    exec(sprintf('/usr/local/bin/git checkout %s', $version), $output, $return);
    if (0 !== $return) {
        file_put_contents(sys_get_temp_dir() . '/last_job_error.json', json_encode(array(
            'error' => 'Failed to checkout version',
        )));
        chdir($startDir);
        return false;
    }

    // install dependencies
    $output = array();
    exec('rm -Rf ./vendor', $output, $return);
    if (0 !== $return) {
        file_put_contents(sys_get_temp_dir() . '/last_job_error.json', json_encode(array(
            'error' => 'Failed to remove vendor directory',
        )));
        chdir($startDir);
        return false;
    }
    $output = array();
    exec('COMPOSER_HOME=/var/www/apache/.composer /usr/local/zend/bin/php /usr/local/bin/composer install -n', $output, $return);
    if (0 !== $return) {
        file_put_contents(sys_get_temp_dir() . '/last_job_error.json', json_encode(array(
            'error' => 'Failed to install dependencies',
            'output' => $output,
            'return' => $return,
        )));
        exec('/usr/local/bin/git checkout -- .');
        chdir($startDir);
        return false;
    }

    // Replace version constant
    $deployClass = file_get_contents('src/Deploy.php');
    $deployClass = preg_replace('/(\s+VERSION = \')([^\']+)\';/s', '$1@package_version@\';', $deployClass);
    file_put_contents('src/Deploy.php', $deployClass);

    // /usr/local/bin/box build
    $output = array();
    unlink('zfdeploy.phar');
    exec(sprintf('/usr/local/zend/bin/php /var/www/apache/box/bin/createPhar.php %s', $version), $output, $return);
    if (0 !== $return) {
        // cleanup
        file_put_contents(sys_get_temp_dir() . '/last_job_error.json', json_encode(array(
            'error' => 'Failed to build phar',
            'output' => $output,
            'return' => $return,
            'user'   => getenv('USER'),
        )));
        exec('/usr/local/bin/git checkout -- .');
        chdir($startDir);
        return false;
    }

    // cp zfdeploy.phar __DIR__ . '/../public/zf-deploy/zfdeploy-$version.phar'
    $releaseFile = sprintf('%s/zfdeploy-%s.phar', $appDir, $version);
    if (false === copy('zfdeploy.phar', $releaseFile)) {
        // cleanup
        file_put_contents(sys_get_temp_dir() . '/last_job_error.json', json_encode(array(
            'error' => 'Failed to copy phar to public directory',
        )));
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
    $manifestFile = sprintf('%s/manifest.json', $appDir);
    $json = file_get_contents($manifestFile);
    $data = json_decode($json, true);
    array_shift($data);
    array_push($data, $manifest);
    $json = json_encode($data);

    // write manifest file
    file_put_contents($manifestFile, $json);

    // symlink zfdeploy.phar to new version
    chdir($appDir);
    unlink('zfdeploy.phar');
    symlink(sprintf('zfdeploy-%s.phar', $version), 'zfdeploy.phar');

    chdir($startDir);
    return true;
}
