<?php

class zobject_element
{
    public $name;
    public $mode;
    public $module;
    public $prefix;
    public $named_template;

    public $result;

    public $page, $page_count;
    public $mRecNo;
    public $record_count;

    public $gid;

    function gid() { return $this->gid; }
    function __destruct()
    {
        zobject::unset_iOBJ($this);    
    }

    function __construct()
    {
        $this->gid = uniqid("ZO_");
        
        $this->page = "1";
        $this->page_count = "30";
        
        $this->record_count = "1";
        
        $this->mRecNo = "";
        
        $this->named_template = "";
        
        $n = func_num_args();
        $a = func_get_args();
        $this->name = ($n >= 1 && is_string($a[0]) ? $a[0] : "");
        $this->mode = ($n >= 2 && is_string($a[1]) ? $a[1] : "");
        $this->args = ($n >= 3 && is_string($a[2]) ? $a[2] : "");
        $this->prefix = ($n >= 4 && is_string($a[3]) ? $a[3] : "");

        zobject::set_iOBJ($this);
    }

    function arg($key)
    {
        if ($x = querystring::get($this->args, $key)) return $x;
        if ($x = $this->result_field($key)) return $x;
        return false;
    }

    public function RecNo($N = "")
    {
        php_logger::call();
        if ($N != "" && $N != null) $this->mRecNo = $N;
        if ($this->mRecNo == "") $this->mRecNo = "1";
        return $this->mRecNo;
    }

    public function form_action() {
        php_logger::call();
        $a = $this->options['action'];
        if ($a == '') $a = 'zo-save';
        $defined = zobject::FetchApiPart($a, '@name') != '';
        php_logger::debug("a=$a, defined=$defined");
        $result = !$defined ? $a : zobject::FetchApiPart($a, '@loc'); 
        php_logger::result($result);
        return $result;
    }

    private function load_object()
    {
        $n = $this->name;                 // local copy
        $this->options = array();

        $this->options['name']              = zobject::FetchObjPart($n, "@name");
        $this->options['module']            = zobject::FetchObjPart($n, "@module");
        php_logger::log("LOAD: " . $this->gid . ", ob=" . $this->options['name'] . ", module=" . $this->options['module']);
        $this->options['type']              = zobject::FetchObjPart($n, "@type");
        $this->options['index']             = zobject::FetchObjPart($n, "@index");
        $this->options['key-field']         = $this->TranslateKeyList(zobject::FetchObjPart($n, "@key-field"));
        $this->options['key-field-optional']= $this->TranslateKeyList(zobject::FetchObjPart($n, "@key-field-optional"));
        $this->options['keys']              = $this->options['key-field'];
        $this->options['action']            = zobject::FetchObjPart($n, '@action');

        $this->options['prefix']            = '';

        
        $key_array                          = // ....  onto next line (double assignment)...
        $this->options['key-array']         = explode(",", $this->options['keys']);
        $this->options['key']               = (count($key_array) == 0) ? "" : $key_array[count($key_array) - 1];
        $this->options['key-array-optional']= explode(",", $this->options['key-field-optional']);
        $this->options['key-array-all']     = array_merge($this->options['key-array'], $this->options['key-array-optional']);

        $this->options['pre-trigger']       = zobject::FetchObjPart($n, "@pre-trigger");
        $this->options['post-trigger']      = zobject::FetchObjPart($n, "@post-trigger");

        $this->options['allow-display']     = zobject::FetchObjPart($n, "@allow-display");
        $this->options['allow-edit']        = zobject::FetchObjPart($n, "@allow-edit");
        $this->options['allow-list']        = zobject::FetchObjPart($n, "@allow-list");
        $this->options['allow-create']      = zobject::FetchObjPart($n, "@allow-create");
        $this->options['allow-delete']      = zobject::FetchObjPart($n, "@allow-delete");

        $this->options['allow-style']       = zobject::FetchObjPart($n, "@allow-delete");

        $this->options['return']            = zobject::FetchObjPart($n, "return");
    }


    function QueryStringSatisfied($ZN, $ZA)
    {
        php_logger::call();

        $kf = $this->options['keys'];
        php_logger::debug("kf=$kf");

        if ($kf == "") return true;
        php_logger::info("QueryStringSatisfied: Checking...");
        foreach (explode(",", $kf) as $k)
            if (zobject::KeyValue($k, $ZA) == "") return false;
        return true;
    }

    function TranslateZName($Z)
    {
        php_logger::call();
        if ($Z == "") $Z = ";zname";
        else if (substr($Z, 0, 1) == ";" && ($f = @$_REQUEST[substr($Z, 1)]) != "") $Z = $f;
        $chk = zobject::FetchObjPart($Z, "@name");
        php_logger::debug("Exists: " . $chk ? "YES" : "NO");
        if (!$chk) throw new Exception("[$Z] is not a valid Object.");
        $this->module = zobject::FetchObjPart($Z, "../@name");
        php_logger::debug("module: $this->module");
        return $this->name = $Z;
    }

