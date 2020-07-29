<?php

class zobject_query
{
    private const EXT = "";

    static function recordset_header($ZName = '', $ZMode = '', $rc = 0, $ixf = "", $wxml = true, $empty = false)
    { 
        return "<?xml version='1.0' ?>\n<recordset" . ($ZName == '' ? '' : " zname='$ZName'") . ($ZMode == '' ? '' : " zmode='$ZMode'") . ($ixf == '' ? '' : " ixf='$ixf'") . " count='$rc' " . ($empty ? "/" : "") . ">\n"; 
    }

    static function empty_recordset($ZName = '', $ZMode = '', &$rc = 0)
    {
        $rc = 0;
        $D = new DOMDocument;
        $D->loadXML(self::recordset_header($ZName, $ZMode, $rc, "", true, true));
        return $D;
    }

    static function data_mode($ZName)
    {
        php_logger::call();
        if (zobject::FetchObjPart($ZName, "sql/@src") == "wpdb") return "wpdb";
        if (zobject::FetchObjPart($ZName, "sql/@type") == "mysql") return "mysql";
        if (zobject::FetchObjPart($ZName, "xmlfile/@src") != "") return "xml";
        if (zobject::FetchObjPart($ZName, "phpsource/@item") != "") return "php";
        if (zobject::FetchObjPart($ZName, "wpoptions/@prefix") != "") return "wpo";
        return "";
    }

    static function get_result($ZName, $ZMode, $ZArgs, &$rc = 0, &$tform = 0)
    {
        $data_mode = self::data_mode($ZName);
        php_logger::call("data_mode=$data_mode");

        switch ($ZMode) {
            case "list":
            case "list-edit":
                switch ($data_mode) {
                    case "wpdb": return self::GetZObjectMultiQuery($ZName, $ZMode, $ZArgs, $ZKey, $prefix, $rc);
                    case "mysql": return self::GetZObjectMultiQuery($ZName, $ZMode, $ZArgs, $ZKey, $prefix, $rc);
                    case "xml": return self::GetZObjectMultiXmlFile($ZName, $ZMode, $ZArgs, $rc);
                    case "php": return self::GetZObjectMultiPHP($ZName, $ZMode, $ZArgs, $rc);
                    default: return self::empty_recordset($ZName, $ZMode, $rc);
                }

            case "create":
            case "list-create":
                $ixf = zobject::FetchObjPart($ZName, "@key");
                $Index = zobject::KeyValue($ixf);
                php_logger::debug("ixf=$ixf, Index=$Index");
                return self::GetZObjectCreateQuery($Index, $ZName, $ZMode, $ZArgs, zobject::iOBJ()->options['key'], zobject::iOBJ()->options['prefix'], $rc);
                break;


            case "edit":
            case "display":
            case "find":
            case "build":
            case "data":
                if ($data_mode == "") return self::empty_recordset($ZName, $ZMode, $rc);

                $ZKey = zobject::iOBJ()->get('key');
                $r = zobject::iOBJ()->get('key-array');
                // php_logger::dump("iOBJ options: ", zobject::iOBJ()->options);
                php_logger::debug("CHECKING ARGS: Zmode=$ZMode, key=$ZKey, count(keys)=" . count($r));
                php_logger::trace($r);
                //				if (is_array($r))
                    {
                    $emptycount = 0;
                    foreach (array_values($r) as $l) if ($l != '' && zobject::KeyValue($l) == "") $emptycount = $emptycount + 1;
                    if ($emptycount > 0 || $ZMode == "find") {
                        php_logger::log("=== Building tForm ===");
                        $tform = "<form name='GetKey' method='GET'>\n";
                        foreach ($r as $zk) $tform = $tform . "$zk: <input name='$zk' value='" . zobject::KeyValue($zk) . "'/><br/>\n";
                        $tform = $tform . "<input type='submit' value='" . ($ZMode == "edit" ? "Edit" : "Show") . "'/>\n";
                        $tform = $tform . "</form>\n";
                        return null;
                    }
                    php_logger::log("sql: " . zobject::FetchObjPart($ZName, "sql"));
                }

                if ($ZMode == "build") {
                    php_logger::log("build zmode: $ZMode");
                    if ($rc != 0) $ZMode = "edit";
                    else {
                        $ZMode = "create";
                        return self::GetZObjectCreateQuery($Index, $ZName, $ZMode, $ZArgs, $ZKey, $prefix, $rc);
                    }
                }
                
                php_logger::log("finishing result");
                switch ($data_mode) {
                    case "wpdb": return self::GetZObjectQuery($ZName, $ZMode, $ZArgs, $ZKey, $Ix, $prefix, $rc);
                    case "xml": return self::GetZObjectXmlFile($ZName, $ZMode, $ZArgs, $rc);
                    case "php": return self::GetZObjectPHP($ZName, $ZMode, $ZArgs, '', $rc);
                    case "wpo": return self::GetZObjectWPOQuery($ZName, $ZMode, $ZArgs, $rc);
                    default: return self::empty_recordset($ZName, $ZMode, $rc);
                }

                break;
            case "delete":
                return "Deletion not working...  try save mode";
            default:
                return "Unknown mode: $ZMode";
        }
    }

    static function save_log($s)
    {
        php_logger::call();
    }

    static function invoke_save_trigger($s = 'post')
    {
        php_logger::call();
        if (zobject::iOBJ()->options["$s-trigger"] != "") php_hook::call("php:" . zobject::iOBJ()->options["$s-trigger"]);
    }

