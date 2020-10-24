<?php

function auth_login_page($a, $b, $c)
    {
    $def  = "<pagedef text='Site Login' template='blank'>\n";
    $def .= "  <content id='content' type='element' element-name='zobject' name='auth-login' />\n";
    // $def .= "  <content id='content' src='x-out.html' />\n";
    $def .= "</pagedef>\n";
    // print_r($def);
    // php_logger::set_log_level("resource_resolver", "debug");
    $page = xml_serve::make_page(xml_file::toXmlFile($def));
    return xml_serve::finalize_page(xml_file::toXml($page));
}

function auth_page($path = null, $location = null)
{
    php_logger::call();
    if ($path == 'login') return auth_login_page();
}
