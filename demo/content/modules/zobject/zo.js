function zoAjaxURL(e)		{return "/wp-admin/admin-ajax.php"+e;}
function zoAjaxArgs(t, c)		{return "action=zobjects&_AJAX=1&_"+t+"=1&_ZA="+c;}

ConfirmCmd = "";
ConfirmTgt = "";
ConfirmSrc = "";

function zoModalLoadError()
	{
	var dlg = jQuery("<div id='modal-dialog-notify-fail' title='Action Failed' />").html("<p align='center'>The AJAX action failed to complete.</p>").appendTo("body");

	dlg.dialog({
			'dialogClass' : 'wp-dialog','modal' : true,'autoOpen' : false,'closeOnEscape' : true
			,'buttons' : 
				{
				'OK' :     {'text':'OK', 'class' : 'button-primary', 'click' : function() {jQuery(this).dialog('close').remove();} },
				}
		}).dialog('open');
	}

function ModalForm(title, content)
	{
	var dlg = jQuery("<div id='modal-dialog' title='Confirm Action' />").html("<p align='center'>" + content + "</p>").appendTo("body");

	dlg.dialog({
			'dialogClass' : 'wp-dialog','modal' : true,'autoOpen' : false,'closeOnEscape' : true, 'width':500
			,open: function(event, ui) { jQuery('#ajax-form').validate({'wrapper':'p'}); }
			,'buttons' : 
				{
				'Cancel' : {'text':'Cancel', 'class' : 'button-secondary', 'click' : function() {jQuery(this).dialog('close').remove();} },
				'OK' :     {'text':'OK', 'class' : 'button-primary', 
						'click' : function() {
								if (!jQuery('#ajax-form').validate().form()) return;
								jQuery.post(zoAjaxURL('?action=zobjects'),
										jQuery('#ajax-form').serialize(),
										function(msg) {
												zoGetObjToTarget(ConfirmTgt, ConfirmSrc);
												jQuery("#modal-dialog").dialog('close').remove();
											}
								);} 
						},
				}
		}).dialog('open');
	}

function zoGetObjToTarget(target, obj)
	{
	jQuery.ajax( { 'url':zoAjaxURL(''), 'data':zoAjaxArgs('ZObj',obj) } )
		.fail(function() { zoModalLoadError(); })
		.done(function( msg ) { jQuery('#'+target).html(msg).effect("highlight", {}, 1500);});
	}

function zoGetObjToDialog(obj, target, src)
	{
	ConfirmTgt = target;
	ConfirmSrc = src;
	jQuery.ajax( { 'url':zoAjaxURL(''), 'data':zoAjaxArgs('ZObj',obj) } )
		.fail(function() { zoModalLoadError(); })
		.done(function( msg ) { ModalForm("Edit Item", msg);});
	}

function zoExecuteToItem(cmd, target, obj)
	{
	ConfirmTgt = target;
	ConfirmSrc = obj;
	jQuery.ajax( { 'url':zoAjaxURL(''), 'data':zoAjaxArgs('Save',cmd) } )
		.fail(function() { zoModalLoadError(); })
		.done(function( msg ) { zoGetObjToTarget(ConfirmTgt, ConfirmSrc); });
	}

function zoModalConfirmOK() 
	{
	jQuery.ajax( { 'url':zoAjaxURL(''), 'data':zoAjaxArgs('Save',ConfirmCmd) } )
		.fail(function() { zoModalLoadError(); })
		.done(function( msg ) { zoGetObjToTarget(ConfirmTgt, ConfirmSrc); });
	}

function zoModalConfirmItem(c, cmd, target, source)
	{
	var dlg = jQuery("<div id='modal-dialog' title='Confirm Action' />").html("<p align='center'>" + c + "</p>").appendTo("body");
	
	ConfirmCmd = cmd;
	ConfirmTgt = target;
	ConfirmSrc = source;

	dlg.dialog({
			'dialogClass' : 'wp-dialog','modal' : true,'autoOpen' : false,'closeOnEscape' : true
			,'buttons' : 
				{
				'Cancel' : {'text':'Cancel', 'class' : 'button-secondary', 'click' : function() {jQuery(this).dialog('close').remove();} },
				'OK' :     {'text':'OK', 'class' : 'button-primary', 'click' : function() {jQuery(this).dialog('close').remove();zoModalConfirmOK();} },
				}
		}).dialog('open');
	}

//////////////////////////////////////////////
//////////////////////////////////////////////

function ConfirmDelete($S)
	{
//	ModalForm('hi');
	ModalConfirm("Really Delete?", "alert('333')");
//	alert("Confirm Delete");
	}

//////////////////////////////////////////////
//////////// COMBO BOX FUNCTIONS /////////////
//////////////////////////////////////////////
var fActiveMenu = false;
var oOverMenu = false;

function mouseSelect(e)
{
	if (fActiveMenu)
	{
		if (oOverMenu == false)
		{
			oOverMenu = false;
			document.getElementById(fActiveMenu).style.display = "none";
			fActiveMenu = false;
			return false;
		}
		return false;
	}
	return true;
}

function menuActivate(idEdit, idMenu, idSel)
{
//alert("menuActivate");
	if (fActiveMenu) return mouseSelect(0);

	oMenu = document.getElementById(idMenu);
	oEdit = document.getElementById(idEdit);
	nTop = parseInt(oEdit.offsetTop) + parseInt(oEdit.offsetHeight) + 2;
	nLeft = parseInt(oEdit.offsetLeft)+2;
	x="";
	while (oEdit.offsetParent != document.body)
	{
		oEdit = oEdit.offsetParent;
		if (oEdit.tagName=="DIV") break;
		nTop += parseInt(oEdit.offsetTop);
		nLeft += parseInt(oEdit.offsetLeft);
		x = x + "\n" + oEdit.tagName + " @ " + nLeft + "x" + nTop + "(" + oEdit.style.position + "," + oEdit.style.display+ ")";
		if (oEdit.tagName=="DIV") break;
	}
//alert("menuActivate: "+x+"\n"+nLeft + "x" + nTop);
	oMenu.style.left = nLeft + "px";
	oMenu.style.top = nTop + "px";
	oMenu.style.display = "";
	fActiveMenu = idMenu;
	x = document.getElementById(idSel);
	if (x) x.focus();
	return false;
}

function textSet(idEdit, o)
{
	text=o.options[o.selectedIndex].text;
	val=o.options[o.selectedIndex].value;
	document.getElementById(idEdit+"TXT").value = text;
	document.getElementById(idEdit+"HID").value = val;
	oOverMenu = false;
	mouseSelect(0);
	document.getElementById(idEdit).focus();
}

function comboKey(idEdit, idSel)
{
	if (window.event.keyCode == 13 || window.event.keyCode == 32)
		textSet(idEdit,idSel);
	else if (window.event.keyCode == 27)
	{
		mouseSelect(0);
		document.getElementById(idEdit).focus();
	}
}
document.onmousedown = mouseSelect;
