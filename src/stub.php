<?php
// Place the dependency manager phar in the same directory ()
if (strpos(__FILE__, ".phar") === false) {
    define('DEPENDENCY_MANAGER_PHAR', __DIR__ . "/phars/php-dependency-manager.phar");
    require_once("phar://" . DEPENDENCY_MANAGER_PHAR . "/src/class-dependency-manager.php");
    dependency_manager("source", __DIR__ . "/dependencies.xml", __DIR__ . "/phars/");
}

spl_autoload_register(function ($name) {
    $d = (strpos(__FILE__, ".phar") === false ? __DIR__ : "phar://" . __FILE__ . "/src");
    if ($name == "xml_site") require_once($d . "/class-xml-site.php");
});

__HALT_COMPILER();
