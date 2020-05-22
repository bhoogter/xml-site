<?php

class zobject_autotemplate
	{
	static function autotemplate($ZName, $ZMode, $which)
		{
        php_logger::log("CALL - $ZName, $ZMode, $which");
		$xmlTEXT = zobject::FetchObjDefString($ZName);

		if ($xmlTEXT=="") return "<autoTemplate unknownZName='$ZName' zmode='$ZMode'/>";
		$xmlTEXT = str_replace("<zobjectdef ", "<zobjectdef mode='$ZMode' ", $xmlTEXT);
		
//print $xmlTEXT;die();
		$xml = new DomDocument;
		$xml->loadXML($xmlTEXT);

		$xsl = false;
		if ($which!="" && file_exists(xml_site::$resource_folder . "/$which"))
			{
			$D = xml_site::$source->force_unknown_document(xml_site::$resource_folder . "/$which");
			$xsl = $D->Doc;
			}
		else $xsl = xml_file::toDoc(self::autotemplate_xsl());

//print "<br/>autotemplate.type=".get_class($xsl);

		$xh = new XsltProcessor();  // Allocate a new XSLT processor 
		$xh->registerPHPFunctions();
		$xh->importStyleSheet($xsl);
		$result = $xh->transformToXML($xml);	// Start the transformation

        // die($result);

		unset($xh);
		unset($xml);
		unset($xsl);
		return $result;
        }
        
        private static function autotemplate_xsl() {
            return realpath(__DIR__ . "/source/auto-template.xsl"); 
        }
	}

