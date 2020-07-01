<?php

class zobject_render
{
    public static function render($el)
    {
        $params = ['name' => 'options', 'mode' => 'display'];
        return zobject::render($params);
    }
}
