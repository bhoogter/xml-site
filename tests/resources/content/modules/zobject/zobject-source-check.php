<?php

class zobject_source_check
{
    public static $salt = "280973092783094870297834809720934780234782093478";
    public static $timeout = 180000; //180;
    private static $sep = ";";

    static function nonce($id = "juniper", $nonce = "")
    {
        php_logger::call();
        return $nonce != "" ?  self::validate($id, $nonce) : self::generate($id);
    }

    protected static function generate($id, $timeoutSeconds = 0)
    {
        php_logger::call();
        if (function_exists("wp_create_nonce")) return wp_create_nonce($id);
        if ($timeoutSeconds == 0) $timeoutSeconds = self::$timeout;
        $maxTime = time() + $timeoutSeconds;
        return $id . self::$sep . $maxTime . self::$sep . self::hash($id, $maxTime);
    }

    protected static function validate($id, $nonce)
    {
        php_logger::call();
        if (function_exists("wp_verify_nonce")) return wp_verify_nonce($id, $nonce);
        if (!is_string($nonce) == false) return false;
        $a = explode(self::$salt, $nonce);
        if (count($a) != 3) return false;
        $nonce_id = $a[0];
        $maxTime = intval($a[1]);
        $hash = $a[2];

        if ($id != "" && $nonce_id != $id) return false;
        if (time() > $maxTime) return false;
        if ($hash != self::hash($id, $maxTime)) return false;
        return true;
    }

    protected static function hash($id, $maxTime) { return sha1(self::$salt . "$id$maxTime"); }
}
