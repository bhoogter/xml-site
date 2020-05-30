<?php

$x = [];

define('DEPENDENCY_MANAGER_PHAR', __DIR__ . "/phars/php-dependency-manager.phar");
require_once("phar://" . DEPENDENCY_MANAGER_PHAR . "/src/class-dependency-manager.php");
// dependency_manager::$log_dump = true;


define('XML_SITE_CLASS', __DIR__ . "/../src/class-xml-site.php");
define('XML_SITE_DPXML', __DIR__ . "/../src/dependencies.xml");

$x[] = microtime(true);

dependency_manager(
    [XML_SITE_DPXML, __DIR__ . "/dependencies.xml"], 
    [ '' => __DIR__ . "/phars/", 'resource' => __DIR__ . "/ext"]
);
$x[] = microtime(true);
require_once(XML_SITE_CLASS);
$x[] = microtime(true);

$path = realpath(__DIR__ . "/content");

xml_site::init($path);
$x[] = microtime(true);
xml_serve::$doc_type = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';

// for($i = 0; $i < count($x) - 1; $i++) print "<h4>loader: " . ($x[$i + 1] - $x[$i]) . "</h4>";