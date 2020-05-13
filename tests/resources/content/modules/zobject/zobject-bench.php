<?php

class zobject_bench
{
    public static function time()
    {
        return microtime(TRUE);
    }

    public static function report($n, $cap = "")
    {
        $x = microtime(TRUE);
        return "TOTAL TIME" . ($cap == "" ? "" : "[$cap]") . ":" . ($x - $n);
    }
}
