<?php
$x = microtime(true);

require_once(__DIR__ . "/load.php");

// php_logger::clear_log_levels('warning');
php_logger::set_log_level('xml_site', 'all');
// php_logger::set_log_level('xml_serve', 'all');
// php_logger::set_log_level('resource_resolver', 'trace');
// php_logger::set_log_level('adjunct', 'all');
// php_logger::set_log_level('page_source', 'all');
// php_logger::set_log_level('render_content', 'trace');
// php_logger::set_log_level('render_perfect', 'trace');
php_logger::set_log_level('zobject', 'trace');
php_logger::set_log_level('zobject_element', 'trace');
// php_logger::set_log_level('zobject_access', 'trace');
php_logger::set_log_level('zobject_query', 'trace');
php_logger::set_log_level('zobject_autotemplate', 'all');
// php_logger::set_log_level('zobject_query::load_result', 'dump');
// php_logger::set_log_level('zobject_query::GetZObjectCreateQuery', 'all');
php_logger::set_log_level('options_api', 'all');
// php_logger::set_log_level('xml_serve', 'all');

$result = xml_serve::get_page(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
print $result;

if (false !== strpos($result, "DOCTYPE")) {

    $x = microtime(true) - $x;
    print "<h4>Elapsed time: $x</h4>";
    $y = memory_get_usage(true);
    if ($y < 1024) $y = "" . $y . "by";
    else if ($y < 1048576) $y = "" . round($y / 1024, 2) . "kb";
    else $y = "" . round($y / 1048576, 2) . "Mb";
    $z = memory_get_peak_usage(true);
    if ($z < 1024) $z = "" . $z . "by";
    else if ($z < 1048576) $z = "" . round($z / 1024, 2) . "kb";
    else $z = "" . round($z / 1048576, 2) . "Mb";
    print "<h4>Memory used: $y, ($z peak)</h4>";
}