    function TranslateZMode($ZN, $ZM, $ZA = "")
    {
        php_logger::call();
        if ($ZM == "") $ZM = ";m";
        if ($ZM[0] == ";" && ($f = @$_REQUEST[substr($ZM, 1)]) != "") {
            $ZM = $f;
            $this->named_template = "";
        } else if (strstr($ZM, ";") !== false) {
            $R = explode(";", $ZM);
            php_logger::dump("R=", $R);
            $ZM = $R[0];
            $name = $R[1];
            $this->named_template = zobject::FetchObjPart($ZN, "render[@name='$name']/@src");
            php_logger::debug("NAMED TEMPLATE (1): " . $this->named_template);
        } else if (($nt = zobject::FetchObjPart($ZN, "render[@name='$ZM']/@type")) != "") {
            $this->named_template = zobject::FetchObjPart($ZN, "render[@name='$ZM']/@src");
            $ZM = zobject::FetchObjPart($ZN, "render[@name='$ZM']/@type");
            php_logger::debug("NAMED TEMPLATE (2): named_template=$this->named_template, ZMode=$ZM");
        }

        php_logger::debug("ZM=$ZM");
        $Sat = $this->QueryStringSatisfied($ZN, $ZA);
        php_logger::debug("QSSatisfied($ZN): " . ($Sat ? "Yes" : "No"));

        php_logger::log("ZM=$ZM");
        if ($ZM == '') {
            if (querystring::get('display') != '') $ZM = '0';
            if (querystring::get('add') != '') $ZM = 'x';
            if (querystring::get('edit') != '') $ZM = '1';
            if (querystring::get('delete') != '') $ZM = 'x';
            if (querystring::get('position') != '') $ZM = 'p';
            if (querystring::get('upposition') != '') $ZM = '<';
            if (querystring::get('dnposition') != '') $ZM = '>';
        }

        if ($ZM == "delete") $ZM = "x";
        if ($ZM == "dnposition") $ZM = ">";
        if ($ZM == "upposition") $ZM = "<";
        if ($ZM == "") $ZM = "d";

        php_logger::debug("switching ZM=$ZM");
        switch (strToLower(substr($ZM, 0, 1))) {
            case "0":  case "d": if ($ZM != 'data') $ZM = $Sat ? "display" : "find"; break;
            case "1":  case "e": $ZM = $Sat ? "edit" : "find"; break;
            case "*":  case "b": $ZM = "build"; break;
            case "-":  case "c": $ZM = "create"; break;
            case "=":  case "l": if ($ZM != "list-create" && $ZM != "list-edit") $ZM = "list"; break;
            case "-":  case "h": $ZM = "list-edit"; break;
            case "+":  case "j": $ZM = "list-create"; break;
            case "^":  case "f": $ZM = "find"; break;
            case '<': $ZM = 'upposition'; break;
            case '>': $ZM = 'dnposition'; break;
            case "p": $ZM = 'position'; break;
            case "x": $ZM = "delete"; break;
            case "&": $ZM = 'data'; break;
            default: $ZM = ($Sat ? "display" : "list-edit"); break;
        }

        switch ($ZM) {
            case "data":
            case "display":
            case "edit":
            case "build":
            case "create":
            case "list":
            case "list-edit":
            case "list-create":
            case "find":
            case "delete":
            case "position":
            case "dnposition":
            case "upposition":
                break;
            default:
                $ZMode = "display";
        }

        //print "<br/>zmode=$ZM, NT=".$this->named_template;
        //log_file("zobject", "<br/>ZM=$ZM");
        //            if ($ZM=="delete" && CheckObjectAccess($ZN,$ZM)!="delete") $ZM="none";
        //            if ($ZM=="create" && CheckObjectAccess($ZN,$ZM)!="create") $ZM="none";
        //            if ($ZM=="list-edit" && CheckObjectAccess($ZN,$ZM)!="list-edit") $ZM="list";
        //            if ($ZM=="list" && CheckObjectAccess($ZN,$ZM)!="list") $ZM="none";
        //
        //            if ($ZM=="build" && CheckObjectAccess($ZN,$ZM)!="build") $ZM="display";
        //            if ($ZM=="edit" && CheckObjectAccess($ZN,$ZM)!="edit") $ZM="display";
        //            if (($ZM=="display" || $ZM=="form") && CheckObjectAccess($ZN,$ZM)!="display") $ZM="none";
        //    //log_file("zobject", "<br/>ZM=$ZM");

        if ($ZM == "") $ZM = "display";
        php_logger::result("zmode=$ZM", "NT=" . $this->named_template);
        return $this->mode = $ZM;
    }

    function TranslateZArgs($ZName, $ZArgs)
    {
        php_logger::call();
        if ($ZArgs == "") $ZArgs = @$_SERVER["QUERY_STRING"];            //  this should be the ONLY place zobject directly references the query string...
        $ZArgs = zobject::decode_args($ZArgs);
        $ZArgs = zobject::InterpretFields($ZArgs);
        $ZArgs = str_replace("'", "", $ZArgs);
        $ZArgs = $this->TransferObjectKeys($ZName, $ZArgs);
        php_logger::result($ZArgs);
        return $this->args = $ZArgs;
    }

    function result_field($f, $rn = "")
    {
        php_logger::call();
        if (!$this->result) return "";
        if ($rn == "") $rn = $this->RecNo();
        $result = $this->result->fetch_part("//row[$rn]/field[@id='$f']");
        php_logger::result($result);
        return $result;
    }

    function set_result($D) { $this->result = new xml_file($D); }
    function get_result() { return $this->result; }

    function load_result(&$tform = null)
    {
        php_logger::call();
        require_once("zobject-query.php");

        $resultDoc = zobject_query::get_result($this->name, $this->mode, $this->args, $this->record_count, $tform);
        if (!$resultDoc) 
        {
            php_logger::warning("No zobject::resultDoc in load_result");
            return false;
        }
        $this->set_result($resultDoc);
        php_logger::dump("RESULT: ", $this->get_result());
        return true;
    }

