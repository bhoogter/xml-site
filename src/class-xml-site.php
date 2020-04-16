<?php

class xml_site extends source
{
    public $server;
    public $resource_folder;

    public function __construct()
    {
        $n = func_num_args();
        $a = func_get_args();
        php_logger::log("CONSTRUCT", $n, $a);

        if ($n >= 1) {
            php_logger::log("Initializing resources: {$a[0]}, {$a[1]}");
            resource_resolver::instance()->init($a[0]);  // 2nd, http-root
        }
        $this->resource_folder = $n >= 1 ? $a[0] : null;
        php_logger::alert("resource_folder=$resource_folder");
        $this->init_source();
        $this->server = new xml_serve($this->get_source("PAGES"), $this->resource_folder);
    }

    public function resolve_file($res, $scn = [], $ext = [], $sub = ['.', '*']) {
        php_logger::log("CALL res=$res");
        return resource_resolver::instance()->resolve_file($res, $scn, $ext, $sub);
    }

    public function resolve_ref($res, $scn = [], $ext = [], $sub = ['.', '*']) {
        php_logger::log("CALL res=$res");
        return resource_resolver::instance()->resolve_ref($res, $scn, $ext, $sub);
    }

    protected function init_source()
    {
        php_logger::log("CALL");
        $this->add_source("SITE", $this->resource_folder . '/site.xml');
        $this->add_source("PAGES", $this->resource_folder . '/pages.xml');
        
        $modules = new xml_file();
        $modules->merge($this->resource_folder . "/content/modules/*/module.xml", "modules", "module", realpath($this->resource_folder . "/content/generated/modules.xml"));
        $this->add_source("MODULES", $modules);
        $this->read_modules();
        $this->include_startup_files();
    }

    protected function read_modules()
    {
        $modules = $this->nds("//MODULES/modules/module");
        foreach ($modules as $m) {
            $module = xml_file::nodeXmlFile($m);
            $elements = $module->nds("/specification/components/element");
            foreach ($elements as $e) {
                $name = $e->getAttribute("name");
                $render = $e->getAttribute("render");
                $src = $e->getAttribute("src");
                page_render::add_handler($render, $src);
            }
        }
    }

    function include_startup_files() {
        return $this->include_support_files('', '', 'startup', '');
    }

    function include_support_files($module = '', $type = 'php', $mode = '', $file_id = "")
    {
        php_logger::debug("CALL ($module, $type, $mode, $file_id)");
        $p = "//MODULES/modules/module/file";
        if ($file_id && $file_id != "") $p .= "[@id='$file_id']";
        if ($module && $module != "") $p .= "[@module='$module']";
        if ($type && $type != "")   $p .= "[@type='$type']";
        if ($mode && $mode != "")   $p .= "[@mode='$mode']";
        $files = $this->nds($p);

        foreach($files as $ff) {
            $src = $ff->getAttribute("src");
            $module = $ff->parentNode->getAttribute("name");
            php_logger::alert("RR: ", resource_resolver::instance());
            $f = $this->resolve_file($src, "module", $module);
            if ($f == "") throw new Exception("Could not find file for module.  Module=$module, src=$src");
            print "\n=========\nsrc=$src, module=$module, f=$f\n";
            switch($type) {
                case 'css': break;
                case 'js': break;
                default:
                    php_logger::debug("SUPPORT FILE: including PHP file: $f");
                    include_once($f);
                    break;
            }
        }
    }

    function get_styles()
    {
        $s = '<list>';
        foreach ($this->nodes("//SYS/*/file[@type='css']") as $n) {
            $f = $n->getAttribute('src');
            $m = $n->getAttribute('module');
            $s .= "<link type='text/css' rel='stylesheet' href='" . ExtendURL(juniper_module_url("$m/$f"), '', true) . "' />\n";
        }
        $s .= "</list>";
        //print $s;
        return xml_file::XMLToDoc($s);
    }

    public function render($index)
    {
        $page = $this->server->get_page($index);
        print $page;
        return $page;
    }
}
