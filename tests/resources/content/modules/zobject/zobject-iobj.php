<?php

class zobject_iobj {
    private static $iOBJs = [];

    public static function unset($o) {
        foreach(self::$iOBJs as $k=>$v) 
            if ($o == $v) unset(self::$iOBJs[$k]);
    }

    public static function set($o) {
        return array_push(self::$iOBJs, $o);
    }

    public static function iOBJ($n = 0) {
        return count(self::$iOBJs) <= $n ? null : self::$iOBJs[-$n];
    }

    public static function iOBJ2() { return self::iOBJ(1); }

    static function named_template() { 
        return self::IOBJ() ? self::IOBJ()->named_template : ''; 
    }
    static function transform_var($n) { 
        return self::iOBJ() ? self::iOBJ()->get_var($n) : ''; 
    }
    static function get_template($f, $n, $m) { 
        return self::iOBJ() ? self::iOBJ()->GetZObjectTemplate($f, $n, $m) : ''; 
    }
    static function template_escape_tokens($s) { 
        return self::iOBJ() ? self::iOBJ()->TemplateEscapeTokens($s) : ''; 
    }
    static function recno($reset = 1) { 
        return self::iOBJ() ? self::iOBJ()->RecNo($reset) : ''; 
    }

}