<?php

class zobject_format
{
    ////////////////////////////////   PRETTY
    static function PrettyCaption($Cap)        { return ucwords($Cap == "" ? "" : ($Cap . (str_replace(array(":","?","!"),array(),$Cap)==$Cap ? ":" : "")));}
    static function PrettyHeader($Cap)	       { return ucwords(str_replace("_", " ", $Cap));}
    static function PrettyValue($C)            { return str_replace("\n","<br/>\n", str_replace(array("<", ">"), array("&#38;", "&#39;"), $C));}
    static function PrettyCaptionHelp($Cap)    { return wordwrap($Cap, 50, "<br>\n", true);}
    static function PrepareTextAreaContent($C) { return str_ireplace("</textarea>", "&lt;/textarea&gt;", $C);}

    public static function FormatDataField($f, $DT)
    {
        // php_logger::set_log_level('zobject_format', 'all');
        // php_logger::set_log_level('php_hook', 'all');
        php_logger::call();
        $N = zobject::FetchDTPart($DT, "@format");
        php_logger::log("N=$N");
        $Na = php_hook::call($N, $f);
        if ($Na != $N) $f = $Na;
        php_logger::log("f=$f");

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

    public static function CleanDate($s) { return self::DressUnixDate($s); }
    public static function CleanDateTime($s) { return self::DressUnixDateTime(strtotime($s)); }

    public static function DressUnixDate($epoch) { return (new DateTime("@$epoch"))->format('Y-m-d'); }
    public static function DressUnixDateTime($epoch) { return (new DateTime("@$epoch"))->format('Y-m-d H:i:s');      }

    public static function Now($epoch) { return self::DressUnixDateTime(time()); }
    public static function Today($epoch) { return self::DressUnixDate(time()); }

    public static function TrueFalseVal($v, $default = false) { 
        switch(strtolower(substr("" . $v, 0, 1))) {
            case "t": case "y": case "1": return true;
            case "f": case "n": case "0": return false;
            default: return $default;
        }
    }

    public static function FalseTrueVal($v) { return self::TrueFalseVal($v, false); }
    public static function YesNoVal($v) { return self::TrueFalseVal($v, true); }
    public static function NoYesVal($v) { return self::TrueFalseVal($v, false); }
    public static function YesNo($v) { return $v ? "Yes" : "No"; }
    public static function TrueFalse($v) { return $v ? "True" : "False"; }

    public static function CleanURL($v) { return $v; }
    public static function DressURL($v) { return $v; }

    public static function htmlToMarkdown($s) { return xml_serve::htmlToMarkdown($s); }
    public static function markdownToHtml($s) { return "<span>" . xml_serve::markdownToHtml($s, false) . "</span>"; }
}
