<?php

class auth_login
{
    public static function render($el)
    {
        $n = "\n";

        $s  = "";
        $s .= $n . '<style>';
        $s .= $n . '';
        $s .= $n . '</style>';
        $s .= $n . '';
        $s .= $n . '<div class="form-group">';
        $s .= $n . '  <div class="col-sm-12">';
        $s .= $n . '    <input type="text" class="form-control" id="userName" name="userName" spellcheck="false" autocomplete="off" maxlength="20" placeholder="USERID" required="" value="" />';
        $s .= $n . '  </div>';
        $s .= $n . '  <div class="col-sm-12">';
        $s .= $n . '  <input class="form-control" type="password" id="password" name="password" placeholder="PASSWORD" required="" value="" />';
        $s .= $n . '  </div>';
        $s .= $n . '  </div>';
        $s .= $n . '</div>"';

        return xml_serve::xml_content($s);
    }
}
