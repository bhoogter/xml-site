<?php

class zobject
{
    public const ZP_PAGE = 'p';
    public const ZP_PAGECOUNT = 'pp';
    public const LOG_DIR = __DIR__ . "/../../../logs";

    private static $iOBJs = [];
    private static $keys = [];

    static function DEBUG_TRANSFORM() { return ""; }
    static function DEBUG_TRANSFORM_ROW() { return ""; }
    static function DEBUG_TRANSFORM_FIELD() { return ""; }
    static function DEBUG_TRANSFORM_DATA_FIELD() { return ""; }
    static function DEBUG_TRANSFORM_DATA_INPUT() { return ""; }

    static function BENCHMARK_TRANSFORM() { return ""; }
    static function BENCHMARK_ROWS() { return ""; }

    static function render($el, $params = [], $vArgs = "")
    {
        php_logger::call();
        if (!is_object($el) || (!is_a($el, "DOMElement") && !is_a($el, "DOMDocument")))
            throw new Exception("Bad argument 1 to zobject::render.  Expected DOMElement.  Got: ".print_r($el, true));
        if (!is_array($params)) 
            throw new Exception("Bad argument 2 to zobject::render.  Expected array.  Got: ".print_r($params, true));

        if (!array_key_exists('name', $params)) {
            $tName = $el->getAttribute('name');
            if (is_string($tName)) $params['name'] = $tName;
        } 
        if (!array_key_exists('name', $params)) throw new Exception("No 'name' found in parameters.");

        if (!array_key_exists('mode', $params)) {
            $tName = $el->getAttribute('mode');
            if (is_string($tName)) $params['mode'] = $tName;
        }
        
        if (!$vArgs) {
            $tName = $el->getAttribute('args');
            if (is_string($tName)) $vArgs = $tName;
        }

        if (is_array($vArgs)) $vArgs = http_build_query($vArgs);

        require_once('zobject-element.php');
        return (new zobject_element())->render($params, $vArgs);
    }

    protected static function qs($k) {
        return querystring::get(@$_SERVER['QUERY_STRING'], $k);
    }

    protected static function set_log_file($n = 'api', $level = 'trace', $suppress = true) {
        php_logger::$log_folder = self::LOG_DIR;
        php_logger::$log_file = "$n.log";
        php_logger::clear_log_levels($level);
        php_logger::$suppress_output = $suppress;
        php_logger::$timestamp = true;
        php_logger::$nanos = true;
    }

    static function render_object($n, $params = [], $vArgs = "") 
    {
        php_logger::call();
        $params['name'] = $n;
        $x = "<?xml version='1.0' ?>\n<$n />";
        return self::render(xml_file::toDocEl($x), $params, $vArgs);
    }

    static function refresh_object() 
    {
        self::set_log_file('refresh', 'log');
        php_logger::call();
        $token = self::qs('token');
        $m = self::qs('mode');
        $args = self::decode_args($token);
        php_logger::debug("ARGS=$args");
        $n = querystring::pop($args, '_ZN');
        $params = [ 'name' => $n ];

        $params['mode'] = !!$m ? $m : querystring::pop($args, '_ZM');
        $params['module'] = querystring::pop($args, '_Zmod');
        $params['prefix'] = querystring::pop($args, '_Zprefix');
        querystring::del($args, '_Ztemp');
        die(xml_file::toXhtml(self::render_object($n, $params, $args)));
    }

    static function query($zname, $vArgs = [])
    {
        self::set_log_file('query', 'none');
        php_logger::call();
        $params['mode'] = 'data';
        $params['name'] = $zname;
        return self::render(xml_file::toDoc("<element />"), $params, $vArgs);
    }

    static function post($zName, $params = [])
    {
        // Turns off all logging for save/redirect.  Comment out the level set to debug save.
        self::set_log_file('post', 'log');
        php_logger::call();
        // php_logger::dump($_POST);
        require_once('zobject-element.php');
        $target =  (new zobject_element())->save($_POST['_ZN'], $_POST['_ZM']);
        php_logger::alert("REDIRECT-TARGET: " . $target);
        xml_serve::redirect($target);
        // died.
    }

    static function get_ajax($a, $b, $c)
    {
        self::set_log_file('ajax', 'none');
        php_logger::clear_log_levels();
        php_logger::call();
        try {
            $object = querystring::get(@$_SERVER['QUERY_STRING'], '_ZN');
            $result = zobject::render_object($object);
           if ($result != null) $result = $result->saveXML();
        } catch(Exception $e) {
            die(header("Status: 400 Bad Request", true, 400));
        }
        return $result;
    }

