<?php

class zobject_render
{
    public static function render($el)
    {
        $params = ['name' => 'options', 'mode' => '-'];
        return (new zobject())->render($params);
    }
}
