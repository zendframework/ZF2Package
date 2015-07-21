<?php
/**
 * ZendJobQueue: Create new zfdeploy.phar and make it available for self-update
 *
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

use Herrera\Box\Box;
use Herrera\Box\StubGenerator;

require __DIR__ . '/../../box/vendor/autoload.php';

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

    // open manifest file, parse, array_shift, array_push manifest
    $manifestFile = sprintf('%s/manifest.json', $appDir);
    $json         = file_get_contents($manifestFile);
    $manifests    = json_decode($json, true);

    // git fetch origin
    $output = array();
    exec('/usr/local/bin/git fetch origin', $output, $return);
    if (0 !== $return) {
        return reportError(array(
            'error' => 'Failed to retrieve changes via git',
        ), $startDir, false);
    }

    // git checkout $version
    $output = array();
    exec(sprintf('/usr/local/bin/git checkout %s', $version), $output, $return);
    if (0 !== $return) {
        return reportError(array(
            'error' => 'Failed to checkout version',
        ), $startDir, false);
    }

    // install dependencies
    $output = array();
    exec('rm -Rf ./vendor', $output, $return);
    if (0 !== $return) {
        return reportError(array(
            'error' => 'Failed to remove vendor directory',
        ), $startDir, false);
    }
    $output = array();
    exec(
        'COMPOSER_HOME=/var/www/apache/.composer /usr/local/zend/bin/php /var/www/apache/bin/composer update -n',
        $output,
        $return
    );
    if (0 !== $return) {
        return reportError(array(
            'error' => 'Failed to install dependencies',
            'output' => $output,
            'return' => $return,
        ), $startDir);
    }

    // if not a tagged version, get normalized version
    if (! preg_match('#^\d+\.\d+\.\d+(?:-[a-z][a-z0-9]*)?$#', $version)) {
        $version = getTagVersion($version, $manifests);
    }

    // Replace version constant
    // Older versions defined it in the Deploy class
    $deployClass = file_get_contents('src/Deploy.php');
    $deployClass = preg_replace('/(\n\s+const VERSION\s+= \')([^\']+)\';/s', '${1}' . $version . '\';', $deployClass);
    file_put_contents('src/Deploy.php', $deployClass);

    // Newer versions define the version in the script
    $script = file_get_contents('bin/zfdeploy.php');
    $quoted = preg_quote("define('VERSION', ");
    $script = preg_replace('/(' . $quoted . ')\'[^\']+\'/s', '${1}\'' . $version . '\'', $script);

    // Strip shebang from script
    $script = str_replace("#!/usr/bin/env php\n", '', $script);
    file_put_contents('bin/zfdeploy.php', $script);

    // Build phar
    $output = array();
    unlink('zfdeploy.phar');
    createPhar($version, getcwd());
    if (! file_exists('zfdeploy.phar')) {
        return reportError(array(
            'error' => 'Failed to build phar',
        ), $startDir);
    }

    // cp zfdeploy.phar __DIR__ . '/../public/zf-deploy/zfdeploy-$version.phar'
    $releaseFile = sprintf('%s/zfdeploy-%s.phar', $appDir, $version);
    if (false === copy('zfdeploy.phar', $releaseFile)) {
        return reportError(array(
            'error' => 'Failed to copy phar to public directory',
        ), $startdir);
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

    // Add manifest to list
    array_push($manifests, $manifest);

    // write manifest file
    file_put_contents($manifestFile, json_encode($manifests));

    // symlink zfdeploy.phar to new version
    chdir($appDir);
    unlink('zfdeploy.phar');
    symlink(sprintf('zfdeploy-%s.phar', $version), 'zfdeploy.phar');

    chdir($startDir);
    return true;
}

function getTagVersion($version, array $manifests)
{
    $tags = array();
    exec('/usr/local/bin/git tag', $tags);

    if (empty($tags)) {
        $tag = '0.1.0';
    } else {
        usort($tags, 'version_compare');
        $tag = array_pop($tags);
    }

    $increment = 1;
    $pcreTag = preg_quote($tag);
    foreach ($manifests as $manifest) {
        $manifestVersion = $manifest['version'];
        if (preg_match('#^' . $pcreTag . '-(?P<inc>\d+)-(?:[a-f0-9]{8})$#', $manifestVersion, $matches)) {
            if ($increment <= (int) $matches['inc']) {
                $increment = $matches['inc'] + 1;
            }
        }
    }

    return sprintf('%s-%d-%s', $tag, $increment, $version);
}

function createPhar($version, $path)
{

    $origin = getcwd();
    $path   = realpath($path);

    chdir($path);

    $box = Box::create($path . '/zfdeploy.phar');
    $rdi = new RecursiveDirectoryIterator(
        $path,
        FilesystemIterator::KEY_AS_PATHNAME
        | FilesystemIterator::CURRENT_AS_FILEINFO
        | FilesystemIterator::SKIP_DOTS
    );
    $rii = new RecursiveIteratorIterator($rdi);
    $filtered = new ZFDeployPharFilter($rii, $path);

    $box->buildFromIterator($filtered, $path);
    $box->addFile($path . '/LICENSE.txt');
    $box->getPhar()->setStub(
        StubGenerator::create()
            ->index('bin/zfdeploy.php')
            ->extract(true)
            ->intercept(true)
            ->alias('zfdeploy.phar')
            ->generate()
    );

    chdir($origin);
}

function reportError($error, $origin, $restore = true)
{
    file_put_contents(sys_get_temp_dir() . '/last_job_error.json', json_encode($error));
    if ($restore) {
        exec('/usr/local/bin/git checkout -- .');
    }
    chdir($origin);
    return false;
}
