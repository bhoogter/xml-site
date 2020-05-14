<?php

class zobject_format
{
    ////////////////////////////////   PRETTY
    static function PrettyCaption($Cap)        { return ucwords($Cap == "" ? "" : ($Cap . (str_replace(array(":","?","!"),array(),$Cap)==$Cap ? ":" : "")));}
    static function PrettyHeader($Cap)	       { return ucwords(str_replace("_", " ", $Cap));}
    static function PrettyValue($C)            { return str_replace("\n","<br/>\n", str_replace(array("<", ">"), array("&#38;", "&#39;"), $C));}
    static function PrettyCaptionHelp($Cap)    { return wordwrap($Cap, 50, "<br>\n", true);}
    static function PrepareTextAreaContent($C) { return str_ireplace("</textarea>", "&lt;/textarea&gt;", $C);}

    static function FormatDataField($f, $DT)
    {
        php_logger::log("FormatDataField($f, $DT)");
        $N = zobject::FetchDTPart($DT, "@format");
        php_logger::log("N=$N");
        $Na = php_hook::call($N, $f);
        if ($Na != $N) $f = $Na;

        if (($k = zobject::FetchDTPart($DT, "@html-type")) == "") $k = $DT;
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
}
