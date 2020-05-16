<?php

class zobject
{
    public const ZP_PAGE = 'p';
    public const ZP_PAGECOUNT = 'pp';

    private static $iOBJs = [];

    static function DEBUG_TRANSFORM() { return ""; }
    static function DEBUG_TRANSFORM_ROW() { return ""; }
    static function DEBUG_TRANSFORM_FIELD() { return ""; }
    static function DEBUG_TRANSFORM_DATA_FIELD() { return ""; }

    static function render($el, $params = [], $vArgs = "")
    {
        php_logger::call();
        if (!is_object($el) || (!is_a($el, "DOMElement") && !is_a($el, "DOMDocument")))
            throw new Exception("Bad argument 1 to zobject::render.  Expected DOMElement.  Got: ".print_r($el, true));
        if (!is_array($params)) throw new Exception("Bad argument 2 to zobject::render.  Expected array.  Got: ".print_r($params, true));

        if (!array_key_exists('name', $params)) {
            $tName = $el->getAttribute('name');
            if (is_string($tName)) $params['name'] = $tName;
        } 
        if (!array_key_exists('name', $params)) throw new Exception("No 'name' found in parameters.");

        if (is_array($vArgs)) $vArgs = http_build_query($vArgs);

        return (new zobject_element())->render($params, $vArgs);
    }

    static function query($zname, $vArgs = [])
    {
        php_logger::call();
        $params['mode'] = 'data';
        $params['name'] = $zname;
        return self::render(xml_file::toDoc("<element />"), $params, $vArgs);
    }

    static function save($zName, $params = [])
    {
        return (new zobject_element())->save($zName);
    }

    static function formcontrols() {
// <xsl:variable name='AJAX' select='php:functionString("zobject::ajax")'/>
// <xsl:variable name='formid' select='php:functionString("zobject::form_id")'/>
// <xsl:if test='string-length($AJAX)=0 and ($mode="edit" or $mode="create")'>
//     <xsl:variable name='value'>
//         <xsl:choose>
//             <xsl:when test='string-length(@value)!=0'><xsl:value-of select='string(@value)'/></xsl:when>
//             <xsl:when test='string-length(@text)!=0'><xsl:value-of select='string(@text)'/></xsl:when>
//             <xsl:otherwise>Submit</xsl:otherwise>
//         </xsl:choose>
//     </xsl:variable>
//     <xsl:variable name='ty' select='substring(@type, 1, 1)'/>
//     <xsl:if test='$ty="s" or $ty=""'>
//         <input type='submit'>
//             <xsl:attribute name='value'><xsl:value-of select='$value'/></xsl:attribute>
//             <xsl:attribute name='class'><xsl:value-of select='@class'/></xsl:attribute>
//         </input>
//     </xsl:if>
// </xsl:if>
        return xml_serve::xml_content("<span>--EDIT--</span>");

    }

    static function itemlink() {
        
    }

    static function transform() { return realpath(__DIR__ . "/source/transform.xsl"); }

    static function ObjectList() {return xml_site::$source->lst("//MODULES/modules/module/zobjectdef/@name");}
    static function ModuleList() {return xml_site::$source->lst("//MODULES/modules/module/@name");}

    static function FetchObjFields($n) { return xml_site::$source->lst("//MODULES/modules/module/zobjectdef[@name='$n']/fielddefs/fielddef/@id"); }
    static function FetchObjPart($n, $p) { return xml_site::$source->get("//MODULES/modules/module/zobjectdef[@name='$n']/$p"); }
    static function FetchObjFieldPart($n, $f, $p) { return xml_site::$source->get("//MODULES/modules/module/zobjectdef[@name='$n']/fielddefs/fielddef[@id='$f']/$p"); }
    static function FetchDTPart($n, $p) { return xml_site::$source->get("//MODULES/modules/module/typedef[@name='$n']/$p"); }
    static function FetchObjFieldDefault($n, $f) { return self::FetchObjFieldPart($n, $f, '@default'); }
    static function FetchObjDefString($n) { return xml_site::$source->def("//MODULES/modules/module/zobjectdef[@name='$n']"); }
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

    static function handled_elements() { return xml_serve::handler_list(); }
    static function source_document($n) { php_logger::call();return xml_site::$source->get_source_doc($n); }

    static function new_jsid($pfx = "js_") { return uniqid($pfx); }

    static function admin() { return ""; }
    static function ajax() { return xml_site::$ajax; }
    static function ajax_url() { return "http://localhost/ajax.php"; }

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
        if (substr($a,0,m)==$S) return self::decode_args(urldecode($a));
            // otherwise, decoding an unencoded string does nothing!
        return $a;
        }


    static function KeyValue($k, $Args="", $alt="")
        {
        php_logger::call();
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
            $cb = create_function('$matches', "return \"'\".juniper()->KeyValue(substr(\$matches[0],$l)).\"'\";");
        else
            $cb = create_function('$matches', "return juniper()->KeyValue(substr(\$matches[0],$l));");

        $f = preg_replace_callback('/'.$token."[a-zA-Z0-9_]+".'/i', $cb, $f);
        php_logger::debug("InterpretFields:", $f);
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
        return count(self::$iOBJs) <= $n ? null : self::$iOBJs[-$n];
    }

    static function iOBJ2() { return self::iOBJ(1); }

    static function named_template() { return !self::iOBJ() ? '' : self::iOBJ()->named_template; }
    static function transform_var($n) { return !self::iOBJ() ? '' : self::iOBJ()->get_var($n); }
    static function get_template($f, $n, $m) { return !self::iOBJ() ? '' : self::iOBJ()->GetZObjectTemplate($f, $n, $m); }
    static function template_escape_tokens($s) { return !self::iOBJ() ? '' : self::iOBJ()->TemplateEscapeTokens($s); }

    static function recno($reset = 1) { return !self::iOBJ() ? '' : self::iOBJ()->RecNo($reset); }

    static function form_id() { return !self::iOBJ() ? '' : self::iOBJ()->form_id(); }
    static function form_action() { return !self::iOBJ() ? '' : self::iOBJ()->form_action(); }
    static function field_mode($n, $f, $m) { return $m; }

    static function get($f) { return !self::iOBJ() ? '' : self::iOBJ()->get($f); }

    static function require_test($c) { return !self::iOBJ() ? '' : self::iOBJ()->require_test($c); }

    static function TransferObjectKeys($zn, $args) { return !self::iOBJ() ? '' : self::iOBJ()->TransferObjectKeys($zn, $args); }

    static function item_link($field, $mode = "create", $text = "", $ajax = "", $C = "", $T = "") { return !self::iOBJ() ? '' : self::iOBJ()->ItemLink($field, $mode, $text, $ajax, $C, $T); }
}