    function process_arguments(&$vName = "", &$vMode = "", &$vArgs = "", $vPrefix = "", $use_form = true)
    {
        php_logger::call();
        php_logger::log("1 - ZName=$vName, ZMode=$vMode, named_template=$this->named_template, vArgs=$vArgs");
        if ($vName != "") $this->name = $vName;
        if ($use_form && $this->name == "") $this->name = @$_REQUEST['_ZN'];
        $this->name = $vName = $this->TranslateZName($this->name);
        $this->load_object();

        php_logger::log("2 - ZName=$vName, ZMode=$vMode, named_template=$this->named_template, vArgs=$vArgs");
        if ($vArgs != "") $this->args = $vArgs;
        if ($use_form && $this->args == "") $this->args = @$_REQUEST['_ZA'];
        $this->args = $vArgs = $this->TranslateZArgs($vName, $this->args);

        php_logger::log("3 - ZName=$vName, ZMode=$vMode, named_template=$this->named_template, vArgs=$vArgs");
        if ($vMode != "") $this->mode = $vMode;
        if ($use_form && $this->mode == "") $this->mode = @$_REQUEST['_ZM'];
        $this->mode = $vMode = $this->TranslateZMode($vName, $this->mode, $vArgs);

        $this->prefix = $vPrefix;

        php_logger::debug("ZName=$vName<br/>ZMode=$vMode, named_template=$this->named_template<br/>vArgs=$vArgs");
        if ($vName == "none") return false;
        return true;
    }

    function render($params = null, $vArgs = "")
    {
        php_logger::call();
        $vName = @$params['name'];
        $vMode = @$params['mode'];
        $vPrefix = @$params['prefix'];
        php_logger::debug("XXX=====   zobject::render($vName, $vMode, $vArgs, $vPrefix)   =====XXX");

        if (!$this->process_arguments($vName, $vMode, $vArgs, $vPrefix, false)) {
            return $this->empty_render();
        }
        if (@$this->options['module'] == '') $this->options['module'] = @$params['module'];


        if (!$this->named_template && key_exists("template", $params))
            $this->named_template = @$params['template'];

        php_logger::trace("zobject::render:  name=$vName, mode=$vMode, args=$vArgs, px=$vPrefix, NT=" . $this->named_template);
        switch ($this->options['type']) {
            case "transform":
                $Ix = zobject::KeyValue("value");
                //print "<br/>Ix=$Ix";
                $r = zobject::FetchObjPart($vName, "action[@value='$Ix']");
                //print ESKf($r);
                return $r == "" ? $this->empty_render($D) : XMLToDoc($r);
            case "querybuilder":
                break;
            default:
                break;
        }

        if (false) {
            $t = "";
            $t = $t . "<table class='DEBUG'>";
            $t = $t . "<tr><td colspan='2' class='title'>renderZObject</td></tr>";
            $t = $t . "<tr><th>Var</th><th>Val</th></tr>";
            $t = $t . "<tr><td>vName</td><td>$vName</td></tr>";
            $t = $t . "<tr><td>vMode</td><td>$vMode</td></tr>";
            $t = $t . "<tr><td>vArgs</td><td>$vArgs</td></tr>";
            $t = $t . "</table>";
            print $t;
        }

        php_logger::log("Including support files");
        xml_site::include_support_files($this->options['module']);        // this is what this particular objects has requested..  required before load_result()

        php_logger::log("Loading result set");
        if (!$this->load_result($tform)) {
            php_logger::log("================ TFORM ================");
            php_logger::debug($tform);
            $D = ($tform == "") ? xml_serve::empty_content() : xml_file::toDoc($tform);
            return $D;
        }

        if ($vMode == 'data') return $this->get_result();

        //die(juniper()->result()->saveXML());

        //print "<br/>named_template=$this->named_template, FP=".FilePath("t", $this->named_template) . ", Result Len=" . strlen($this->result->saveXML());
        //print "<br/>".$this->args;
        //print "<br/>".$this->arg64();
        //print "<br/>transform: ".$this->transform(); 

        php_logger::log("Generating Output");
        $res = new xml_file($this->get_result(), '', zobject::transform());
        $zobj = $res->Doc;
//die($res->saveXML());
        unset($res);

        return $zobj;
    }    //  FUNCTION: render

    function source($enc = true, $s = "")
    {
        if ($s == "") $s = $this->args;
        $s = querystring::add($s, "_ZN", $this->name);
        if ($this->named_template)
            $s = querystring::add($s, "_ZM", $this->mode . ";" . $this->named_template);
        else
            $s = querystring::add($s, "_ZM", $this->mode);

        if ($enc) $s = zobject::encode_args($s);
        return $s;
    }

    function default_save_redirect($vName, $vArgs) {
        php_logger::call();
        $s = parse_url(zobject::decode_args($_REQUEST['_ZO']), PHP_URL_PATH);
        $a = $this->TransferObjectKeys($vName, $vArgs);
        $a = querystring::remove($a, "edit");
        $a = querystring::remove($a, "delete");
        $a = querystring::remove($a, "create");
        if ($a != '') $a = querystring::aqm($a);
        $result = "$s$a";
        php_logger::result($result);
        return $result;
    }

