<?php
if ($argc != 3) {
    printf("[%s] Invalid arguments (requires 2, received %d)\n", $argv[0], $argc);
    printf("Usage:\n    %s [package] [filter|composer|pyrus]\n", $argv[0]);
    exit(1);
}
$package = $argv[1];
$type    = $argv[2];

$package = strtolower($package);
if (0 === strpos($package, 'zendservice')) {
    $package = str_replace('_', '', $package);
}

$components = [
    'zend_authentication' => [
        'pyrus' => 'zend_authentication',
        'filter' => 'library/Zend/Authentication',
        'composer' => 'zendframework/zend-authentication',
    ],
    'zend_barcode' => [
        'pyrus' => 'zend_barcode',
        'filter' => 'library/Zend/Barcode',
        'composer' => 'zendframework/zend-barcode',
    ],
    'zend_cache' => [
        'pyrus' => 'zend_cache',
        'filter' => 'library/Zend/Cache',
        'composer' => 'zendframework/zend-cache',
    ],
    'zend_captcha' => [
        'pyrus' => 'zend_captcha',
        'filter' => 'library/Zend/Captcha',
        'composer' => 'zendframework/zend-captcha',
    ],
    'zend_code' => [
        'pyrus' => 'zend_code',
        'filter' => 'library/Zend/Code',
        'composer' => 'zendframework/zend-code',
    ],
    'zend_config' => [
        'pyrus' => 'zend_config',
        'filter' => 'library/Zend/Config',
        'composer' => 'zendframework/zend-config',
    ],
    'zend_console' => [
        'pyrus' => 'zend_console',
        'filter' => 'library/Zend/Console',
        'composer' => 'zendframework/zend-console',
    ],
    'zend_crypt' => [
        'pyrus' => 'zend_crypt',
        'filter' => 'library/Zend/Crypt',
        'composer' => 'zendframework/zend-crypt',
    ],
    'zend_db' => [
        'pyrus' => 'zend_db',
        'filter' => 'library/Zend/Db',
        'composer' => 'zendframework/zend-db',
    ],
    'zend_debug' => [
        'pyrus' => 'zend_debug',
        'filter' => 'library/Zend/Debug',
        'composer' => 'zendframework/zend-debug',
    ],
    'zend_di' => [
        'pyrus' => 'zend_di',
        'filter' => 'library/Zend/Di',
        'composer' => 'zendframework/zend-di',
    ],
    'zend_dom' => [
        'pyrus' => 'zend_dom',
        'filter' => 'library/Zend/Dom',
        'composer' => 'zendframework/zend-dom',
    ],
    'zend_escaper' => [
        'pyrus' => 'zend_escaper',
        'filter' => 'library/Zend/Escaper',
        'composer' => 'zendframework/zend-escaper',
    ],
    'zend_eventmanager' => [
        'pyrus' => 'zend_eventmanager',
        'filter' => 'library/Zend/EventManager',
        'composer' => 'zendframework/zend-eventmanager',
    ],
    'zend_feed' => [
        'pyrus' => 'zend_feed',
        'filter' => 'library/Zend/Feed',
        'composer' => 'zendframework/zend-feed',
    ],
    'zend_file' => [
        'pyrus' => 'zend_file',
        'filter' => 'library/Zend/File',
        'composer' => 'zendframework/zend-file',
    ],
    'zend_filter' => [
        'pyrus' => 'zend_filter',
        'filter' => 'library/Zend/Filter',
        'composer' => 'zendframework/zend-filter',
    ],
    'zend_form' => [
        'pyrus' => 'zend_form',
        'filter' => 'library/Zend/Form',
        'composer' => 'zendframework/zend-form',
    ],
    'zend_http' => [
        'pyrus' => 'zend_http',
        'filter' => 'library/Zend/Http',
        'composer' => 'zendframework/zend-http',
    ],
    'zend_i18n' => [
        'pyrus' => 'zend_i18n',
        'filter' => 'library/Zend/I18n',
        'composer' => 'zendframework/zend-i18n',
    ],
    'zend_inputfilter' => [
        'pyrus' => 'zend_inputfilter',
        'filter' => 'library/Zend/InputFilter',
        'composer' => 'zendframework/zend-inputfilter',
    ],
    'zend_json' => [
        'pyrus' => 'zend_json',
        'filter' => 'library/Zend/Json',
        'composer' => 'zendframework/zend-json',
    ],
    'zend_ldap' => [
        'pyrus' => 'zend_ldap',
        'filter' => 'library/Zend/Ldap',
        'composer' => 'zendframework/zend-ldap',
    ],
    'zend_loader' => [
        'pyrus' => 'zend_loader',
        'filter' => 'library/Zend/Loader',
        'composer' => 'zendframework/zend-loader',
    ],
    'zend_log' => [
        'pyrus' => 'zend_log',
        'filter' => 'library/Zend/Log',
        'composer' => 'zendframework/zend-log',
    ],
    'zend_mail' => [
        'pyrus' => 'zend_mail',
        'filter' => 'library/Zend/Mail',
        'composer' => 'zendframework/zend-mail',
    ],
    'zend_math' => [
        'pyrus' => 'zend_math',
        'filter' => 'library/Zend/Math',
        'composer' => 'zendframework/zend-math',
    ],
    'zend_memory' => [
        'pyrus' => 'zend_memory',
        'filter' => 'library/Zend/Memory',
        'composer' => 'zendframework/zend-memory',
    ],
    'zend_mime' => [
        'pyrus' => 'zend_mime',
        'filter' => 'library/Zend/Mime',
        'composer' => 'zendframework/zend-mime',
    ],
    'zend_modulemanager' => [
        'pyrus' => 'zend_modulemanager',
        'filter' => 'library/Zend/ModuleManager',
        'composer' => 'zendframework/zend-modulemanager',
    ],
    'zend_mvc' => [
        'pyrus' => 'zend_mvc',
        'filter' => 'library/Zend/Mvc',
        'composer' => 'zendframework/zend-mvc',
    ],
    'zend_navigation' => [
        'pyrus' => 'zend_navigation',
        'filter' => 'library/Zend/Navigation',
        'composer' => 'zendframework/zend-navigation',
    ],
    'zend_paginator' => [
        'pyrus' => 'zend_paginator',
        'filter' => 'library/Zend/Paginator',
        'composer' => 'zendframework/zend-paginator',
    ],
    'zend_permissions_acl' => [
        'pyrus' => 'zend_permissions_acl',
        'filter' => 'library/Zend/Permissions/Acl',
        'composer' => 'zendframework/zend-permissions-acl',
    ],
    'zend_permissions_rbac' => [
        'pyrus' => 'zend_permissions_rbac',
        'filter' => 'library/Zend/Permissions/Rbac',
        'composer' => 'zendframework/zend-permissions-rbac',
    ],
    'zend_progressbar' => [
        'pyrus' => 'zend_progressbar',
        'filter' => 'library/Zend/ProgressBar',
        'composer' => 'zendframework/zend-progressbar',
    ],
    'zend_serializer' => [
        'pyrus' => 'zend_serializer',
        'filter' => 'library/Zend/Serializer',
        'composer' => 'zendframework/zend-serializer',
    ],
    'zend_server' => [
        'pyrus' => 'zend_server',
        'filter' => 'library/Zend/Server',
        'composer' => 'zendframework/zend-server',
    ],
    'zend_servicemanager' => [
        'pyrus' => 'zend_servicemanager',
        'filter' => 'library/Zend/ServiceManager',
        'composer' => 'zendframework/zend-servicemanager',
    ],
    'zend_session' => [
        'pyrus' => 'zend_session',
        'filter' => 'library/Zend/Session',
        'composer' => 'zendframework/zend-session',
    ],
    'zend_soap' => [
        'pyrus' => 'zend_soap',
        'filter' => 'library/Zend/Soap',
        'composer' => 'zendframework/zend-soap',
    ],
    'zend_stdlib' => [
        'pyrus' => 'zend_stdlib',
        'filter' => 'library/Zend/Stdlib',
        'composer' => 'zendframework/zend-stdlib',
    ],
    'zend_tag' => [
        'pyrus' => 'zend_tag',
        'filter' => 'library/Zend/Tag',
        'composer' => 'zendframework/zend-tag',
    ],
    'zend_text' => [
        'pyrus' => 'zend_text',
        'filter' => 'library/Zend/Text',
        'composer' => 'zendframework/zend-text',
    ],
    'zend_uri' => [
        'pyrus' => 'zend_uri',
        'filter' => 'library/Zend/Uri',
        'composer' => 'zendframework/zend-uri',
    ],
    'zend_validator' => [
        'pyrus' => 'zend_validator',
        'filter' => 'library/Zend/Validator',
        'composer' => 'zendframework/zend-validator',
    ],
    'zend_version' => [
        'pyrus' => 'zend_version',
        'filter' => 'library/Zend/Version',
        'composer' => 'zendframework/zend-version',
    ],
    'zend_view' => [
        'pyrus' => 'zend_view',
        'filter' => 'library/Zend/View',
        'composer' => 'zendframework/zend-view',
    ],
    'zend_xmlrpc' => [
        'pyrus' => 'zend_xmlrpc',
        'filter' => 'library/Zend/XmlRpc',
        'composer' => 'zendframework/zend-xmlrpc',
    ],

    'zendcloud' => [
        'pyrus' => 'zendcloud',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendcloud',
    ],
    'zendgdata' => [
        'pyrus' => 'zendgdata',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendgdata',
    ],
    'zendoauth' => [
        'pyrus' => 'zendoauth',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendoauth',
    ],
    'zendopenid' => [
        'pyrus' => 'zendopenid',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendopenid',
    ],
    'zendpdf' => [
        'pyrus' => 'zendpdf',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendpdf',
    ],
    'zendqueue' => [
        'pyrus' => 'zendqueue',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zend-queue',
    ],
    'zendrest' => [
        'pyrus' => 'zendrest',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendrest',
    ],
    'zendserviceagilezen' => [
        'pyrus' => 'zendserviceagilezen',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-agilezen',
    ],
    'zendserviceakismet' => [
        'pyrus' => 'zendserviceakismet',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-akismet',
    ],
    'zendserviceamazon' => [
        'pyrus' => 'zendserviceamazon',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-amazon',
    ],
    'zendserviceappleapns' => [
        'pyrus' => 'zendserviceappleapns',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-apple-apns',
    ],
    'zendserviceaudioscrobbler' => [
        'pyrus' => 'zendserviceaudioscrobbler',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-audioscrobbler',
    ],
    'zendservicedelicious' => [
        'pyrus' => 'zendservicedelicious',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-delicious',
    ],
    'zendserviceflickr' => [
        'pyrus' => 'zendserviceflickr',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-flickr',
    ],
    'zendservicegogrid' => [
        'pyrus' => 'zendservicegogrid',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-gogrid',
    ],
    'zendservicegooglegcm' => [
        'pyrus' => 'zendservicegooglegcm',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-google-gcm',
    ],
    'zendservicelivedocx' => [
        'pyrus' => 'zendservicelivedocx',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-livedocx',
    ],
    'zendservicenirvanix' => [
        'pyrus' => 'zendservicenirvanix',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-nirvanix',
    ],
    'zendservicerackspace' => [
        'pyrus' => 'zendservicerackspace',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-rackspace',
    ],
    'zendservicerecaptcha' => [
        'pyrus' => 'zendservicerecaptcha',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-recaptcha',
    ],
    'zendserviceslideshare' => [
        'pyrus' => 'zendserviceslideshare',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-slideshare',
    ],
    'zendservicestrikeiron' => [
        'pyrus' => 'zendservicestrikeiron',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-strikeiron',
    ],
    'zendservicetechnorati' => [
        'pyrus' => 'zendservicetechnorati',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-technorati',
    ],
    'zendservicetwitter' => [
        'pyrus' => 'zendservicetwitter',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-twitter',
    ],
    'zendservicewindowsazure' => [
        'pyrus' => 'zendservicewindowsazure',
        'filter' => 'composer.json library/',
        'composer' => 'zendframework/zendservice-windowsazure',
    ],
];

if (!isset($components[$package])) {
    file_put_contents('php://stdout', '');
    exit(0);
}
$return = $components[$package][$type];
$return = trim($return, '/');
file_put_contents('php://stdout', $return);
exit(0);
