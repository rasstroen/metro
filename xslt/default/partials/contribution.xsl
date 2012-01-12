<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="*" mode="p-contribution-list">
    <xsl:param name="authors" select="authors"/>
    <xsl:param name="books" select="books"/>
    <xsl:param name="genres" select="genres"/>
    <xsl:param name="series" select="series"/>

    <xsl:variable name="book" select="$books/item[@id=current()/@id_book]"/>
    <xsl:variable name="author" select="$authors/item[@id=current()/@id_author]"/>
    <xsl:variable name="serie" select="$series/item[@id=current()/@id_serie]"/>
    <xsl:variable name="genre" select="$genres/item[@id=current()/@id_genre]"/>

    <li class="p-contribution-list">
      <div class="p-contribution-list-date">
        <xsl:call-template name="h-abbr-time">
          <xsl:with-param select="@date" name="time"/>
        </xsl:call-template>
      </div>
      <div class="p-contribution-list-title">
        <xsl:apply-templates select="." mode="h-action-names">
          <xsl:with-param name="object" select="'contribution'"/>
        </xsl:apply-templates>
        <xsl:text> </xsl:text>
        <xsl:choose>
          <xsl:when test="contains(@action,'books_') or @action='ocr_add'">
            <xsl:apply-templates select="$book" mode="h-book-link"/>
          </xsl:when>
          <xsl:when test="contains(@action,'authors_')">
            <xsl:apply-templates select="$book" mode="h-author-link"/>
          </xsl:when>
          <xsl:when test="contains(@action,'genres_')">
            <xsl:apply-templates select="$genre" mode="h-genre-link"/>
          </xsl:when>
          <xsl:when test="contains(@action,'series_')">
            <xsl:apply-templates select="$serie" mode="h-serie-link"/>
          </xsl:when>
          <xsl:otherwise/>
        </xsl:choose>
        <xsl:if test="@points">
          <em class="p-contribution-list-points">
            <xsl:text> – </xsl:text>
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="@points" name="amount"/>
              <xsl:with-param select="'балл балла баллов'" name="words"/>
            </xsl:call-template>
          </em>
        </xsl:if>
      </div>
    </li>
  </xsl:template>

</xsl:stylesheet>