    function save($vName, $vMode = "", $vArgs = "")
    {
        php_logger::call();
        if (!$this->process_arguments($vName, $vMode, $vArgs, "", true)) {
            return false;
        }

        php_logger::log("SAVE ZOBJECT: ZName=$this->name, $this->mode, named_template=$this->named_template, vArgs=$this->args");
        $this->args = zobject::decode_args($this->args);

        xml_site::include_support_files($this->options['module']);        // this is what this particular objects has requested..  required for save and load

        include_once("zobject-query.php");
        zobject_query::save_form();

        //print "<br/>".juniper()->FetchSpecPart($this->options['module'], "program/control[@type='page']/@src");
        $Target = php_hook::call(zobject::FetchSpecPart($this->options['module'], "program/control[@type='page']/@src"), array("save_object::" . $this->name, "action"), true);
        if (!$Target) $Target = php_hook::call(zobject::FetchObjPart($this->name, "@redirect"));
        if (!$Target) $Target = $this->default_save_redirect($vName, $vArgs);

        php_logger::result($Target);
        return $Target;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////


    function NormalizeInputField($f, $DT)
    {
        php_logger::call();
        $N = zobject::FetchDTPart($DT, "@normalize");
        //print "<br/>N=$N";
        $Na = php_hook::call($N, $f);
        if ($Na != $N) $f = $Na;

        if (($k = zobject::FetchDTPart($DT, "@html-type")) == "") $k = $DT;
        switch ($k) {
            case "wysiwyg":
            case "rtf":
            case "richtext":
                $f = str_replace(array("<div><br></div>", "<br>", "<br/>", "<br />"), array("\n", "\n", "\n", "\n"), $f);
                $f = trim($f);
                break;
        }

        $dbt = zobject::FetchDTPart($DT, "@db-type");
        if ($dbt == "integer" || $dbt == "float" || $dbt == "currency") {
            if ($f == "Yes" || $f == "yes") $f = 1;
            if ($f == "No" || $f == "no") $f = 0;
            $f = 0 + $f;
        }
        return $f;
    }

    function TranslateKeyList($List, $Prev = "", $KeysOnly = true)
    {
        php_logger::call();
        if ($List == "") return $List;

        $z = "";
        $m = explode(',', $List);
        foreach ($m as $kk) {
            php_logger::debug("kk=$kk");
            if ($kk[0] == ";") {
                $t = zobject::FetchObjPart(substr($kk, 1), "@key-field");
                php_logger::debug("key-field=$t");
                if ($t != "" && strstr($Prev, $kk) == "") {
                    $f = $this->TranslateKeyList($t, $Prev . $kk);
                    if ($f != "")    $z = $z . (strlen($z) > 0 ? "," : "") . $f;
                }
            } else {
                if ($KeysOnly) {
                    $xk = explode(":", $kk);
                    $z = $z . (strlen($z) > 0 ? "," : "") . $xk[0];
                } else {
                    $z = $z . (strlen($z) > 0 ? "," : "") . $kk[0];
                }
            }
        }
        php_logger::trace("TranslateKeyList: $z");
        return $z;
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////




    function XMLAutoNumber()            //  ### multiple broken parts... this should probably be part of zobject-query, simply because it uses @src
    {
        include_once("class-zobject-query.php");
        return zobject_query::GetXMLAutoNumber();
    }

    function DefaultValue($f)
    {
        return php_hook::call($f);
    }

    function DisplayMultiValue_List($ValueStr)
    {
        $o = "";
        $s = "";
        $x = false;
        $rn = 0;
        for ($i = 0, $n = strlen($ValueStr); $i < $n; $i++) {
            $c = substr($ValueStr, $i, 1);
            $nc = $i < $n - 1 ? substr($ValueStr, $i + 1, 1) : "";
            if ($c == "'") {
                if ($nc == "'" && $x) {
                    $s = $s . "'";
                    $i++;
                } elseif (($nc == "," || $nc == "") && $x) {
                    $rn++;
                    $o = "$o<item n='$rn'><![CDATA[$s]]></item>";
                    $s = "";
                    $i++;
                    $x = false;
                } else
                    $x = true;
            } else
                $s = $s . substr($ValueStr, $i, 1);
        }
        $o = "<list count='$rn'>" . $o . "</list>";
        return xml_file::XMLToDoc($o);
    }


    //////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////

    //  Called on attributes when interpreting unhandled HTML elements.
    //  Allows fields to be handled inside of things like an HREF element
    function TemplateEscapeTokens($s, $t = "", $u = "")
    {
        php_logger::call();
        // if (strlen($s)<100)print "<br/>TemplateEscapeTokens($s)";else print "<br/>TemplateEscapeTokens(...)";
        while (($a = strpos($s, "{@")) !== false) {
            $b = strpos($s, "}", $a);
            $c = substr($s, $a + 2, $b - $a - 2);
            $d = zobject::KeyValue($c, $this->args);
            php_logger::debug("test=".zobject::KeyValue($c, $this->args));

            if ($d == "") $d = $this->DefaultValue(zobject::FetchObjFieldPart($this->name, $c, "@default"));
            php_logger::debug("TemplateEscapeTokens 1: a=$a, b=$b, c=$c, d=$d");
            $s = str_replace("{@" . $c . "}", $d, $s);
        }

        while (($a = strpos($s, "{php:")) !== false) {
            $b = strpos($s, "}", $a);
            $c = substr($s, $a + 5, $b - $a - 5);
            $d = zobject::KeyValue($c, $this->args);
            $d = php_hook::call("php:$c", $this->args);
            php_logger::debug("TemplateEscapeTokens 2: a=$a, b=$b, c=$c, d=$d");
            $s = str_replace("{php:" . $c . "}", $d, $s);
        }
        php_logger::result($s);
        //die($s);
        return $s;
    }

    function GetZobjectAutoTemplate()
    {
        php_logger::call();
        //$_a = BenchTime();
        require_once("zobject-autotemplate.php");

        php_logger::debug($this->gid().", module=".$this->options['module']);
        $f = zobject::FetchSpecPart($this->options['module'], "program/control[@type='autotemplate']/@src");
        php_logger::debug("autotemplate control=$f");
        if ($f != "") $f = 'modules/' . $this->options["module"] . "/" . $f;
        php_logger::debug("autotemplate control=$f");

        $t = zobject_autotemplate::autotemplate($this->name, $this->mode, $f);

        php_logger::debug("zobject_template", $t);
        if (strlen(querystring::get("SaveAutoTemplate")) > 0) {
            //Warning("Saving AutoTemplate to: ".rPATH_AUTOTEMPLATES . $ZName . "_" . $ZMode . ".xml", "GetZObjectTemplate");
            file_put_contents(rPATH_AUTOTEMPLATES . $ZName . "_" . $ZMode . ".xml", DoTidyXMLString($t->SaveXML()));
        }

        $D = new DOMDocument;
        $D->loadXML($t);
        return $D;
    }

    function GetZObjectTemplate($FName = "", $ZName = "", $ZMode = "")
    {
        php_logger::call();
        if ($FName != '') {
            $module = zobject::FetchObjPart($this->name, '../@name');
            $FName = xml_site::resolve_file($FName, ["module"], ["module" => $module], );
        }
        php_logger::debug("FName=$FName");
        if (!($FName == "") && !file_exists($FName)) {
            php_logger::warn("Specified Template File Does Not Exists: $FName, " . getcwd() . "," . realpath($FName), "ZObj::GetZObjectTemplate");
            $FName = "";
        }
        $t = "";
        if ($FName != "" && strlen($t = file_get_contents($FName)) == 0) {
            php_logger::warn("Specified Template File is empty or no access: $FName", "ZObj::GetZObjectTemplate");
            $FName = "";
        }
        if ($t != "") {
            $d = new DOMDocument;
            $d->loadXML($t);
            if ($d === false) {
                php_logger::warn("Failed to load template: $FName", "ZObj::GetZObjectTemplate");
                $t = "";
            }
        }
        if ($t == "") $d = $this->GetZobjectAutoTemplate();
        if (gettype($d)=="object") {
            php_logger::debug("RESULT=".get_class($d));
            // php_logger::trace($d->saveXML());
        }
        return $d;
    }

    ///////////////////////////////////////////////////////////////////////////


    function TransferSourceKeys($List, $HREF)
    {
        php_logger::call();
        $N = 0;
        $List = $this->TranslateKeyList($List, true);
        //print "<br/>List=$List";
        if ($List == "") return $HREF;

        $X = $HREF;
        if (!strstr($HREF, "?"))
            $X = $X . "?";
        else
            $N = 1;

        //print "<br/>N=$N";
        //print "<br/>X=$X";
        $R = explode(',', $List);
        foreach ($R as $L) {
            //print "<br/>L=$L";
            if (!strstr($L, ":")) {
                $tf = $L;
                $ts = $L;
            } else {
                $M = explode(":", $L);
                $tf = $M[1];
                $ts = $M[0];
                //print "<br/>tf=$tf<br/>ts=$ts";
            }
            if ($ts[0] == "@")
                $tv = $ts;
            else
                $tv = $_GET[$ts];
            $X = add($X, $tf, $tv);
            //            $X = $X . ($N>0?"&":"") . "$tf=$tv";
            $N = 1;
        }
        $r = $N > 0 ? $X : $HREF;
        //print "<br/>r=$r";
        $r = xml_site::InterpretFields($r);
        //print "<br/>TransferSourceKeys: $r";
        return $r;
    }

    function TransferFields($List, $HREF)
    {
        php_logger::call();
        $x = explode(";", $List);
        foreach ($x as $l) {
            $t = explode(":", $l);
            $a = $t[0];
            $b = $t[1];
            switch ($a[0]) {
                case "@":
                    $f = substr($a, 1);
                    $v = $this->result_field($f, "");
                    $HREF = querystring::add($HREF, $b, $v);
                    //print "<br/>HREF=$HREF";
                    break;
                default:
                    die("Unknown Field Identifier in TransferFields()");
            }
        }
        return $HREF;
    }

    function GetZobjectSQL($ZName, $type = "")
    {
        php_logger::call();
        $sl = zobject::FetchObjPart($ZName, "sql[@type='$type']");
        //print "<br/>GetZobjectSQL: $sl";
        return $sl;
    }

    function BuildZObjectQuery($ZName, $ZMode, $Args)
    {
        php_logger::call();
        if (($sl = GetZObjectSQL($ZName, $ZMode)) == "") return "";
        $sx = explode(";", $sl);
        //print "<br/>";print_r($sx);
        $sr = array();
        $fl = array();
        $sql = array();
        $n = 0;
        foreach ($sx as $l) {
            $n = $n + 1;
            $a = strpos($l, "{");
            $b = strpos($l, "}");
            $fieldlist = substr($l, $a + 1, $b - $a - 1);
            //print "<br/>a=$a, b=$b";
            //print "<br/>fieldlist=$fieldlist";
            if ($ZMode == "create")
                $sr[] = str_replace('{' . $fieldlist . '}', '({*}) VALUES ({**})', $l);
            else
                $sr[] = str_replace('{' . $fieldlist . '}', '{*}', $l);
            $fl[] = ",,$fieldlist,";
            $sql[] = "";
        }
        //print "<br/>sr=";print_r($sr);
        //print "<br/>fl=";print_r($fl);

        foreach (zobject::FetchObjFields($ZName) as $ckf) {
            //print "<br/>ckf=$ckf";
            for ($i = 0; $i < $n; $i++) {
                if (GetFieldMode($ZName, $ckf, $ZMode) == $ZMode) {
                    //print "<br/>i=$i,fl=".$fl[$i];
                    if (strstr($fl[$i], ",$ckf,") != "" or strstr($fl[$i], "*") != "") {
                        if ($sql[$i] == "") $sql[$i] = $sr[$i];
                        $v = "";
                        if (array_key_exists($ckf, $_REQUEST)) {
                            if (is_array($_REQUEST[$ckf]))
                                $v = implode(",", $_REQUEST[$ckf]);
                            else
                                $v = $_REQUEST[$ckf];
                        }

                        $datatype = zobject::FetchObjFieldPart($ZName, $ckf, "@datatype");
                        $deff = zobject::FetchObjFieldPart($ZName, $ckf, "@default");
                        if ($deff == "") $deff = zobject::FetchDTPart($datatype, "@default");
                        //print "<br>@default=".FetchDTPart($datatype, "@default");
                        if ($v == "") $v = php_hook::call($deff);

                        if ($v != "") {
                            $v = SVF($v, $datatype);

                            if ($ZMode == "create") {
                                $sql[$i] = str_replace('{*}', _SD . $ckf . (DS_) . ',{*}', $sql[$i]);
                                $sql[$i] = str_replace('{**}', $v . ',{**}', $sql[$i]);
                            } else
                                $sql[$i] = str_replace('{*}', _SD . $ckf . DS_ . '=' . $v . ',{*}', $sql[$i]);
                        }
                    }
                }
                //print "<br/>... ".$sql[i];
            }
        }
        for ($i = 0; $i < $n; $i++) if (strstr($sql[i], ",{*}") == "") unset($sql[i]);
        $s = implode(";", $sql);

        $s = str_replace(',{*}', '', $s);
        $s = str_replace(',{**}', '', $s);

        while (strstr($s, "@@") != "") {
            $a = strpos($s, "@@");
            for ($i = $a; $i < strlen($s); $i++) {
                $c = substr($s, $i, 1);
                if ($c == " " || $c == "\t" || $c == "\n" || $c == "\r" || $c == ";" || $c == ",") break;
            }
            $kp = substr($s, $a, $i - $a);
            $kf = substr($kp, 2);
            //print "<br/>kp=$kp, kf=$kf, Args=$Args";
            $s = str_replace($kp, zobject::KeyValue($kf, $Args), $s);
        }

        //die("<br/>sql=".$s);
        return $s;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////

    function TransferObjectKeys($zn, $Args)
    {
        php_logger::call();
        $l = $this->options['key-array-all'];
        php_logger::trace($l);
        $l[] = zobject::ZP_PAGE;
        $l[] = zobject::ZP_PAGECOUNT;
        foreach ($l as $m) {
            if ($m == '' || $m == null) continue;
            php_logger::trace("m=$m, Args=$Args");
            $v = zobject::KeyValue($m, $Args);
            if ($m != "" && $m[0] != '#' && $v != '') {
                $Args = querystring::add($Args, $m, $v);
                php_logger::debug("m=$m, Args=$Args");
            }
        }
        php_logger::result("TransferObjectKeys: $Args");
        return $Args;
    }

    function FillInQueryStringKeys($m, $ZArgs = "", $dolast = true)
    {
        php_logger::call("L=".$this->options['key'].",".$this->options['key-field-optional']);
        $k = $this->options['key'];
        php_logger::debug("field=".implode(",",$this->options['key-array-all']));
        foreach ($this->options['key-array-all'] as $l)
            if ($l != "" && ($dolast || (!$dolast && $l != $k))) {
                $m = str_replace("@" . $l, zobject::KeyValue($l, $ZArgs), $m);
                //print "<br/>FillInQueryStringKeys <b>loop</b> l=$l - $m";
            }
        php_logger::debug("FillInQueryStringKeys: $m");
        return $m;
    }

    function TransferQueryStringKeys($List, $HREF)
    {
        php_logger::call();
        $N = 0;
        $List = $this->TranslateKeyList($List);
        //print "<br/>List=$List";
        if ($List == "") return $HREF;

        $X = $HREF;
        if (!strstr($HREF, "?"))
            $X = $X . "?";
        else
            $N = 1;

        $R = explode(',', $List);
        foreach ($R as $L) {
            //print "<br/>L=$L";
            if (!strstr($L, ":")) {
                $tf = $L;
                $ts = $L;
            } else {
                $M = explode(":", $L);
                $tf = $M[1];
                $ts = $M[0];
            }
            switch ($ts[0]) {
                case "@":
                    $tv = $ts;
                    break;
                case "#":
                    switch (strtoupper($ts)) {
                        case "#USERID#":
                            $tv = GetCurrentUsername();
                        default:
                            $tv = $_REQUEST[$ts];
                    }
                    break;
                default:
                    $tv = $_REQUEST[$ts];
                    break;
            }
            $X = $X . ($N > 0 ? "&" : "") . "$tf=$tv";
            $N = 1;
        }
        return $N > 0 ? $X : $HREF;
    }

    function form_id()
    {
        return zobject::ajax() ? "ajax-form" : "F" . $this->gid();
    }

    function FormAction($FormID = "", $Args = "0")
    {
        php_logger::log("FormAction($FormID, $Args), ajax=".(zobject::ajax()?"Yes":"No"));
        if (zobject::ajax()) return zobject::ajax_url('save-zobject') . "?_AJAX=1&_Save=1";
        $r = "";
        //        $r = juniper()->php_hook(zobject::is->FetchObjPart($this->name, "action"));
        if ($r == "") $r = CurrentPage();
        return $r;
    }

    function LinkArgs($Mode, $TN, $Args)
    {
        php_logger::call();
        $key = "@" . $this->options["key"];

        if ($key != "") {
            if (in_array($Mode, array("display", "edit", "delete", "position", "upposition" . "dnposition"))) {
                $id = $this->options['index'];
                $kv = $this->result_field($id);
                //print "<br/>KEY: key=$key, id=$id, kv=$kv";
                $Args = querystring::add($Args, substr($key, 1), $kv);
            }
            //            else $Args = querystring::remove_querystring_var($Args, substr($key,1));
        }
        php_logger::result("Args=$Args");
        return $Args;
    }

    function ItemLink($field, $mode = "create", $text = "", $ajax = "", $C = "", $T = "")
    {
        php_logger::call();

        if (($TN = $this->name) == "") return "";
        //print "<br/>name=$TN";

        if ($C == "") $C = "ItemLink";

        if ($field != "") {
            $ZName = zobject::FetchObjFieldPart($TN, $field, "@datatype");
            if ($ZName[0] == ":") $ZName = substr($ZName, 1);
            //print "<br/>ZName=$ZName";
            if ($ZName == "") return "";
            $TN = $ZName;
            //print "<br/>TN=$TN";
        }
        if ($text == "") switch ($mode) {
            case "cancel":          $text = "\\";                break;
            case "save":            $text = ".";                break;
            case "display":         $text = "@";                break;
            case "create":          $text = "*";                break;
            case "edit":            $text = "#";                break;
            case "delete":          $text = "X";                break;
            case "position":        $text = "Pos";              break;
            case "upposition":      $text = "-";                break;
            case "dnposition":      $text = "+";                break;
            case "refresh":         $text = "~";                break;
            default:                $text = "[??? mode]";       break;
        }
        $text = zobject::InterpretFields($text);
        if ($text == "") $text = "[???]";

        //print "<br/>args: $this->args";
        $Args = $this->TransferObjectKeys($this->name, $this->args);
        //print "<br/>Item Args=$Args";
        $Args = $this->LinkArgs($mode, $TN, $Args);
        //print "<br/>Item Args=$Args";

        $tid = $this->gid();
        $url = zobject::ajax_url();
        $params = "{ '_AJAX' : 1, '_Save' : 1, '_ZA' : '" . $this->get("args64") . "' }";

        // if ($ajax != "") {
            // php_logger::log("== Ajax ==");
            // $Args = querystring::add($Args, '_ZN', $this->name);
            // $Args = querystring::add($Args, '_ZM', ($T == "") ? $mode : "$mode;$T");
            // $Args64 = zobject::encode_args($Args);
            // //print "<br/>Args=$Args";
            // //print "<br/>source=".$this->source(false);
            // //print "<br/>Args64=$Args64";

            // $gid = $this->gid();
            // $src = $this->source();

            // switch ($mode) {
            //     case "display":
            //         $title = "Show Item";
            //         $s = "zoGetObjToDialog('$Args64','$gid', '$src');";
            //         break;
            //     case "create":
            //         $title = "Add Item";
            //         $s = "zoGetObjToDialog('$Args64','$gid', '$src');";
            //         break;
            //     case "edit":
            //         $title = "Edit Item";
            //         $s = "zoGetObjToDialog('$Args64','$gid', '$src');";
            //         break;
            //     case "delete":
            //         $title = "Delete Item";
            //         $s = "zoModalConfirmItem('Really Delete?','$Args64','$gid', '$src' );";
            //         break;
            //     case "position":
            //         $title = "Move Item";
            //         $s = "AdjustRow('$Args64', '1');";
            //         $s2 = "AdjustRow('$Args64', '-1');";
            //         $s = "$('#$tid').load('$url', $params)";
            //         $s2 = "$('#$tid').load('$url', $params)";
            //         break;
            //     case "upposition":
            //         $title = "Move Item Up";
            //         $s = "zoExecuteToItem('$Args64','$gid', '$src');";
            //         break;
            //     case "dnposition":
            //         $title = "Move Item Down";
            //         $s = "zoExecuteToItem('$Args64','$gid', '$src');";
            //         break;
            //     case 'refresh':
            //         $t = 'Refresh';
            //         $s = "zoRefresh('{$this->gid()}');";
            //     default:
            //         $s = "";
            //         break;
            // }
            // $a = "";
            // if ($mode != "position") {
            //     //if ($mode="add") print "<br/>ITEM LINK ADD Args=$Args";
            //     $a = $a . "<span id='" . zobject::new_jsid() . "'>";
            //     $a = $a . "<a title='$title' class='$C $mode' onClick=\"$s\">$text</a>";
            //     $a = $a . "</span>";
            // } else {
            //     $a = $a . "<span id='" . zobject::new_jsid() . "'>";
            //     $a = $a . "<a class='$C $mode up' title='Move Up' onClick=\"$s\" style=\"font-family:helvetica\">&#9660;</a>";
            //     $a = $a . " / ";
            //     $a = $a . "<a class='$C $mode down' title='Move Up' onClick=\"$s2\" style=\"font-family:helvetica\">&#9650;</a>";
            //     $a = $a . "</span>";
            // }
        // } else {
            php_logger::log("== Regular ==");
            $s = querystring::aqm($Args);
            $s = querystring::remove($s, 'edit');
            $s = querystring::remove($s, 'delete');
            $s = querystring::remove($s, 'add');
            $s = querystring::remove($s, 'pos');
            $s = querystring::remove($s, '_ZM');
            // $s = querystring::add($s, '_ZN', $this->name);
            // $s = querystring::add($s, '_ZM', $this->mode);
            $indexer = '';
            if (substr($this->mode, 0, 4) == 'list') {
                $indexer = querystring::rqm($this->TransferObjectKeys($this->name, $s));
                $indexer = str_replace('&', '&amp;', $indexer);
            }
            if ($indexer != '') $indexer = "&amp;$indexer";
            php_logger::debug("s=$s, mode=$mode, indexer=$indexer");
            

            $func = false;
            switch ($mode) {
                case "save":            $func = "zoPostForm(\"{$this->gid()}\");";              break;
                case "edit":            $func = "zoRefresh(\"{$this->gid()}\", \"mode=edit$indexer\");";     break;
                case "display":         $func = "zoRefresh(\"{$this->gid()}\", \"mode=display$indexer\");";  break;
                case "cancel":          $func = "zoRefresh(\"{$this->gid()}\", \"mode=display\");";   break;
                case "create":          $func = "zoRefresh(\"{$this->gid()}\", \"mode=create\");";   break;
                case "delete":          $s = querystring::add($s, 'delete', '1');               break;
                case "position":        $s = querystring::add($s, 'pos', '1');                  break;
                case "upposition":      $s = querystring::add($s, 'upposition', '1');           break;
                case "dnposition":      $s = querystring::add($s, 'dnposition', '1');           break;
                case "refresh":         $func = "zoRefresh(\"{$this->gid()}\");";               break;
                default:                $s = "";                                                break;
            }

            $p = zobject::FetchSpecPart($this->options['module'], 'program/control[@type="page"]/@src');
            //print "<br/>p=$p";
            if ($p != "") $s = php_hook::call($p, array(":" . $this->name, $s), true);
            php_logger::log("p=$p, s=$s");

            if (!$func) {
                $a  = "";
                $a .= "<span id='" . zobject::new_jsid() . "'>";
                $a .= "<a class='$C' href='" . str_replace("&", "&amp;", $s) . "'>$text</a>";
                $a .= "</span>";
            } else {
                $a  = "";
                $a .= "<span id='" . zobject::new_jsid() . "'>";
                $a .= "<a class='$C' href='javascript:$func'>$text</a>";
                $a .= "</span>";
            }
        // }


        //print $a;
        //die($a);
        php_logger::result($a);
        $D = new DOMDocument;
        $D->loadXML($a);
        return $D;
    }

    function refresh_link($listrow = false) {
        php_logger::call();
        $r = $this->get('args');
        // if ($list) {
            // $r = querystring::add($r, '_Zlistrow', '1');
        // }
        // $r = querystring::add($r, zobject::ZP_PAGE, $this->page);
        // $r = querystring::add($r, zobject::ZP_PAGECOUNT, $this->page_count);
        $r = querystring::add($r, '_ZN', $this->name);
        $r = querystring::add($r, '_ZM', $this->mode);
        if (isset($this->module))
            $r = querystring::add($r, '_Zmod', $this->module);
        if (isset($this->prefix))
            $r = querystring::add($r, '_Zprefix', $this->prefix);
        if (isset($this->named_template))
            $r = querystring::add($r, '_Ztemp', $this->named_template);
        php_logger::dump("r=$r");
    
        return zobject::encode_args($r);
    }

    function MultiAddLink($a, $b)
    {
        php_logger::call();
        return "javascript:document.getElementById('$b').style.display='none';document.getElementById('$a').style.display='';";
    }

    function GetZSource64($mode = "")
    {
        php_logger::call();
        $b = "?" . @$_SERVER['QUERY_STRING'];
        if ($mode != "=") $b = querystring::add($b, "m", $mode);

        $x = @$_SERVER["REDIRECT_URL"];
        if ($x == "" && @$_SERVER['SCRIPT_NAME'] != "/content.php") $x = @$_SERVER['SCRIPT_NAME'];
        $f = $x . $b;
        //print "<br/>ZSource=$f";
        return zobject::encode_args($f);
    }

    function args64()       { return zobject::encode_args($this->args); }
    function get($f)        { return $this->get_var($f); }
    function set($f, $v)    { return $this->options[$f] = $v; }
    function get_var($VarName)
    {
        switch ($VarName) {
            case "login-key":       return "";
            case "uid":             return $this->gid();
            case "name":            return $this->name;
            case "mode":            return $this->mode;
            case "prefix":          return $this->prefix;
            case "page":            return $this->page;
            case "page-count":      return $this->page_count;
            case "args":            return $this->args;
            case "args64":          return $this->args64();
            case "source64":        return $this->GetZSource64();
            case "count":           return $this->record_count;
            case "jsid":            return $this->gid();
            case "named_template":  return $this->named_template;
            default:                return $this->options[$VarName];
        }
    }
}
