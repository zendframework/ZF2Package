<?php

return function($class) {
    static $classmap = null;
    if ($classmap === null) {
        $classmap = include __DIR__ . '/autoload_classmap.php';
    }
    if (!isset($classmap[$class])) {
        return false;
    }
    return include $classmap[$class];
}