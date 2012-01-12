<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>
	<xsl:output omit-xml-declaration="yes"/>
	<xsl:output indent="yes"/>
	<xsl:include href="../layout.xsl" />
	<xsl:include href="../module.xsl"/>
	<xsl:include href="../helpers.xsl" />
	<xsl:include href="../partials/user.xsl"/>

	<xsl:template match="module[@error and @action='show']" mode="p-module">
		<h1>
			<xsl:text>Ошибка </xsl:text>
			<xsl:if test="@error_code != 0 ">
				<xsl:value-of select="@error_code"/>
			</xsl:if>
		</h1>
		<div class="form-error">
			<xsl:value-of select="@error"/>
			<xsl:if test="@return_path != ''">
				<p><a href="{@return_path}">Вернуться и попробовать что-нибудь ещё</a></p>
			</xsl:if>
		</div>
		<xsl:if test="@error_description != ''">
			<div class="form-error">
				<xsl:value-of select="@error_description" disable-output-escaping="yes"/>
			</div>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>
