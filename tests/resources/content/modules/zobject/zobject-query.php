<?php

class zobject_query
	{
	static function recordset_header($ZName='', $ZMode='', $rc=0, $ixf="", $wxml=true, $empty=false)
		{return "<?xml version='1.0' ?>\n<recordset".($ZName==''?'':" zname='$ZName'").($ZMode==''?'':" zmode='$ZMode'").($ixf==''?'':" ixf='$ixf'")." count='$rc' ".($empty?"/":"").">\n";}

	static function empty_recordset($ZName='', $ZMode='', &$rc=0)
		{
		$rc = 0;
		$D = new DOMDocument;
		$D->loadXML(self::recordset_header($ZName, $ZMode, $rc, "", true, true));
		return $D;
		}

	static function data_mode($ZName)
		{
		if (juniper()->FetchObjPart($ZName, "sql/@src")=="wpdb") return "wpdb";
		if (juniper()->FetchObjPart($ZName, "sql/@type")=="mysql") return "mysql";
		if (juniper()->FetchObjPart($ZName, "xmlfile/@src")!="") return "xml";
		if (juniper()->FetchObjPart($ZName, "phpsource/@item")!="") return "php";
		if (juniper()->FetchObjPart($ZName, "wpoptions/@prefix")!="") return "wpo";
		return "";
		}

	static function get_result($ZName, $ZMode, $ZArgs, &$rc, &$tform)
		{
//print "<br/>ZOBJECT_QUERY :: get_result($ZName, $ZMode, $ZArgs, ..., ...)";
		
		$data_mode = self::data_mode($ZName);
//print "<br/>get_result, ZMode=$ZMode, data_mode=$data_mode";

		switch($ZMode)
			{
			case "list":
			case "list-edit":	switch($data_mode)
				{
				case "wpdb":	return self::GetZObjectMultiQuery($ZName, $ZMode, $ZArgs, $ZKey, $prefix, $rc);
				case "mysql": return self::GetZObjectMultiQuery($ZName, $ZMode, $ZArgs, $ZKey, $prefix, $rc);
				case "xml":	return self::GetZObjectMultiXmlFile($ZName, $ZMode, $ZArgs, $rc);
				case "php":	return self::GetZObjectMultiPHP($ZName, $ZMode, $ZArgs, $rc);
				default:	return self::empty_recordset($ZName, $ZMode, $rc);
				}

			case "create": case "list-create":
				$ixf = juniper()->FetchObjPart($ZName, "@key");
				$Index = juniper()->KeyValue($ixf);
//log_file("zobject", "ixf=$ixf, Index=$Index");
				return self::GetZObjectCreateQuery($Index, $ZName, $ZMode, $ZArgs, iOBJ()->options['key'], iOBJ()->options['prefix'], $rc);
				break;


			case "edit": case "display": case "find": case "build":
				if ($data_mode=="") return self::empty_recordset($ZName, $ZMode, $rc);

				$ZKey = iOBJ()->options['key'];
				$r = iOBJ()->options['key-array'];
//print "<br/>iOBJ options: ";print_r(iOBJ()->options);
//juniper()->dump_iOBJ();
//log_file("zobject","Zmode=$ZMode, key=$ZKey, $r, count(keys)=".count($r));
//print "<br/>ZN=$ZName, Zmode=$ZMode, key=$ZKey, $r, count(keys)=".count($r);
//print_r($r);
//zoDie('test');
//				if (is_array($r))
					{
					$emptycount=0;
					foreach(array_values($r) as $l) if ($l!='' && juniper()->KeyValue($l)=="") $emptycount=$emptycount+1;
					if ($emptycount>0 || $ZMode=="find")
						{
print "<br/>building tform";
						$tform = "<form name='GetKey' method='GET'>\n";
						foreach($r as $zk) $tform = $tform . "$zk: <input name='$zk' value='".juniper()->KeyValue($zk)."'/><br/>\n";
						$tform = $tform . "<input type='submit' value='".($ZMode=="edit"?"Edit":"Show")."'/>\n";
						$tform = $tform . "</form>\n";
						return null;
						}
//print "<br/>sql: " .juniper()->FetchObjPart($ZName, "sql");
					}

				if ($ZMode=="build") 
					{
//print "<br/>build zmode: $ZMode";
//log_file("zobject","build ZMode=$ZMode");
					if ($rc!=0) $ZMode="edit";
					else
						{
						$ZMode="create";
						return self::GetZObjectCreateQuery($Index, $ZName, $ZMode, $ZArgs, $ZKey, $prefix, $rc);
						}
					}
//print "<br/>finishing result";
				switch($data_mode)
					{
					case "wpdb":	return self::GetZObjectQuery($ZName, $ZMode, $ZArgs, $ZKey, $Ix, $prefix, $rc);
					case "xml":	return self::GetZObjectXmlFile($ZName, $ZMode, $ZArgs, $rc);
					case "php":	return self::GetZObjectPHP($ZName, $ZMode, $ZArgs, '', $rc);
					case "wpo":	return self::GetZObjectWPOQuery($ZName, $ZMode, $ZArgs, $rc);
					default:	return self::empty_recordset($ZName, $ZMode, $rc);
					}
					
				break;
			case "delete":	return "Deletion not working...  try save mode";
			default:		return "Unknown mode: $ZMode";
			}
		}
	
	static function save_log($s)
		{
		print "<br/>zsave: $s";
//		log_file("zsave", $s);
		}

	static function invoke_save_trigger($s='post')
		{
		if (iOBJ()->options["$s-trigger"] != "") juniper()->php_hook("php:".iOBJ()->options["$s-trigger"]);
		}
	
	static function save_form()
		{
// _ZN, _ZM, _ZA, _ZA64, _ZS, _ZL
		$o = iOBJ();			// zobject
		if ($o->args=="")	self::save_log("No Args at all");
			
//self::save_log("ZName=$o->name\n<br/>ZMode=$o->mode\n<br/>Args=$o->args, REQ:", $_REQUEST);
//die();
		
		if ($o->options['type'] == "querybuilder")
			{
			$n = 1;
			$q = "";
			foreach(juniper()->FetchObjFields($o->name) as $f)
				{
				$v = urlencode($_REQUEST[$id]);
//self::save_log("id=$id, f=$f, v=$v");
				if ($v!="") $q = (!strlen($q)?"?":($q . "&")) . "$f=$v";
				$n = $n + 1;
				}
			$r = $o->options['return'] . EXT . $q;
//self::save_log("querybuilderresult=$r");
			return $r;
			}


		self::invoke_save_trigger('pre');

		$data_mode = self::data_mode($o->name);
		if ($o->mode!="pos" && $o->mode!="upposition" && $o->mode!="dnposition")
			$v = self::pre_save($o->name, $o->mode);
//self::save_log("ZName=$o->name, ZMode=$o->mode, Args=$o->args, datamode=$data_mode");
//print_r($_REQUEST);
//die();
		switch($o->mode)
			{
			case "delete": switch($data_mode)
				{
				case "xml":
						$f = self::GetXMLFile($o->name, $o->args, $lst, $bse, $d);
						if (!$f)
							{
							$f = juniper()->php_hook($d);
							if (is_string($f)) $f = juniper()->force_unknown_document($f);
							}
						$bse = $o->FillInQueryStringKeys($bse, '', true);
//self::save_log("b=$b");
						$f->delete_node($bse);		// will be saved later, automatically
//die();
						break;
				case "wpdb":	self::SaveZObjectQuery($o->name, "delete", $o->args, $v); break;
				case "mysql":	self::SaveZObjectQuery($o->name, "delete", $o->args, $v); break;
				case "php":	self::SaveZObjectToPHP($o->name, $o->mode, $v); break;
				case "wpo":	self::SaveZObjectToWPO($o->name, $o->mode, $v); break;
				default:	break;
				}
				break;
			case "edit": switch($data_mode)
				{
				case "xml": 	self::SaveZObjectToXMLFile($o->name, $o->mode, $v); break;
				case "wpdb":	self::SaveZObjectQuery($o->name, "edit", $o->args, $v); break;
				case "mysql":	self::SaveZObjectQuery($o->name, "edit", $o->args, $v); break;
				case "php":	self::SaveZObjectToPHP($o->name, $o->mode, $v); break;
				case "wpo":	self::SaveZObjectToWPO($o->name, $o->mode, $v); break;
				default:	break;
				}
				break;
			case "pos": case "dnposition": case "upposition": switch($data_mode)		//  position adjust
				{
				case "xml": 	
						if ($o->mode == "position")	$ZL = @$_REQUEST["_ZL"];
						else if ($o->mode == "dnposition") $ZL = 1;
						else if ($o->mode == "upposition") $ZL = -1;
						$f = self::GetXMLFile($o->name, $o->args, $lst, $bse, $d);
						if (!$f)
							{
							$f = juniper()->php_hook($d);
							if (is_string($f)) $f = juniper()->force_unknown_document($f);
							}
						$bse = $o->FillInQueryStringKeys($bse, '', true);
//self::save_log("Adjust Position, bse=$bse, l=".$ZL);
						$f->adjust_part($bse, $ZL);		// will be saved later, automatically
//die();
						break;
				case "php":	$v = self::pre_save($o->name, $o->mode); self::SaveZObjectToPHP($o->name, $o->mode, $v); break;
				case "wpdb":	break;		// can't do positioning on SQL elements
				case "mysql":	break;		
				case "wpo": default:	break;	// Can't do positioning here either
				}
				break;
			case "create": switch($data_mode)		//  position adjust
				{
				case "xml": 	self::SaveZObjectToXMLFile($o->name, $o->mode, $v); break;
				case "wpdb":	self::SaveZObjectQuery($o->name, "create", $o->args, $v, $zKey); break;
				case "mysql":	self::SaveZObjectQuery($o->name, "create", $o->args, $v, $zKey); break;
				case "php":	self::SaveZObjectToPHP($o->name, $o->mode, $v); break;
				case "wpo":	self::SaveZObjectToWPO($o->name, $o->mode, $v); break;
				default:	break;
				}
				break;
			default:	break;	// unrecognized mode for saving....
			}		// end switch on ZMode


		self::invoke_save_trigger('post');

		return true;
		}


/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////



	function MakePOSTValueReady($key, $data_type, $n = 0, $Target="SQL")
		{
//log_file("save", "MakePOSTValueReady($key, $data_type, $n, $Target)");
//print "<br/> MakePOSTValueReady($key, $data_type, $n, $Target)\n";

		if ($n > 0 && is_array(@$_REQUEST[$key]) && count(@$_REQUEST[$key]) >= $n)
			$v = @$_REQUEST[$key][$n-1];
		else
			$v = @$_REQUEST[$key];
//print "<br/>v=$v\n";
//log_file("save", "v=$v");
		if (is_array($v)) $v = implode(",", $v);

		$v = str_replace(array("\\'","\\\"","\\\\"), array("'","\"","\\"), $v);
//print "<br/>v=$v";
//log_file("save", "v=$v");

		if ($data_type != "") $dfD = juniper()->php_hook(juniper()->FetchDTPart($data_type, "@default"));
//print "<br/>dfD=$dfD";
//		if ($v == "" && $dfD != "") $v = DFV($dfD);
//print "<br/>v=$v";
//log_file("save", "v=$v");

		$v = iOBJ()->NormalizeInputField($v, $data_type);
//print "<br/>v=$v";
//log_file("save", "v=$v");

		if ($Target=="SQL") $v = SVF($v, $data_type);
//log_file("save", "MakePOSTValueReady: $v");
		return $v;
		}

	private function pre_save($ZName, $ZMode)
		{
//self::save_log("<br/>zobject-query::pre_save($ZName, $ZMode)");
//die();
		$o = iOBJ();							// zobject
		$v = array();
		$v['_ZName'] = $ZName;
		$v['_ZMode'] = $ZMode;

		$px = $o->options['prefix'];

		
		$nkv = juniper()->KeyValue($ix=$o->options['index']);
		if ($ix!="" && $nkv == "")
			{
			$def = juniper()->FetchObjFieldPart($ZName, $ix, "@default");
//self::save_log("def=$def");
			$nkv = iOBJ()->NormalizeInputField(juniper()->php_hook($def), juniper()->FetchObjFieldPart($ZName, $ix, "@datatype"));
			}

		if ($ZMode=="delete")
			{
			$v[$ix] = $o->arg($o->options['key']);
//print "<br/>class_zobject_query::pre_save delete result (".$o->options['key']."): ";print_r($v);die();
			return $v;
			}

		$found = false;
		foreach(juniper()->FetchObjFields($ZName) as $fid)
			{
//self::save_log("FID=$fid");
			if (!zobject_access::zobject_field_access($ZName, $fid, $ZMode)) continue;

			$dt = juniper()->FetchObjFieldPart($ZName, $fid, "@datatype");
			if ($dt=="") $dt="string";
//self::save_log("dt=$dt");
			$m = 0;

			while(true)
				{
//self::save_log("m=$m");
				if ($dt[0]==':')
					{
//  sub zobjects would result in a full list-edit, which we're avoiding..
//					$pfx = GetSubPrefix($ZName, $px);
//					$res = SaveZObjectToXMLFile($D, substr($dt, 1), $ZMode, $ZArgs, $pfx);
					}
				else
					{
					$mult = YesNoVal(juniper()->FetchObjFieldPart($ZName, $fid, "@multiple"),false);
					if ($mult)
						{
//self::save_log("Multi-Field Set: $fid";
						$v[$fid] = array();
						$n = 0;
						$m=0;
						$deleted=0;
						while($m<25)
							{
							$n++;
							$tfix = $px . $fid . "___" . $n;
							$r = $this->arg($tfix);
							if ($r!="") $m=0;							// basically, try 25 after last sequential.. then stop looking
							$val = $this->MakePOSTValueReady($tfix, $dt, $o->mRecNo, "XML");
//self::save_log("tfix=$tfix, dt=$dt, r=$r,	-----------------> multivalue ===> $val");
							$v[$fid][] = $val;
							}
						}
					else
						{
						$tfix = $px . $fid;
//self::save_log("tfix=$tfix, is_array(tfix)=" . TrueFalse(is_array($this->arg($tfix))) . ", COUNT=" . count($this->arg($tfix)));

						if ((is_array($o->arg($tfix)) && $o->mRecNo > count($o->arg($tfix))))
							{
//self::save_log("Returning False");
							return false;
							}
						if (count($o->arg($tfix))!=0) $found=true;
						if ($fid==$ix)
							$val = $nkv;
						else
							$val = self::MakePOSTValueReady($tfix, $dt, $o->mRecNo, "XML");
//self::save_log("tfix=$tfix, dt=$dt, r=$r,	-----------------> value ===> $val");
						$v[$fid]=$val;
						}
					}				if (($m++)==0 || !$res) break;
				}
			}
//print "<br/>class_zobject_query::pre_save result: ";print_r($v);die();
		return self::pre_save_result($v);
		}

function pre_save_result($v)
	{
	$s  = self::recordset_header(iOBJ()->name, iOBJ()->mode, 1);
	$s .= "<row>\n";
	foreach($v as $a=>$b)
		if (is_array($b))
			$s .= "<field id='$a'><![CDATA[".join(",",$b)."]]></field>\n";
		else
			$s .= "<field id='$a'><![CDATA[".$b."]]></field>\n";
	$s .= "</row>\n";
	$s .= "</recordset>\n";


//	die($s);
	$D = new DOMDocument;
	$D->loadXML($s);
	iOBJ()->set_result($D);


	return $v;
	}

/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////

	static function GetMultiValuesFromDoc_Map($i) {return "'". str_replace("'","''",$i)."'";}
	static function GetMultiValuesFromDoc($D, $p)
		{
//print "<br/>GetMultiValuesFromDoc(..., $p)";
		$r = FetchDocList($D, $p);
		$r = array_values(array_map("GetMultiValuesFromDoc_Map", $r));
		return implode(",", $r);
		return $r;
		}


	static function GetZObjectEmptyQuery($Index, $ZName, $ZMode, $ZArgs, $Key, $prefix)
		{
//print "<br/>GetZObjectEmptyQuery($Index, $ZName, $ZMode, $ZArgs, $Key, $prefix, $AsList)";
		$ixf=FetchObjPart($ZName, "@index");
		
		if ($ixf=="")
			$X = self::recordset_header($ZName, $ZMode, 1, $ixf, true, true);
		else
			$X = self::recordset_header($ZName, $ZMode, 1, $ixf, true, false)."<row><field id='$ixf'><![CDATA[".$Index."]]></field></row></recordset>";
//if($ZName=="y_pagedef_content")die($X);

		$D = new DOMDocument;
		$D->loadXML($X);
		return $D;
		}

	static function GetZObjectCreateQuery($Index, $ZName, $ZMode, $ZArgs, $Key, $prefix, &$rc)
		{
//print "<br/>GetZObjectCreateQuery($Index, $ZName, $ZMode, $ZArgs, $Key, $prefix)";
//log_file("zobject", "GetZObjectCreateQuery($Index, $ZName, $ZMode, $ZArgs, $Key, $prefix)");
		$ixf=juniper()->FetchObjPart($ZName, "@index");
//log_file("zobject","ixf=$ixf");
		

		$X = self::recordset_header($ZName, $ZMode, 1, "")."\n<row>";
		foreach(juniper()->FetchObjFields($ZName) as $l)
			{
//print "<br/>l=<b>$l</b>, ZName=$ZName";
//log_file("zobject","l=$l, ZName=$ZName, ixf=$ixf, Key=".iOBJ()->options['key'];

			$v = "";
			if ($l == $ixf) $v = juniper_querystring::get_querystring_var($ZArgs, iOBJ()->options['key']);
			if ($v=="") $v = juniper()->php_hook(juniper()->FetchObjFieldPart($ZName, $l, "@default"), $ZArgs);
			if ($v=="") $v = juniper()->php_hook(juniper()->FetchDTPart(juniper()->FetchObjFieldPart($ZName, $l, "@datatype"), "@default"), $ZArgs);
//log_file("zobject","v=$v");
			$X = $X . "<field id='$l'><![CDATA[$v]]></field>";
			}

		$X .= "</row></recordset>";
//if($ZName=="y_pagedef_content")die($X);
//log_file("zobject",$X);

		$D = new DOMDocument;
		$D->loadXML($X);
		return $D;
		}



/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////

	static function GetZObjectSQL($ZName, $ZMode, $ZArgs)
		{
//print "<br/>GetZObjectSQL($ZName, $ZMode, $ZArgs)";
		$sql = juniper()->FetchObjPart($ZName, "sql[@type='$ZMode']");
		if ($sql=="") print "<br/>No SQL for requested operation: $ZMode";
//print "<br/>sql[@type='$ZMode']: $sql";
//		if ($sql=="") $sql = juniper()->FetchObjPart($ZName, "sql");
//print "<br/>GetZObjectSQL($ZName, $ZMode, $ZArgs): $sql";
		return $sql;
		}

	static function SaveZObjectQuery($ZName, $ZMode, $ZArgs, $v, $new_key="")
		{
//log_file("zobject", "GetZObjectQuery($ZName, $ZMode, $ZArgs, ..., $new_key)");
//print "<br/>SaveZObjectQuery($ZName, $ZMode, $ZArgs, ..., $new_key)";

		switch( self::data_mode($ZName) )
			{
			case "wpdb":	include_once("class-zobject-db-wpdb.php"); $o = new zobject_db_wpdb(); break;
			case "mysql":	include_once("class-zobject-db-mysql.php"); $o = new zobject_db_mysql(); break;
			}

		switch($ZMode)
			{
			case "delete": $tmode = "delete";break;
			case "create": $tmode = "insert";break;
			case "edit": $tmode = "update";break;
			default: $tmode=""; break;
			}

		$sql = self::GetZObjectSQL($ZName, $tmode, $ZArgs);
//print "<br/>sql=$sql";
		$sql = zobject_db::InterpretInteractiveSQL($sql, $ZArgs);
		if ($ZMode == "create" || $ZMode == "edit") $sql = zobject_db::BuildZObjectQuery($sql, $v, self::data_mode($ZName));

		$sql = $o->prepare_sql($sql);
//print "<br/>datamode=". self::data_mode($ZName). ", sql=$sql";		
		$o->execute($sql, '', $rc);

		return true;
		}

	static function GetZObjectQuery($ZName, $ZMode, $ZArgs, $Key="", $Ix="", $prefix="", $rc="")
		{
//log_file("zobject", "GetZObjectQuery($ZName, $ZMode, $ZArgs, $Key, $Ix, $prefix)");
//print "<br/>GetZObjectQuery($ZName, $ZMode, $ZArgs, $Key, $Ix, $prefix)";

		$Extras = "zname='$ZName' zmode='$ZMode' ixf='$ixf'";

		switch( self::data_mode($ZName) )
			{
			case "wpdb":	include_once("class-zobject-db-wpdb.php"); $o = new zobject_db_wpdb(); break;
			case "mysql":	include_once("class-zobject-db-mysql.php"); $o = new zobject_db_mysql(); break;
			}

		switch($ZMode)
			{
			case "edit": case "display": $tmode = "select";break;
			case "list": $tmode = "list";break;
			default: $tmode=""; break;
			}

		$sql = self::GetZObjectSQL($ZName, $tmode, $ZArgs);
//print "<br/>sql=$sql";
		$sql = zobject_db::InterpretInteractiveSQL($sql, $ZArgs);
//		$sql = zobject_db::BuildZObjectQuery($sql, $v);

		$sql = $o->prepare_sql($sql);
//print "<br/>sql=$sql";
		$X = $o->execute_to_xml($sql, $Extras , $rc);
//die($X);
//self::save_log("zobject", $X);
		$D = new DOMDocument;
		$D->loadXML($X);
		return $D;
		}

	static function GetZObjectMultiQuery($ZName, $ZMode, $ZArgs, $Key, $prefix, &$rc)
		{
//print "<br/>GetZObjectMultiQuery($ZName, $ZMode, $ZArgs, $Key, $prefix, $AsList)";
		$S1 = juniper()->FetchObjPart($ZName, "sql[@type='$ZMode']");
		$S2 = juniper()->FetchObjPart($ZName, "sql[@type='list']");
		$S3 = juniper()->FetchObjPart($ZName, "sql");
		$ActualSQL = ChooseBest($S1, $S2, $S3); //FetchObjPart($ZName, "sql");
//print "<br/>Multi-ActualSQL=$ActualSQL";

		switch( self::data_mode($ZName) )
			{
			case "wpdb":	include_once("class-zobject-db-wpdb.php"); $o = new zobject_db_wpdb(); break;
			case "mysql":	include_once("class-zobject-db-mysql.php"); $o = new zobject_db_mysql(); break;
			}

		$Extras = "zname='$ZName' zmode='$ZMode' ixf='$ixf'";

		$ActualSQL = zobject_db::InterpretInteractiveSQL($ActualSQL, $ZArgs);
		$X = $o->execute_to_xml($ActualSQL, $Extras, $rc);
		unset($o);
//print $X;die();
		
		$D = new DOMDocument;
		$D->loadXML($X);
		return $D;
		}
		
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////

	static function GetXMLFile($ZName, $ZArgs, &$Lst="", &$Bse="", &$d="")
		{
//print "<br/>GetXMLFile($ZName, $ZArgs, &$Lst, &$Bse, &$d)";

		$id = juniper()->FetchObjPart($ZName, 'xmlfile/@src');
//print "<br/>id=$id";
		if (juniper()->is_php_hook($id))
			{
			$Lst = juniper()->FetchObjPart($ZName, 'xmlfile/@list');
			$Bse = juniper()->FetchObjPart($ZName, 'xmlfile/@base');
			}
		else
			{
			if (strstr($id, ".xml") !== false) // specified xml file
				{
				$d = $id;
				$Lst = juniper()->FetchObjPart($ZName, 'xmlfile/@list');
				$Bse = juniper()->FetchObjPart($ZName, 'xmlfile/@base');
				}
			else					// prob id
				{
				$d = juniper()->FetchDSPart($id, '@src');
				$Lst = ChooseBest(juniper()->FetchObjPart($ZName, 'xmlfile/@list'), juniper()->FetchDSPart($id, '@list'));
				$Bse = ChooseBest(juniper()->FetchObjPart($ZName, 'xmlfile/@base'), juniper()->FetchDSPart($id, '@base'));
				$M = juniper()->FetchDSPart($id, '@module');
				if (file_exists(WP_PLUGIN_DIR . "/zobjects/modules/$M/$d")) $d = WP_PLUGIN_DIR . "/zobjects/modules/$M/$d";
				}
			}

		$Lst = juniper()->php_hook($Lst, $ZArgs);
		if ($Bse[strlen($Bse)-1]!='/') $Bse = $Bse . "/";


//print "<br/>src=$id, lst=$Lst, Bse=$Bse";
		if ($Lst=="") {juniper()->backtrace();Warning("No listpath for $ZName. (OBJFILE::/zobjectdefs/zobjectdef[@id='$ZName']/xmlfile/@list");die();}
		if ($Bse=="") {juniper()->backtrace();Warning("No basepath for $ZName. (OBJFILE::/zobjectdefs/zobjectdef[@id='$ZName']/xmlfile/@base");die();}

		if (juniper()->source_exists($id)) return juniper()->get_source($id);

//		if (!file_exists($d)) $d = ZOSOURCE_DIR . $d;

//print "<br/>query-xmlfile id=$id, d=$d";
		if (!juniper()->is_php_hook($id)) return juniper()->force_document($id, $d);
		$d = $id;
		return null;
		}

	function GetXMLAutoNumber()
		{
//print "<br/>zobject::XMLAutoNumber - FIX ME";
//log_file("XMLAutoNumber","===== zobject::XML AUTO NUBMER - FIX ME");
		$D = self::GetXMLFile(iOBJ()->name, iOBJ()->args, $L);
		$L = iOBJ()->FillInQueryStringKeys($L, iOBJ()->args);
//log_r("XMLAutoNumber", $L);
		$S = $D->fetch_list($L);
		$n = max($S)+1;
//log_file("XMLAutoNumber", "n=$n");
		return $n;
		}
		
	static function GetZObjectXmlFile($ZName, $ZMode, $ZArgs, &$rc)
		{
//print "<br/>GetZObjectXmlFile($ZName, $ZMode, $ZArgs)";
		if ($ZName=="") {Warning("<font style='font-weight:bold;font-size:20'>DIE:</font> <u>No ZName in GetZObjectXmlFile</u>");die();}

		$rc = 1;
		$x = self::recordset_header($ZName, $ZMode, 1, "");
		$x = $x . "  <row>\n";
		
		$D = self::GetXMLFile($ZName, $ZArgs, $l, $b, $d);
		if (!$D)
			{
			$D = juniper()->php_hook($d);
			if (is_string($D)) $D = juniper()->force_unknown_document($D);
			}

		$b = iOBJ()->FillInQueryStringKeys($b, $ZArgs);

//print "<br/>b=$b, f=".juniper()->php_hook(juniper()->FetchObjPart($ZName, 'xmlfile/@src'), $ZArgs);
		if (!isset($D)) 
			{
			Warning("Failed to load file: $d", "ZObj::GetZObjectXMLFile");
			juniper()->backtrace();
			return "";
			}

		if ($D->count_parts(substr($b, 0, strlen($b)-1))==0) 
			{
print "<br/>GetZObjectXmlFile - no parts, empty recordset.  <br/><b>b=</b>$b<br/><b>D=</b>$D";
			return self::empty_recordset($ZName, $ZMode, $rc);
			}

		$index = iOBJ()->options['index'];
		$key = iOBJ()->options['key'];
		$ixval = juniper_querystring::get_querystring_var($ZArgs, $key);
//print "<br/>index=$index, key=$key, ixval=$ixval";
//print "<br/>fields: ".print_r(juniper()->FetchObjFields($ZName));

		foreach(juniper()->FetchObjFields($ZName) as $l)
			{
			if ($l == $index)
				$v = $ixval;
			else
				{
//print "<br/>l=<b>$l</b>";
				$m = xml_file::extend_path($b, $l, juniper()->FetchObjFieldPart($ZName, $l, "@access"));
//print "<br/>m=$m";
//				$M = TrueFalseVal(juniper()->FetchObjFieldPart($ZName, $l, "@multiple"), false);
				$M = juniper()->FetchObjFieldPart($ZName, $l, "@multiple")=="1";
//print "<br/>Multiple? " . YesNo($M);
				$d = juniper()->FetchObjFieldPart($ZName, $l, "@datatype");
//print "<br/>field datatype=$d";
				if (substr($d, 0, 1) == ":") $v = "";
					else $v = $M ? self::GetMultiValuesFromDoc($D, $m) : $v = $D->fetch_part($m);
				}
			if ($v=="") $v = juniper()->php_hook(juniper()->FetchObjFieldPart($ZName, $l, "@default"), $ZArgs);
			if ($v=="") $v = juniper()->php_hook(juniper()->FetchDTPart($d,"@default"), $ZArgs);
//print "<br/>v=<u>$v</u>";

			$x .= "    <field id='$l'>";
			$x .= "<![CDATA[$v]]>";
			$x .= "</field>\n";
//			$x .= "    <field id='$l'><![CDATA[$v]]></field>\n";
			}

		$x .= "  </row>\n";
		$x .= "</recordset>\n";
//log_file("GetZObjectXmlFile", $x);log_file("GetZObjectXmlFile","-----------------");
//print $x;die();
//$x=str_replace(array("\n"," "),array("<br/>","&nbsp;"),ESKf($x));print $x;die();

		$D = new DOMDocument;
		$D->loadXML($x);
		return $D;
		}
		
	static function GetZObjectMultiXmlFile($ZName, $ZMode, $ZArgs, &$rc)
		{
//print "<br/>GetZObjectMultiXmlFile($ZName, $ZMode, $ZArgs)";
		if ($ZName=="") {Warning("<span style='font-weight:bold;font-size:20'>DIE:</span> <u>No ZName in GetZObjectXmlFile</u>");juniper()->backtrace();die();}

		$x = "";
		$rx = $x . "<?xml version='1.0' encoding='ISO-8859-1'?>\n";

		$D = self::GetXMLFile($ZName, $ZArgs, $listpath, $itempath, $F);
//print "<br/>GetZObjectMultiXmlFile: F=$F";

		$fl = juniper()->FetchObjFields($ZName);		// field list
		$fc = count($fl);
//print "<br/>Field List (n=$fc):";print_r($fl);

//print "<br/>listpath=$listpath";
		$listpath = juniper()->php_hook($listpath, $ZArgs);
//print "<br/>";print_r($listpath);
		if (is_array($listpath)) $f = $listpath;  // php_hook returned an array!
		else 
			{
			if (isset($D)) $lD = $D;
			if (!isset($lD) && juniper()->is_php_hook($F)) 
				{
//print "<br/>F=$F, td=$td";
				$td = juniper()->php_hook($F,'');
//print "<br/>F=$F, td=$td";
				if (is_string($F)) $lD = juniper()->force_unknown_document($td);
				else if (is_object($F)) $lD = $F;
				}
//print "<br/>ld=$lD";
			if (isset($lD))
				{
//print "<br/><b>listpath</b> = $listpath, <b>itempath</b>=$itempath";
				$listpath = iOBJ()->FillInQueryStringKeys($listpath, $ZArgs, false);
				$itempath = iOBJ()->FillInQueryStringKeys($itempath, $ZArgs, false);
//print "<br/><b><u>Altered:</u></b> <b>listpath =</b> $listpath, <b>itempath=</b>$itempath";
		
				$oix = iOBJ()->options['index'];
				if ($oix=="position()" || $oix == "")
					{
//print "<br/>Positioned elements: $ZName";
					$nn = $lD->count_parts($listpath);
					for ($f=array(),$i=1;$i<=$nn;$i++) $f[$i]=$i;
					}
				 else
					$f = $lD->fetch_list($listpath);
				}
			}

//print "<br/>f: ";print_r($f);
		$rc = count($f);
		$x = self::recordset_header($ZName, $ZMode, count($f));

		$fieldinfo=array();

		foreach($fl as $fld)
			{
//print "<br/>i=$i, fl[i]=".$fl[$i]."  ";print_r($fl);
			$tmp = array();
			$tmp["datatype"] = juniper()->FetchObjFieldPart($ZName, $fld, "@datatype");
			$tmp["default"] = juniper()->FetchObjFieldPart($ZName, $fld, "@default");
			$tmp["multiple"] = YesNoVal(juniper()->FetchObjFieldPart($ZName, $fld, "@multiple"),false);
			$tmp["access"] = juniper()->FetchObjFieldPart($ZName, $fld, "@access");
			$fieldinfo[$fld] = $tmp;
			}
//print "<br/>field defs: ";print_r($fieldinfo);

		$key = '@'.iOBJ()->options['key'];
		$index = iOBJ()->options['index'];
//print "<br/>key=$key, index=$index";

		foreach($f as $rowx)
			{
			$tA = juniper_querystring::add_querystring_var($ZArgs, substr($key, 1), $rowx);
			if (juniper()->is_php_hook($F))
				{
//print "<br/>F=$F";
//print "<br/>tA=<b>$tA</b>, F=<b><u>$F</u></b>, actual file=<b>".juniper()->php_hook($F, $tA)."</b>";
				unset($D);
				$Did = juniper()->add_file(juniper()->php_hook($F));
				$D = juniper()->get_source($Did);
//print "<br/>isset(D)=".(isset($D)?'y':'n');
				}

//print "<br/>index=$index, key=$key, rowx=$rowx, itempath=$itempath";
			$x = $x . "  <row>\n";
			$tp = str_replace($key, $rowx, $itempath);
//print "<br/>tp=$tp";

			foreach($fl as $l)
				{
//				if ($l == substr($key,1))
				if ($l == $index)
					$x .= "    <field id='$l'><![CDATA[$rowx]]></field>\n";
				else
					{
//print "<br/><b>l=$l</b>";
					$m = xml_file::extend_path($tp, $l, $fieldinfo[$l]["access"]);
//print "<br/>m=$m";
					$M = $fieldinfo[$l]['multiple'];
//print "<br/>Multiple? " . YesNo($M);
//print "<br/>field datatype=".$fieldinfo[$l]["datatype"];
					if (substr($fieldinfo[$l]["datatype"], 0, 1) == ":") $v = "";
					 else $v = $M ? GetMultiValuesFromDoc($D, $m) : $v = $D->fetch_part($m);
					if ($v=="") $v = juniper()->php_hook($fieldinfo[$l]["default"], $tA);
					if ($v=="") $v = juniper()->php_hook(juniper()->FetchDTPart($fieldinfo[$l]["datatype"],"@default"), $tA);
//print "<br/>v=<u>$v</u>";

					$x .= "    <field id='$l'><![CDATA[$v]]></field>\n";
					}
				}

			$x .= "  </row>\n";
			}
		$x .= "</recordset>\n";
//die($x);

//print "<br/>ZArgs=$ZArgs";
//if ($ZName=='y_module_file') die($x);
		$D = new DOMDocument;
		$D->loadXML($x);
		return $D;
		}


//////////////////////////////////////////////////////////////////////////////////////////



	private function SaveZObjectToXMLFile($ZName, $ZMode, $v)
		{
		$o = iOBJ();							// zobject
		$ZArgs = $o->args;
		$D = self::GetXMLFile($ZName, $o->args, $l, $base, $d);
		if (!$D)
			{
			$D = juniper()->php_hook($d);
			if (is_string($D)) $D = juniper()->force_unknown_document($D);
			}

//self::save_log("base=$base, Args=$ZArgs, key=".$o->options['key'].", index=".$o->options['index'].", KV=".juniper()->KeyValue($o->options['index']));

		$nkv = juniper()->KeyValue($ix = $o->options['index']);
		if ($nkv == "") 
			{
			$def = juniper()->FetchObjFieldPart($ZName, $ix, "@default");
//self::save_log("def=$def");
			$nkv = iOBJ()->NormalizeInputField(juniper()->php_hook($def, $ZArgs), juniper()->FetchObjFieldPart($ZName, $ix, "@datatype"));
			}

		if ($ZMode=="create")
			{
			$base = str_replace('@'.$o->options['key'], $nkv, $base);
//self::save_log("index=$index, nkv=$nkv, def=$def, base=$base");
			$ZArgs = juniper_querystring::add_querystring_var($ZArgs, $ix, $nkv);
			}
		else
			$base = str_replace('@'.$o->options['key'], juniper()->KeyValue($o->options['key'], $ZArgs), $base);

//self::save_log("ix=$ix, nkv=$nkv, base=$base, args=".iOBJ()->args);

		$base = $o->FillInQueryStringKeys($base);

//self::save_log("ix=$ix, nkv=$nkv, base=$base");

		$found = false;
		foreach(juniper()->FetchObjFields($ZName) as $fid)
			{
//self::save_log("FID=$fid");
			if (!zobject_access::zobject_field_access($ZName, $fid, $ZMode)) continue;

			$fa = juniper()->FetchObjFieldPart($ZName, $fid, "@access");
			if ($fa == "-") continue;
			if ($fa == "@") $fa = "@" . $fid;
			if ($fa == "")  $fa = $fid;
//self::save_log("fa=$fa");

			$fv = $v[$fid];
//self::save_log("FId=$fid, fa=$fa, fv=$fv");
			if (!is_array($fv))
				{
//self::save_log("SET: $base$fa ===> $fv");
				$D->set_part($base . $fa, $fv);
//print "<br/>mod=".$D->modified;
				}
			else
				{
				$n = 0;
				$deleted = 0;
				foreach($fv as $fvv)
					{
					$fl = xml_file::add_field_accessor($base.$fa);
					$fl = xml_file::replace_field_accessor($fl, $n-$deleted);
					$D->set_part(fl, $fvv);
					if ($fvv == "") $deleted++;
					}
				}
			}

//die();

//self::save_log("<font size=+3>Save to (SaveZObjectToXMLFile): <b><u>$file</u></b></font>");
//self::save_log(""file=".(is_object($file)?"	.":$file));
		return $found;
		}



/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////

	static function GetZObjectPHP($ZName, $ZMode, $ZArgs, $prefix="", &$rc=0)
		{
//log_file("zobject", "GetZObjectPHP($ZName, $ZMode, $ZArgs, $Key, $Ix, $prefix)");
//print "<br/>GetZObjectPHP($ZName, $ZMode, $ZArgs, $prefix)";
		$rc = 1;
		$f = juniper()->FetchObjPart($ZName, "phpsource/@item");

		$key = iOBJ()->options['key'];
		$val = juniper_querystring::get_querystring_var($ZArgs, $key);
//log_file("zobject", "hook=$f, val=$val");
//print "<br/>hook=$f, key=$key, val=$val";
		$a = juniper()->php_hook($f, $val);

		if (!is_array($a)) return XMLToDoc(self::empty_recordset($ZName, $ZMode, $rc));

		$x = self::recordset_header($ZName, $ZMode, 1, "")."";
		$x = $x . "  <row>\n";
		foreach($a as $b=>$c) 
			{
//print "<br/>b=".$b;
			if ($c=="") $c = juniper()->php_hook(juniper()->FetchObjFieldPart($ZName, $b, "@default"), $ZArgs);
			if ($c=="") $c = juniper()->php_hook(juniper()->FetchDTPart(juniper()->FetchObjFieldPart($ZName, $b, "@datatype"),"@default"), $ZArgs);
			$x = $x . "    <field id='$b'><![CDATA[$c]]></field>\n";
			}
		$x = $x . "  </row>\n";
		$x = $x . "</recordset>\n";

//die($x);

		$D = new DOMDocument;
		$D->loadXML($x);
		return $D;
		}

	static function GetZObjectMultiPHP($ZName, $ZMode, $ZArgs, &$rc)
		{
//log_file("zobject", "GetZObjectMultiPHP($ZName, $ZMode, $ZArgs)");
//print "<br/>GetZObjectMultiPHP($ZName, $ZMode, $ZArgs)";
		$l = juniper()->FetchObjPart($ZName, "phpsource/@list");
//log_file("zobject", "list hook=$l");
//print "<br/>list hook=$l";
		$L = juniper()->php_hook($l, $ZArgs);
//print "<br/>list=";print_r($L);

		if (!is_array($L) || !count($L)) return self::empty_recordset($ZName, $ZMode, $rc);

		$f = juniper()->FetchObjPart($ZName, "phpsource/@item");
//log_file("zobject", "hook=$f");
//print "<br/>hook=$f";
		$token = "@@RECORD_COUNT-".uniqid()."@@";
		$x = self::recordset_header($ZName, $ZMode, $token, "");
		$rc=0;

		$key = '@'.iOBJ()->options['key'];
		$index = iOBJ()->options['index'];
//print "<br/>key=$key, index=$index";

		foreach($L as $item)
			{
			$tA = juniper_querystring::add_querystring_var($ZArgs, substr($key, 1), $item);

//print "<br/>item=$item";
			if (is_array($a = juniper()->php_hook($f, $item)))
				{
//print "<br/>item is...";  print_r($a);
				$rc++;
				$x = $x . "  <row>\n";
				foreach($a as $b=>$c) 
					{
					if ($c=="") $c = juniper()->php_hook(juniper()->FetchObjFieldPart($ZName, $b, "@default"), $tA);
					if ($c=="") $c = juniper()->php_hook(juniper()->FetchDTPart(juniper()->FetchObjFieldPart($ZName, $b, "@datatype"),"@default"), $tA);
					$x = $x . "    <field id='$b'><![CDATA[$c]]></field>\n";
					}
				$x = $x . "  </row>\n";
				}
			}
		$x = $x . "</recordset>\n";
		$x = str_replace($token, $rc, $x);
//die($x);

		$D = new DOMDocument;
		$D->loadXML($x);
		return $D;
		}


	static function SaveZObjectToPHP($ZName, $ZMode, $val)
		{
//log_file("zobject", "SaveZObjectToPHP($ZName, $ZMode, $ZArgs)");
//print "<br/>SaveZObjectToPHP($ZName, $ZMode, $ZArgs)";

		$s = juniper()->FetchObjPart($ZName, "phpsource/@save");
		$r = juniper()->php_hook($s, $val);
		return $r;
		}


/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////


	static function GetZObjectWPOQuery($ZName, $ZMode, $ZArgs, &$rc)
		{
//print "<br/>GetZObjectWPOQuery...";
		if (!function_exists("get_option")) return self::empty_recordset();		// wp tie-in

		$s  = self::recordset_header($ZName, $ZMode, 1); 
		$s .= "  <row>\n";
		foreach(juniper()->FetchObjFields($ZName) as $f)
			$s .= "    <field id='$f'><![CDATA[" . get_option($f) . "]]></field>\n";
		$s .= "  </row>\n";
		$s .= "</recordset>\n";
		
//wp_die($s);

		$D = new DOMDocument;
		$D->loadXML($s);
		return $D;
		}

	static function SaveZObjectToWPO($ZName, $ZMode, $ZArgs)
		{
//print "<br/>SaveZObjectToWPO($ZName, $ZMode, $ZArgs)";
		if (!function_exists("update_option")) return false;
		foreach(juniper()->FetchObjFields($ZName) as $f)
			{
//print "<br/>field=$f, val=" . $_REQUEST[$f];
			if (isset($_REQUEST[$f])) update_option($f, $_REQUEST[$f]);
			}
		return true;
		}
	}
