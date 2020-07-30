<?xml version="1.0" encoding="ISO-8859-1" ?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" xsl:extension-element-prefixes="php" exclude-result-prefixes="php">

	<xsl:template name='data-field'>
		<xsl:param name='iDataTypes'/>
		<xsl:param name='F'/>
		<xsl:param name='ZName'/>
		<xsl:param name='FormZMode'/>
		<xsl:param name='ZMode'/>
		<xsl:param name='FID'/>
		<xsl:param name='datatype'/>
		<xsl:param name='name'/>
		<xsl:param name='value'/>
		<xsl:param name='source'/>
		<xsl:param name='isMultiple'/>
		<xsl:param name='validation' />
		<xsl:param name='remote' />

		<xsl:variable name='display' select='php:functionString("zobject_access::check_field", string($ZName), string($FID), string($ZMode))'/>
        <xsl:variable name='HType'>
            <xsl:choose>
                <xsl:when test='string($iDataTypes/*/typedef[@name=$datatype]/@html-type) != ""'><xsl:value-of select='string($iDataTypes/*/typedef[@name=$datatype]/@html-type)' /></xsl:when>
                <xsl:when test='string($datatype) != ""'><xsl:value-of select='string($datatype)' /></xsl:when>
                <xsl:otherwise>text</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

		<xsl:variable name='nValue'><xsl:value-of select='php:function("zobject_format::FormatDataField", string($value), string($datatype))'/></xsl:variable>

        <xsl:if test='php:function("zobject::DEBUG_TRANSFORM_DATA_FIELD")'>
            <table class='DEBUG'>
                <tr><td colspan='2' class='title'>DATAINPUT.XSL - data-field</td></tr>
                <tr><th>Var</th><th>Val</th></tr>
                <tr><td>ZName</td><td><xsl:value-of select='$ZName'/></td></tr>
                <tr><td>ZMode</td><td><xsl:value-of select='$ZMode'/></td></tr>
                <tr><td>FID</td><td><xsl:value-of select='$FID'/></td></tr>
                <tr><td>datatype</td><td><xsl:value-of select='$datatype'/></td></tr>
                <tr><td>ZM:Form/Field</td><td><xsl:value-of select='$FormZMode'/>..<xsl:value-of select='$ZMode'/></td></tr>
                <tr><td>HType</td><td><xsl:value-of select='$HType'/></td></tr>
                <tr><td>name</td><td><xsl:value-of select='$name'/></td></tr>
                <tr><td>value</td><td><xsl:copy-of select='$value'/></td></tr>
                <tr><td>source</td><td><xsl:value-of select='$source'/></td></tr>
                <tr><td>isMultiple</td><td><xsl:value-of select='$isMultiple'/></td></tr>
                <tr><td>nValue</td><td><xsl:value-of disable-output-escaping='yes' select='$nValue'/></td></tr>
                <tr><td>validation</td><td><xsl:value-of select='$validation'/></td></tr>
                <tr><td>remote</td><td><xsl:value-of select='$remote'/></td></tr>
            </table>
        </xsl:if>
		
		<xsl:choose>
			<xsl:when test='$isMultiple'>
				<xsl:variable name='vlist' select='php:function("DisplayMultiValue_List", $nValue)'/>
				<xsl:choose>
					<xsl:when test='$display="hidden"'>
						<xsl:for-each select='$vlist/*/item'>
							<input>
								<xsl:attribute name="type">hidden</xsl:attribute>
								<xsl:attribute name="name"><xsl:value-of select="concat($name,'___',@n)" /></xsl:attribute>
								<xsl:attribute name="value"><xsl:value-of select="$nValue" /></xsl:attribute>
							</input>
						</xsl:for-each>
					</xsl:when>
					<xsl:when test='$display="edit" or $display="list-create"'>
						<ul>
							<xsl:for-each select='$vlist/*/item'>
								<li>
									<xsl:call-template name='data-input'>
										<xsl:with-param name='iDataTypes' select='$iDataTypes'/>
										<xsl:with-param name='F'          select='$F'/>
										<xsl:with-param name='name'       select='concat($name,"___",@n)' />
										<xsl:with-param name='datatype'   select='$datatype' />
										<xsl:with-param name='value'      select='.' />
										<xsl:with-param name='source'     select='$source' />
										<xsl:with-param name='isMultiple' select='false()' />
										<xsl:with-param name='validation' select='$validation' />
										<xsl:with-param name='remote' select='$remote' />
									</xsl:call-template>
								</li>
							</xsl:for-each>
							<xsl:variable name='AddID' select='concat($name, "___ADD")'/>
							<li style="display:none">
								<xsl:attribute name="id"><xsl:value-of select='$AddID'/></xsl:attribute>
								<xsl:call-template name='data-input'>
									<xsl:with-param name='iDataTypes' select='$iDataTypes'/>
									<xsl:with-param name='F'          select='$F'/>
									<xsl:with-param name='name'       select='concat($name,"___",string(number($vlist/list/@count)+1))' />
									<xsl:with-param name='datatype'   select='$datatype' />
									<xsl:with-param name='value'      select='.' />
									<xsl:with-param name='source'     select='$source' />
									<xsl:with-param name='isMultiple' select='false()' />
									<xsl:with-param name='validation' select='$validation' />
									<xsl:with-param name='remote' select='$remote' />
								</xsl:call-template>
							</li>
							
							<xsl:variable name='AddLinkID' select='concat($name, "___ADDLINK")'/>
							<li style="display:ok">
								<xsl:attribute name="id"><xsl:value-of select='$AddLinkID'/></xsl:attribute>
								<span style='color:blue;cursor:pointer;font: bold 12 arial;'><xsl:attribute name='onclick'><xsl:value-of select='php:functionString("MultiAddLink",$AddID,$AddLinkID)'/></xsl:attribute>Add</span>
							</li>
						</ul>
					</xsl:when>
					<xsl:otherwise>
						<ul>
							<xsl:for-each select='$vlist/*/item'>
								<li><xsl:copy-of select='.'/></li>
							</xsl:for-each>
						</ul>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test='$display="hidden"'>
				<input>
					<xsl:attribute name="type">hidden</xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="$name" /></xsl:attribute>
					<xsl:attribute name="value"><xsl:value-of select="$nValue" /></xsl:attribute>
				</input>
			</xsl:when>
			<xsl:when test='$display="edit" or $display="create" or $display="list-create"'>
				<xsl:call-template name='data-input'>
					<xsl:with-param name='iDataTypes' select='$iDataTypes'/>
					<xsl:with-param name='F'          select='$F'/>
					<xsl:with-param name='name'       select='$name' />
					<xsl:with-param name='datatype'   select='$datatype' />
					<xsl:with-param name='value'      select='$nValue' />
					<xsl:with-param name='source'     select='$source' />
					<xsl:with-param name='isMultiple' select='$isMultiple' />
					<xsl:with-param name='validation' select='$validation' />
					<xsl:with-param name='remote' select='$remote' />
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:if test='$FormZMode="edit" or $FormZMode="create"'>
					<input type='hidden'>
						<xsl:attribute name='name'><xsl:value-of select='$name'/></xsl:attribute>
						<xsl:attribute name='value'><xsl:value-of select='$nValue'/></xsl:attribute>
					</input>
				</xsl:if>
				<xsl:variable name='match'>
					<xsl:if test='$HType="combobox"'>0</xsl:if>
					<xsl:if test='$HType!="combobox"'>1</xsl:if>
				</xsl:variable>
				<xsl:variable name='dispval'>
					<xsl:choose>
						<xsl:when test='$HType="combobox" or $HType="listbox"'>
							<xsl:value-of select='php:functionString("SelectOptionListDisplayText", $source, $nValue, $match)' />
						</xsl:when>
						<xsl:otherwise><xsl:copy-of select='$nValue' /></xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name='pval' select='php:functionString("zobject_format::PrettyValue", $dispval)'/>
				<xsl:choose>
					<xsl:when test='0!=string-length($iDataTypes/*/*/typedef[@name=$datatype]/@output-html)'>
						<xsl:copy-of disable-output-escaping='no' select='php:function("xml_file::toDocEl", $dispval)' />
					</xsl:when>
					<xsl:when test='0!=string-length($iDataTypes/*/*/typedef[@name=$datatype]/@output-escape)'>
						[<xsl:value-of disable-output-escaping='yes' select='$pval' />]
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of disable-output-escaping='no' select='$pval' />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>


	<xsl:template name='data-input'>
		<xsl:param name='iDataTypes'/>
		<xsl:param name='F'         />
		<xsl:param name='name'      />
		<xsl:param name='datatype'  />
		<xsl:param name='value'     />
		<xsl:param name='source'    />
		<xsl:param name='isMultiple'/>
		<xsl:param name='validation'/>
		<xsl:param name='remote'/>
		