    static function validate()
    {
        // Turns off all logging for save/redirect.  Comment out the level set to debug save.
        // php_logger::clear_log_levels('none');
        self::set_log_file('validate', 'none');
        php_logger::call();
    }

    static function transform() { return realpath(__DIR__ . "/source/transform.xsl"); }

    static function ObjectList() {return xml_site::$source->lst("//MODULES/modules/module/zobjectdef/@name");}
    static function ModuleList() {return xml_site::$source->lst("//MODULES/modules/module/@name");}

    static function FetchObjFields($n) { return xml_site::$source->lst("//MODULES/modules/module/zobjectdef[@name='$n']/fielddefs/fielddef/@id"); }
    static function FetchObjPart($n, $p) { return xml_site::$source->get("//MODULES/modules/module/zobjectdef[@name='$n']/$p"); }
    static function FetchDTPart($n, $p) { return xml_site::$source->get("//MODULES/modules/module/typedef[@name='$n']/$p"); }
    static function FetchObjDefString($n) { return xml_site::$source->def("//MODULES/modules/module/zobjectdef[@name='$n']"); }
    static function FetchObjFieldDefault($n, $f) { return self::FetchObjFieldPart($n, $f, '@default'); }
    static function FetchObjFieldPart($n, $f, $p) {
        $x = xml_site::$source->get("//MODULES/modules/module/zobjectdef[@name='$n']/fielddefs/fielddef[@id='$f']/$p"); 
        if ($x == '' &&  ($s = self::FetchObjPart($n, '@source')) != '') 
            $x = xml_site::$source->get("//MODULES/modules/module/ztabledef[@name='$s']/fielddefs/fielddef[@id='$f']/$p");
        return $x;
    }
    static function FetchObjFieldCategories($n)
        { 
            php_logger::log($n);
            $lst = array_unique(xml_site::$source->lst("//MODULES/modules/module/zobjectdef[@name='$n']/fieldsdefs/fielddef/@category"));
            $lst += ['general'];
            php_logger::debug("lst: ", $lst);
            return xml_file::toDoc(sizeof($lst) ?
                 "<categories><category>" . join("</category><category>", $lst) . "</category></categories>" :
                 "<categories />");
        }

    static function FetchDSPart($n, $p) { return xml_site::$source->get("//MODULES/modules/module/datasource[@name='$n']/$p"); }
    static function FetchSpecPart($n, $p) { return xml_site::$source->get("//MODULES/modules/module/specification/control[@name='$n']/$p"); }

    static function FetchActPart($n, $p = "") { return xml_site::$source->get("//MODULES/modules/module/zactiondef[@name='$n']".($p==""?"":"/$p")); }
    static function FetchActRulePart($n, $r, $p = "") { return xml_site::$source->get("//MODULES/modules/module/zactiondef[@name='$n']/action[@value='$r']".($p==""?"":"/$p")); }

    static function FetchApiPart($n, $p = "") { return xml_site::$source->get("//MODULES/modules/module/api[@name='$n']".($p==""?"":"/$p")); }

    static function handled_elements() { return xml_serve::handler_list(); }
    static function source_document($n) { php_logger::call();return xml_site::$source->get_source_doc($n); }

    static function jsid($pfx = "js_") { return uniqid($pfx); }
    static function new_jsid($pfx = "js_") { return uniqid($pfx); }

    static function admin() { return ""; }
    static function ajax() { return xml_site::$ajax; }
    static function ajax_url() { return "http://localhost/ajax.php"; }
    static function origin() { return self::encode_args($_SERVER['REQUEST_URI']); }

    static function args_prefix()    { return '@@';        }
    static function encode_args($a)  { return self::args_prefix().base64_encode(str_rot13($a));    }
    static function decode_args($a)
        {
        php_logger::call();
        $p = self::args_prefix();
        $n = strlen($p);

            // this is the only real algorithm... as long as it matches the encode and is reversible, it is fine to change...
//print "<br/>substr($a,0,$n)";
        if (substr($a,0,$n)==$p) return str_rot13(base64_decode(substr($a,$n)));
//print "<br/>;lkj;lj.........";
            // it may have been urlencode'd somewhere...
        $S = urlencode($p);
        $m = strlen($S);
        if (substr($a,0,$m)==$S) return self::decode_args(urldecode($a));
            // otherwise, decoding an unencoded string does nothing!
        return $a;
        }


    static function get_key_value($k) {
        php_logger::call();
        return @self::$keys[$k];
    }
    static function set_key_value($k, $v) {
        php_logger::call();
        return self::$keys[$k] = $v;
    }

