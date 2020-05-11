<?php

class juniper_action 
	{
	public $ID;
	public $value;
	public $ref;

	function __construct()
		{
		$n = func_num_args();
		$a = func_get_args();
		$this->ID    = ($n >= 1 && is_string($a[0]) ? $a[0] : "");
		$this->value = ($n >= 2 && is_string($a[1]) ? $a[1] : "");
		$this->ref   = ($n >= 3 && is_string($a[2]) ? $a[2] : "");
		$this->args  = ($n >= 4 && is_string($a[3]) ? $a[3] : "");
		$this->_gid = uniqid("ZACT_");
		}

	private function get_val($n, $x)
		{
		switch($n)
			{
			case "id": return $this->ID = ($x != "" ? $x : ($this->ID != "" ? $this->ID : urldecode(@$_REQUEST["ActionID"])));
			case "value": return $this->value = ($x != "" ? $x : ($this->value != "" ? $this->value : urldecode(@$_REQUEST["ActionValue"])));
			case "ref": return $this->ref = ($x != "" ? $x : ($this->ref != "" ? $this->ref : base64_decode(@$_REQUEST["ActionRef"])));
			case "args": return $this->args = ($x != "" ? $x : ($this->args != "" ? $this->args : $this->get_args()));
			}
		}

	private function get_args()
		{
		$args = "";
		foreach($_REQUEST as $a=>$b) $args = juniper_querystring::add_querystring_var($args, $a, $b);
		if (strlen($args)>0) $Args = "?" . $args;

		return $args;
		}

	private function part($p)          {return juniper()->FetchActPart($this->ID, $p);}
	private function rule_part($r, $p) {return juniper()->FetchActRulePart($this->ID, $r, $p);}

	private function log($m, $r = "")
		{
//return;
		if ($_SERVER['SCRIPT_FILENAME']==__FILE__)
			{
			print "<br>".$m;
			if ($r != "") print_r($r);
			}
		else
			{
			log_file("zaction",$m);
			if ($r != "") log_r($r);
			}
		}

	function execute($aID="", $aValue="", $aRef="", $args="")
		{
//$this->log("zaction::execute($aID, $aValue, $aRef, $args), REQ:", $_REQUEST);
		$aID = $this->get_val("id", $aID);
		$aValue = $this->get_val("value", $aValue);
		$aRef = $this->get_val("ref", $aRef);
		$args = $this->get_val("args", $args);
		
//$this->log("PerformZAction(): action=$aID, actionv=$aValue, action=$aRef, args=$args

		$t = $this->part("@rule-type");
//$this->log("t=[$t], ".$this->rule_part("*", "@value"));

		if ($this->rule_part($aValue, "@value")!="") $rref = $aValue;
		else if ($this->rule_part("*", "@value")!="") $rref = "*";
		else return;
		
//$this->log("t=[$t], rref=$rref");
		switch($t)
			{
			case 'sql':
				$i = $this->part("@require");
				$i = zobject_keys::TranslateKeyList($i);
				$a = $this->rule_part($rref, "text()");
				$a = str_replace("@@Value", $aValue, $a);
//$this->log(":: i=$i, a=$a");
				foreach(explode(',',$i) as $l)
					{
//$this->log("::: l=$l");
					$val = $KeyValue($l, $args);
					$a = str_replace("@$l", $val, $a);
//$this->log("::: a=$a");
					}
				DBExecute($a);
				break;
			case 'php':
				$cmd = trim($this->rule_part($rref, ""));
//$this->log("php command($aID, $rref): $cmd");
				if ($cmd!="")
					{
					$f = "$cmd('$aValue', '$args');";
					eval($f);
					}
				break;
			case "replace": break;
			default:
				die("<br/>Don't know how to handle action type: $t");
			}

		if ($aRef != "") die(header("Location: $aRef")); 
		}		
	}

function class_zaction_test_output($r="")
	{
print "<br/>CZTO: $r";
	}

function class_zaction_test()
	{
	include_once('module_test.php');
	zobject_test_header("ZACTION");

	$testname = "Create Object";
	$Z = new zobject_action("testaction", "", "", "");
	$testresult = $Z->execute();
	$testexpect = "";
	$testok = ($testresult == $testexpect);
	zobject_test_result($testname, $testresult, $testok, $A);
	
	zobject_test_footer();
	}

if (($_SERVER['SCRIPT_FILENAME']==__FILE__)) class_zaction_test();