    static function save_form()
    {
        php_logger::call();
        // _ZN, _ZM, _ZA, _ZA64, _ZS, _ZL
        $o = zobject::iOBJ();            // zobject
        if ($o->args == "") php_logger::warning("No Args at all");

        //self::save_log("ZName=$o->name\n<br/>ZMode=$o->mode\n<br/>Args=$o->args, REQ:", $_REQUEST);
        //die();

        if ($o->options['type'] == "querybuilder") {
            $n = 1;
            $q = "";
            foreach (zobject::FetchObjFields($o->name) as $f) {
                $v = urlencode($_REQUEST[$id]);
                //self::save_log("id=$id, f=$f, v=$v");
                if ($v != "") $q = (!strlen($q) ? "?" : ($q . "&")) . "$f=$v";
                $n = $n + 1;
            }
            $r = $o->options['return'] . self::EXT . $q;
            //self::save_log("querybuilderresult=$r");
            return $r;
        }


        self::invoke_save_trigger('pre');

        $data_mode = self::data_mode($o->name);
        if ($o->mode != "pos" && $o->mode != "upposition" && $o->mode != "dnposition")
            $v = self::pre_save($o->name, $o->mode);


        php_logger::note("ZName=$o->name, ZMode=$o->mode, Args=$o->args, datamode=$data_mode");
        switch ($o->mode) {
            case "delete":
                switch ($data_mode) {
                    case "xml":
                        $f = self::GetXMLFile($o->name, $o->args, $lst, $bse, $d);
                        php_logger::debug("Deleting from XML: $f");
                        if (!$f) {
                            $f = php_hook::call($d);
                            if (is_string($f)) $f = xml_site::$source->force_unknown_document($f);
                        }
                        $bse = $o->FillInQueryStringKeys($bse, '', true);
                        php_logger::debug("b=$b");
                        $f->delete_node($bse);
                        break;
                    case "wpdb": self::SaveZObjectQuery($o->name, "delete", $o->args, $v); break;
                    case "mysql": self::SaveZObjectQuery($o->name, "delete", $o->args, $v); break;
                    case "php": self::SaveZObjectToPHP($o->name, $o->mode, $v); break;
                    case "wpo": self::SaveZObjectToWPO($o->name, $o->mode, $v); break;
                    default: break;
                }
                break;
            case "edit":
                switch ($data_mode) {
                    case "xml": self::SaveZObjectToXMLFile($o->name, $o->mode, $v); break; 
                    case "wpdb": self::SaveZObjectQuery($o->name, "edit", $o->args, $v); break;
                    case "mysql": self::SaveZObjectQuery($o->name, "edit", $o->args, $v); break;
                    case "php": self::SaveZObjectToPHP($o->name, $o->mode, $v); break;
                    case "wpo": self::SaveZObjectToWPO($o->name, $o->mode, $v); break;
                    default:
                        break;
                }
                break;
            case "pos":
            case "dnposition":
            case "upposition":
                switch ($data_mode)        //  position adjust
                {
                    case "xml":
                        if ($o->mode == "position")    $ZL = @$_REQUEST["_ZL"];
                        else if ($o->mode == "dnposition") $ZL = 1;
                        else if ($o->mode == "upposition") $ZL = -1;
                        $f = self::GetXMLFile($o->name, $o->args, $lst, $bse, $d);
                        if (!$f) {
                            $f = php_hook::call($d);
                            if (is_string($f)) $f = xml_site::$source->force_unknown_document($f);
                        }
                        $bse = $o->FillInQueryStringKeys($bse, '', true);
                        //self::save_log("Adjust Position, bse=$bse, l=".$ZL);
                        $f->adjust_part($bse, $ZL);        // will be saved later, automatically
                        //die();
                        break;
                    case "php":
                        $v = self::pre_save($o->name, $o->mode);
                        self::SaveZObjectToPHP($o->name, $o->mode, $v);
                        break;
                    case "wpdb": break;        // can't do positioning on SQL elements
                    case "mysql": break;
                    case "wpo":
                    default: break;    // Can't do positioning here either
                }
                break;
            case "create":
                switch ($data_mode)        //  position adjust
                {
                    case "xml":     self::SaveZObjectToXMLFile($o->name, $o->mode, $v);                 break;
                    case "wpdb":    self::SaveZObjectQuery($o->name, "create", $o->args, $v, $zKey);    break;
                    case "mysql":   self::SaveZObjectQuery($o->name, "create", $o->args, $v, $zKey);    break;
                    case "php":     self::SaveZObjectToPHP($o->name, $o->mode, $v);                     break;
                    case "wpo":     self::SaveZObjectToWPO($o->name, $o->mode, $v);                     break;
                    default:        break;
                }
                break;
            default:
                break;    // unrecognized mode for saving....
        }        // end switch on ZMode


        self::invoke_save_trigger('post');

        return true;
    }


    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////



    static function MakePOSTValueReady($key, $data_type, $n = 0, $Target = "SQL")
    {
        php_logger::call();

        if ($n > 0 && is_array(@$_REQUEST[$key]) && count(@$_REQUEST[$key]) >= $n)
            $v = @$_REQUEST[$key][$n - 1];
        else
            $v = @$_REQUEST[$key];
        php_logger::debug("v=$v");
        if (is_array($v)) $v = implode(",", $v);
        
        $v = str_replace(array("\\'", "\\\"", "\\\\"), array("'", "\"", "\\"), $v);
        php_logger::debug("v=$v");
        
        if ($data_type != "") $dfD = php_hook::call(zobject::FetchDTPart($data_type, "@default"));
        php_logger::debug("dfD=$dfD");
        //		if ($v == "" && $dfD != "") $v = DFV($dfD);
        
        $v = zobject::iOBJ()->NormalizeInputField($v, $data_type);
        php_logger::debug("v=$v");

        if ($Target == "SQL") $v = SVF($v, $data_type);
        php_logger::result($v);
        return $v;
    }

