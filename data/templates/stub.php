{PACKAGE_REQUIRE_DEPENDENCIES}
if (version_compare(phpversion(), '5.3.1', '<')) {
    if (substr(phpversion(), 0, 5) != '5.3.1') {
        echo "{PACKAGE_NAME} requires PHP 5.3.1 or newer.\n";
        exit -1;
    }
}

foreach (array('phar', 'spl', 'pcre', 'simplexml') as $ext) {
    if (!extension_loaded($ext)) {
        echo 'Extension ', $ext, " is required\n";
        exit -1;
    }
}

try {
    Phar::mapPhar();
} catch (Exception $e) {
    echo "Cannot process {PACKAGE_NAME} phar:\n";
    echo $e->getMessage(), "\n";
    exit -1;
}

spl_autoload_register(function ($class) {
    $class = str_replace(array('_', '\\'), '/', $class);
    if (file_exists('phar://' . __FILE__ . '/{PACKAGE_NAME}-@PACKAGE_VERSION@/php/' . $class . '.php')) {
        include 'phar://' . __FILE__ . '/{PACKAGE_NAME}-@PACKAGE_VERSION@/php/' . $class . '.php';
    }
});

$phar = new Phar(__FILE__);
$sig = $phar->getSignature();
define('{PACKAGE_NAME}_SIG', $sig['hash']);
define('{PACKAGE_NAME}_SIGTYPE', $sig['hash_type']);

__HALT_COMPILER();