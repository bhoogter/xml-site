<?php

function options_xml_file()
{
    $f = xml_site::$resource_folder . "/data/options.xml";
    if (!file_exists($f)) file_put_contents($f, "<?xml version='1.0' ?>\n<options />");
    return $f;
}
