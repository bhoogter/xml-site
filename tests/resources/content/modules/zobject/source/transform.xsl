<?xml version="1.0" encoding="ISO-8859-1" ?>

<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
    xmlns:php="http://php.net/xsl" 
    xsl:extension-element-prefixes="php" 
    exclude-result-prefixes="php"
    >
    <xsl:import href="data-input.xsl" />

    <xsl:variable name='BenchmarkTRANSFORM' select='0'/>
    <xsl:variable name='BenchmarkROWS' select='0'/>


    <xsl:variable name='DEFS' select='php:function("zobject::source_document","MODULES")'/>
    <xsl:variable name='HandledElements' select='php:functionString("zobject::handled_elements")'/>
    <!-- NOTE:  $PS for page-file, via page.xsl -->

    <xsl:variable name='ZName' select='php:functionString("zobject::transform_var", "name")' />
    <xsl:variable name='requested-object-mode' select='php:functionString("zobject::transform_var", "mode")' />
    <xsl:variable name='login-key' select='php:functionString("zobject::transform_var", "login-key")' />
    <xsl:variable name='OID' select='php:functionString("zobject::transform_var", "uid")' />
    <xsl:variable name='ZPage' select='php:functionString("zobject::transform_var", "page")' />
    <xsl:variable name='ZPageCount' select='php:functionString("zobject::transform_var", "page-count")' />
    <xsl:variable name='ZCount' select='php:functionString("zobject::transform_var", "count")' />
    <xsl:variable name='ZArgs' select='php:functionString("zobject::transform_var", "args")' />
    <xsl:variable name='ZArgs64' select='php:functionString("zobject::transform_var", "args64")' />
    <xsl:variable name='ZPrefix' select='c' />

    <xsl:variable name='jsid' select='php:functionString("zobject::transform_var", "jsid")' />

    <xsl:variable name='ZDef' select='$DEFS//*/zobjectdef[@name=$ZName]' />
    <xsl:variable name='ZSrc' select='$ZDef/source' />
    <xsl:variable name='obj' select='//.' />

    <xsl:variable name='mode' select='php:functionString("zobject_access::access", string($ZName), string($requested-object-mode))' />


    <xsl:template match='/'>
        <xsl:variable name='benchstart' select='php:functionString("zobject::bench_time")'/>
        <xsl:variable name='named_template' select='php:functionString("zobject::named_template")'/>
        <xsl:variable name='specific_template' select='$ZDef/render[@type=$mode]/@src'/>
        <xsl:variable name='alt_template'>
            <xsl:choose>
                <xsl:when test='($mode="edit" or $mode="create") and string-length($ZDef/render[@type="display"]/@src)!=0'>
                    <xsl:value-of select='$ZDef/render[@type="display"]/@src'/>
                </xsl:when>
                <xsl:when test='($mode="list-edit" or $mode="list-create") and string-length($ZDef/render[@type="list"]/@src)!=0'>
                    <xsl:value-of select='$ZDef/render[@type="list"]/@src'/>
                </xsl:when>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name='general_template' select='$ZDef/render[string(@type)=""]/@src'/>
        <xsl:variable name='docTemplateFile' select='php:functionString("ChooseBest", string($named_template), string($specific_template), string($alt_template), string($general_template))'/>
        <xsl:variable name='docTemplate' select='php:function("GetZObjectTemplate", $docTemplateFile, $ZName, $mode)' />

        <xsl:if test="false()">
            <table class='DEBUG'>
                <tr>
                    <td class='title' colspan='2'>TRANSFORM.XSL</td>
                </tr>
                <tr>
                    <th>Var</th>
                    <th>Val</th>
                </tr>
                <tr>
                    <td>ZName</td>
                    <td>
                        <xsl:value-of select='$ZName'/>
                    </td>
                </tr>
                <tr>
                    <td>requested-object-mode</td>
                    <td>
                        <xsl:value-of select='$requested-object-mode'/>
                    </td>
                </tr>
                <tr>
                    <td>mode</td>
                    <td>
                        <xsl:value-of select='$mode'/>
                    </td>
                </tr>

                <!--
	<tr><td>uid</td><td><xsl:value-of select='php:functionString("juniper_transform_var", "uid")'/></td></tr>
	<tr><td>zpage</td><td><xsl:value-of select='$ZPage'/></td></tr>
	<tr><td>zpagecount</td><td><xsl:value-of select='$ZPageCount'/></td></tr>
	<tr><td colspan='2' style='background-color: black;'>_</td></tr>
	<tr><td>zname</td><td><xsl:value-of select='$ZName'/></td></tr>
	<tr><td>prefix</td><td><xsl:value-of select='$ZPrefix'/></td></tr>
	<tr><td>args</td><td><xsl:value-of select='$ZArgs'/></td></tr>
	<tr><td colspan='2' style='background-color: black;'>_</td></tr>
	<tr><td>named_template</td><td><xsl:value-of select='$named_template'/></td></tr>
	<tr><td>specific_template</td><td><xsl:value-of select='$specific_template'/></td></tr>
	<tr><td>alt_template</td><td><xsl:value-of select='$alt_template'/></td></tr>
	<tr><td>general_template</td><td><xsl:value-of select='$general_template'/></td></tr>
	<tr><td>docTemplateFile</td><td><xsl:value-of select='$docTemplateFile'/></td></tr>
