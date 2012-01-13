<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>
	<xsl:output omit-xml-declaration="yes"/>
	<xsl:output indent="yes"/>

	<xsl:template match="module[@name='map' and @action ='show' and @mode='location']" mode="p-module">
		<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=true&amp;key=ABQIAAAAtQOEXHSz-AcbZ5xEPbzcDhTUjHX1IzFatkZz1PdxZt40zH0hkxSrT4VGcK8nowltaJI2wnz2OMh96Q" type="text/javascript"></script>
		
		<article>
			<p>
				<span id="status">ищем Вас...</span>
			</p>
		</article>
		<div id="map_canvas"></div>
		<script>
var markers={};
			<xsl:for-each select="stations/item">
				<xsl:text>markers[</xsl:text>
				<xsl:value-of select="@id" />
				<xsl:text>]={title:"</xsl:text>
				<xsl:value-of select="@title" />
				<xsl:text>",lat:</xsl:text>
				<xsl:value-of select="@lat" />
				<xsl:text>,lon:</xsl:text>
				<xsl:value-of select="@lon" />
				<xsl:text>};</xsl:text>
				<xsl:text>
				</xsl:text>
			</xsl:for-each>
			drawMap();
		</script>
		
		
	</xsl:template>


</xsl:stylesheet>
