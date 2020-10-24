<?php

class auth_status
{
    public static function render($el)
    {
        php_logger::log("CALL");
        // $id = $el->getAttribute("id");
        // if (!$id) $id = 'content';

        return xml_serve::xml_content("<h3>ADJUNCT</h3>");
    }
}
