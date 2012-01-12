<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="*" mode="p-statistic-list">
    <xsl:param select="books" name="books"/>
    <xsl:param select="authors" name="authors"/>
    <xsl:param select="genres" name="genres"/>
    <xsl:param select="users" name="users"/>
    <xsl:variable name="evenness">
      <xsl:choose>
      	<xsl:when test="(position() mod 2) = 1">odd</xsl:when>
      	<xsl:otherwise>even</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="@id_book">
        <tr class="p-statistic-list {$evenness}">
          <td>#<xsl:value-of select="@id_book"/></td>
          <td><xsl:apply-templates select="$books/item[@id=current()/@id_book]" mode="h-book-link"/></td>
          <td><xsl:value-of select="@count"/></td>
        </tr>
      </xsl:when>
      <xsl:when test="@id_author">
        <tr class="p-statistic-list {$evenness}">
          <td>#<xsl:value-of select="@id_author"/></td>
          <td><xsl:apply-templates select="$authors/item[@id=current()/@id_author]" mode="h-author-link"/></td>
          <td><xsl:value-of select="@count"/></td>
        </tr>
      </xsl:when>
      <xsl:when test="@id_genre">
        <tr class="p-statistic-list {$evenness}">
          <td>#<xsl:value-of select="@id_genre"/></td>
          <td><xsl:apply-templates select="$genres/item[@id=current()/@id_genre]" mode="h-genre-link"/></td>
          <td><xsl:value-of select="@count"/></td>
        </tr>
      </xsl:when>
      <xsl:when test="@id_user">
        <tr class="p-statistic-list {$evenness}">
          <td><xsl:apply-templates select="$users/item[@id=current()/@id_user]" mode="h-user-link"/></td>
          <td><xsl:value-of select="@count"/></td>
        </tr>
      </xsl:when>
    	<xsl:otherwise></xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="*" mode="p-statistic-single">
    <xsl:variable name="evenness">
      <xsl:choose>
      	<xsl:when test="(position() mod 2) = 1">odd</xsl:when>
      	<xsl:otherwise>even</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <tr class="p-statistic-single {$evenness}">
      <td>#<xsl:value-of select="@time"/></td>
      <td><xsl:value-of select="@count"/></td>
    </tr>
  </xsl:template>

</xsl:stylesheet>
