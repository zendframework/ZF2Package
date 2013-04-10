{PACKAGE_REQUIRE_DEPENDENCIES}

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

__HALT_COMPILER();