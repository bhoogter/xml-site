<?php

class xml_site 
{
    public static $resource_folder;
    public static $source;

    public static function init()
    {
        $n = func_num_args();
        $a = func_get_args();
        php_logger::log("CONSTRUCT", $n, $a);
        self::$resource_folder = $n >= 1 ? $a[0] : null;
        php_logger::log("FOLDER=" . self::$resource_folder);

        self::init_source();
        xml_serve::init(
            realpath(self::$resource_folder),
            realpath(dirname(self::$resource_folder)),
            self::$source->get_source("PAGES"),
            self::$source->get_source("SITE")
        );
        self::load_modules();
    }

    public static function resolve_files($resource, $types = [], $mappings = [], $subfolders = ['.', '*']) { return xml_serve::resource_resolver()->resolve_files($resource, $types, $mappings, $subfolders); }
    public static function resolve_refs($resource, $types = [], $mappings = [], $subfolders = ['.', '*']) { return xml_serve::resource_resolver()->resolve_refs($resource, $types, $mappings, $subfolders); }
    public static function resolve_file($resource, $types = [], $mappings = [], $subfolders = ['.', '*']) { return xml_serve::resource_resolver()->resolve_file($resource, $types, $mappings, $subfolders); }
    public static function resolve_ref($resource, $types = [], $mappings = [], $subfolders = ['.', '*']) { return xml_serve::resource_resolver()->resolve_ref($resource, $types, $mappings, $subfolders); }
    public static function content_type($filename) { return xml_serve::resource_resolver()->content_type($filename); }

    protected static function init_source()
    {
        php_logger::log("CALL");
        self::$source = new source();
        self::$source->add_source("SITE", self::$resource_folder . '/site.xml');
        self::$source->add_source("PAGES", self::$resource_folder . '/pages.xml');
    }

    protected function load_modules() 
    {
        $modules = new xml_file();
        $f = self::resolve_files("module.xml", [], [], ["modules/*"]);
        php_logger::debug("DETECTED MODULES", $f);
        $modules->merge($f, "modules", "module", realpath(self::$resource_folder . "/content/generated/modules.xml"));

        self::$source->add_source("MODULES", $modules);
        self::read_modules();
        self::include_startup_files();
    }

    protected function read_modules()
    {
        $modules = self::$source->nds("//MODULES/modules/module");
        php_logger::dump("MODULES: ", $modules);
        foreach ($modules as $m) {
            $module = xml_file::toXmlFile($m);
            $elements = $module->nds("/module/specification/components/element");
            foreach ($elements as $e) {
                $name = $e->getAttribute("name");
                $render = $e->getAttribute("render");
                $src = $e->getAttribute("src");
                php_logger::trace("ADD HANDLER", $name, $render, $src);
                xml_serve::add_handler($name, "$render::render");
            }
        }
    }

    function include_startup_files()
    {
        return self::include_support_files('', '', 'startup', '');
    }

    function include_support_files($module = '', $type = 'php', $mode = '', $file_id = "")
    {
        php_logger::debug("CALL ($module, $type, $mode, $file_id)");
        $p = "//MODULES/modules/module/file";
        if ($file_id && $file_id != "") $p .= "[@id='$file_id']";
        if ($module && $module != "") $p .= "[@module='$module']";
        if ($type && $type != "")   $p .= "[@type='$type']";
        if ($mode && $mode != "")   $p .= "[@mode='$mode']";
        $files = self::$source->nds($p);
        php_logger::dump("support files: ", $files);

        foreach ($files as $ff) {
            $src = $ff->getAttribute("src");
            $module = $ff->parentNode->getAttribute("name");
            $f = self::resolve_file($src, "module", $module);
            if ($f == "") throw new Exception("Could not find file for module.  Module=$module, src=$src");
            // print "\n=========\nsrc=$src, module=$module, f=$f\n";
            switch ($type) {
                case 'css': break;
                case 'js':break;
                default:
                    php_logger::debug("SUPPORT FILE: $f");
                    include_once($f);
                    break;
            }
        }
    }

    function get_styles()
    {
        $s = '<list>';
        foreach (self::$source->nds("//SYS/*/file[@type='css']") as $n) {
            $f = $n->getAttribute('src');
            $m = $n->getAttribute('module');
            $s .= "<link type='text/css' rel='stylesheet' href='" . ExtendURL(juniper_module_url("$m/$f"), '', true) . "' />\n";
        }
        $s .= "</list>";
        return xml_file::XMLToDoc($s);
    }
}
