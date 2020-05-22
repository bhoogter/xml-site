<?php


	function textfile($F) {return file_exists($F)?file_get_contents($F):"";}
	function DirList($dirname, $match="", $leadpath="")
		{
//print "<br/>DirList($dirname)";
		if (($handle = @opendir($dirname))===false) return "";
		for ( $s = array(); ( $file = readdir( $handle )) !== false;) 
			if(is_file($dirname."/".$file) && ($match!="" && preg_match("/$match/i", $file) || $match==""))
				if ($file!="." && $file!="..") $s[] = $leadpath.$file;
		closedir($handle);
//print "<br/>DirList: ";print_r($s);
		return $s;
		}

	function ZNameList()
		{
		$l = FetchDocList(ObjectDefs(), "/*/zobjectdef/@name");
		sort($l, SORT_STRING);
		return "|".implode("|", $l);
		}
		
	function DataTypeListInternal($withsystem=false)
		{
		$l = juniper()->lst("//SYS/*/typedef/@name");
		sort($l, SORT_STRING);
		return "|".join("|", $l);
		}
	function DataTypeList()			{return DataTypeListInternal(true);}
	function DataTypeListNoSystem()	{return DataTypeListInternal(false);}
		
	function FileListForSource($dirname, $match="", $leadpath="")
		{
//print "<br/>FileListForSource($dirname)";
		if (($handle = @opendir($dirname))===false) return "";
		for ( $s = ""; ( $file = readdir( $handle )) !== false;) 
			if(is_file($dirname."/".$file) && ($match!="" && preg_match("/$match/i", $file) || $match==""))
				if ($file!="." && $file!="..") $s = $s.($s==""?"":"|").$leadpath.$file;
		closedir($handle);
//print "<br/>FileListForSource: $s";
		return $s;
		}

	function ImageList_Icons()			{return FileListForSource(rPATH_IMG,"[.]ico$");}
	function ImageList_Any()			{return FileListForSource(rPATH_IMG);}
	function CssList()				{return FileListForSource(rPATH_CSS);}
	function RSSList()				{return FileListForSource(rPATH_RSS);}
	
	function PageTemplateCustomDivSource($pid)
		{
		return "";
		}
	
	function TemplateTransformList()
		{
		$a = GetTransformVar("args");
//print "<br/>a=$a";
		$v = GetXMLFileList(TemplateFileName($a), "/pagetemplate/transform/@id");
		return $v;
		}
	function PageTemplateList()			
		{
		$d = rPATH_PAGETEMPLATES;
		if (($handle = @opendir($d))===false) return "";
		for ( $s = ""; ( $file = readdir( $handle )) !== false;) 
			{
			$dn = $d . "/" . $file;
			if(is_dir($dn) && $file!="." && $file!="..")
				{
				$fn = $dn."/template.xml";
				$v = $file;
				$n = strtoupper($v);
//print "<br/>fn=$fn";
				$D = FileToDoc($fn);
				$n = FetchDocPart($D, "/pagetemplate/@name");
				unset($D);
				if ($n=="") $n = "[".$v."]";
				$s = $s.($s==""?"":"|").$n."=".($leadpath.$file);
				}
			}
		closedir($handle);
//print "<br/>PageTemplateList: $s";
		return $s;
		}
		
	function ObjectTemplateList()
		{
		$dirname = rPATH_SYSTEMPLATES;
		if (($handle = @opendir($dirname))!==false)
			{
			for ( $s = ""; ( $file = readdir( $handle )) !== false;) if ($file!="." && $file!="..") $s = "$s|$file";
			closedir($handle);
			}
		$dirname = rPATH_TEMPLATES;
		if (($handle = @opendir($dirname))!==false)
			{
			for ( $s = ""; ( $file = readdir( $handle )) !== false;) if ($file!="." && $file!="..") $s = "$s|$file";
			closedir($handle);
			}
		return $s;
		}
		
	function GetPageListSource()
		{return FetchDocPart(PageDefs(), "/*/global/linklists");}		
