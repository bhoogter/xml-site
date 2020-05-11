<?php

class xml_serve_extensions
{
    protected static $extension_folder;

    public static function init($fld)
    {
        if (substr($fld, -1) == '/') $fld = substr($fld, 0, strlen($fld) - 1);
        self::$extension_folder = $fld;
        self::load_extensions();
    }

    public static function extenion_list()
    {
        $src = [];
        $src += glob(self::$extension_folder . "/*/module.xml");
        // $src += glob(self::$extension_folder . "/*.phar");
        return $src;
    }

    public static function extension_type($ext)
    {
        if (is_dir(self::$extension_folder . "/$ext")) return "SRC";
        if (file_exists(self::$extension_folder . "/$ext.phar")) return "PHAR";
        return null;
    }

    public static function load_extensions($source)
    {
        $ext = new xml_file();
        // merge($scan, $root = null, $item = null, $persist = null)
        $ext->merge(self::extension_list(), "modules", "module");
        return $ext;
    }
}
