#!/usr/bin/env php
<?php

include 'functions.php';

define('ROOT', realpath(__DIR__ . '/../../'));

$default_data = [
    'type'         => 'library',
    'homepage'     => 'http://packages.zendframework.com/',
    'license'      => 'BSD-3-Clause',
    'repositories' => [
        'type' => 'composer',
        'url'  => 'http://packages.zendframework.com/',
    ],
    'support'      => [
        'email'    => 'fw-general-subscribe@lists.zend.com',
        'irc'      => 'irc://irc.freenode.net/zftalk',
        'issues'   => 'https://github.com/zendframework/zf2/issues',
        'source'   => 'https://github.com/zendframework/zf2',
    ],
];

$composers = [];

// move composer files first
script_run_command('cp ' . ROOT . '/packages/component-composer/*.zip ' . ROOT . '/public/composer/');

// create packages.json
$di = new DirectoryIterator(ROOT . '/public/composer/');
foreach ($di as $file) {
    if ($file->isDot()) {
        continue;
    }

    $zip = new ZipArchive();
    $zip->open($file->getPathname());
    $composer_index_in_root = $zip->locateName('composer.json');
    if ($composer_index_in_root !== false) {
        $fp = $zip->getStream($zip->getNameIndex($composer_index_in_root));
        $composer_content = json_decode(stream_get_contents($fp), true);
    } else {
        $composer_index_anywhere = $zip->locateName('composer.json', ZIPARCHIVE::FL_NODIR);
        if ($composer_index_anywhere) {
            $fp = $zip->getStream($zip->getNameIndex($composer_index_anywhere));
            $composer_content = json_decode(stream_get_contents($fp), true);
        } else {
            $composer_content = create_composer_json_stub($file->getBasename());
        }
    }

    $composer_content = array_merge($default_data, $composer_content);

    if (!isset($composer_content['dist'])) {
        $composer_content['dist'] = [
            'url'  => 'http://packages.zendframework.com/composer/' . $file->getFilename(),
            'type' => 'zip',
        ];
    }

    $composers[$file->getBasename()] = $composer_content;
    $zip->close();
}

$zf2_metapackage_template = [
    'name'         => 'zendframework/zendframework',
    'description'  => 'Zend Framework 2',
    'type'         => 'library',
    'license'      => 'BSD-3-Clause',
    'support'      => [
        'email'    => 'fw-general-subscribe@lists.zend.com',
        'irc'      => 'irc://irc.freenode.net/zftalk',
        'issues'   => 'https://github.com/zendframework/zf2/issues',
        'source'   => 'https://github.com/zendframework/zf2',
    ],
    'keywords'     => [
        'framework',
        'zf2',
    ],
    'autoload'     => [
        'psr-0' => [
            'Zend\\' => 'library/',
            'ZendTest\\' => 'tests/',
        ],
    ],
    'source'       => [
        'type' => 'git',
        'url'  => 'git://github.com/zendframework/zf2.git',
        'reference' => 'release-',
    ],
    'dist'     => [
        'type'      => 'zip',
        'url'       => 'http://packages.zendframework.com/releases/ZendFramework-%s/ZendFramework-%s.zip',
        'reference' => 'release-',
        'shasum'    => '',
    ],
    'require'      => [
        'php'      => '>=5.3.3',
    ],
    'require-dev'  => [
        'doctrine/common' => '>=2.1',
    ],
    'suggest'  => [
        'doctrine/common'                     => 'Doctrine\Common >=2.1 for annotation features',
        'ext-intl'                            => 'ext/intl for i18n features',
        'pecl-weakref'                        => 'Implementation of weak references for Zend\Stdlib\CallbackHandler',
        'zendframework/zendpdf'               => 'ZendPdf for creating PDF representations of barcodes',
        'zendframework/zendservice-recaptcha' => 'ZendService\ReCaptcha for rendering ReCaptchas in Zend\Captcha and/or Zend\Form',
    ],
    'replace' => [
        'zendframework/zend-acl'            => 'self.version',
        'zendframework/zend-authentication' => 'self.version',
        'zendframework/zend-barcode'        => 'self.version',
        'zendframework/zend-cache'          => 'self.version',
        'zendframework/zend-captcha'        => 'self.version',
        'zendframework/zend-code'           => 'self.version',
        'zendframework/zend-config'         => 'self.version',
        'zendframework/zend-console'        => 'self.version',
        'zendframework/zend-crypt'          => 'self.version',
        'zendframework/zend-db'             => 'self.version',
        'zendframework/zend-di'             => 'self.version',
        'zendframework/zend-dom'            => 'self.version',
        'zendframework/zend-escaper'        => 'self.version',
        'zendframework/zend-eventmanager'   => 'self.version',
        'zendframework/zend-feed'           => 'self.version',
        'zendframework/zend-file'           => 'self.version',
        'zendframework/zend-filter'         => 'self.version',
        'zendframework/zend-form'           => 'self.version',
        'zendframework/zend-http'           => 'self.version',
        'zendframework/zend-i18n'           => 'self.version',
        'zendframework/zend-inputfilter'    => 'self.version',
        'zendframework/zend-json'           => 'self.version',
        'zendframework/zend-ldap'           => 'self.version',
        'zendframework/zend-loader'         => 'self.version',
        'zendframework/zend-log'            => 'self.version',
        'zendframework/zend-mail'           => 'self.version',
        'zendframework/zend-markup'         => 'self.version',
        'zendframework/zend-math'           => 'self.version',
        'zendframework/zend-memory'         => 'self.version',
        'zendframework/zend-mime'           => 'self.version',
        'zendframework/zend-modulemanager'  => 'self.version',
        'zendframework/zend-mvc'            => 'self.version',
        'zendframework/zend-navigation'     => 'self.version',
        'zendframework/zend-paginator'      => 'self.version',
        'zendframework/zend-progressbar'    => 'self.version',
        'zendframework/zend-serializer'     => 'self.version',
        'zendframework/zend-server'         => 'self.version',
        'zendframework/zend-servicemanager' => 'self.version',
        'zendframework/zend-session'        => 'self.version',
        'zendframework/zend-soap'           => 'self.version',
        'zendframework/zend-stdlib'         => 'self.version',
        'zendframework/zend-tag'            => 'self.version',
        'zendframework/zend-text'           => 'self.version',
        'zendframework/zend-uri'            => 'self.version',
        'zendframework/zend-validator'      => 'self.version',
        'zendframework/zend-view'           => 'self.version',
        'zendframework/zend-xmlrpc'         => 'self.version',
    ],
];

