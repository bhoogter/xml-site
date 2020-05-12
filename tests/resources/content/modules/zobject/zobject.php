<?php

class zobject
{
    public const ZP_PAGE = 'p';
    public const ZP_PAGECOUNT = 'pp';

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

    function __construct()
    {
        $this->page = "1";
        $this->page_count = "30";

        $this->record_count = "1";

        $this->gid = uniqid("ZO_");
        $this->mRecNo = "";

        $this->named_template = "";

        $n = func_num_args();
        $a = func_get_args();
        $this->name = ($n >= 1 && is_string($a[0]) ? $a[0] : "");
        $this->mode = ($n >= 2 && is_string($a[1]) ? $a[1] : "");
        $this->args = ($n >= 3 && is_string($a[2]) ? $a[2] : "");
        $this->prefix = ($n >= 4 && is_string($a[3]) ? $a[3] : "");
    }

    function transform()
    {
        return realpath(__DIR__ . "/source/transform.xsl");
    }

    function FetchObjPart($n, $p) {
        $p = "//MODULES/modules/module/zobjectdef[@name='$n']/$p";
        // php_logger::trace($n, $p);
        return xml_site::$source->get($p);
    }

    function FetchDTPart($n, $p) {
        $p = "//MODULES/modules/module/zobject[@name='$p']/$n";
        php_logger::trace($n, $p);
        php_logger::trace(xml_site::$source->get("//MODULES/*/module/zobjectdef")); 
        return xml_site::$source->get($p);
    }

    function arg($key)
    {
        if ($x = querystring::get($this->args, $key)) return $x;
        if ($x = $this->result_field($key)) return $x;
        return false;
    }

    public function RecNo($N = "")
    {
        if ($N != "") $this->mRecNo = $N;
        if ($this->mRecNo == "") $this->mRecNo = "1";
        return $this->mRecNo;
    }

    private function load_object()
    {
        $n = $this->name;                 // local copy
        $this->options = array();

        $this->options['name']            = $this->FetchObjPart($n, "@name");
        $this->options['module']            = $this->FetchObjPart($n, "@module");
        php_logger::log("LOAD: " . $this->gid . ", ob=" . $this->options['name'] . ", module=" . $this->options['module']);
        $this->options['type']            = $this->FetchObjPart($n, "@type");
        $this->options['index']            = $this->FetchObjPart($n, "@index");
        $this->options['key-field']            = $this->TranslateKeyList($this->FetchObjPart($n, "@key-field"));
        $this->options['key-field-optional']    = $this->TranslateKeyList($this->FetchObjPart($n, "@key-field-optional"));
        $this->options['keys']            = $this->options['key-field'];

        $this->options['prefix']            = '';

        // ....  onto next line...
        $key_array                          = $this->options['key-array']            = explode(",", $this->options['keys']);
        $this->options['key']               = (count($key_array) == 0) ? "" : $key_array[count($key_array) - 1];
        $this->options['key-array-optional']= explode(",", $this->options['key-field-optional']);
        $this->options['key-array-all']     = array_merge($this->options['key-array'], $this->options['key-array-optional']);

        $this->options['pre-trigger']       = $this->FetchObjPart($n, "@pre-trigger");
        $this->options['post-trigger']      = $this->FetchObjPart($n, "@post-trigger");

        $this->options['allow-display']     = $this->FetchObjPart($n, "@allow-display");
        $this->options['allow-edit']        = $this->FetchObjPart($n, "@allow-edit");
        $this->options['allow-list']        = $this->FetchObjPart($n, "@allow-list");
        $this->options['allow-create']      = $this->FetchObjPart($n, "@allow-create");
        $this->options['allow-delete']      = $this->FetchObjPart($n, "@allow-delete");

        $this->options['allow-style']       = $this->FetchObjPart($n, "@allow-delete");

        $this->options['return']            = $this->FetchObjPart($n, "return");
    }


    function QueryStringSatisfied($ZN, $ZA)
    {
        php_logger::log("QueryStringSatisfied($ZN, $ZA)");

        $kf = $this->options['keys'];
        php_logger::debug("kf=$kf");

        if ($kf == "") return true;
        php_logger::info("QueryStringSatisfied: Checking...");
        foreach (explode(",", $kf) as $k)
            if (xml_site::KeyValue($k, $ZA) == "") return false;
        return true;
    }

