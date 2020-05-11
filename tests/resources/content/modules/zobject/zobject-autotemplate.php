<?php

class zobject_autotemplate
	{
	static function autotemplate($ZName, $ZMode, $which)
		{
//print "<br/>AutoTemplate($ZName, $ZMode, ".juniper_dir("/$which").")";
		$xmlTEXT = juniper()->FetchObjDefString($ZName);

		if ($xmlTEXT=="") return "<autoTemplate unknownZName='$ZName' zmode='$ZMode'/>";
		$xmlTEXT = str_replace("<zobjectdef ", "<zobjectdef mode='$ZMode' ", $xmlTEXT);
		
//print $xmlTEXT;die();
		$xml = new DomDocument;
		$xml->loadXML($xmlTEXT);

		$xsl = false;
		if ($which!="" && file_exists(juniper_dir("/$which")))
			{
			$D = juniper()->force_unknown_document(juniper_dir("/$which"));
			$xsl = $D->Doc;
			}
		else $xsl = xml_file::FileToDoc(juniper_module_dir('/zobject/components/source/AutoTemplate.xsl'));

//print "<br/>autotemplate.type=".get_class($xsl);

		$xh = new XsltProcessor();  // Allocate a new XSLT processor 
		$xh->registerPHPFunctions();
		$xh->importStyleSheet($xsl);
		$result = $xh->transformToXML($xml);	// Start the transformation

//die($result);

		unset($xh);
		unset($xml);
		unset($xsl);
		return $result;
		}
	}