    static function KeyValue($k, $Args="", $alt="")
        {
        php_logger::call();
        if ($v = self::get_key_value($k)) return $v;
//        if ($k=='#USERNAME') return GetCurrentUsername();
        $v = @$_REQUEST[$k];
        if ($Args == "" && self::iOBJ()!=null)  {
            $Args = self::iOBJ()->args;
            php_logger::debug("args=$Args");
        }
        if ($v=="" && $Args!="") $v = querystring::get($Args, $k);
        if ($v=="" && self::iOBJ()) $v = self::iOBJ()->arg($k);
        if ($v=="" && self::iOBJ() && method_exists(self::iOBJ(), 'result_field')) $v = self::iOBJ()->result_field($k);
        if ($v=="" && self::iOBJ2()) $v = self::iOBJ2()->arg($k);
        if ($v=="" && self::iOBJ2() && method_exists(self::iOBJ2(), 'result_field')) $v = self::iOBJ2()->result_field($k);        // previous object...  ?
        if ($v=="" && $alt!="") $v=$alt;
        php_logger::result($v);
        return $v;
        }
        
    static function InterpretFields($f, $auto_quote = false, $token = "@")
        {
        php_logger::call();
        
        $l = strlen($token);
        if ($auto_quote)
            $cb = function($matches) use ($l) { return "'".self::KeyValue(substr($matches[0], $l))."'"; };
        else
            $cb = function($matches) use ($l) { return self::KeyValue(substr($matches[0], $l)); };

        $f = preg_replace_callback('/'.$token."[a-zA-Z0-9_]+".'/i', $cb, $f);
        php_logger::result($f);
        return $f;
        }
        
    static function TransformSourceScripts($s)
        {
        php_logger::call();
        static $Cache;
        if (!php_hook::is_hook($s)) return $s;
        if (!$Cache) $Cache = array();
        if ($t=@$Cache[$s]) return $t;
        return $Cache[$s]=php_hook::call($s);        // returned assignment
        }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        
    static function unset_iOBJ($o) {
        foreach(self::$iOBJs as $k=>$v) 
            if ($o == $v) unset(self::$iOBJs[$k]);
    }

    static function set_iOBJ($o) {
        return array_push(self::$iOBJs, $o);
    }

    static function iOBJ($n = 0) {
        return count(self::$iOBJs) <= $n ? null : self::$iOBJs[count(self::$iOBJs) - $n - 1];
    }

    static function iOBJ2() { return self::iOBJ(1); }

    static function named_template() { return !self::iOBJ() ? '' : self::iOBJ()->named_template; }
    static function transform_var($n) { return !self::iOBJ() ? '' : self::iOBJ()->get_var($n); }
    static function get_template($f, $n, $m) { return !self::iOBJ() ? '' : self::iOBJ()->GetZObjectTemplate($f, $n, $m); }
    static function template_escape_tokens($s) { return !self::iOBJ() ? '' : self::iOBJ()->TemplateEscapeTokens($s); }

    static function recno($reset = "") { return !self::iOBJ() ? '' : self::iOBJ()->RecNo($reset); }

    static function form_id() { return !self::iOBJ() ? '' : self::iOBJ()->form_id(); }
    static function form_action() { return !self::iOBJ() ? '' : self::iOBJ()->form_action(); }
    static function field_mode($n, $f, $m) { return $m; }

    static function get($f) { return !self::iOBJ() ? '' : self::iOBJ()->get($f); }
    static function set($f, $v) { return !self::iOBJ() ? '' : self::iOBJ()->set($f, $v); }

    static function require_test($c) { return !self::iOBJ() ? '' : self::iOBJ()->require_test($c); }

    static function TransferObjectKeys($zn, $args) { return !self::iOBJ() ? '' : self::iOBJ()->TransferObjectKeys($zn, $args); }

    static function item_link($field, $mode = "create", $text = "", $ajax = "", $C = "", $T = "") { return !self::iOBJ() ? '' : self::iOBJ()->ItemLink($field, $mode, $text, $ajax, $C, $T); }
    static function refresh_link() { return !self::iOBJ() ? '' : self::iOBJ()->refresh_link(); }
    static function AutoPageLinkByID($zname, $oid) { }

    static function YesNoVal($v) { $v = strtolower("$v"); return $v != '' && ($v[0] == 'y' || $v[0] == 't' || $v[0] == '1'); }
    static function TrueFalse($v) { return self::YesNoVal("$v") ? 'true' : 'false'; }
}
