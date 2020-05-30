<?xml version='1.0' encoding='ISO-8859-1'?>

<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:php="http://php.net/xsl"
                xsl:extension-element-prefixes="php" exclude-result-prefixes="php">
    <xsl:template match='/'>
        <xsl:variable name='DEF' select='.'/>
        <xsl:variable name='ZName' select='/*/@name'/>
        <xsl:variable name='ZMode' select='/*/@mode'/>
        <xsl:variable name='ZCaption' select='//style/caption'/>
        <xsl:variable name='AJAX' select='php:functionString("zobject::ajax")'/>
        <xsl:variable name='ADMIN' select='php:functionString("zobject::admin")'/>

        <span>
            <xsl:choose>

                <!-- Object List -->
                <xsl:when test='$ZMode="list" or $ZMode="list-edit"'>
                    <xsl:variable name='TClass' select='php:functionString("zobject::FetchObjPart", $ZName, "style/class")' />

                    <xsl:choose>
                        <xsl:when test='string(//style/class) != ""'><xsl:value-of select='string(//style/class)'/></xsl:when>
                        <xsl:otherwise><xsl:value-of select='string(//style/class)'/>zdefault-list
                        </xsl:otherwise>
                    </xsl:choose>
                    <p align='center'>
                        <pagelist prefix="Page: "/>
                    </p>
                    <startform/>
                    <table>
                        <xsl:attribute name='class'><xsl:value-of select='$TClass'/></xsl:attribute>
                        <thead>
                            <xsl:if test='$ZMode="list-edit"'>
                                <th>#</th>
                            </xsl:if>
                            <xsl:for-each select='/*/fielddefs/*'>
                                <xsl:variable name='fmode' select='php:functionString("zobject::field_mode", $ZName, @id, $ZMode)'/>
                                <xsl:if test='$fmode="list-edit" or $fmode="list" or $fmode="create"'>
                                    <th><xsl:value-of select='php:functionString("zobject_format::PrettyHeader", @id)'/></th>
                                </xsl:if>
                            </xsl:for-each>
                            <xsl:if test='string-length(//style/commands/@list)!=0'>
                                <th>Execute</th>
                            </xsl:if>
                        </thead>

                        <row>
                            <tr>
                                <xsl:variable name='trclass' select='concat("#",$TClass,"-row")'/>
                                <xsl:attribute name='class'>
                                    <xsl:value-of select='$trclass'/>
                                </xsl:attribute>
                                <xsl:if test='$ZMode="list-edit"'>
                                    <td>
                                        <editlink/> / <dellink/> / <positionlink/>
                                    </td>
                                </xsl:if>
                                <xsl:variable name='lid' select='php:functionString("zobject::FetchObjPart", $ZName, "@list-index")'/>
                                <xsl:variable name='oid' select='php:functionString("zobject::FetchObjPart", $ZName, "@index")'/>
                                <xsl:variable name='OID'>
                                    <xsl:choose>
                                        <xsl:when test='string-length($lid) != 0'><xsl:value-of select='$lid'/></xsl:when>
                                        <xsl:otherwise><xsl:value-of select='$oid'/></xsl:otherwise>
                                    </xsl:choose>
                                </xsl:variable>
                                <xsl:for-each select='/*/fielddefs/*'>
                                    <xsl:variable name='fid' select='@id'/>
                                    <xsl:variable name='fmode' select='php:functionString("zobject::field_mode", $ZName, $fid, $ZMode)'/>
                                    <xsl:if test='$fmode="list-edit" or $fmode="list" or $fmode="create"'>
                                        <td>
                                            <xsl:variable name='SUBZ' select='"1"'/>
                                            <xsl:choose>
                                                <xsl:when test='@id=$OID and ($ZMode="list" or $ZMode="list-edit") and $SUBZ="0"'><xsl:copy-of select='php:function("zobject::AutoPageLinkByID", string($ZName), concat("@",$OID))'/></xsl:when>
                                                <xsl:otherwise><field><xsl:attribute name='id'><xsl:value-of select='@id'/></xsl:attribute></field></xsl:otherwise>
                                            </xsl:choose>
                                        </td>
                                    </xsl:if>
                                </xsl:for-each>
                                <xsl:if test='string-length(//style/commands/@list)!=0'>
                                    <xsl:variable name='key' select='php:functionString("GetObjectKey", $ZName)'/>
                                    <xsl:variable name='alist' select='concat($key,"=","@",/*/@index)'/>
                                    <td><xsl:copy-of select='php:function("GetObjectCommands", string($ZName), "1", string($alist))'/></td>
                                </xsl:if>
                            </tr>
                        </row>

                        <xsl:if test='$ZMode="list-edit" or //style/options/@addonlist!=""'>
                            <tr><td colspan='55' align='center'><addlink/></td></tr>
                        </xsl:if>
                    </table>
                    <endform/>
                    <p align='center'><pagelist prefix="Page: "/></p>
                </xsl:when>

                <!--  Single Object -->

                <xsl:otherwise>
                    <xsl:variable name='Categories' select='string-length(/*/fielddefs/fielddef[string-length(@category)!=0]/@id)'/>
                    <xsl:variable name='TClass'>
                        <xsl:choose>
                            <xsl:when test='string-length(//style/class)!=0'><xsl:value-of select='//style/class'/></xsl:when>
                            <xsl:otherwise>zdefault</xsl:otherwise>
                        </xsl:choose>
                    </xsl:variable>
                    <xsl:variable name='TabID' select='php:functionString("zobject::new_jsid")'/>

                    <xsl:if test='$ZMode="display"'>
                        <formcommands/>
                    </xsl:if>
                    <startform/>
                    <xsl:if test='$Categories!=0'>
                        <xsl:text disable-output-escaping='yes'>&lt;div id='tabs' class='tabs'&gt;</xsl:text>
                        <div id='tabstrip' class='tabstrip'>
                            <xsl:for-each select='php:function("zobject::FetchObjFieldCategories", string($ZName))/categories/category'>
                                <xsl:variable name='cap' select='php:functionString("PrettyHeader", string(text()))'/>
                                <div id='tabbutton_{$TabID}_{position()}' onClick='javascript:openTab("{$TabID}",{position()})'>
                                    <xsl:attribute name='class'>tabbutton
                                        <xsl:if test='position()=1'>-active</xsl:if>
                                    </xsl:attribute>
                                    <xsl:value-of select='$cap'/>
                                </div>
                            </xsl:for-each>
                        </div>
                    </xsl:if>


                    <xsl:for-each select='php:function("zobject::FetchObjFieldCategories", string($ZName))/categories/category'>
                        <xsl:variable name='Category'>
                            <xsl:if test='text()="general"'></xsl:if>
                            <xsl:if test='text()!="general"'><xsl:value-of select='text()'/></xsl:if>
                        </xsl:variable>

                        <xsl:if test='$Categories!=0'>
                            <xsl:text disable-output-escaping='yes'>&lt;div id='tab_</xsl:text><xsl:value-of
                                select='$TabID'/>_<xsl:value-of select='position()'/>' class='tab<xsl:if
                                test='position()=1'>-active</xsl:if>'
                            <xsl:text disable-output-escaping='yes'>&gt;</xsl:text>
                        </xsl:if>
                        <table>
                            <xsl:attribute name='class'><xsl:value-of select='$TClass'/></xsl:attribute>

                            <xsl:if test='$AJAX="0" or $ADMIN="1"'>
                                <thead><th colspan='2'><xsl:value-of select='$ZCaption'/></th></thead>
                            </xsl:if>
                            <xsl:variable name='id' select='@index'/>

                            <xsl:if test="false()">
                                Category=<xsl:value-of select='$Category'/>
                                <br/>
                                <xsl:for-each select='$DEF/*/fielddefs/fielddef'>
                                    <xsl:value-of select='string-length(@category)'/> = [<xsl:value-of select='@category'/>]
                                    <br/>
                                </xsl:for-each>
                            </xsl:if>
                            <xsl:for-each select='$DEF/*/fielddefs/fielddef[$Categories=0 or ($Categories!=0 and (@category=$Category or (string-length(@category)=0 and $Category="")))]'>
                                <xsl:variable name='fmode' select='php:functionString("zobject_access::check_field", $ZName, @id, $ZMode)'/>
                                <xsl:choose>
                                    <xsl:when test='$fmode="edit" or $fmode="display" or $fmode="create"'>
                                        <xsl:choose>
                                            <xsl:when test='substring(@datatype,1,1)!=":"'>

                                                <tr>
                                                    <td class='form-caption'>
                                                        <caption>
                                                            <xsl:attribute name='id'><xsl:value-of select='@id'/></xsl:attribute>
                                                        </caption>
                                                    </td>
                                                    <td class='form-value'>
                                                        <field>
                                                            <xsl:attribute name='id'><xsl:value-of select='@id'/></xsl:attribute>
                                                        </field>
                                                    </td>
                                                </tr>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <tr>

                                                    <td class='form-caption'>
                                                        <caption>
                                                            <xsl:attribute name='id'><xsl:value-of select='@id'/></xsl:attribute>
                                                        </caption>
                                                    </td>
                                                    <td>
                                                        <field>
                                                            <xsl:attribute name='id'><xsl:value-of select='@id'/></xsl:attribute>
                                                            <xsl:attribute name='mode'>
                                                                <xsl:choose>
                                                                    <xsl:when test='$ZMode="edit"'>list-edit</xsl:when>
                                                                    <xsl:otherwise>list</xsl:otherwise>
                                                                </xsl:choose>
                                                            </xsl:attribute>
                                                        </field>
                                                    </td>
                                                </tr>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </xsl:when>
                                    <xsl:when test='$fmode=""'>
                                        <field display='hidden'>
                                            <xsl:attribute name='id'><xsl:value-of select='@id'/></xsl:attribute>
                                        </field>
                                    </xsl:when>
                                </xsl:choose>
                            </xsl:for-each>
                            <tr>
                                <td class='blank' colspan="2">&#160;</td>
                            </tr>
                            <tr>
                                <td class='formcontrols' colspan="2" align="center">
                                    <xsl:variable name='CanEdit' select='php:functionString("zobject_access::access", $ZName, "edit")'/>
                                    <xsl:variable name='CanDelete' select='php:functionString("zobject_access::access", $ZName, "delete")'/>
                                    <xsl:if test='$ZMode="display"'>
                                        <refreshlink/> 
                                        <xsl:if test='$CanEdit="edit"'><editlink/> </xsl:if>
                                        <xsl:if test='$ZMode="edit"'><formcontrols/> </xsl:if>
                                        <xsl:if test='$CanDelete="delete"'><dellink/> </xsl:if>
                                    </xsl:if>
                                    <xsl:if test='$ZMode="edit"'>
                                        <savelink/><cancellink />
                                    </xsl:if>
                                    <xsl:if test='$ZMode="create"'>
                                        <formcontrols/>
                                    </xsl:if>
                                </td>
                            </tr>
                        </table>

                        <xsl:if test='$Categories!=0'>
                            <xsl:text disable-output-escaping='yes'>&lt;/div&gt;</xsl:text>
                        </xsl:if>

                    </xsl:for-each>

                    <xsl:if test='$Categories!=0'>
                        <xsl:text disable-output-escaping='yes'>&lt;/div&gt;</xsl:text>
                    </xsl:if>
                    <endform/>
                </xsl:otherwise>
            </xsl:choose>
        </span>
    </xsl:template>
</xsl:stylesheet>
