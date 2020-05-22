<?php

class zobject_access
{

    static function user_can($what)
    {
        if ($what == "0") return false;
        if (strstr($what, "[-]") !== false) return false;
        return true;
    }

    static function page_access($pid, $mode = "")
    {
        return true;
    }

    static function conditions_met($what, $v)
    {
        return true;
    }

    static function check($ZName, $ZMode = "")
    {
        self::access($ZName, $ZMode);
        return $ZMode;
    }

    static function access($ZName, &$ZMode = "")
    {
        php_logger::log("CALL - $ZName, $ZMode");
        $oMode = $ZMode;
        if ($ZMode == "create" || $ZMode == "delete") {
            $test = zobject::FetchObjPart($ZName, "@allow-$ZMode");
            if (!self::user_can($test)) {
                $ZMode = "";
                return false;
            }
            return $ZMode == $oMode;
        }

        if ($ZMode == "pos" || $ZMode == "edit") {
            $test = zobject::FetchObjPart($ZName, "@allow-$ZMode");
            if (!self::user_can($test)) $ZMode = "display";
        }

        if ($ZMode == "list") {
            $test = zobject::FetchObjPart($ZName, "@allow-$ZMode");
            if (!self::user_can($test)) $ZMode = "display";
        }

        return $ZMode == $oMode;
    }

    static function check_field($ZName, $fid = "", &$ZMode = "")
    {
        self::field_access($ZName, $fid, $ZMode);
        return $ZMode;
    }

    static function field_access($ZName, $fid = "", &$ZMode = "")
    {
        php_logger::call();
        $oMode = $ZMode;
        if ($ZMode == "list-edit") {
            $test = zobject::FetchObjFieldPart($ZName, $fid, "@allow-$ZMode");
            if (!self::user_can($test)) $ZMode = "list";
        }
        if ($ZMode == "list") {
            $test = zobject::FetchObjFieldPart($ZName, $fid, "@allow-$ZMode");
            if (!self::user_can($test)) {
                $ZMode = "";
                return false;
            }
        }
        if ($ZMode == "create" || $ZMode == "delete") {
            $test = zobject::FetchObjFieldPart($ZName, $fid, "@allow-$ZMode");
            if (!self::user_can($test)) {
                $ZMode = "";
                return false;
            }
        }
        if ($ZMode == "edit") {
            $test = zobject::FetchObjFieldPart($ZName, $fid, "@allow-$ZMode");
            php_logger::log("@allow-$ZMode, test=$test");
            if (!self::user_can($test)) $ZMode = "display";
        }
        if ($ZMode == "display") {
            $test = zobject::FetchObjFieldPart($ZName, $fid, "@allow-$ZMode");
            php_logger::log("test=$test");
            if (!self::user_can($test)) {
                $ZMode = "";
                return false;
            }
        }

        php_logger::result($ZMode);
        return $ZMode == $oMode;
    }
}
