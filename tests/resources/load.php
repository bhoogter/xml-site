<?php

require_once(__DIR__ . "/../../src/stub.php");
$path = realpath(__DIR__ . "/content");
// php_logger::clear_log_levels('all');
// php_logger::set_log_level("xml_site", "trace");
// php_logger::set_log_level("resource_resolver", "all");
xml_site::init($path);
