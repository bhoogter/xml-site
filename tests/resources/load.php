<?php

require_once(__DIR__ . "/../../src/stub.php");
$path = realpath(__DIR__ . "/content");
xml_site::init($path);
xml_serve::$doc_type = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
