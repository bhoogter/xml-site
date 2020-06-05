<?php

class xml_site 
{
    public static $resource_folder;
    public static $http_root;
    public static $source;
    public static $ajax = "";

    public static function init()
    {
        $n = func_num_args();
        $a = func_get_args();
        php_logger::log("CONSTRUCT", $n, $a);
        self::$resource_folder = $n >= 1 ? realpath($a[0]) : null;
        self::$http_root = $n >= 1 ? realpath(dirname($a[0])) : null;
        php_logger::log("FOLDER=" . self::$resource_folder);

        self::init_source();
        xml_serve::init(
            self::$resource_folder,
            self::$http_root,
            self::$source->get_source("PAGES"),
            self::$source->get_source("SITE")
        );
        self::load_modules();
    }

    public static function resolve_files($resource, $types = [], $mappings = [], $subfolders = []) { return xml_serve::resource_resolver()->resolve_files($resource, $types, $mappings, $subfolders); }
    public static function resolve_refs($resource, $types = [], $mappings = [], $subfolders = []) { return xml_serve::resource_resolver()->resolve_refs($resource, $types, $mappings, $subfolders); }
    public static function resolve_file($resource, $types = [], $mappings = [], $subfolders = []) { return xml_serve::resource_resolver()->resolve_file($resource, $types, $mappings, $subfolders); }
    public static function resolve_ref($resource, $types = [], $mappings = [], $subfolders = []) { return xml_serve::resource_resolver()->resolve_ref($resource, $types, $mappings, $subfolders); }
    public static function content_type($filename) { return xml_serve::resource_resolver()->content_type($filename); }

    protected static function init_source()
    {
        php_logger::call();
        self::$source = new source();
        self::$source->add_source("SITE", self::$resource_folder . '/site.xml');
        self::$source->add_source("PAGES", self::$resource_folder . '/pages.xml');
        self::$source->autosave = true;
    }

    protected static function load_modules() 
    {
        php_logger::call();
        $modules = new xml_file();
        $f = glob(self::$resource_folder . "/modules/*/module.xml");
        php_logger::debug("DETECTED MODULES", $f);
        $modules->merge($f, "modules", "module", realpath(self::$resource_folder . "/content/generated/modules.xml"));

        self::$source->add_source("MODULES", $modules);
        self::read_modules();
        self::include_startup_files();
    }
    
    protected static function read_modules()
    {
        php_logger::call();
        $modules = self::$source->nds("//MODULES/modules/module");
        php_logger::dump("MODULES: ", $modules);
        foreach ($modules as $m) {
            $module = xml_file::toXmlFile($m);
            self::load_datasources($module);
            self::load_element_handlers($module);
            self::load_extensions($module);
        }
    }

    protected static function load_datasources($module) 
    {
        $sources = $module->nds("/module/datasource");
        foreach ($sources as $e) {
            $name = $e->getAttribute("name");
            $type = $e->getAttribute("type");
            $src = $e->getAttribute("src");
            
            $file = self::$resource_folder . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . $src;
            php_logger::trace("ADD DATASOURCE", $name, $type, $src, "file=$file");
            self::$source->add_source($name, $file);
        }
    }

    protected static function load_element_handlers($module) 
    {
        $elements = $module->nds("/module/specification/components/element");
        foreach ($elements as $e) {
            $name = $e->getAttribute("name");
            $render = $e->getAttribute("render");
            $src = $e->getAttribute("src");
            php_logger::trace("ADD HANDLER", $name, $render, $src);

            if (is_callable($render)) 
                xml_serve::add_handler($name, $render);
            else
                xml_serve::add_handler($name, "$render::render");
        }
    }

    protected static function load_extensions($module) 
    {
        xml_path_handlers::add("/ajax/handler/{elementName}", "GET", "xml_site::handle_element");

        $path_handlers = $module->nds("/module/api");
        foreach ($path_handlers as $e) {
            $type = $e->getAttribute("type");
            $loca = $e->getAttribute("loc");
            $meth = $e->getAttribute("method");
            $targ = $e->getAttribute("target");


            php_logger::trace("ADD PATH EXTENSION", $type, $loca, $meth, $targ);
            xml_path_handlers::add($loca, $meth, $targ);
        }

        $extensions = $module->nds("/module/specification/program/extension");
        foreach ($extensions as $e) {
            $name = $e->getAttribute("name");
            if (!$name) $name = $module->get("/module/@name");
            $type = $e->getAttribute("type");
            $psrc = $e->getAttribute("src");
            if (php_hook::is_hook($psrc)) $psrc = php_hook::get_callable($psrc);

            php_logger::trace("ADD MODULE EXTENSION", $name, $type, $psrc);
            // die();
            xml_serve_extensions::add_extension_handler($name, $type, $psrc);
        }
    }

    static function handle_element($params, $method, $path) {
        php_logger::$log_file = __DIR__ . "/handle_element.log";
        php_logger::clear_log_levels();
        php_logger::call();
        $n = $params['elementName'];
        return xml_file::toXmlFile(xml_serve::handle_element($n, xml_file::toDoc("<$n />")->documentElement))->saveXML();
    }

    static function include_startup_files()
    {
        return self::include_support_files('', '', 'startup', '');
    }

    static function include_support_files($module = '', $type = 'php', $mode = '', $file_id = "")
    {
        php_logger::call();
        $p = "//MODULES/modules/module/file";
        if ($file_id && $file_id != "") $p .= "[@id='$file_id']";
        if ($module && $module != "") $p .= "[@module='$module']";
        if ($type && $type != "")   $p .= "[@type='$type']";
        if ($mode && $mode != "")   $p .= "[@mode='$mode']";
        php_logger::trace("support files path: ", $p);
        $files = self::$source->nds($p);
        php_logger::dump("support files: ", $files);

        foreach ($files as $ff) {
            $src = $ff->getAttribute("src");
            $module = $ff->parentNode->getAttribute("name");
            $fType = $ff->getAttribute('type');
            if ($fType == '') $fType = pathinfo($src, PATHINFO_EXTENSION);
            if ($fType == '') $fType = $type;
            $f = self::resolve_file($src, "module", $module);
            if ($f == "") throw new Exception("Could not find file for module.  Module=$module, src=$src");
            php_logger::scan("src=$src, module=$module, type=$fType, f=$f");
            switch ($fType) {
                case 'css':  case 'js':
                    $r = self::resolve_ref($src, 'module', $module);
                    php_logger::debug("SUPPORT FILE " . strtoupper($fType) . ": $r ($f)");
                    if (file_exists(realpath(self::$http_root . $r))) {
                        if ($fType == 'css') xml_serve::$additional_css[] = $r;
                        if ($fType == 'js')  xml_serve::$additional_scripts[] = $r;
                    } else {
                        php_logger::warn(strtoupper($fType) . " Not found: $r");
                    }
                    break;
                default:
                    php_logger::debug("SUPPORT FILE: $f");
                    include_once($f);
                    break;
            }
        }
    }
}