$dev_master_package = $zf2_metapackage_template;
$dev_master_package['version'] = 'dev-master';
$dev_master_package['require']['zendframework/zf2'] = 'master';
$dev_master_package['source']['reference'] = 'master';
unset($dev_master_package['dist']);
$zf2_metapackage = ['dev-master' => $dev_master_package];

$dev_develop_package = $zf2_metapackage_template;
$dev_develop_package['version'] = 'dev-develop';
$dev_develop_package['require']['zendframework/zf2'] = 'develop';
$dev_develop_package['source']['reference'] = 'develop';
unset($dev_develop_package['dist']);
$zf2_metapackage = ['dev-develop' => $dev_develop_package];

$packages = [];
foreach ($composers as $filename => $composer) {
    if (!isset($packages[$composer['name']])) {
        $packages[$composer['name']] = array();
    }

    // check some needed info
    if (!isset($composer['version'])) {
        list($filename_name, $filename_version) = preg_split('#-#', pathinfo($filename)['filename'], 2);
        $composer['version'] = $filename_version;
    }
    if (!isset($composer['repositories'])) {
        $composer['repositories'] = array('type' => 'composer', 'url' => 'http://packages.zendframework.com/');
    }
    if (!isset($composer['type'])) {
        $composer['type'] = 'library';
    }
    $packages[$composer['name']][$composer['version']] = $composer;

    if (!preg_match('#^zendframework/zend-#', $composer['name'])) {
        continue;
    }

    if (!isset($zf2_metapackage[$composer['version']])) {
        $zf2_metapackage[$composer['version']] = $zf2_metapackage_template;
        $zf2_metapackage[$composer['version']]['version'] = $composer['version'];
        $zf2_metapackage[$composer['version']]['source']['reference'] .= $composer['version'];
        $zf2_metapackage[$composer['version']]['dist']['url'] = sprintf($zf2_metapackage[$composer['version']]['dist']['url'], $composer['version'], $composer['version']);
        $zf2_metapackage[$composer['version']]['dist']['reference'] .= $composer['version'];
        $zf2_release_filename = sprintf(ROOT . '/public/releases/ZendFramework-%s/ZendFramework-%s.zip', $composer['version'], $composer['version']);
        if (file_exists($zf2_release_filename)) {
            $zf2_metapackage[$composer['version']]['dist']['shasum'] = sha1_file($zf2_release_filename);
        } else {
            $zf2_release_filename = sprintf(ROOT . '/packages/plain/ZendFramework-%s.zip', $composer['version']);
            if (file_exists($zf2_release_filename)) {
                $zf2_metapackage[$composer['version']]['dist']['shasum'] = sha1_file($zf2_release_filename);
            }
        }
    }
}

$packages['zendframework/zendframework'] = $zf2_metapackage;
$packages = array('packages' => $packages);

file_put_contents(ROOT . '/public/packages.json', json_encode($packages, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