<xsl:if test='php:function("zobject::DEBUG_TRANSFORM_DATA_INPUT")'>
	<table class='DEBUG'>
		<tr><td colspan='2' class='title'>DATAINPUT.XSL - data-input</td></tr>
		<tr><th>Variable</th><th>Value</th></tr>
		<tr><td>name</td><td><xsl:value-of select='$name' /></td></tr>
		<tr><td>datatype</td><td><xsl:value-of select='$datatype' /></td></tr>
		<tr><td>value</td><td><xsl:value-of select='$value' /></td></tr>
		<tr><td>source</td><td><xsl:value-of select='$source' /></td></tr>
		<tr><td>isMultiple</td><td><xsl:value-of select='$isMultiple' /></td></tr>
		<tr><td>validation</td><td><xsl:value-of select='$validation' /></td></tr>
		<tr><td>remote</td><td><xsl:value-of select='$remote' /></td></tr>
	</table>
</xsl:if>
		<xsl:variable name='DTDef' select='$iDataTypes/*/typedef[@name=$datatype]'/>
        <xsl:variable name='HType'>
            <xsl:choose>
                <xsl:when test='string($DTDef/@html-type)!=""'><xsl:value-of select='string($DTDef/@html-type)' /></xsl:when>
                <xsl:when test='string($datatype)!=""'><xsl:value-of select='string($datatype)' /></xsl:when>
                <xsl:otherwise>text</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
		<xsl:choose>
			<xsl:when test='$HType="select" or $HType="multi-select"'>
				<select>
					<xsl:attribute name='name'><xsl:value-of select='$name'/><xsl:if test='$HType="multi-select"'>[]</xsl:if></xsl:attribute>
					<xsl:attribute name='id'><xsl:value-of select='$name'/></xsl:attribute>
					<xsl:attribute name='class'><xsl:value-of select='$validation'/></xsl:attribute>
					<xsl:if test='string-length($remote)!=0'><xsl:attribute name='remote'><xsl:value-of select='$remote'/></xsl:attribute></xsl:if>
					<xsl:if test='number($DTDef/@size)>0'><xsl:attribute name='size'><xsl:value-of select='$DTDef/@size'/></xsl:attribute></xsl:if>
					<xsl:if test='$HType="multi-select"'><xsl:attribute name='multiple'>1</xsl:attribute></xsl:if>
					<xsl:for-each select='$F/@*'>
						<xsl:choose>
							<xsl:when test='name()="datatype"'></xsl:when>
							<xsl:otherwise><xsl:copy-of select='.' /></xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
					<xsl:if test='string-length($source)!=0'>
						<xsl:variable name='ol' select='php:function("SelectOptionList", string($source))'/>
					    <xsl:for-each select='$ol/*/option'>
							<option>
								<xsl:if test='string-length(@label)>0'><xsl:attribute name='label'><xsl:value-of select='@label'/></xsl:attribute></xsl:if>
								<xsl:if test='string-length(@value)>0'><xsl:attribute name='value'><xsl:value-of select='@value'/></xsl:attribute></xsl:if>
								<xsl:if test='$value=text() or contains($value,text())'><xsl:attribute name='selected'><xsl:value-of select='1'/></xsl:attribute></xsl:if>
								<xsl:copy-of select='text()' />
							</option>
						</xsl:for-each>
					</xsl:if>
					
					<xsl:if test='string-length($source)=0'>
					<xsl:for-each select='$DTDef/option'>
						<option>
							<xsl:if test='string-length(@label)>0'><xsl:attribute name='label'><xsl:value-of select='@label'/></xsl:attribute></xsl:if>
							<xsl:if test='string-length(@value)>0'><xsl:attribute name='value'><xsl:value-of select='@value'/></xsl:attribute></xsl:if>
							<xsl:if test='$value=text() or contains($value,text())'><xsl:attribute name='selected'><xsl:value-of select='1'/></xsl:attribute></xsl:if>
							<xsl:copy-of select='text()' />
						</option>
					</xsl:for-each>
					</xsl:if>
				</select>
			</xsl:when>
			<xsl:when test='$HType="combobox"'>
				<xsl:variable name='CBTxtID' select='concat($name,"TXT")'/>
				<xsl:variable name='CBTxtNM' select='concat($name,"TXT")'/>
				<xsl:variable name='CBHidID' select='concat($name,"HID")'/>
				<xsl:variable name='CBDivID' select='concat($name,"DIV")'/>
				<xsl:variable name='CBSelID' select='concat($name,"SEL")'/>
				<xsl:variable name='SQ' select='php:functionString("SingleQuoteChar")'/>
				<xsl:variable name='size'>
                    <xsl:choose>
                        <xsl:when test='string(@size)!=""'><xsl:value-of select='string(@size)' /></xsl:when>
                        <xsl:when test='string($DEFS/*/zobjectdef[@name=$ZName]/fielddefs/fielddef[@id=$name]/@size)!=""'><xsl:value-of select='string($DEFS/*/zobjectdef[@name=$ZName]/fielddefs/fielddef[@id=$name]/@size)' /></xsl:when>
                        <xsl:when test='string($DTDef/@size)!=""'><xsl:value-of select='string($DTDef/@size)' /></xsl:when>
                        <xsl:otherwise>20</xsl:otherwise>
                    </xsl:choose>
                </xsl:variable>
				<xsl:variable name='dispval' select='php:functionString("SelectOptionListDisplayText", $source, $value)'/>

				<input type='text' class='combo-text {$validation}'>
					<xsl:attribute name='name'><xsl:value-of select='$CBTxtNM'/></xsl:attribute>
					<xsl:attribute name='id'><xsl:value-of select='$CBTxtID'/></xsl:attribute>
					<xsl:if test='string-length($remote)!=0'><xsl:attribute name='remote'><xsl:value-of select='$remote'/></xsl:attribute></xsl:if>
					<xsl:if test='number($size)>0'><xsl:attribute name='size'><xsl:value-of select='$size'/></xsl:attribute></xsl:if>
					<xsl:attribute name='value'><xsl:value-of select='$dispval' /></xsl:attribute>
					<xsl:attribute name='onChange'><xsl:value-of select='concat("javascript:document.getElementById(",$SQ,$CBHidID,$SQ,").value=this.value;")'/></xsl:attribute>
				</input>
				<input type='hidden'>
					<xsl:attribute name='name'><xsl:value-of select='$name'/></xsl:attribute>
					<xsl:attribute name='id'><xsl:value-of select='$CBHidID'/></xsl:attribute>
					<xsl:attribute name='value'><xsl:value-of select='$value' /></xsl:attribute>
				</input>
				<input type="button" class='combo-button {$validation}' hidefocus="1" value="&#9660;">
					<xsl:attribute name='onclick'><xsl:value-of select='concat("javascript:menuActivate(",$SQ,$CBTxtID,$SQ,",",$SQ,$CBDivID,$SQ,",",$SQ,$CBSelID,$SQ,");")'/></xsl:attribute>
				</input>
				
				<div id="combodiv" class='combo-div {$validation}' style='display:none;'>
					<xsl:attribute name="id"><xsl:value-of select='$CBDivID'/></xsl:attribute>
					<xsl:attribute name='onmouseover'><xsl:value-of select='concat("javascript:oOverMenu=",$SQ,$CBDivID,$SQ,";")'/></xsl:attribute>
					<xsl:attribute name='onmouseout'><xsl:value-of select='"javascript:oOverMenu=false;"'/></xsl:attribute>
					<select id="combosel" class="combo-select" size="8">
						<xsl:attribute name="id"><xsl:value-of select='$CBSelID'/></xsl:attribute>
						<xsl:attribute name='onclick'><xsl:value-of select="concat('javaScript:textSet(',$SQ,$name,$SQ,',this);')"/></xsl:attribute>
						<xsl:attribute name='onkeypress'><xsl:value-of select="concat('javaScript:comboKey(',$SQ,$name,$SQ,',this);')"/></xsl:attribute>
						<xsl:for-each select='$F/@*'>
							<xsl:choose>
								<xsl:when test='name()="datatype"'></xsl:when>
								<xsl:otherwise><xsl:copy-of select='.' /></xsl:otherwise>
							</xsl:choose>
						</xsl:for-each>

						<xsl:if test='string-length($source)!=0'>
							<xsl:variable name='ol' select='php:function("SelectOptionList", string($source))'/>
						    <xsl:for-each select='$ol/*/option'>
								<option class="comboopt">
									<xsl:if test='string-length(@label)>0'><xsl:attribute name='label'><xsl:value-of select='@label'/></xsl:attribute></xsl:if>
									<xsl:if test='string-length(@value)>0'><xsl:attribute name='value'><xsl:value-of select='@value'/></xsl:attribute></xsl:if>
									<xsl:if test='$value=text() or contains($value,text())'><xsl:attribute name='selected'><xsl:value-of select='1'/></xsl:attribute></xsl:if>
									<xsl:copy-of select='text()' />
								</option>
							</xsl:for-each>
						</xsl:if>
						
					</select>
				</div>
			</xsl:when>
			<xsl:when test='$HType="checklist"'>
				<xsl:variable name='SQ' select='php:functionString("SingleQuoteChar")'/>
				<xsl:variable name='B_' select='concat($SQ,"[",$SQ)'/>
				<xsl:variable name='_B' select='concat($SQ,"]",$SQ)'/>
                <xsl:variable name='pHg'>
                    <xsl:choose>
                        <xsl:when test='string-length(@size)!=0'><xsl:value-of select='string(@size)' /></xsl:when>
                        <xsl:otherwise>5</xsl:otherwise>
                    </xsl:choose>
                </xsl:variable>
				<xsl:variable name='Hg' select='number($pHg) * 20'/>
                <xsl:variable name='Wd'>
                    <xsl:choose>
                        <xsl:when test='string-length(@width)!=0'><xsl:value-of select='string(@width)' /></xsl:when>
                        <xsl:otherwise>250</xsl:otherwise>
                    </xsl:choose>
                </xsl:variable>
				<xsl:variable name='id' select='php:functionString("zobject::new_jsid")'/>
				<xsl:variable name='script' select='concat("r=",$B_,"+this.value+",$_B,";f=document.getElementById(",$SQ,$id,$SQ,");f.value=(!this.checked?f.value.replace(r,",$SQ,$SQ,"):f.value+r);")'/>
				
				<xsl:variable name='Wdg' select='concat($Wd,"px")'/>
				<xsl:variable name='Hgg' select='concat($Hg,"px")'/>
				
				<input type='hidden'>
					<xsl:attribute name="id"><xsl:value-of select='$id'/></xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select='$name'/></xsl:attribute>
					<xsl:attribute name="value"><xsl:value-of select='$value'/></xsl:attribute>
					<xsl:attribute name="class"><xsl:value-of select='$validation'/></xsl:attribute>
					<xsl:if test='string-length($remote)!=0'><xsl:attribute name='remote'><xsl:value-of select='$remote'/></xsl:attribute></xsl:if>
				</input>

				<div id="checklistdiv" class="checklist-div {$validation}" style="height:{$Hgg};width:{$Wdg};">
					<xsl:if test='string-length($source)!=0'>
						<xsl:variable name='ol' select='php:function("SelectOptionList", string($source))'/>
						<xsl:for-each select='$ol/*/option'>
							<xsl:variable name='oid' select='php:functionString("NewJSID")'/>
							<xsl:variable name='match' select='concat("[",text(),"]")'/>
							<input type='checkbox' class='checklist-checkbox {$validation}'>
								<xsl:attribute name="id"><xsl:value-of select='$oid'/></xsl:attribute>
								<xsl:attribute name="name"><xsl:value-of select='$oid'/></xsl:attribute>
								<xsl:attribute name="value"><xsl:value-of select='text()'/></xsl:attribute>
								<xsl:attribute name="onclick"><xsl:value-of select='$script'/></xsl:attribute>
								<xsl:if test='$value=$match or contains($value,$match)'><xsl:attribute name='checked'><xsl:value-of select='1'/></xsl:attribute></xsl:if>
								<xsl:for-each select='$F/@*'>
									<xsl:choose>
										<xsl:when test='name()="datatype"'></xsl:when>
										<xsl:otherwise><xsl:copy-of select='.' /></xsl:otherwise>
									</xsl:choose>
								</xsl:for-each>
							</input>
							<label>
								<xsl:attribute name='for'><xsl:value-of select='$oid'/></xsl:attribute>
								<xsl:copy-of select='text()' />
							</label>
							<br/>
						</xsl:for-each>
					</xsl:if>
				</div>
			</xsl:when>
			<xsl:when test='$HType="wysiwyg"'>
				<xsl:copy-of select='php:function("WYSIWYG",$name,$value)'/>
			</xsl:when>
			<xsl:when test='$HType="richtext" or $HType="rtf"'>
				<xsl:copy-of select='php:function("RTF",$name,$value)'/>
			</xsl:when>
			<xsl:when test='$HType="stringlist"'>
				<xsl:variable name='ol' select='php:function("SelectOptionList", string($source))'/>
				<xsl:for-each select='$ol/*/item'>
					<input>
						<xsl:attribute name="class"><xsl:value-of select='$validation'/></xsl:attribute>
						<xsl:if test='string-length($remote)!=0'><xsl:attribute name='remote'><xsl:value-of select='$remote'/></xsl:attribute></xsl:if>
						<xsl:if test='string-length(@label)>0'><xsl:attribute name='label'><xsl:value-of select='@label'/></xsl:attribute></xsl:if>
						<xsl:if test='string-length(@value)>0'><xsl:attribute name='value'><xsl:value-of select='@value'/></xsl:attribute></xsl:if>
						<xsl:if test='$value=text() or contains($value,text())'><xsl:attribute name='selected'><xsl:value-of select='1'/></xsl:attribute></xsl:if>
						<xsl:copy-of select='text()' />
					</input><br/>
				</xsl:for-each>
				<a href=''/>
			</xsl:when>
			<xsl:when test='$HType="yesno" or $HType="checkbox"'>
				<input>
					<xsl:attribute name="class"><xsl:value-of select='$validation'/></xsl:attribute>
					<xsl:attribute name='type'>checkbox</xsl:attribute>
					<xsl:attribute name='name'><xsl:value-of select='$name'/></xsl:attribute>
					<xsl:attribute name='id'><xsl:value-of select='$name'/></xsl:attribute>
					<xsl:attribute name='value'>Yes</xsl:attribute>
					<xsl:if test='string-length($remote)!=0'><xsl:attribute name='remote'><xsl:value-of select='$remote'/></xsl:attribute></xsl:if>
					<xsl:if test='string($value)!="" and string($value)!="No" and string($value)!="0"'>
						<xsl:attribute name='checked' select='1'/>
					</xsl:if>
					<xsl:for-each select='$F/@*'>
						<xsl:choose>
							<xsl:when test='name()="datatype"'></xsl:when>
							<xsl:otherwise><xsl:copy-of select='.' /></xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
				</input>
			</xsl:when>
			<xsl:when test='$HType="hidden"'>
				<input>
					<xsl:attribute name='type'>hidden</xsl:attribute>
					<xsl:attribute name='name'><xsl:value-of select='$name'/></xsl:attribute>
					<xsl:attribute name='id'><xsl:value-of select='$name'/></xsl:attribute>
					<xsl:attribute name='value'><xsl:value-of select='$value'/></xsl:attribute>
					<xsl:for-each select='$F/@*'>
						<xsl:choose>
							<xsl:when test='name()="datatype"'></xsl:when>
							<xsl:otherwise><xsl:copy-of select='.' /></xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
				</input>
			</xsl:when>
			<xsl:when test='$HType="password"'>
				<input>
					<xsl:attribute name="class"><xsl:value-of select='$validation'/></xsl:attribute>
					<xsl:attribute name='type'>password</xsl:attribute>
					<xsl:attribute name='name'><xsl:value-of select='$name' /></xsl:attribute>
					<xsl:attribute name='id'><xsl:value-of select='$name' /></xsl:attribute>
					<xsl:attribute name='value'><xsl:value-of select='$value' /></xsl:attribute>
					<xsl:if test='string-length($remote)!=0'><xsl:attribute name='remote'><xsl:value-of select='$remote'/></xsl:attribute></xsl:if>
					<xsl:if test='number($DTDef/@size) > 0'>
						<xsl:attribute name='size'><xsl:value-of select='$DTDef/@size'/></xsl:attribute>
					</xsl:if>
					<xsl:if test='number($DTDef/@maxlength) > 0'>
						<xsl:attribute name='maxlength'><xsl:value-of select='$DTDef/@maxlength'/></xsl:attribute>
					</xsl:if>
					<xsl:attribute name='size'><xsl:value-of select='$DTDef/@size'/></xsl:attribute>
					<xsl:for-each select='$F/@*'>
						<xsl:choose>
							<xsl:when test='name()="datatype"'></xsl:when>
							<xsl:otherwise><xsl:copy-of select='.' /></xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
				</input>
			</xsl:when>
			<xsl:when test='$HType="textarea"'>
				<textarea>
					<xsl:attribute name="class"><xsl:value-of select='$validation'/></xsl:attribute>
					<xsl:attribute name='name'><xsl:value-of select='$name' /></xsl:attribute>
					<xsl:attribute name='id'><xsl:value-of select='$name' /></xsl:attribute>
					<xsl:attribute name='rows'><xsl:value-of select='$DTDef/@rows' /></xsl:attribute>
					<xsl:attribute name='cols'><xsl:value-of select='$DTDef/@cols' /></xsl:attribute>
					<xsl:if test='string-length($remote)!=0'><xsl:attribute name='remote'><xsl:value-of select='$remote'/></xsl:attribute></xsl:if>
					<xsl:for-each select='$F/@*'>
						<xsl:choose>
							<xsl:when test='name()="datatype"'></xsl:when>
							<xsl:otherwise><xsl:copy-of select='.' /></xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
					<xsl:value-of select='php:functionString("PrepareTextAreaContent", string($value))'/> 
				</textarea>
			</xsl:when>
			<xsl:when test='$HType="user"'>
				<!--  Ideas? -->
				<xsl:message terminate='yes'>html-type "user" not supported at this time</xsl:message>
			</xsl:when>
			<xsl:otherwise>
                <xsl:variable name='size'>
                    <xsl:choose>
                        <xsl:when test='string(@size)!=""'><xsl:value-of select='string(@size)' /></xsl:when>
                        <xsl:when test='string($DEFS/*/zobjectdef[@name=$ZName]/fielddefs/fielddef[@id=$name]/@size)!=""'><xsl:value-of select='string($DEFS/*/zobjectdef[@name=$ZName]/fielddefs/fielddef[@id=$name]/@size)' /></xsl:when>
                        <xsl:when test='string($DTDef/@size)!=""'><xsl:value-of select='string($DTDef/@size)' /></xsl:when>
                        <xsl:otherwise>20</xsl:otherwise>
                    </xsl:choose>
                </xsl:variable>
				<input>
					<xsl:attribute name="class"><xsl:value-of select='$validation'/></xsl:attribute>
					<xsl:attribute name='type'>text</xsl:attribute>
					<xsl:attribute name='name'><xsl:value-of select='$name' /></xsl:attribute>
					<xsl:attribute name='id'><xsl:value-of select='$name' /></xsl:attribute>
					<xsl:attribute name='value'><xsl:value-of select='$value' /></xsl:attribute>
					<xsl:attribute name='size'><xsl:value-of select='$size'/></xsl:attribute>
					<xsl:if test='string-length($remote)!=0'><xsl:attribute name='remote'><xsl:value-of select='$remote'/></xsl:attribute></xsl:if>
					<xsl:if test='number($DTDef/@maxlength) > 0'>
						<xsl:attribute name='maxlength'><xsl:value-of select='$DTDef/@maxlength'/></xsl:attribute>
					</xsl:if>

					<xsl:for-each select='$F/@*'>
						<xsl:choose>
							<xsl:when test='name()="datatype"'></xsl:when>
							<xsl:otherwise><xsl:copy-of select='.' /></xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
				</input>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>