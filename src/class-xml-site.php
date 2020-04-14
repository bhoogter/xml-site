<?php

class xml_site
{
    private $source;
    private $server;

    public function __construct()
    {
        $n = func_num_args();
        $a = func_get_args();

        $this->source = new xml_source();

        $pages_source = null;
        if ($n >= 1) {
            if (is_object($a[0])) $pages_source = $a[0];
            else $pages_source = new xml_file($a[0]);
        }
        $resource_folder = $n >= 2 ? $a[1] : null;
        $this->server = new xml_serve($pages_source, $resource_folder);
    }
}