-->
            </table>
        </xsl:if>

        <xsl:variable name='resetRecNo' select='php:functionString("juniper_recno", "1")'/>
        <div>
            <xsl:attribute name='id'>
                <xsl:value-of select='$jsid'/>
            </xsl:attribute>
            <xsl:apply-templates select="$docTemplate/*" />
        </div>
        <xsl:if test='number($BenchmarkTRANSFORM)>0'>
            <xsl:value-of select='php:functionString("BenchReport",$benchstart, "zobject transform")'/>
        </xsl:if>
    </xsl:template>

    <xsl:template match='@*'>
        <xsl:choose>
            <xsl:when test='name()="admin"'></xsl:when>
            <xsl:otherwise>
                <xsl:variable name='aname' select='name()'/>
                <xsl:variable name='atext'>
                    <xsl:value-of select='.'/>
                </xsl:variable>
                <xsl:attribute name='{$aname}'>
                    <xsl:value-of select='php:functionString("TemplateEscapeTokens", string($atext))'/>
                </xsl:attribute>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="node()">
        <xsl:variable name='N' select='name()' />
        <xsl:variable name='Ck' select='concat(",",name(),",")' />
        <xsl:variable name='HasNodeHandler' select='string-length($N)!=0 and contains($HandledElements, $Ck)' />
        <xsl:choose>
            <xsl:when test='name()="text"'>
                <xsl:call-template name='text'/>
            </xsl:when>
            <xsl:when test='name()="require"'>
                <xsl:call-template name='require'/>
            </xsl:when>
            <xsl:when test='name()="value"'>
                <xsl:call-template name='value'/>
            </xsl:when>
            <xsl:when test='name()="editor"'>
                <xsl:call-template name='editor'/>
            </xsl:when>

            <xsl:when test='name()="page"'>
                <xsl:call-template name='page'/>
            </xsl:when>
            <xsl:when test='name()="a" or name()="A"'>
                <xsl:call-template name='a'/>
            </xsl:when>
            <xsl:when test='name()="img" or name()="IMG"'>
                <xsl:call-template name='img'/>
            </xsl:when>

            <xsl:when test='$HasNodeHandler'>
                <xsl:copy-of select='php:function("juniper_render_node", $N, current())' />
            </xsl:when>

            <xsl:when test='name()="startform"'>
                <xsl:call-template name='startform'/>
            </xsl:when>
            <xsl:when test='name()="endform"'>
                <xsl:call-template name='endform'/>
            </xsl:when>
            <xsl:when test='name()="formcontrols"'>
                <xsl:call-template name='formcontrols'/>
            </xsl:when>
            <xsl:when test='name()="caption"'>
                <xsl:call-template name='caption'/>
            </xsl:when>
            <xsl:when test='name()="field"'>
                <xsl:call-template name='field'/>
            </xsl:when>
            <xsl:when test='name()="fieldhelp"'>
                <xsl:call-template name='fieldhelp'/>
            </xsl:when>
            <xsl:when test='name()="fielddesc"'>
                <xsl:call-template name='fielddesc'/>
            </xsl:when>

            <xsl:when test='name()="row"'>
                <xsl:call-template name='row'/>
            </xsl:when>
            <xsl:when test='name()="pagelist"'>
                <xsl:call-template name='pagelist'/>
            </xsl:when>
            <xsl:when test='name()="formcommands"'>
                <xsl:call-template name='form-commands'/>
            </xsl:when>
            <xsl:when test='name()="addlink"'>
                <xsl:call-template name='addlink'/>
            </xsl:when>
            <xsl:when test='name()="dellink"'>
                <xsl:call-template name='dellink'/>
            </xsl:when>
            <xsl:when test='name()="displaylink"'>
                <xsl:call-template name='displaylink'/>
            </xsl:when>
            <xsl:when test='name()="editlink"'>
                <xsl:call-template name='editlink'/>
            </xsl:when>
            <xsl:when test='name()="positionlink"'>
                <xsl:call-template name='positionlink'/>
            </xsl:when>
            <xsl:when test='name()="uppositionlink"'>
                <xsl:call-template name='uppositionlink'/>
            </xsl:when>
            <xsl:when test='name()="dnpositionlink"'>
                <xsl:call-template name='dnpositionlink'/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:copy>
                    <xsl:apply-templates select="@*|node()"/>
                </xsl:copy>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name='require'>
        <xsl:variable name='test' select='@test' />
        <xsl:variable name='rn' select='php:functionString("juniper_recno")'/>
        <xsl:variable name='result' select='php:functionString("requireTest", string($OID), string($test), string($rn))'/>
        <xsl:if test='$result!="0"'>
            <xsl:apply-templates select="node()"/>
        </xsl:if>
    </xsl:template>

    <xsl:template name='text'>
        <!--	<xsl:value-of select='.'/> -->
    </xsl:template>

    <xsl:template name='value'>
        <xsl:variable name='rn' select='php:functionString("juniper_recno")'/>
        <xsl:if test='@select!=""'>
            <xsl:value-of disable-output-escaping='yes' select='php:functionString("valueSelect", string($OID), string(@select), string($rn))'/>
        </xsl:if>
    </xsl:template>

    <xsl:template name='startform'>
        <xsl:variable name='formid' select='php:functionString("zoFormID")'/>
        <xsl:variable name='action' select='php:functionString("FormAction", $ZName, $formid, $ZArgs)'/>
        <xsl:variable name='ZS64' select='php:functionString("GetZSource64")'/>
        <xsl:variable name='ZA64' select='php:functionString("GetZArgs64")'/>
        <xsl:variable name='AJAX' select='php:functionString("ForAjax")'/>
        <xsl:variable name='FSC' select='php:functionString("juniper_form_source_check")'/>

        <xsl:if test='$mode="edit" or $mode="create"'>
            <xsl:text disable-output-escaping="yes">&lt;form method="POST" action="</xsl:text>
            <xsl:value-of select='$action'/>
            <xsl:text disable-output-escaping="yes">"</xsl:text>
            <xsl:if test='string-length($formid) != 0'>
                <xsl:text disable-output-escaping="yes"> id="</xsl:text>
                <xsl:value-of select='$formid'/>
                <xsl:text disable-output-escaping="yes">"</xsl:text>
                <xsl:text disable-output-escaping="yes"> name="</xsl:text>
                <xsl:value-of select='$formid'/>
                <xsl:text disable-output-escaping="yes">"</xsl:text>
            </xsl:if>
            <xsl:text disable-output-escaping="yes">&gt;</xsl:text>

            <xsl:if test='string-length($AJAX)!=0'>
                <input type='hidden' name='_AJAX' value='1'/>
            </xsl:if>

            <input type='hidden' name='_Save' value='1'/>
            <input type='hidden' name='_ZN' value='{$ZName}'/>
            <input type='hidden' name='_ZM' value='{$mode}'/>
            <input type='hidden' name='_ZA' value='{$ZA64}'/>
            <xsl:if test='string-length($FSC)!=0'>
                <input type='hidden' name='_FSC' value='{$FSC}'/>
            </xsl:if>
        </xsl:if>

    </xsl:template>

    <xsl:template name='endform'>
        <xsl:variable name='formid' select='php:functionString("zoFormID")'/>
        <xsl:if test='$mode="edit" or $mode="create"'>
            <xsl:text disable-output-escaping="yes">&lt;/form&gt;</xsl:text>
            <script>jQuery(document).ready(function(){jQuery("#<xsl:value-of select='$formid'/>
").validate();});</script>
        </xsl:if>
    </xsl:template>



    <xsl:template name='formcontrols'>
        <xsl:variable name='AJAX' select='php:functionString("ForAjax")'/>
        <xsl:variable name='formid' select='php:functionString("zoFormID")'/>
        <xsl:if test='string-length($AJAX)=0 and ($mode="edit" or $mode="create")'>
            <xsl:variable name='value' select='php:functionString("ChooseBest", string(@value), string (@text), "Submit")'/>
            <xsl:variable name='ty' select='substring(@type, 1, 1)'/>
            <xsl:if test='$ty="s" or $ty=""'>
                <input type='submit'>
                    <xsl:attribute name='value'>
                        <xsl:value-of select='$value'/>
                    </xsl:attribute>
                    <xsl:attribute name='class'>
                        <xsl:value-of select='@class'/>
                    </xsl:attribute>
                </input>
            </xsl:if>
        </xsl:if>
    </xsl:template>

    <xsl:template name='field'>
        <xsl:variable name='F' select='.' />
        <xsl:variable name='rn' select='php:functionString("juniper_recno")'/>
        <xsl:variable name='fid' select='@id' />
        <xsl:variable name='fDef' select='$ZDef/fielddefs/fielddef[@id=$fid]' />
        <xsl:variable name='ixf' select='$ZDef/@index'/>

        <xsl:variable name='multiple' select='$fDef/@multiple'/>

        <xsl:variable name='default' select='php:functionString("php_hook", $fDef/@default)'/>
        <xsl:variable name='bfvalue' select='php:functionString("ChooseBest", $obj/row[number($rn)]/field[@id=$fid], $default)'/>
        <xsl:variable name='bfvalueFormat' select='php:functionString("ChooseBest", @format, @format)'/>
        <xsl:variable name='Fbfvalue'>
            <xsl:choose>
                <xsl:when test='string-length($bfvalueFormat)!=0'>
                    <xsl:value-of select='php:functionString("php_hook", $bfvalueFormat, $bfvalue)'/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select='$bfvalue'/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

        <xsl:variable name='datatype' select='php:functionString("ChooseBest", string(@datatype), string($fDef/@datatype))'/>
        <xsl:variable name='fmode1' select='php:functionString("ChooseBest", string(@display), string($mode))'/>
        <xsl:variable name='fmode'>
            <xsl:choose>
                <xsl:when test='$fmode1="create" and $ixf=$fid and $bfvalue!=""'>display</xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select='$fmode1'/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name='sc1' select='$DEFS/*/typedef[@name=$datatype]/@source' />
        <xsl:variable name='sc2' select='$fDef/@source' />
        <xsl:variable name='sc3' select='@source'/>
        <xsl:variable name='tsource' select='php:functionString("ChooseBest", $sc3, $sc2, $sc1)'/>

        <xsl:variable name='source' select='php:functionString("TransformSourceScripts", string($tsource))'/>
        <xsl:variable name='bvalidation' select='php:functionString("ChooseBest", string(@validation), string($ZDef/fielddefs/fielddef[@id=$fid]/@validation), string($DEFS/*/typedef[@name=$datatype]/@validation))' />
        <xsl:variable name='rvalidation' select='php:functionString("ChooseBest", string(@required), string($ZDef/fielddefs/fielddef[@id=$fid]/@required))' />
        <xsl:variable name='validation' select='php:functionString("juniper_validation_string", string($bvalidation), string($rvalidation))'/>
        <xsl:variable name='remote' select='php:functionString("juniper_remote_validation_url", $fid)'/>

        <xsl:if test="false()">

            <table class='DEBUG'>
                <tr>
                    <td class='title' colspan='2'>TRANSFORM.XSL - field</td>
                </tr>
                <tr>
                    <th>Var</th>
                    <th>Val</th>
                </tr>
                <tr>
                    <td>rn</td>
                    <td>
                        <xsl:value-of select='$rn'/>
                    </td>
                </tr>
                <tr>
                    <td>fid</td>
                    <td>
                        <xsl:value-of select='$fid'/>
                    </td>
                </tr>
                <tr>
                    <td>ixf</td>
                    <td>
                        <xsl:value-of select='$ixf'/>
                    </td>
                </tr>
                <tr>
                    <td>default</td>
                    <td>
                        <xsl:value-of select='$default'/>
                    </td>
                </tr>
                <tr>
                    <td>bfvalue</td>
                    <td>
                        <xsl:value-of disable-output-escaping='yes' select='$bfvalue'/>
                    </td>
                </tr>
                <tr>
                    <td>bfvalueFormat</td>
                    <td>
                        <xsl:value-of disable-output-escaping='yes' select='$bfvalueFormat'/>
                    </td>
                </tr>
                <tr>
                    <td>Fbfvalue</td>
                    <td>
                        <xsl:value-of disable-output-escaping='yes' select='$Fbfvalue'/>
                    </td>
                </tr>
                <tr>
                    <td>source</td>
                    <td>
                        <xsl:value-of select='$source'/>
                    </td>
                </tr>
                <tr>
                    <td>datatype</td>
                    <td>
                        <xsl:value-of select='$datatype'/>
                    </td>
                </tr>
                <tr>
                    <td>formzmode</td>
                    <td>
                        <xsl:value-of select='$mode'/>
                    </td>
                </tr>
                <tr>
                    <td>fmode</td>
                    <td>
                        <xsl:value-of select='$fmode'/>
                    </td>
                </tr>
                <tr>
                    <td>multiple</td>
                    <td>
                        <xsl:value-of select='$multiple'/>
                    </td>
                </tr>
                <tr>
                    <td>ZPrefix</td>
                    <td>
                        <xsl:value-of select='$ZPrefix'/>
                    </td>
                </tr>
                <tr>
                    <td>ZArgs</td>
                    <td>
                        <xsl:value-of select='$ZArgs'/>
                    </td>
                </tr>
                <tr>
                    <td>validation</td>
                    <td>
                        <xsl:value-of select='$validation'/>
                    </td>
                </tr>
                <tr>
                    <td>remote</td>
                    <td>
                        <xsl:value-of select='$remote'/>
                    </td>
                </tr>
            </table>
        </xsl:if>
        <xsl:choose>
            <xsl:when test='substring($datatype,1,1)=":"'>
                <xsl:variable name='newZName' select='substring($datatype,2)'/>
                <xsl:variable name='addix' select='$ZDef/@index' />
                <xsl:variable name='addkey' select='substring($ZDef/xmlfile/@key,2)' />
                <xsl:variable name='addval'>
                    <xsl:choose>
                        <xsl:when test='$ZDef/xmlfile/@index="position()"'>
                            <xsl:value-of select='$rn'/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select='$obj/row[position()=number($rn)]/field[@id=$addix]'/>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:variable>
                <!--
		<xsl:variable name='addargs1' select='php:functionString("add_querystring_var", $ZArgs, "_SUBZ", "1")'/>
			<xsl:variable name='addargs' select='php:functionString("add_querystring_var", $addargs1, $addkey, $addval)'/>
-->
                <xsl:variable name='newargs' select='php:functionString("TransferObjectKeys", $ZName, $ZArgs)'/>
                <xsl:variable name='newmode'>
                    <xsl:choose>
                        <xsl:when test='$mode="edit" and $fDef/@mode="list"'>list-edit</xsl:when>
                        <xsl:when test='string-length($fDef/@mode)!=0'>
                            <xsl:value-of select='$fDef/@mode'/>
                        </xsl:when>
                        <xsl:when test='string-length(@mode)!=0'>
                            <xsl:value-of select='@mode'/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select='"list"'/>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:variable>
                <xsl:if test="false()">
                    <table class='DEBUG'>
                        <tr>
                            <td class='title' colspan='2'>TRANSFORM.XSL - field, type=ZObject</td>
                        </tr>
                        <tr>
                            <th>Var</th>
                            <th>Val</th>
                        </tr>
                        <tr>
                            <td>newZName</td>
                            <td>
                                <xsl:value-of select='$newZName'/>
                            </td>
                        </tr>
                        <tr>
                            <td>addix</td>
                            <td>
                                <xsl:value-of select='$addix'/>
                            </td>
                        </tr>
                        <tr>
                            <td>addkey</td>
                            <td>
                                <xsl:value-of select='$addkey'/>
                            </td>
                        </tr>
                        <tr>
                            <td>addval</td>
                            <td>
                                <xsl:value-of select='$addval'/>
                            </td>
                        </tr>
                        <tr>
                            <td>addargs</td>
                            <td>
                                <xsl:value-of select='$addargs'/>
                            </td>
                        </tr>
                        <tr>
                            <td>newargs</td>
                            <td>
                                <xsl:value-of select='$newargs'/>
                            </td>
                        </tr>
                        <tr>
                            <td>$mode</td>
                            <td>
                                <xsl:value-of select='$mode'/>
                            </td>
                        </tr>
                        <tr>
                            <td>@mode</td>
                            <td>
                                <xsl:value-of select='$fDef/@mode'/>
                            </td>
                        </tr>
                        <tr>
                            <td>newmode</td>
                            <td>
                                <xsl:value-of select='$newmode'/>
                            </td>
                        </tr>
                    </table>
                </xsl:if>
                <xsl:copy-of select='php:function("renderZObject",substring($datatype,2),$newmode, string($newargs))' />
            </xsl:when>
            <xsl:when test='$fDef/@id=""'>
            </xsl:when>
            <xsl:otherwise>
                <xsl:call-template name='data-field'>
                    <xsl:with-param name='iDataTypes' select='$DEFS' />
                    <xsl:with-param name='F' select='$F' />
                    <xsl:with-param name='ZName' select='$ZName' />
                    <xsl:with-param name='FormZMode' select='$mode' />
                    <xsl:with-param name='ZMode' select='$fmode' />
                    <xsl:with-param name='FID' select='$fid' />
                    <xsl:with-param name='datatype' select='$datatype' />
                    <xsl:with-param name='name' select='concat($ZPrefix, $fid)' />
                    <xsl:with-param name='value' select='$Fbfvalue' />
                    <xsl:with-param name='source' select='$source' />
                    <xsl:with-param name='isMultiple' select='$multiple' />
                    <xsl:with-param name='validation' select='$validation' />
                    <xsl:with-param name='remote' select='$remote' />
                </xsl:call-template>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name='caption'>
        <xsl:variable name='fid' select='@id' />
        <xsl:variable name='dCap' select='$fid' />
        <xsl:variable name='specificZN' select='@zname' />
        <xsl:variable name='specificCap'>
            <xsl:if test='string-length($specificZN)!=0'>
                <xsl:value-of select='$DEFS/*/zobjectdef[@name=$specificZN]/fielddefs/fielddef[@id=$fid]/@caption'/>
            </xsl:if>
        </xsl:variable>
        <xsl:variable name='tCap' select='$ZDef/fielddefs/fielddef[@id=$fid]/@caption' />
        <xsl:variable name='bCap' select='php:functionString("ChooseBest", string($specificCap), string($tCap), string($dCap))'/>

        <label>
            <xsl:attribute name='for'>
                <xsl:value-of select='$fid'/>
            </xsl:attribute>
            <xsl:choose>
                <xsl:when test='substring($mode, 1, 4)="list"'>
                    <xsl:value-of disable-output-escaping='yes' select='php:functionString("PrettyHeader", string($bCap))'/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of disable-output-escaping='yes' select='php:functionString("PrettyCaption", string($bCap))'/>
                </xsl:otherwise>
            </xsl:choose>
        </label>
    </xsl:template>

    <xsl:template name='fieldhelp'>
        <xsl:variable name='fid' select='@id' />
        <xsl:variable name='bCap' select='$DEFS/*/ztabledef[@name=$ZSrc]/fielddefs/fielddef[@id=$fid]/help' />
        <div class='capex'>
            <xsl:copy-of select='php:functionString("PrettyCaptionHelp", string($bCap))' />
        </div>
    </xsl:template>

    <xsl:template name='fielddesc'>
        <xsl:variable name='fid' select='@id' />
        <xsl:variable name='bCap' select='$DEFS/*/ztabledef[@name=$ZSrc]/fielddefs/fielddef[@id=$fid]/description' />
        <xsl:copy-of select='php:functionString("PrettyCaptionHelp", string($bCap))' />
    </xsl:template>

    <xsl:template name='row'>
        <xsl:variable name='row' select='.' />
        <xsl:variable name='rangeFrom' select='($ZPage - 1) * $ZPageCount + 1'/>
        <xsl:variable name='rangeTo' select='$ZPage * $ZPageCount'/>
        <xsl:if test='false()'>
            <table class='DEBUG'>
                <tr>
                    <td class='title' colspan='2'>TRANSFORM.XSL - row</td>
                </tr>
                <tr>
                    <th>Var</th>
                    <th>Val</th>
                </tr>
                <tr>
                    <td>rangeFrom</td>
                    <td>
                        <xsl:value-of select='$rangeFrom'/>
                    </td>
                </tr>
                <tr>
                    <td>rangeTo</td>
                    <td>
                        <xsl:value-of select='$rangeTo'/>
                    </td>
                </tr>
            </table>
        </xsl:if>
        <xsl:for-each select='$obj/row'>
            <xsl:if test='position() &gt;= $rangeFrom and position() &lt;= $rangeTo'>
                <xsl:variable name='rowstart' select='php:functionString("BenchTime")'/>
                <xsl:variable name='setRecNo' select='php:functionString("juniper_recno", string(position()))'/>
                <xsl:for-each select='$row/*'>
                    <xsl:apply-templates select='.' />
                </xsl:for-each>
                <xsl:if test='number($BenchmarkROWS)>0'>
                    <xsl:value-of select='php:functionString("BenchReport", string($rowstart), "Rows")'/>
                </xsl:if>
            </xsl:if>
        </xsl:for-each>
    </xsl:template>

    <xsl:template match='tr'>
        <xsl:variable name='C' select='@class'/>
        <xsl:variable name='R' select='php:functionString("juniper_recno", "")'/>
        <xsl:variable name='alt_ext'>
            <xsl:if test='(number($R) mod 2) = 0'>-alt</xsl:if>
        </xsl:variable>
        <tr>
            <xsl:for-each select='@*'>
                <xsl:choose>
                    <xsl:when test='substring($C, 1, 1)="#" and name()="class"'>
                        <xsl:attribute name='class'>
                            <xsl:copy-of select='concat(substring($C, 2), $alt_ext)'/>
                        </xsl:attribute>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:copy-of select='.'/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:for-each>
            <xsl:for-each select='node()'>
                <xsl:apply-templates select='.' />
            </xsl:for-each>
        </tr>
    </xsl:template>

    <xsl:template match='li'>
        <xsl:variable name='C' select='@class'/>
        <xsl:variable name='R' select='php:functionString("juniper_recno", "")'/>
        <xsl:variable name='alt_ext'>
            <xsl:if test='(number($R) mod 2) = 0'>-alt</xsl:if>
        </xsl:variable>
        <li>
            <xsl:for-each select='@*'>
                <xsl:choose>
                    <xsl:when test='substring($C, 1, 1)="#" and name()="class"'>
                        <xsl:attribute name='class'>
                            <xsl:copy-of select='concat(substring($C, 2), $alt_ext)'/>
                        </xsl:attribute>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:copy-of select='.'/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:for-each>
            <xsl:for-each select='node()'>
                <xsl:apply-templates select='.' />
            </xsl:for-each>
        </li>
    </xsl:template>

    <xsl:template name='pagelist'>
        <xsl:copy-of select='php:function("PageList", string($ZCount), string($ZPageCount), string($ZPage), string(@prefix), string(@postfix))'/>
    </xsl:template>

    <xsl:template name='addlink'>
        <xsl:variable name='ntext' select='php:functionString("ChooseBest", string(@text), "Add")' />
        <xsl:copy-of select='php:function("ItemLink", string(@field), "create", string($ntext), string(@ajax), string(@class), string(@template))'/>
    </xsl:template>

    <xsl:template name='displaylink'>
        <xsl:variable name='ntext' select='php:functionString("ChooseBest", string(@text), "@")' />
        <xsl:copy-of select='php:function("ItemLink", string(@field), "display", string($ntext), string(@ajax), string(@class), string(@template))' disable-output-escaping='yes'/>
    </xsl:template>

    <xsl:template name='editlink'>
        <xsl:variable name='ntext' select='php:functionString("ChooseBest", string(@text), "#")' />
        <xsl:copy-of select='php:function("ItemLink", string(@field), "edit", string($ntext), string(@ajax), string(@class), string(@template))' disable-output-escaping='yes'/>
    </xsl:template>

    <xsl:template name='dellink'>
        <xsl:variable name='ntext' select='php:functionString("ChooseBest", string(@text), "X")' />
        <xsl:copy-of select='php:function("ItemLink", string(@field), "delete", string($ntext), string(@ajax), string(@class))'/>
    </xsl:template>

    <xsl:template name='positionlink'>
        <xsl:variable name='najax' select='@ajax' />
        <xsl:copy-of select='php:function("ItemLink", string(@field), "position", "", string(@ajax), string(@class))'/>
    </xsl:template>

    <xsl:template name='uppositionlink'>
        <xsl:variable name='ntext' select='php:functionString("ChooseBest", string(@text), "-")' />
        <xsl:copy-of select='php:function("ItemLink", string(@field), "upposition", string($ntext), string(@ajax), string(@class))'/>
    </xsl:template>
    <xsl:template name='dnpositionlink'>
        <xsl:variable name='ntext' select='php:functionString("ChooseBest", string(@text), "+")' />
        <xsl:copy-of select='php:function("ItemLink", string(@field), "dnposition", string($ntext), string(@ajax), string(@class))'/>
    </xsl:template>


    <xsl:template name='form-commands'>
        <div id='form-commands'>
            <xsl:variable name='cmds' select='php:function("GetObjectCommands", $ZName)'/>
            <xsl:for-each select='$cmds/*'>
                <xsl:apply-templates/>
            </xsl:for-each>
        </div>
    </xsl:template>

    <xsl:template name='editor'>
        <xsl:copy-of select='php:function("WYSIWYG",string(@name),"")'/>
    </xsl:template>

    <!-- this extends image paths, when not specified, allowing for template image auto-supplantation -->
    <!-- It also works to guarantee better standards conformance by enforcing both @alt and @title, with default of "Image" -->
    <xsl:template name='img'>
        <xsl:variable name='Nalt' select='php:functionString("ChooseBest", @alt, @title, "Image")'/>
        <xsl:variable name='Ntitle' select='php:functionString("ChooseBest", @title, @alt, "Image")'/>

        <xsl:variable name='asrc' select='php:functionString("TemplateEscapeTokens", string(@src))'/>
        <xsl:variable name='nsrc' select='php:functionString("ExtendURL",$asrc,"i","", 1)'/>
        <img>
            <xsl:for-each select='@*'>
                <xsl:copy-of select='.'/>
            </xsl:for-each>
            <xsl:attribute name='src'>
                <xsl:value-of select='$nsrc'/>
            </xsl:attribute>
            <xsl:attribute name='alt'>
                <xsl:value-of select='$Nalt'/>
            </xsl:attribute>
            <xsl:attribute name='title'>
                <xsl:value-of select='$Ntitle'/>
            </xsl:attribute>

        </img>
    </xsl:template>

    <xsl:template name='a'>
        <xsl:variable name='aHREF' select='php:functionString("TemplateEscapeTokens", string(@href))'/>
        <xsl:variable name='nHREF' select='php:functionString("ExtendURL", $aHREF, "a", "", 1)'/>
        <!-- [href=<xsl:value-of select='@href'/>] [aHREF=<xsl:value-of select='$aHREF'/>] [nHREF=<xsl:value-of select='$nHREF'/>]
-->
        <a>
            <xsl:for-each select='@*'>
                <xsl:choose>
                    <xsl:when test='name()="href"'>
                        <xsl:attribute name='href'>
                            <xsl:value-of select='$nHREF'/>
                        </xsl:attribute>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:apply-templates select='.'/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:for-each>
            <xsl:for-each select='node()'>
                <xsl:apply-templates select='.'/>
            </xsl:for-each>
        </a>
    </xsl:template>


</xsl:stylesheet>