    private static function pre_save($ZName, $ZMode)
    {
        php_logger::call();
        //die();
        $o = zobject::iOBJ();                            // zobject
        $v = array();
        $v['_ZName'] = $ZName;
        $v['_ZMode'] = $ZMode;

        $px = $o->options['prefix'];


        $nkv = zobject::KeyValue($ix = $o->options['index']);
        if ($ix != "" && $nkv == "") {
            $def = zobject::FetchObjFieldPart($ZName, $ix, "@default");
            php_logger::trace("def=$def");
            $nkv = zobject::iOBJ()->NormalizeInputField(php_hook::call($def), zobject::FetchObjFieldPart($ZName, $ix, "@datatype"));
        }

        if ($ZMode == "delete") {
            $v[$ix] = $o->arg($o->options['key']);
            php_logger::trace("pre_save delete result (" . $o->options['key'] . "): ", r);
            return $v;
        }

        $found = false;
        foreach (zobject::FetchObjFields($ZName) as $fid) {
            php_logger::trace("FID=$fid");
            if (!zobject_access::access($ZName, $fid, $ZMode)) continue;

            $dt = zobject::FetchObjFieldPart($ZName, $fid, "@datatype");
            if ($dt == "") $dt = "string";
            php_logger::trace("dt=$dt");
            $m = 0;

            while (true) {
                php_logger::trace("m=$m");
                if ($dt[0] == ':') {
                    //  sub zobjects would result in a full list-edit, which we're avoiding..
                    //					$pfx = GetSubPrefix($ZName, $px);
                    //					$res = SaveZObjectToXMLFile($D, substr($dt, 1), $ZMode, $ZArgs, $pfx);
                } else {
                    $mult = zobject::YesNoVal(zobject::FetchObjFieldPart($ZName, $fid, "@multiple"), false);
                    if ($mult) {
                        php_logger::debug("Multi-Field Set: $fid");
                        $v[$fid] = array();
                        $n = 0;
                        $m = 0;
                        $deleted = 0;
                        while ($m < 25) {
                            $n++;
                            $tfix = $px . $fid . "___" . $n;
                            $r = $o->arg($tfix);
                            if ($r != "") $m = 0;                            // basically, try 25 after last sequential.. then stop looking
                            $val = self::MakePOSTValueReady($tfix, $dt, $o->mRecNo, "XML");
                            php_logger::debug("tfix=$tfix, dt=$dt, r=$r,	-----------------> multivalue ===> $val");
                            $v[$fid][] = $val;
                        }
                    } else {
                        $tfix = $px . $fid;
                        php_logger::debug("tfix=$tfix, is_array(tfix)=" . zobject::TrueFalse(is_array($o->arg($tfix))) . ", COUNT=" . count($o->arg($tfix)));

                        if ((is_array($o->arg($tfix)) && $o->mRecNo > count($o->arg($tfix)))) {
                            php_logger::debug("Returning False");
                            return false;
                        }
                        $arg_tfix = $o->arg($tfix);
                        if (is_array($arg_tfix) && count($o->arg($tfix)) != 0) $found = true;
                        if ($fid == $ix)
                            $val = $nkv;
                        else
                            $val = self::MakePOSTValueReady($tfix, $dt, $o->mRecNo, "XML");
                        php_logger::trace("tfix=$tfix, dt=$dt, r=$r,	-----------------> value ===> $val");
                        $v[$fid] = $val;
                    }
                }
                if (($m++) == 0 || !$res) break;
            }
        }
        $v = self::pre_save_result($v);
        php_logger::result($v);
        return $v;
    }