    function TranslateZName($Z)
    {
        php_logger::log("TranslateZName($Z)");
        if ($Z == "") $Z = ";zname";
        else if (substr($Z, 0, 1) == ";" && ($f = @$_REQUEST[substr($Z, 1)]) != "") $Z = $f;
        $chk = $this->FetchObjPart($Z, "@name");
        php_logger::debug("Exists: " . $chk ? "YES" : "NO");
        if (!$chk) throw new Exception("[$Z] is not a valid Object.");
        php_logger::log("---");
        $this->module = $this->FetchObjPart($Z, "../@name");
        php_logger::debug("module: $this->module");
        return $this->name = $Z;
    }

    function TranslateZMode($ZN, $ZM, $ZA = "")
    {
        php_logger::log("TranslateZMode($ZN, $ZM, $ZA)");
        if ($ZM == "") $ZM = ";m";
        if ($ZM[0] == ";" && ($f = @$_REQUEST[substr($ZM, 1)]) != "") {
            $ZM = $f;
            $this->named_template = "";
        } else if (strstr($ZM, ";") !== false) {
            $R = explode(";", $ZM);
            php_logger::debug("R=", $R);
            $ZM = $R[0];
            $name = $R[1];
            $this->named_template = $this->FetchObjPart($ZN, "render[@name='$name']/@src");
            php_logger::debug("NT: " . $this->named_template);
        } else if (($nt = $this->FetchObjPart($ZN, "render[@name='$ZM']/@type")) != "") {
            $this->named_template = $this->FetchObjPart($ZN, "render[@name='$ZM']/@src");
            $ZM = $this->FetchObjPart($ZN, "render[@name='$ZM']/@type");
            php_logger::debug("NAMED TEMPLATE: named_template=$this->named_template, ZMode=$ZM");
        }

        $Sat = $this->QueryStringSatisfied($ZN, $ZA);
        php_logger::debug("QSSatisfied($ZN): " . ($Sat ? "Yes" : "No"));

        php_logger::log("ZM=$ZM");
        if ($ZM == "delete") $ZM = "x";
        if ($ZM == "dnposition") $ZM = ">";
        if ($ZM == "upposition") $ZM = "<";
        if ($ZM == "") $ZM = "d";
        switch ($ZM[0]) {
            case "0":            case "d":            case "D":                $ZM = $Sat ? "display" : "find";                break;
            case "1":            case "e":            case "E":                $ZM = $Sat ? "edit" : "find";                break;
            case "*":            case "b":            case "B":                $ZM = "build";                break;
            case "-":            case "c":            case "C":                $ZM = "create";                break;
            case "=":            case "l":            case "L":                if ($ZM != "list-create" && $ZM != "list-edit")                    $ZM = "list";                break;
            case "-":            case "h":            case "H":                $ZM = "list-edit";                break;
            case "+":            case "j":            case "J":                $ZM = "list-create";                break;
            case "^":            case "f":            case "F":                $ZM = "find";                break;
            case '<':                $ZM = 'upposition';                break;            case '>':                $ZM = 'dnposition';                break;
            case "p":                $ZM = 'position';                break;
            case "x":                $ZM = "delete";                break;
            default:                $ZM = ($Sat ? "display" : "list");                break;
        }

        switch ($ZM) {
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
        //			if ($ZM=="delete" && CheckObjectAccess($ZN,$ZM)!="delete") $ZM="none";
        //			if ($ZM=="create" && CheckObjectAccess($ZN,$ZM)!="create") $ZM="none";
        //			if ($ZM=="list-edit" && CheckObjectAccess($ZN,$ZM)!="list-edit") $ZM="list";
        //			if ($ZM=="list" && CheckObjectAccess($ZN,$ZM)!="list") $ZM="none";
        //
        //			if ($ZM=="build" && CheckObjectAccess($ZN,$ZM)!="build") $ZM="display";
        //			if ($ZM=="edit" && CheckObjectAccess($ZN,$ZM)!="edit") $ZM="display";
        //			if (($ZM=="display" || $ZM=="form") && CheckObjectAccess($ZN,$ZM)!="display") $ZM="none";
        //	//log_file("zobject", "<br/>ZM=$ZM");

        if ($ZM == "") $ZM = "display";
        php_logger::log("zmode=$ZM, NT=" . $this->named_template);
        return $this->mode = $ZM;
    }

    function TranslateZArgs($ZName, $ZArgs)
    {
        php_logger::log("TranslateZArgs($ZName, $ZArgs)");
        if ($ZArgs == "") $ZArgs = @$_SERVER["QUERY_STRING"];            //  this should be the ONLY place zobject directly references the query string...
        $ZArgs = xml_site::InterpretFields($ZArgs);
        $ZArgs = str_replace("'", "", $ZArgs);
        $ZArgs = $this->TransferObjectKeys($ZArgs);
        return $this->args = $ZArgs;
    }

    function result_field($f, $rn = "")
    {
        if (!$this->result) return "";
        if ($rn == "") $rn = $this->RecNo();
        return $this->result->fetch_part("//row[$rn]/field[@id='$f']");
    }

    function set_result($D)
    {
        $this->result = new xml_file($D);
    }

    function load_result(&$tform = null)
    {
        php_logger::log("load_result($tform)");
        require_once("zobject-query.php");

        $resultDoc = zobject_query::get_result($this->name, $this->mode, $this->args, $this->record_count, $tform);
        if (!$resultDoc) print "<br/>No zobject::resultDoc in load_result";
        if (!$resultDoc) return false;
        $this->set_result($resultDoc);
        return true;
    }

    function process_arguments(&$vName = "", &$vMode = "", &$vArgs = "", $vPrefix = "", $use_form = true)
    {
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

    function render($params = nil, $vArgs = "")
    {
        php_logger::log("CALL - ", $params, $vArgs);
        $vName = @$params['name'];
        $vMode = @$params['mode'];
        $vPrefix = @$params['prefix'];
        php_logger::debug("XXX=====   zobject::render($vName, $vMode, $vArgs, $vPrefix)   =====XXX");

        if (!$this->process_arguments($vName, $vMode, $vArgs, $vPrefix)) {
            return $this->empty_render();
        }

        php_logger::trace("zobject::render:  name=$vName, mode=$vMode, args=$vArgs, px=$vPrefix, NT= " . $this->named_template);
        switch ($this->options['type']) {
            case "transform":
                $Ix = xml_site::KeyValue("value");
                //print "<br/>Ix=$Ix";
                $r = $this->FetchObjPart($vName, "action[@value='$Ix']");
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

        xml_site::include_support_files($this->options['module']);        // this is what this particular objects has requested..  required before load_result()

        if (!$this->load_result($tform)) {
            php_logger::trace("tform");
            $D = ($tform == "") ? $this->empty_render() : new xml_file($tform);
            return $D;
        }

        //die(juniper()->result()->saveXML());

        //print "<br/>named_template=$this->named_template, FP=".FilePath("t", $this->named_template) . ", Result Len=" . strlen($this->result->saveXML());
        //print "<br/>".$this->args;
        //print "<br/>".$this->arg64();
        //print "<br/>transform: ".$this->transform(); 

        $res = new xml_file(juniper()->resultDoc(), '', $this->transform());
        $zobj = $res->Doc;
        //die($res->saveXML());
        unset($res);

        //		$zobj = xml_file::make_tidy_doc($zobj, "xhtml");

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

        if ($enc) $s = juniper()->encode_args($s);
        return $s;
    }

    function save($vName, $vMode = "", $vArgs = "")
    {
        php_logger::log("CALL $vName, $vMode, $vArgs");
        if (!$this->process_arguments($vName, $vMode, $vArgs, "", true)) {
            return false;
        }

        //print "<br/>ZName=$this->name<br/>ZMode=$this->mode, named_template=$this->named_template<br/>vArgs=$this->args";
        $this->args = juniper()->decode_args($this->args);

        xml_site::include_support_files($this->options['module']);        // this is what this particular objects has requested..  required for save and load

        include_once("class-zobject-query.php");
        zobject_query::save_form();

        //print "<br/>".juniper()->FetchSpecPart($this->options['module'], "program/control[@type='page']/@src");
        $Target = juniper()->php_hook(juniper()->FetchSpecPart($this->options['module'], "program/control[@type='page']/@src"), array("save_object::" . $this->name, "action"), true);
        if (!$Target) $Target = juniper()->php_hook($this->FetchObjPart($this->name, "action"));

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
        //print "<br/>NormalizeInputField($f, $DT)";
        $N = juniper()->FetchDTPart($DT, "@normalize");
        //print "<br/>N=$N";
        $Na = juniper()->php_hook($N, $f);
        if ($Na != $N) $f = $Na;

        if (($k = juniper()->FetchDTPart($DT, "@html-type")) == "") $k = $DT;
        switch ($k) {
            case "wysiwyg":
            case "rtf":
            case "richtext":
                $f = str_replace(array("<div><br></div>", "<br>", "<br/>", "<br />"), array("\n", "\n", "\n", "\n"), $f);
                $f = trim($f);
                break;
        }

        $dbt = juniper()->FetchDTPart($DT, "@db-type");
        if ($dbt == "integer" || $dbt == "float" || $dbt == "currency") {
            if ($f == "Yes" || $f == "yes") $f = 1;
            if ($f == "No" || $f == "no") $f = 0;
            $f = 0 + $f;
        }
        return $f;
    }

    function FormatDataField($f, $DT)
    {
        php_logger::log("FormatDataField($f, $DT)");
        $N = juniper()->FetchDTPart($DT, "@format");
        php_logger::log("br/>N=$N");
        $Na = juniper()->php_hook($N, $f);
        if ($Na != $N) $f = $Na;

        if (($k = juniper()->FetchDTPart($DT, "@html-type")) == "") $k = $DT;
        switch ($k) {
            case "wysiwyg":
            case "rtf":
            case "richtext":
                $f = str_replace(array("\n"), array("<br/>"), $f);
                $f = trim($f);
                break;
        }
        return $f;
    }

    function TranslateKeyList($List, $Prev = "", $KeysOnly = true)
    {
        php_logger::log("TranslateKeyList([$List], $Prev)");
        if ($List == "") return $List;

        $z = "";
        $m = explode(',', $List);
        foreach ($m as $kk) {
            php_logger::debug("kk=$kk");
            if ($kk[0] == ";") {
                $t = $this->FetchObjPart(substr($kk, 1), "@key-field");
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
        return juniper()->php_hook($f);
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
    function TemplateEscapeTokens($s)
    {
        //if (strlen($s)<100)print "<br/>TemplateEscapeTokens($s)";else print "<br/>TemplateEscapeTokens(...)";
        while (($a = strpos($s, "{@")) !== false) {
            $b = strpos($s, "}", $a);
            $c = substr($s, $a + 2, $b - $a - 2);
            $d = xml_site::KeyValue($c, $this->args);
            //print "<br/>test=".xml_site::KeyValue($c, $this->args);

            if ($d == "") $d = DefaultValue(juniper()->FetchObjFieldPart($this->name, $c, "@default"));
            //print "<br/>TemplateEscapeTokens 1: a=$a, b=$b, c=$c, d=$d";
            $s = str_replace("{@" . $c . "}", $d, $s);
        }

        while (($a = strpos($s, "{php:")) !== false) {
            $b = strpos($s, "}", $a);
            $c = substr($s, $a + 5, $b - $a - 5);
            $d = xml_site::KeyValue($c, $this->args);
            $d = juniper()->php_hook("php:$c", $this->args);
            //print "<br/>TemplateEscapeTokens 2: a=$a, b=$b, c=$c, d=$d";
            $s = str_replace("{php:" . $c . "}", $d, $s);
        }
        //print "<br/>s=$s";
        //die($s);
        return $s;
    }

    function GetZobjectAutoTemplate()
    {
        php_logger::log("CALL GetZobjectAutoTemplate");
        //$_a = BenchTime();
        include_once("class-autotemplate.php");

        //print "<br/>".$this->gid().", module=".$this->options['module'];
        $f = juniper()->FetchSpecPart($this->options['module'], "program/control[@type='autotemplate']/@src");
        //print "<br/>GetZobjectAutoTemplate.test=".juniper()->FetchSpecPart($this->options['module'], "program/control[@type='autotemplate']/@src");
        if ($f != "") $f = 'modules/' . $this->options["module"] . "/" . $f;

        $t = zobject_autotemplate::autotemplate($this->name, $this->mode, $f);

        //print "<br><br>".BenchReport($_a, "Auto Template");
        //log_file("zobject_template", $t);
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
        //print "<br/>GetZObjectTemplate($FName, $ZName, $ZMode)";
        $tf = $FName;
        $FName = FilePath("z", $FName);
        //print "<br/>FName=$FName";
        if (!($FName == "") && !file_exists($FName)) {
            Warning("Specified Template File Does Not Exists: $FName, " . getcwd() . "," . realpath($FName), "ZObj::GetZObjectTemplate");
            $FName = "";
        }
        $t = "";
        if ($FName != "" && strlen($t = file_get_contents($FName)) == 0) {
            Warning("Specified Template File is empty or no access: $FName", "ZObj::GetZObjectTemplate");
            $FName = "";
        }
        if ($t != "") {
            $d = new DOMDocument;
            $d->loadXML($t);
            if ($d === false) {
                Warning("Failed to load template: $FName", "ZObj::GetZObjectTemplate");
                $t = "";
            }
        }
        if ($t == "") $d = $this->GetZobjectAutoTemplate();
        //print "<br/>GetZObjectTemplate.test=".get_class($t);
        return $d;
    }

    ///////////////////////////////////////////////////////////////////////////


    function TransferSourceKeys($List, $HREF)
    {
        //print "<br/>TransferSourceKeys($List, $HREF)";
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
            //			$X = $X . ($N>0?"&":"") . "$tf=$tv";
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
        php_logger("TransferFields($List, $HREF)");
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
        php_logger::log("CALL GetZobjectSQL(<b><u>$ZName</u></b>, '<u>$type</u>')");
        $sl = $this->FetchObjPart($ZName, "sql[@type='$type']");
        //print "<br/>GetZobjectSQL: $sl";
        return $sl;
    }

    function BuildZObjectQuery($ZName, $ZMode, $Args)
    {
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

        foreach (juniper()->FetchObjFields($ZName) as $ckf) {
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

                        $datatype = juniper()->FetchObjFieldPart($ZName, $ckf, "@datatype");
                        $deff = juniper()->FetchObjFieldPart($ZName, $ckf, "@default");
                        if ($deff == "") $deff = FetchDTPart($datatype, "@default");
                        //print "<br>@default=".FetchDTPart($datatype, "@default");
                        if ($v == "") $v = juniper()->php_hook($deff);

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
            $s = str_replace($kp, xml_site::KeyValue($kf, $Args), $s);
        }

        //die("<br/>sql=".$s);
        return $s;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////

    function TransferObjectKeys($Args)
    {
        php_logger::log("CALL TransferObjectKeys($Args), L=", $this->options['key-array-all']);
        $l = $this->options['key-array-all'];
        $l[] = zobject::ZP_PAGE;
        $l[] = zobject::ZP_PAGECOUNT;
        foreach ($l as $m) if ($m != "" && $m[0] != '#') {
            //print "<br/>TransferObjectKeys: m=$m";
            $Args = querystring::add($Args, $m, xml_site::KeyValue($m, $Args));
        }
        //print "<br/>TransferObjectKeys: $Args";
        return $Args;
    }

    function FillInQueryStringKeys($m, $ZArgs = "", $dolast = true)
    {
        //print "<br/>FillInQueryStringKeys($m, $ZArgs, $dolast), L=".$this->options['key'].",".$this->options['key-field-optional'];
        $k = $this->options['key'];
        //print "<br/>FillInQueryStringKeys field=".implode(",",$this->options['key-array-all']);
        foreach ($this->options['key-array-all'] as $l)
            if ($l != "" && ($dolast || (!$dolast && $l != $k))) {
                $m = str_replace("@" . $l, xml_site::KeyValue($l, $ZArgs), $m);
                //print "<br/>FillInQueryStringKeys <b>loop</b> l=$l - $m";
            }
        //print "<br/>FillInQueryStringKeys: $m";
        return $m;
    }

    function TransferQueryStringKeys($List, $HREF)
    {
        //print "<br/>TransferQueryStringKeys($List, $HREF)";
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

    function FormID()
    {
        return juniper()->AJAX ? "ajax-form" : "F" . $this->gid();
    }

    function FormAction($FormID = "", $Args = "0")
    {
        //print "<br/>FormAction($FormID, $Args), ajax=".(juniper()->AJAX?"Yes":"No");
        if (juniper()->AJAX) return juniper()->ajaxURL('save-zobject') . "?_AJAX=1&_Save=1";
        $r = "";
        //		$r = juniper()->php_hook($this->FetchObjPart($this->name, "action"));
        if ($r == "") $r = CurrentPage();
        return $r;
    }

    function LinkArgs($Mode, $TN, $Args)
    {
        //print "<br/>LinkArgs($Mode, $TN, $Args)";
        $key = "@" . $this->options["key"];

        if ($key != "") {
            if (in_array($Mode, array("display", "edit", "delete", "position", "upposition" . "dnposition"))) {
                $id = $this->options['index'];
                $kv = $this->result_field($id);
                //print "<br/>KEY: key=$key, id=$id, kv=$kv";
                $Args = querystring::add($Args, substr($key, 1), $kv);
            }
            //			else $Args = querystring::remove_querystring_var($Args, substr($key,1));
        }
        //print "<br/>zobject::LinkArgs - Args=$Args";
        return $Args;
    }

    function ItemLink($field, $mode = "create", $text = "", $ajax = "", $C = "", $T = "")
    {
        //print "<br/>ItemLink($field, $mode, $text, $ajax, $C, $T)";
        //print "<br/>gid=$this->gid(), args=$this->args";

        if (($TN = $this->name) == "") return "";
        //print "<br/>name=$TN";

        if ($C == "") $C = "ItemLink";

        if ($field != "") {
            $ZName = juniper()->FetchObjFieldPart($TN, $field, "@datatype");
            if ($ZName[0] == ":") $ZName = substr($ZName, 1);
            //print "<br/>ZName=$ZName";
            if ($ZName == "") return "";
            $TN = $ZName;
            //print "<br/>TN=$TN";
        }
        if ($text == "") switch ($mode) {
            case "display":
                $text = "@";
                break;
            case "create":
                $text = "*";
                break;
            case "edit":
                $text = "#";
                break;
            case "delete":
                $text = "X";
                break;
            case "position":
                $text = "Pos";
                break;
            case "upposition":
                $text = "-";
                break;
            case "dnposition":
                $text = "+";
                break;
            default:
                $text = "[??? mode]";
                break;
        }
        $text = juniper()->InterpretFields($text);
        if ($text == "") $text = "[???]";

        //print "<br/>args: $this->args";
        $Args = $this->TransferObjectKeys($this->args);
        //print "<br/>Item Args=$Args";
        $Args = $this->LinkArgs($mode, $TN, $Args);
        //print "<br/>Item Args=$Args";

        $tid = $this->gid();
        $url = juniper()->ajaxURL();
        $params = "{ '_AJAX' : 1, '_Save' : 1, '_ZA' : '" . $this->arg64() . "' }";

        if ($ajax != "") {
            $Args = querystring::add($Args, '_ZN', $this->name);
            $Args = querystring::add($Args, '_ZM', ($T == "") ? $mode : "$mode;$T");
            $Args64 = juniper()->encode_args($Args);
            //print "<br/>Args=$Args";
            //print "<br/>source=".$this->source(false);
            //print "<br/>Args64=$Args64";

            $gid = $this->gid();
            $src = $this->source();

            switch ($mode) {
                case "display":
                    $title = "Show Item";
                    $s = "zoGetObjToDialog('$Args64','$gid', '$src');";
                    break;
                case "create":
                    $title = "Add Item";
                    $s = "zoGetObjToDialog('$Args64','$gid', '$src');";
                    break;
                case "edit":
                    $title = "Edit Item";
                    $s = "zoGetObjToDialog('$Args64','$gid', '$src');";
                    break;
                case "delete":
                    $title = "Delete Item";
                    $s = "zoModalConfirmItem('Really Delete?','$Args64','$gid', '$src' );";
                    break;
                case "position":
                    $title = "Move Item";
                    $s = "AdjustRow('$Args64', '1');";
                    $s2 = "AdjustRow('$Args64', '-1');";
                    $s = "$('#$tid').load('$url', $params)";
                    $s2 = "$('#$tid').load('$url', $params)";
                    break;
                case "upposition":
                    $title = "Move Item Up";
                    $s = "zoExecuteToItem('$Args64','$gid', '$src');";
                    break;
                case "dnposition":
                    $title = "Move Item Down";
                    $s = "zoExecuteToItem('$Args64','$gid', '$src');";
                    break;
                default:
                    $s = "";
                    break;
            }
            $a = "";
            if ($mode != "position") {
                //if ($mode="add") print "<br/>ITEM LINK ADD Args=$Args";
                $a = $a . "<span id='" . NewJSID() . "'>";
                $a = $a . "<a title='$title' class='$C $mode' onClick=\"$s\">$text</a>";
                $a = $a . "</span>";
            } else {
                $a = $a . "<span id='" . NewJSID() . "'>";
                $a = $a . "<a class='$C $mode up' title='Move Up' onClick=\"$s\" style=\"font-family:helvetica\">&#9660;</a>";
                $a = $a . " / ";
                $a = $a . "<a class='$C $mode down' title='Move Up' onClick=\"$s2\" style=\"font-family:helvetica\">&#9650;</a>";
                $a = $a . "</span>";
            }
        } else {
            $s = $Args;
            $s = querystring::add($s, '_ZN', $this->name);
            $s = querystring::add($s, '_ZM', $this->mode);

            switch ($mode) {
                case "display":                    $s = querystring::add($s, 'display', $this->name);                    break;
                case "create":                    $s = querystring::add($s, 'add', $this->name);                    break;
                case "edit":                    $s = querystring::add($s, 'edit', '1');                    break;
                case "delete":                    $s = querystring::add($s, 'delete', '1');                    break;
                case "position":                    $s = querystring::add($s, 'pos', '1');                    break;
                case "upposition":                    $s = querystring::add($s, 'pos', '1');                    break;
                case "dnposition":                    $s = querystring::add($s, 'pos', '1');                    break;
                default:                    $s = "";                    break;
            }

            $p = juniper()->FetchSpecPart($this->options['module'], 'program/control[@type="page"]/@src');
            //print "<br/>p=$p";
            if ($p != "") $s = juniper()->php_hook($p, array(":" . $this->name, $s), true);
            //print "<br/>p=$p, s=$s";

            $a  = "";
            $a .= "<span id='" . NewJSID() . "'>";
            $a .= "<a class='$C' href='" . str_replace("&", "&amp;", $s) . "'>$text</a>";
            $a .= "</span>";
        }


        //print $a;
        //die($a);
        $D = new DOMDocument;
        $D->loadXML($a);
        return $D;
    }

    function MultiAddLink($a, $b)
    {
        //print "<br/>MultiAddLink($a, $b)";
        return "javascript:document.getElementById('$b').style.display='none';document.getElementById('$a').style.display='';";
    }

    function GetZSource64($mode = "")
    {
        $b = "?" . @$_SERVER['QUERY_STRING'];
        if ($mode != "=") $b = querystring::add($b, "m", $mode);

        $x = @$_SERVER["REDIRECT_URL"];
        if ($x == "" && @$_SERVER['SCRIPT_NAME'] != "/content.php") $x = @$_SERVER['SCRIPT_NAME'];
        $f = $x . $b;
        //print "<br/>ZSource=$f";
        return juniper()->encode_args($f);
    }


    public function transform_var($VarName)
    {
        //print "<br/>transform_var($VarName)";
        switch ($VarName) {
            case "login-key":
                return "";
            case "uid":
                return $this->gid();
            case "name":
                return $this->name;
            case "mode":
                return $this->mode;
            case "prefix":
                return $this->prefix;
            case "page":
                return $this->page;
            case "page-count":
                return $this->page_count;
            case "args":
                return $this->args;
            case "args64":
                return $this->arg64();
            case "count":
                return $this->record_count;
            case "jsid":
                return $this->gid();
        }
    }
}        // CLASS: zobject


//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////



function class_zobject_test()
{
    date_default_timezone_set('America/New_York');
    include_once('module_test.php');

    include_once("class-source.php");
    if (@$_REQUEST["_Save"] != "") return class_zobject_save_test();


    zobject_test_header("ZOBJECT");

    $x = 1;

    if ($x == 1) {
        $Z = new zobject("EventSource", "", "");

        $testname = "Create Object";
        $testresult = $Z->name;
        $testexpect = "EventSource";
        $testok = ($testresult == $testexpect);
        zobject_test_result($testname, $testresult, $testok, $A);

        $testname = "Render Object";
        $testresult = "" . $Z;
        $testok = $testresult != "";
        zobject_test_result($testname, $testresult, $testok, $A);
    } else if ($x == 2) {
        print new zobject("y_zobject_field", "", "");
    }


    zobject_test_footer();
}

function class_zobject_save_test()
{
    zobject_test_header("ZOBJECT");
    juniper()->save();
    zobject_test_footer();
}
