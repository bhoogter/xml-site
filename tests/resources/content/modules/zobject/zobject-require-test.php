<?php

class zobject_require_test
{
    static function InjectVariables()
    {
        php_logger::call();
        $s  = "";
        $s .= "<xsl:variable name='name' select='\"" . zobject_iobj::iOBJ()->name . "\"' />\n";
        $s .= "<xsl:variable name='mode' select='\"" . zobject_iobj::iOBJ()->mode . "\"' />\n";
        return $s;
    }

    static function test($UID, $Test, $Row)
    {
        php_logger::call();
        if ($Test == "") return true;
        if (php_hook::is_hook($Test)) return php_hook::call($Test);

        //print "<br/>requireTest($UID, $Test, $Row)";
        if (preg_match("/^@([a-zA-Z_])([a-zA-Z0-9_])*$/", $Test) != '1')
            return zobject::InterpretFields($Test) != "" ? "1" : "0";
        else if (preg_match("/^[!]@([a-zA-Z_])([a-zA-Z0-9_])*$/", $Test) == 1)
            return zobject::InterpretFields(substr($Test, 1)) == "" ? "1" : "0";
        else if (preg_match("/^[#]([][a-zA-Z_])([][a-zA-Z0-9_])*$/", $Test) == 1)
            return zobject_access::conditions_met(substr($Test, 1), "1");
        else if (preg_match("/^[!][#]([a-zA-Z_])([a-zA-Z0-9_])*$/", $Test) == 1)
            return zobject_access::conditions_met(substr($Test, 1), "1") == "" ? "1" : "";
        else
            $Test = zobject::InterpretFields($Test);
        $Test = str_replace(array("<", ">", "&", "'"), array("&lt;", "&gt;", "&amp;", '"'), $Test);

        //print "<br/>Test: $Test";

        $r = "<?xml version='1.0' encoding='ISO-8859-1'?>\n";
        $r = $r . "<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>\n";
        $r = $r . self::InjectVariables();
        $r = $r . "  <xsl:template match='/'>\n";
        $r = $r . "    <res>\n";
        $r = $r . "      <xsl:choose>\n";
        $r = $r . "        <xsl:when test='$Test'>1</xsl:when>\n";
        $r = $r . "        <xsl:otherwise>0</xsl:otherwise>\n";
        $r = $r . "      </xsl:choose>\n";
        $r = $r . "    </res>\n";
        $r = $r . "  </xsl:template>\n";
        $r = $r . "</xsl:stylesheet>\n";

        $x = "<?xml version='1.0' encoding='ISO-8859-1' ?>\n<data />";

        //if (strstr($Test, "create"))
        //die($r);
        $xml = new DomDocument;
        $xml->loadXML($x);

        $xh = new XsltProcessor();  // Allocate a new XSLT processor 
        $xsl = new DomDocument;

        if (!($xsl->loadXML($r))) {
            php_logger::warn("Failed to compile REQUIRE expression: $Test", "requireTest");
            return "0";
        }
        $xh->importStyleSheet($xsl);
        $result = $xh->transformToDoc($xml);    // Start the transformation
        $x = $result->textContent;
        //print "<br/>Test: $Test  === $x";
        return $x;
    }
}
