<?php

// if (
//     false !== strpos($_SERVER['REQUEST_URI'], 'refresh') &&
//     false !== strpos($_SERVER['REQUEST_URI'], 'post') &&
//     true
// )
 {
    // require_once("phar://" . realpath(__DIR__ . "/phars/bhoogter-php-logger-1-0-0.phar") . "/src/logger-stub.php");
}

$start = microtime(true);

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (false != ($k = strpos($url, '.phar/'))) {
    $file = $_SERVER['DOCUMENT_ROOT'] . $url;
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $ct = content_type($ext);
    header("Content-Type: $ct");
    readfile("phar://$file");
    die();
}


$init = microtime(true);
require_once(__DIR__ . "/load.php");
$init_end = microtime(true);

// php_logger::$timestamp = true;
php_logger::$nanos = true;


// php_logger::clear_log_levels('warning');
// php_logger::set_log_level('xml_site', 'all');
// php_logger::set_log_level('xml_serve', 'all');
// php_logger::set_log_level('resource_resolver', 'trace');
// php_logger::set_log_level('adjunct', 'all');
// php_logger::set_log_level('page_source', 'all');
// php_logger::set_log_level('render_content', 'trace');
// php_logger::set_log_level('render_perfect', 'trace');
php_logger::set_log_level('zobject', 'trace');
// php_logger::set_log_level('zobject_access', 'trace');
// php_logger::set_log_level('zobject_autotemplate', 'all');
php_logger::set_log_level('zobject_element', 'trace');
php_logger::set_log_level('zobject_query', 'trace');
// php_logger::set_log_level('zobject_query::load_result', 'dump');
// php_logger::set_log_level('zobject_query::GetZObjectCreateQuery', 'all');
php_logger::set_log_level('options_api', 'all');
// php_logger::set_log_level('xml_serve', 'all');
php_logger::set_log_level('xml_file', 'all');


// php_logger::clear_log_levels('none');
// php_logger::$disable = true;
// php_logger::$suppess_output = false;

$result = xml_serve::get_page($url);
print $result;
// die();

if (false !== strpos($result, "DOCTYPE")) {
    timeout($init, $init_end, 'init');
    timeout($start, 0, 'total');
    memout();
}


function timeout($x, $n, $id)
{
    if (!$n) $n = microtime(true);
    $v = $n - $x;
    print "<h4>Elapsed time [" . strtoupper($id) . "]: $v</h4>";
}

function memout()
{
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

function content_type($ext)
{
    switch(strtolower($ext))
    {
        case 'jpg': $r = "image/jpg"; break;
        case 'bmp': $r = "image/bmp"; break;
        case 'gif': $r = "image/gif"; break;
        case 'png': $r = "image/png"; break;

        case 'ico': $r = "image/ico"; break;

        case 'txt': $r = "text/plain"; break;

        case 'htm': $r = "text/html"; break;
        case 'html': $r = "text/html"; break;
        case 'xhtml': $r = "text/xhtml"; break;

        case 'css': $r = "text/css"; break;

        case 'js': $r = "text/javascript"; break;

        case 'xml': $r = "text/xml"; break;
        case 'xsl': $r = "text/xml"; break;

        default: $r = "application/octet-stream"; break;
    }
    return $r;
}