    static function pre_save_result($v)
    {
        php_logger::call();
        $s  = self::recordset_header(zobject::iOBJ()->name, zobject::iOBJ()->mode, 1);
        $s .= "<row>\n";
        foreach ($v as $a => $b)
            if (is_array($b))
                $s .= "<field id='$a'><![CDATA[" . join(",", $b) . "]]></field>\n";
            else
                $s .= "<field id='$a'><![CDATA[" . $b . "]]></field>\n";
        $s .= "</row>\n";
        $s .= "</recordset>\n";


        //	die($s);
        $D = new DOMDocument;
        $D->loadXML($s);
        zobject::iOBJ()->set_result($D);

        return $v;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////

    static function GetMultiValuesFromDoc_Map($i)
    {
        return "'" . str_replace("'", "''", $i) . "'";
    }
    static function GetMultiValuesFromDoc($D, $p)
    {
        php_logger::call();
        $r = FetchDocList($D, $p);
        $r = array_values(array_map("GetMultiValuesFromDoc_Map", $r));
        return implode(",", $r);
        return $r;
    }


    static function GetZObjectEmptyQuery($Index, $ZName, $ZMode, $ZArgs, $Key, $prefix)
    {
        php_logger::call();
        $ixf = zobject::FetchObjPart($ZName, "@index");

        if ($ixf == "")
            $X = self::recordset_header($ZName, $ZMode, 1, $ixf, true, true);
        else
            $X = self::recordset_header($ZName, $ZMode, 1, $ixf, true, false) . "<row><field id='$ixf'><![CDATA[" . $Index . "]]></field></row></recordset>";
        //if($ZName=="y_pagedef_content")die($X);

        $D = new DOMDocument;
        $D->loadXML($X);
        return $D;
    }

    static function GetZObjectCreateQuery($Index, $ZName, $ZMode, $ZArgs, $Key, $prefix, &$rc)
    {
        php_logger::call();
        $ixf = zobject::FetchObjPart($ZName, "@index");
        php_logger::log("ixf=$ixf");


        $X = self::recordset_header($ZName, $ZMode, 1, "") . "\n<row>";
        foreach (zobject::FetchObjFields($ZName) as $l) {
            php_logger::trace("l=$l, ZName=$ZName, ixf=$ixf, Key=" . zobject::iOBJ()->options['key']);

            $v = "";
            if ($l == $ixf) $v = querystring::get($ZArgs, zobject::iOBJ()->options['key']);
            if ($v == "") $v = php_hook::call(zobject::FetchObjFieldPart($ZName, $l, "@default"), $ZArgs);
            if ($v == "") $v = php_hook::call(zobject::FetchDTPart(zobject::FetchObjFieldPart($ZName, $l, "@datatype"), "@default"), $ZArgs);
            php_logger::trace("v=$v");
            $X = $X . "<field id='$l'><![CDATA[$v]]></field>";
        }

        $X .= "</row></recordset>";
        php_logger::dump("create xml: ", $X);

        $D = new DOMDocument;
        $D->loadXML($X);
        return $D;
    }



    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////

    static function GetZObjectSQL($ZName, $ZMode, $ZArgs)
    {
        php_logger::call();
        $sql = zobject::FetchObjPart($ZName, "sql[@type='$ZMode']");
        if ($sql == "") print "<br/>No SQL for requested operation: $ZMode";
        //print "<br/>sql[@type='$ZMode']: $sql";
        //		if ($sql=="") $sql = zobject::FetchObjPart($ZName, "sql");
        //print "<br/>GetZObjectSQL($ZName, $ZMode, $ZArgs): $sql";
        return $sql;
    }

    static function SaveZObjectQuery($ZName, $ZMode, $ZArgs, $v, $new_key = "")
    {
        php_logger::call();

        switch (self::data_mode($ZName)) {
            case "wpdb":
                include_once("class-zobject-db-wpdb.php");
                $o = new zobject_db_wpdb();
                break;
            case "mysql":
                include_once("class-zobject-db-mysql.php");
                $o = new zobject_db_mysql();
                break;
        }

        switch ($ZMode) {
            case "delete":
                $tmode = "delete";
                break;
            case "create":
                $tmode = "insert";
                break;
            case "edit":
                $tmode = "update";
                break;
            default:
                $tmode = "";
                break;
        }

        $sql = self::GetZObjectSQL($ZName, $tmode, $ZArgs);
        //print "<br/>sql=$sql";
        $sql = zobject_db::InterpretInteractiveSQL($sql, $ZArgs);
        if ($ZMode == "create" || $ZMode == "edit") $sql = zobject_db::BuildZObjectQuery($sql, $v, self::data_mode($ZName));

        $sql = $o->prepare_sql($sql);
        //print "<br/>datamode=". self::data_mode($ZName). ", sql=$sql";		
        $o->execute($sql, '', $rc);

        return true;
    }

    static function GetZObjectQuery($ZName, $ZMode, $ZArgs, $Key = "", $Ix = "", $prefix = "", $rc = "")
    {
        php_logger::call();

        $Extras = "zname='$ZName' zmode='$ZMode' ixf='$ixf'";

        switch (self::data_mode($ZName)) {
            case "wpdb":
                include_once("class-zobject-db-wpdb.php");
                $o = new zobject_db_wpdb();
                break;
            case "mysql":
                include_once("class-zobject-db-mysql.php");
                $o = new zobject_db_mysql();
                break;
        }

        switch ($ZMode) {
            case "edit":
            case "display":
                $tmode = "select";
                break;
            case "list":
                $tmode = "list";
                break;
            default:
                $tmode = "";
                break;
        }

        $sql = self::GetZObjectSQL($ZName, $tmode, $ZArgs);
        //print "<br/>sql=$sql";
        $sql = zobject_db::InterpretInteractiveSQL($sql, $ZArgs);
        //		$sql = zobject_db::BuildZObjectQuery($sql, $v);

        $sql = $o->prepare_sql($sql);
        //print "<br/>sql=$sql";
        $X = $o->execute_to_xml($sql, $Extras, $rc);
        //die($X);
        //self::save_log("zobject", $X);
        $D = new DOMDocument;
        $D->loadXML($X);
        return $D;
    }

    static function GetZObjectMultiQuery($ZName, $ZMode, $ZArgs, $Key, $prefix, &$rc)
    {
        php_logger::call();
        $S1 = zobject::FetchObjPart($ZName, "sql[@type='$ZMode']");
        $S2 = zobject::FetchObjPart($ZName, "sql[@type='list']");
        $S3 = zobject::FetchObjPart($ZName, "sql");
        $ActualSQL = ChooseBest($S1, $S2, $S3); //FetchObjPart($ZName, "sql");
        //print "<br/>Multi-ActualSQL=$ActualSQL";

        switch (self::data_mode($ZName)) {
            case "wpdb":
                include_once("class-zobject-db-wpdb.php");
                $o = new zobject_db_wpdb();
                break;
            case "mysql":
                include_once("class-zobject-db-mysql.php");
                $o = new zobject_db_mysql();
                break;
        }

        $Extras = "zname='$ZName' zmode='$ZMode' ixf='$ixf'";

        $ActualSQL = zobject_db::InterpretInteractiveSQL($ActualSQL, $ZArgs);
        $X = $o->execute_to_xml($ActualSQL, $Extras, $rc);
        unset($o);
        //print $X;die();

        $D = new DOMDocument;
        $D->loadXML($X);
        return $D;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////

    static function GetXMLFile($ZName, $ZArgs, &$Lst = "", &$Bse = "", &$d = "")
    {
        php_logger::call();

        $id = zobject::FetchObjPart($ZName, 'xmlfile/@src');
        php_logger::debug("id=$id");
        if (php_hook::is_hook($id)) {
            php_logger::debug("HOOK");
            $Lst = zobject::FetchObjPart($ZName, 'xmlfile/@list');
            $Bse = zobject::FetchObjPart($ZName, 'xmlfile/@base');
        } else {
            if (strstr($id, ".xml") !== false) // specified xml file
            {
                $d = $id;
                $Lst = zobject::FetchObjPart($ZName, 'xmlfile/@list');
                $Bse = zobject::FetchObjPart($ZName, 'xmlfile/@base');
            } else                    // prob id
            {
                $d = zobject::FetchDSPart($id, '@src');
                $Lst = zobject::FetchObjPart($ZName, 'xmlfile/@list');
                if ($Lst == '') $Lst = zobject::FetchDSPart($id, '@list');
                $Bse = zobject::FetchObjPart($ZName, 'xmlfile/@base');
                if ($Bse == '') $Bse = zobject::FetchDSPart($id, '@base');
                $M = zobject::FetchDSPart($id, '@module');
                // if (file_exists(WP_PLUGIN_DIR . "/zobjects/modules/$M/$d")) $d = WP_PLUGIN_DIR . "/zobjects/modules/$M/$d";
                php_logger::debug("DATASOURCE: ", $d, $Lst, $Bse, $M);
            }
        }

        $Lst = php_hook::call($Lst, $ZArgs);
        if ($Bse[strlen($Bse) - 1] != '/') $Bse = $Bse . "/";

        php_logger::debug("src=$id, lst=$Lst, Bse=$Bse");
        if ($Lst == "") throw new Exception("No listpath for $ZName. (OBJFILE::/zobjectdefs/zobjectdef[@id='$ZName']/xmlfile/@list");
        if ($Bse == "") throw new Exception("No basepath for $ZName. (OBJFILE::/zobjectdefs/zobjectdef[@id='$ZName']/xmlfile/@base");

        if (xml_site::$source->source_exists($id)) return xml_site::$source->get_source($id);

        //		if (!file_exists($d)) $d = ZOSOURCE_DIR . $d;

        php_logger::debug("query-xmlfile id=$id, d=$d");
        if (!php_hook::is_hook($id)) return xml_site::$source->force_document($id, $d);

        $d = $id;
        php_logger::result("RESOLVED TO HOOK: ", "(byref)", $d);
        return null;
    }

    static function GetXMLAutoNumber()
    {
        php_logger::call("FIX ME");
        $D = self::GetXMLFile(zobject::iOBJ()->name, zobject::iOBJ()->args, $L);
        $L = zobject::iOBJ()->FillInQueryStringKeys($L, zobject::iOBJ()->args);
        //log_r("XMLAutoNumber", $L);
        $S = $D->fetch_list($L);
        $n = max($S) + 1;
        //log_file("XMLAutoNumber", "n=$n");
        return $n;
    }

    static function GetZObjectXmlFile($ZName, $ZMode, $ZArgs, &$rc)
    {
        php_logger::call();
        if ($ZName == "") throw new Exception("No ZName in GetZObjectXmlFile.");

        $rc = 1;
        $x = self::recordset_header($ZName, $ZMode, 1, "");
        $x = $x . "  <row>\n";

        $D = self::GetXMLFile($ZName, $ZArgs, $l, $b, $d);
        if (!$D) {
            $D = php_hook::call($d);
            if (is_string($D)) $D = xml_site::$source->force_unknown_document($D);
        }

        php_logger::debug("D=$D");

        $b = zobject::IOBJ() ? zobject::iOBJ()->FillInQueryStringKeys($b, $ZArgs) : $b;

        // php_logger::debug("b=$b, f=" . php_hook::call(zobject::FetchObjPart($ZName, 'xmlfile/@src'), $ZArgs));
        if (!isset($D))
            throw new Exception("Failed to load file: $d", "ZObj::GetZObjectXMLFile");

        php_logger::dump('****************' . $D->saveXML());

        if ($D->count_parts(substr($b, 0, strlen($b) - 1)) == 0) {
            php_logger::warning("GetZObjectXmlFile - no parts, empty recordset.  <br/><b>b=</b>$b<br/><b>D=</b>$D");
            return self::empty_recordset($ZName, $ZMode, $rc);
        }

        $index = zobject::iOBJ()->options['index'];
        $key = zobject::iOBJ()->options['key'];
        $ixval = querystring::get($ZArgs, $key);
        php_logger::debug("index=$index, key=$key, ixval=$ixval");
        php_logger::debug("fields: ", zobject::FetchObjFields($ZName));

        foreach (zobject::FetchObjFields($ZName) as $l) {
            if ($l == $index)
                $v = $ixval;
            else {
                php_logger::trace("querying field: $l");
                $m = xml_file::extend_path($b, $l, zobject::FetchObjFieldPart($ZName, $l, "@access"));
                php_logger::trace("querying field: m=$m");
                //				$M = TrueFalseVal(zobject::FetchObjFieldPart($ZName, $l, "@multiple"), false);
                $M = zobject::FetchObjFieldPart($ZName, $l, "@multiple") == "1";
                $d = zobject::FetchObjFieldPart($ZName, $l, "@datatype");
                php_logger::trace("Field $l: DT=$d, Multiple=$M");
                $getter = zobject::FetchDTPart($d, '@getter');
                if ($getter == '') $getter = zobject::FetchObjFieldPart($ZName, $l, '@getter');
                if (php_hook::is_hook($getter)) {
                    php_logger::log("Invoking getter [$getter]");
                    $v = php_hook::invoke($getter, $ixval);
                } else if (substr($d, 0, 1) == ":") $v = "";
                else $v = $M ? self::GetMultiValuesFromDoc($D, $m) : $v = $D->fetch_part($m);
            }
            if ($v == "") $v = php_hook::call(zobject::FetchObjFieldPart($ZName, $l, "@default"), $ZArgs);
            if ($v == "") $v = php_hook::call(zobject::FetchDTPart($d, "@default"), $ZArgs);
            //print "<br/>v=<u>$v</u>";

            $x .= "    <field id='$l'>";
            $x .= "<![CDATA[$v]]>";
            $x .= "</field>\n";
            //			$x .= "    <field id='$l'><![CDATA[$v]]></field>\n";
        }

        $x .= "  </row>\n";
        $x .= "</recordset>\n";
        //log_file("GetZObjectXmlFile", $x);log_file("GetZObjectXmlFile","-----------------");
        // print $x;die();
        //$x=str_replace(array("\n"," "),array("<br/>","&nbsp;"),ESKf($x));print $x;die();

        $D = new DOMDocument;
        php_logger::result($x);
        $D->loadXML($x);
        return $D;
    }

    static function GetZObjectMultiXmlFile($ZName, $ZMode, $ZArgs, &$rc)
    {
        php_logger::call();
        if ($ZName == "") throw new Exception("<span style='font-weight:bold;font-size:20'>DIE:</span> <u>No ZName in GetZObjectXmlFile</u>");

        $x = "";

        $D = self::GetXMLFile($ZName, $ZArgs, $listpath, $itempath, $F);
        php_logger::debug("F=$F");

        $fl = zobject::FetchObjFields($ZName);        // field list
        php_logger::debug("Field List: ", $fl);

        php_logger::debug("listpath=", $listpath);
        $listpath = php_hook::call($listpath, $ZArgs);
        php_logger::debug("listpath=", $listpath);
        if (is_array($listpath)) $f = $listpath;  // php_hook returned an array!
        else {
            if (isset($D)) $lD = $D;
            if (!isset($lD) && php_hook::is_hook($F)) {
                php_logger::trace("hook -- F=$F");
                $td = php_hook::call($F, '');
                php_logger::trace("hook result -- F=$F, td=$td");
                if (is_string($F)) $lD = xml_site::$source->force_unknown_document($td);
                else if (is_object($F)) $lD = $F;
            }
            php_logger::trace("ld=$lD");
            if (isset($lD)) {
                php_logger::trace("<b>listpath</b> = $listpath, <b>itempath</b>=$itempath");
                $listpath = zobject::iOBJ()->FillInQueryStringKeys($listpath, $ZArgs, false);
                $itempath = zobject::iOBJ()->FillInQueryStringKeys($itempath, $ZArgs, false);
                php_logger::trace("<b><u>Altered:</u></b> <b>listpath =</b> $listpath, <b>itempath=</b>$itempath");

                $oix = zobject::iOBJ()->options['index'];
                if ($oix == "position()" || $oix == "") {
                    php_logger::debug("Positioned elements: $ZName");
                    $nn = $lD->count_parts($listpath);
                    for ($f = array(), $i = 1; $i <= $nn; $i++) $f[$i] = $i;
                } else
                    php_logger::debug("Straight list: $ZName, listpath=$listpath");
                    $f = $lD->fetch_list($listpath);
                    php_logger::trace("List: ", $f);
            }
        }

        php_logger::dump("F: ", $f);
        $rc = count($f);
        $x = self::recordset_header($ZName, $ZMode, count($f));

        $fieldinfo = array();

        php_logger::dump($fl);
        foreach ($fl as $fld) {
            php_logger::trace("fld=$fld");
            $tmp = array();
            $tmp["datatype"] = zobject::FetchObjFieldPart($ZName, $fld, "@datatype");
            $tmp["getter"] = zobject::FetchDTPart($ZName, $fld, "@getter");
            $tmp["default"] = zobject::FetchObjFieldPart($ZName, $fld, "@default");
            $tmp["multiple"] = zobject::YesNoVal(zobject::FetchObjFieldPart($ZName, $fld, "@multiple"), false);
            $tmp["access"] = zobject::FetchObjFieldPart($ZName, $fld, "@access");
            $fieldinfo[$fld] = $tmp;
        }
        php_logger::debug("field defs: ", print_r($fieldinfo));

        $key = '@' . zobject::iOBJ()->options['key'];
        $index = zobject::iOBJ()->options['index'];
        php_logger::debug("key=$key, index=$index");

        foreach ($f as $rowx) {
            php_logger::debug("rowx=$rowx");
            $tA = querystring::add($ZArgs, substr($key, 1), $rowx);
            if (php_hook::is_hook($F)) {
                php_logger::debug("F=$F");
                $tV = php_hook::call($F);
                php_logger::debug("tA=<b>$tA</b>, F=<b><u>$F</u></b>, actual file=<b>$tV</b>");
                unset($D);
                $Did = xml_site::$source->add_file($tV);
                $D = xml_site::$source->get_source($Did);
                php_logger::debug("isset(D)=" . (isset($D) ? 'y' : 'n'));
            }

            php_logger::trace("index=$index, key=$key, rowx=$rowx, itempath=$itempath");
            $x = $x . "  <row>\n";
            $tp = str_replace($key, $rowx, $itempath);
            php_logger::debug("tp=$tp");

            foreach ($fl as $l) {
                php_logger::trace("Data [$l]:");
                //				if ($l == substr($key,1))
                if ($l == $index)
                    $x .= "    <field id='$l'><![CDATA[$rowx]]></field>\n";
                else {
                    php_logger::trace("tp=$tp, <b>l=$l</b>, <u>" . $fieldinfo[$l]["access"] . "</u>");
                    $m = xml_file::extend_path($tp, $l, $fieldinfo[$l]["access"]);
                    php_logger::trace("Extended path: $m");
                    //print "<br/>m=$m";
                    $M = $fieldinfo[$l]['multiple'];
                    //print "<br/>Multiple? " . YesNo($M);
                    php_logger::trace("field datatype=".$fieldinfo[$l]["datatype"]);
                    // php_logger::alert("GETTER: " . php_hook::is_hook($fieldinfo[$l]["getter"]));
                    if (php_hook::is_hook($fieldinfo[$l]["getter"])) $v = php_hook::invoke($fieldinfo[$l]["getter"], $rowx);
                    else if (substr($fieldinfo[$l]["datatype"], 0, 1) == ":") $v = "";
                    else $v = $M ? GetMultiValuesFromDoc($D, $m) : $v = $D->fetch_part($m);
                    if ($v == "") $v = php_hook::call($fieldinfo[$l]["default"], $tA);
                    if ($v == "") $v = php_hook::call(zobject::FetchDTPart($fieldinfo[$l]["datatype"], "@default"), $tA);
                    php_logger::debug("<b>field value</b>=<u>$v</u>");

                    $x .= "    <field id='$l'><![CDATA[$v]]></field>\n";
                }
            }

            $x .= "  </row>\n";
        }
        $x .= "</recordset>\n";

        php_logger::result($x);
        //die($x);

        //print "<br/>ZArgs=$ZArgs";
        //if ($ZName=='y_module_file') die($x);
        $D = new DOMDocument;
        $D->loadXML($x);
        return $D;
    }


    //////////////////////////////////////////////////////////////////////////////////////////



    private static function SaveZObjectToXMLFile($ZName, $ZMode, $v)
    {
        php_logger::call();
        $o = zobject::iOBJ();
        $ZArgs = $o->args;
        $D = self::GetXMLFile($ZName, $o->args, $l, $base, $d);
        php_logger::log("Saving to XML File: $d");

        if (!$D) {
            php_logger::log("Calling hook: $d");
            $D = php_hook::call($d);
            if (is_string($D)) $D = xml_site::$source->force_unknown_document($D);
            php_logger::debug("D=$D");
        }

        php_logger::log("setup save: base=$base, Args=$ZArgs, key=".$o->options['key'].", index=".$o->options['index'].", KV=".zobject::KeyValue($o->options['index']));

        $nkv = zobject::KeyValue($ix = $o->options['index']);
        if ($nkv == "") {
            $def = zobject::FetchObjFieldPart($ZName, $ix, "@default");
            php_logger::log("def=$def");
            $nkv = zobject::iOBJ()->NormalizeInputField(php_hook::call($def, $ZArgs), zobject::FetchObjFieldPart($ZName, $ix, "@datatype"));
        }

        if ($ZMode == "create") {
            $base = str_replace('@' . $o->options['key'], $nkv, $base);
            php_logger::log("index=$index, nkv=$nkv, def=$def, base=$base");
            $ZArgs = querystring::add($ZArgs, $ix, $nkv);
        } else
            $base = str_replace('@' . $o->options['key'], zobject::KeyValue($o->options['key'], $ZArgs), $base);

        php_logger::log("Base Calculated: ix=$ix, nkv=$nkv, base=$base, args=".zobject::iOBJ()->args);

        $base = $o->FillInQueryStringKeys($base);

        php_logger::log("filled in qs: ix=$ix, nkv=$nkv, base=$base");

        $found = false;
        foreach (zobject::FetchObjFields($ZName) as $fid) {
            php_logger::trace("FID=$fid");
            if (!zobject_access::access($ZName, $fid, $ZMode)) continue;

            $fa = zobject::FetchObjFieldPart($ZName, $fid, "@access");
            if ($fa == "-") continue;
            if ($fa == "@") $fa = "@" . $fid;
            if ($fa == "")  $fa = $fid;
            php_logger::trace("Save Target: fa=$fa");

            $fv = $v[$fid];
            php_logger::trace("FId=$fid, fa=$fa, fv=$fv");
            if (!is_array($fv)) {
                php_logger::trace("SET: $base$fa ===> $fv");
                $D->set_part($base . $fa, $fv);
                // php_logger::trace("mod=".$D->modified);
            } else {
                $n = 0;
                $deleted = 0;
                foreach ($fv as $fvv) {
                    $fl = xml_file::add_field_accessor($base . $fa);
                    $fl = xml_file::replace_field_accessor($fl, $n - $deleted);
                    $D->set_part($fl, $fvv);
                    if ($fvv == "") $deleted++;
                }
            }
        }

        
        //self::save_log("<font size=+3>Save to (SaveZObjectToXMLFile): <b><u>$file</u></b></font>");
        //self::save_log(""file=".(is_object($file)?"	.":$file));
        // php_logger::headline($file->saveXML());
        // die();
        php_logger::result($found);
        return $found;
    }



    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////

    static function GetZObjectPHP($ZName, $ZMode, $ZArgs, $prefix = "", &$rc = 0)
    {
        php_logger::call();
        $rc = 1;
        $f = zobject::FetchObjPart($ZName, "phpsource/@item");

        $key = zobject::iOBJ()->options['key'];
        $val = querystring::get($ZArgs, $key);
        //log_file("zobject", "hook=$f, val=$val");
        //print "<br/>hook=$f, key=$key, val=$val";
        $a = php_hook::call($f, $val);

        if (!is_array($a)) return XMLToDoc(self::empty_recordset($ZName, $ZMode, $rc));

        $x = self::recordset_header($ZName, $ZMode, 1, "") . "";
        $x = $x . "  <row>\n";
        foreach ($a as $b => $c) {
            //print "<br/>b=".$b;
            if ($c == "") $c = php_hook::call(zobject::FetchObjFieldPart($ZName, $b, "@default"), $ZArgs);
            if ($c == "") $c = php_hook::call(zobject::FetchDTPart(zobject::FetchObjFieldPart($ZName, $b, "@datatype"), "@default"), $ZArgs);
            $x = $x . "    <field id='$b'><![CDATA[$c]]></field>\n";
        }
        $x = $x . "  </row>\n";
        $x = $x . "</recordset>\n";

        //die($x);

        $D = new DOMDocument;
        $D->loadXML($x);
        return $D;
    }

    static function GetZObjectMultiPHP($ZName, $ZMode, $ZArgs, &$rc)
    {
        php_logger::call();
        $l = zobject::FetchObjPart($ZName, "phpsource/@list");
        //log_file("zobject", "list hook=$l");
        //print "<br/>list hook=$l";
        $L = php_hook::call($l, $ZArgs);
        //print "<br/>list=";print_r($L);

        if (!is_array($L) || !count($L)) return self::empty_recordset($ZName, $ZMode, $rc);

        $f = zobject::FetchObjPart($ZName, "phpsource/@item");
        //log_file("zobject", "hook=$f");
        //print "<br/>hook=$f";
        $token = "@@RECORD_COUNT-" . uniqid() . "@@";
        $x = self::recordset_header($ZName, $ZMode, $token, "");
        $rc = 0;

        $key = '@' . zobject::iOBJ()->options['key'];
        $index = zobject::iOBJ()->options['index'];
        //print "<br/>key=$key, index=$index";

        foreach ($L as $item) {
            $tA = querystring::add($ZArgs, substr($key, 1), $item);

            //print "<br/>item=$item";
            if (is_array($a = php_hook::call($f, $item))) {
                //print "<br/>item is...";  print_r($a);
                $rc++;
                $x = $x . "  <row>\n";
                foreach ($a as $b => $c) {
                    if ($c == "") $c = php_hook::call(zobject::FetchObjFieldPart($ZName, $b, "@default"), $tA);
                    if ($c == "") $c = php_hook::call(zobject::FetchDTPart(zobject::FetchObjFieldPart($ZName, $b, "@datatype"), "@default"), $tA);
                    $x = $x . "    <field id='$b'><![CDATA[$c]]></field>\n";
                }
                $x = $x . "  </row>\n";
            }
        }
        $x = $x . "</recordset>\n";
        $x = str_replace($token, $rc, $x);
        //die($x);

        $D = new DOMDocument;
        $D->loadXML($x);
        return $D;
    }


    static function SaveZObjectToPHP($ZName, $ZMode, $val)
    {
        php_logger::call();

        $s = zobject::FetchObjPart($ZName, "phpsource/@save");
        $r = php_hook::call($s, $val);
        return $r;
    }


    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////


    static function GetZObjectWPOQuery($ZName, $ZMode, $ZArgs, &$rc)
    {
        php_logger::call();
        if (!function_exists("get_option")) return self::empty_recordset();        // wp tie-in

        $s  = self::recordset_header($ZName, $ZMode, 1);
        $s .= "  <row>\n";
        foreach (zobject::FetchObjFields($ZName) as $f)
            $s .= "    <field id='$f'><![CDATA[" . get_option($f) . "]]></field>\n";
        $s .= "  </row>\n";
        $s .= "</recordset>\n";

        //wp_die($s);

        $D = new DOMDocument;
        $D->loadXML($s);
        return $D;
    }

    static function SaveZObjectToWPO($ZName, $ZMode, $ZArgs)
    {
        php_logger::call();
        if (!function_exists("update_option")) return false;
        foreach (zobject::FetchObjFields($ZName) as $f) {
            //print "<br/>field=$f, val=" . $_REQUEST[$f];
            if (isset($_REQUEST[$f])) update_option($f, $_REQUEST[$f]);
        }
        return true;
    }
}
