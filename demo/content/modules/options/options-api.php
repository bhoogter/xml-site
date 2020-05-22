<?php

class options_api
{
    static function get_option_by_id($vars, $method, $path)
    {
        // php_logger::call();
        php_logger::clear_log_levels();
        $x = zobject::query('options', ['userid' => '3']);
        return xml_file::toXmlFile($x)->saveJson("recordset");
    }
    
    static function post_option_by_id()
    {
        return zobject::save_json("option");
    }
    
    static function ajax_get_option() {
        php_logger::clear_log_levels();
        $result = zobject::render_object("options");
        if ($result != null) $result = $result->saveXML();
        return $result;
    }

    static function ajax_post_option() { return zobject::save("option"); }
}
