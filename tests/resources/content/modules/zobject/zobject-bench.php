<?php

class zobject_bench
{
    private static $record = [];

    public static function time($unit = '')
    {
        return (self::$record[$unit] = microtime(TRUE));
    }

    public static function report($n, $cap = '')
    {
        $x = microtime(TRUE);
        $diff = is_string($n) ? @self::$record[$n] : $x - $n;
        if ($cap == '' && is_string($n)) $cap = strtoupper($n);
        return "TOTAL TIME" . (is_string($n) ? "" : " [$n]") . ": {$diff}s";
    }
}
