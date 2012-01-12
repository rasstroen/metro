<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="*" mode="p-log-list">
    <xsl:param name="mode"/>
    <xsl:param name="users" select="users"/>
    <xsl:param name="user" select="$users/item[@id=current()/@id_user]"/>
    <xsl:param name="books" select="books"/>
    <xsl:param name="book" select="$books/item[@id=current()/@book_id]"/>
    <xsl:param name="authors" select="authors"/>
    <xsl:param name="author" select="$authors/item[@id=current()/@author_id]"/>
    <xsl:param name="genres" select="genres"/>
    <xsl:param name="genre" select="$genres/item[@id=current()/@genre_id]"/>
    <xsl:param name="series" select="series"/>
    <xsl:param name="serie" select="$series/item[@id=current()/@serie_id]"/>
    <li>
      <xsl:attribute name="class">
        p-log-list
        <xsl:if test="@applied=0">cancelled</xsl:if>
      </xsl:attribute>
      <div class="p-log-list-checkbox"><input type="checkbox" name="log[{@id}]"/></div>
      <xsl:if test="not($mode) or ($mode!='user')">
        <div class="p-log-list-image"><xsl:apply-templates select="$user" mode="h-user-image"/></div>
      </xsl:if>
      <div class="p-log-list-text">
        <p class="p-log-list-text-controls" id="log-{@id}">
          <a href="#" class="cancel-log">Откатить</a> | <a class="apply-log" href="#">Накатить</a>
        </p>
        <p class="p-log-list-text-date">
          <xsl:call-template name="h-abbr-time">
            <xsl:with-param select="@time" name="time"/>
          </xsl:call-template>
        </p>
        <p class="p-log-list-text-title">
          <xsl:if test="not($mode) or $mode!='user'">
            <xsl:apply-templates select="$user" mode="h-user-link"/>
            <xsl:text>&nbsp;</xsl:text>
          </xsl:if>
          <xsl:apply-templates select="." mode="h-action-names">
            <xsl:with-param select="'log'" name="object"/>
          </xsl:apply-templates>
          <xsl:text>&nbsp;</xsl:text>
          <xsl:if test="not($mode) or ($mode!='author' and $mode!='book')">
            <xsl:choose>
              <xsl:when test="contains(@action,'authors_')">
                <xsl:apply-templates select="$author" mode="h-author-link"/>
              </xsl:when>
              <xsl:when test="contains(@action,'books_')">
                <xsl:apply-templates select="$book" mode="h-book-link"/>
              </xsl:when>
              <xsl:when test="contains(@action,'series_')">
                <xsl:apply-templates select="$serie" mode="h-serie-link"/>
              </xsl:when>
              <xsl:when test="contains(@action,'genres_')">
                <xsl:apply-templates select="$genre" mode="h-genre-link"/>
              </xsl:when>
              <xsl:otherwise/>
            </xsl:choose>
          </xsl:if>
        </p>
      </div>
      <div class="p-log-list-values">
        <table>
          <thead><th class="first"></th><th>Было</th><th>Стало</th></thead>
          <xsl:apply-templates select="values/item" mode="p-log-list-value">
            <xsl:with-param select="$books" name="books"/>
            <xsl:with-param select="$authors" name="authors"/>
          </xsl:apply-templates>
        </table>
      </div>
    </li>
  </xsl:template>

  <xsl:template match="*" mode="p-log-list-value">
    <xsl:param select="books" name="books"/>
    <xsl:param select="authors" name="authors"/>
    <xsl:variable name="item" select="." />
    <tr class="p-log-list-value">
      <xsl:choose>
        <xsl:when test="@name ='id_lang'">
          <td class="p-log-list-value-name"><xsl:value-of select="@name"/></td>
          <td><xsl:value-of select="//lang_codes/item[@id = $item/@old]/@title" /></td>
          <td><xsl:value-of select="//lang_codes/item[@id = $item/@new]/@title" /></td>
        </xsl:when>
        <xsl:when test="@name ='id_basket'">
          <td class="p-log-list-value-name">Редакции</td>
          <td>
            <xsl:apply-templates select="following-sibling::old_relations/item" mode="p-log-relation">
              <xsl:with-param select="$books" name="books"/>
              <xsl:with-param select="$authors" name="authors"/>
            </xsl:apply-templates>
          </td>
          <td>
            <xsl:choose>
              <xsl:when test="following-sibling::new_relations">
                Добавили:
              </xsl:when>
              <xsl:when test="following-sibling::deleted_relations">
                Удалили:
              </xsl:when>
            	<xsl:otherwise></xsl:otherwise>
            </xsl:choose>
            <xsl:apply-templates select="following-sibling::new_relations/item" mode="p-log-relation">
              <xsl:with-param select="$books" name="books"/>
              <xsl:with-param select="$authors" name="authors"/>
            </xsl:apply-templates>
            <xsl:apply-templates select="following-sibling::deleted_relations/item" mode="p-log-relation">
              <xsl:with-param select="$books" name="books"/>
              <xsl:with-param select="$authors" name="authors"/>
            </xsl:apply-templates>
          </td>
        </xsl:when>
        <xsl:otherwise>
          <td class="p-log-list-value-name"><xsl:value-of select="@name"/></td>
          <td><xsl:value-of select="@old" disable-output-escaping="yes"/></td>
          <td><xsl:value-of select="@new" disable-output-escaping="yes"/></td>
        </xsl:otherwise>
      </xsl:choose>
    </tr>
  </xsl:template>

  <xsl:template match="item" mode="p-log-relation">
    <xsl:param name="books" select="books"/>
    <xsl:param name="authors" select="authors"/>
    <xsl:variable name="book" select="$books/item[@id=current()/@book_id]"/>
    <xsl:variable name="author" select="$authors/item[@id=current()/@author_id]"/>
    <xsl:choose>
      <xsl:when test="@book_id">
        <p><xsl:apply-templates select="$book" mode="h-book-link"/></p>
      </xsl:when>
      <xsl:when test="@author_id">
        <p><xsl:apply-templates select="$author" mode="h-author-link"/></p>
      </xsl:when>
    	<xsl:otherwise></xsl:otherwise>
    </xsl:choose>
  </xsl:template>

</xsl:stylesheet>

