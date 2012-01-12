<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template mode="p-genre-list" match="*">
    <li class="p-genre-list">
      <h2 class="p-genre-list-title">
        <xsl:apply-templates select="." mode="h-genre-link"/>
      </h2>
      <div class="p-genre-list-count">
        <xsl:call-template name="h-this-amount">
          <xsl:with-param select="@books_count" name="amount"></xsl:with-param>
          <xsl:with-param select="'книга книги книг'" name="words"></xsl:with-param>
        </xsl:call-template>
      </div>
      <ul class="p-genre-list-subgenres">
        <xsl:apply-templates select="subgenres/item" mode="p-genre-subgenre"/>
      </ul>
    </li>
  </xsl:template>

  <xsl:template mode="p-genre-loved" match="*">
    <li class="p-genre-loved">
      <xsl:apply-templates select="." mode="h-genre-link"/>
    </li>
  </xsl:template>

  <xsl:template match="*" mode="p-genre-subgenre">
    <li class="p-genre-subgenre">
      <xsl:apply-templates select="." mode="h-genre-link"/>
      <xsl:variable name="subgenre-books-amount">
        <xsl:call-template name="h-this-amount">
          <xsl:with-param select="@books_count" name="amount"></xsl:with-param>
          <xsl:with-param select="'книга книги книг'" name="words"></xsl:with-param>
        </xsl:call-template>
      </xsl:variable>
      <em title="{$subgenre-books-amount}"><xsl:value-of select="@books_count"/></em>
    </li>
  </xsl:template>

  <xsl:template match="module[@name='genres' and @action='show']" mode="p-module">
    <xsl:param name="amount" select="30"/>
    <xsl:param name="authors" select="genre/authors"/>
    <input type="hidden" name="id" value="{genre/@id}" />
    <h2 class="p-genre-show-title"><xsl:value-of select="genre/@title"/></h2>
    <div class="p-genre-show-count">
      <xsl:call-template name="h-this-amount">
        <xsl:with-param select="genre/@books_count" name="amount"></xsl:with-param>
        <xsl:with-param select="'книга книги книг'" name="words"></xsl:with-param>
      </xsl:call-template>
    </div>

    <xsl:if test="&current_profile;/@id">
      <div class="p-genre-show-info-loved">
        <p/>
        <a href="#" class="add-loved-genre">Добавить жанр в любимые</a>
      </div>
      <div class="p-genre-show-info-subscribed">
        <p/>
        <a href="#" class="subscribe-genre">Подписаться на новые книги этого жанра</a>
      </div>
    </xsl:if>

    <div class="p-genre-show-description">
      <xsl:value-of select="genre/@description" disable-output-escaping="yes"/>
    </div>

    <xsl:if test="&access;/books_edit">
      <div class="p-genre-show-edit">
        <a href="{genre/@path_edit}">Редактировать описание жанра</a>
      </div>
    </xsl:if>

    <ul class="p-genre-show-books-list">
      <xsl:apply-templates select="genre/books/item[not (position()>$amount)]" mode="p-book-list">
        <xsl:with-param select="$authors" name="authors"/>
      </xsl:apply-templates>
    </ul>
    <ul class="p-genre-show-parent">
      Наджанр: <xsl:apply-templates select="genre/parent/item" mode="h-genre-link"/>
    </ul>
    <ul class="p-genre-show-subgenres">
      <xsl:apply-templates select="genre/subgenres/item" mode="p-genre-subgenre"/>
    </ul>
  </xsl:template>


  <xsl:template match="module[@name='genres' and @action='edit']" mode="p-module">
    <form method="post" action="{&prefix;}genres/{genre/@id}/edit">
      <input type="hidden" name="writemodule" value="GenreWriteModule" />
      <input type="hidden" name="id" value="{genre/@id}" />
      <div class="form-group">
        <h2>Редактирование жанра «<xsl:value-of select="genre/@title"/>»</h2>
        <div class="form-field">
          <label>Описание</label>
          <textarea name="description">
            <xsl:value-of select="genre/@description" />
          </textarea>
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Сохранить информацию"/>
      </div>
    </form>
  </xsl:template>

</xsl:stylesheet>
