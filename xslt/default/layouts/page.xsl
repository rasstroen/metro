<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">

	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>
	<xsl:output omit-xml-declaration="yes"/>
	<xsl:output indent="yes"/>
	<xsl:include href="../layout.xsl" />
	<xsl:include href="../module.xsl"/>
	<xsl:include href="../helpers.xsl" />

	<xsl:include href="../partials/author.xsl"/>
	<xsl:include href="../partials/setting.xsl"/>
	<xsl:include href="../partials/user.xsl"/>
	<xsl:include href="../partials/map.xsl"/>
	<xsl:include href="../partials/misc.xsl"/>
</xsl:stylesheet>
