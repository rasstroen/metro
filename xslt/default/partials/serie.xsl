<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template mode="p-serie-list" match="*">
    <xsl:param name="books" select="books"/>
    <xsl:param name="authors" select="authors"/>
    <xsl:param name="amount" select="4"/>
    <li class="p-serie-list">
      <h2 class="p-serie-list-title">
        <xsl:value-of select="$books/@title" />
        <xsl:if test="$books/@count">
          <em class="p-serie-list-book-count">
            <xsl:variable name="books_count">
              <xsl:call-template name="h-this-amount">
                <xsl:with-param select="$books/@count" name="amount"/>
                <xsl:with-param select="'книга книги книг'" name="words"/>
              </xsl:call-template>
            </xsl:variable>
            (<xsl:value-of select="$books_count"/>)
          </em>
        </xsl:if>
      </h2>
      <ul class="p-serie-list-books">
        <xsl:apply-templates select="books/item[not (position()>$amount)]" mode="p-book-list">
          <xsl:with-param select="$authors" name="authors"/>
        </xsl:apply-templates>
      </ul>
      <xsl:if test="$books/@link_title and $books/@link_url">
        <div class="p-serie-list-books-item-link">
          <a href="{&prefix;}{$books/@link_url}">
            <xsl:value-of select="$books/@link_title"/>
          </a>
        </div>
      </xsl:if>
    </li>
  </xsl:template>

  <xsl:template mode="p-serie-search" match="*">
    <li class="p-serie-loved">
      <xsl:apply-templates select="." mode="h-serie-link"/>
    </li>
  </xsl:template>

  <xsl:template mode="p-serie-loved" match="*">
    <li class="p-serie-loved">
      <xsl:apply-templates select="." mode="h-serie-link"/>
    </li>
  </xsl:template>

  <xsl:template match="module[@name='series' and @action='show']" mode="p-module">
    <xsl:param name="amount" select="30"/>
    <input type="hidden" name="id" value="{serie/@id}"/>
    <h2 class="p-serie-show-title"><xsl:value-of select="serie/@title"/></h2>
    <div class="p-serie-show-info-loved">
      <p/>
      <a href="#" class="add-loved-serie">Добавить серию в любимые</a>
    </div>

    <xsl:if test="&access;/books_edit">
      <p><a href="{&page;/@current_url}edit">Редактировать серию</a></p>
      <p><a href="#" class="p-serie-show-concat-show">Склеить серии</a></p>
      <div class="p-serie-show-concat">
        <form action="" method="post">
          <input type="hidden" name="writemodule" value="SeriesWriteModule" />
          <input type="hidden" name="serie1_id" value="{serie/@id}" />
          <div class="form-group">
            <div class="form-field">
              <label>Склеиваем с серией:</label>
              <input name="serie2_id"/>
            </div>
            <div class="form-field form-field-checkbox">
              <input type="checkbox" name="is_main"/>
              <label>Наша серия главная</label>
            </div>
          </div>
          <div class="form-control">
            <input type="submit" value="Склеить серии"/>
          </div>
        </form>
      </div>
    </xsl:if>

    <xsl:if test="&access;/logs_view">
      <a href="{&page;/@current_url}log">Лог изменений серии</a>
    </xsl:if>

    <div class="p-serie-show-description">
      <xsl:value-of select="serie/@description" disable-output-escaping="yes"/>
    </div>
    <div class="p-serie-show-count">
      <xsl:call-template name="h-this-amount">
        <xsl:with-param select="serie/books/@count" name="amount"></xsl:with-param>
        <xsl:with-param select="'книга книги книг'" name="words"></xsl:with-param>
      </xsl:call-template>
    </div>
    <xsl:if test="serie/parent/item/@title">
      <xsl:call-template name="p-serie-parent">
        <xsl:with-param select="serie/parent/item" name="serie"/>
      </xsl:call-template>
    </xsl:if>
    <xsl:if test="serie/series">
      <ul class="p-serie-show-children">
        <h3>Подсерии:</h3>
        <xsl:apply-templates select="serie/series/item" mode="p-serie-children">
          <xsl:with-param select="'series-show'" name="prefix"/>
        </xsl:apply-templates>
      </ul>
    </xsl:if>
    <ul class="p-serie-show-books">
      <xsl:apply-templates select="serie/books/item[not (position()>$amount)]" mode="p-book-list">
        <xsl:with-param select="authors" name="authors"/>
      </xsl:apply-templates>
    </ul>
  </xsl:template>

	<xsl:template match="*" mode="p-serie-children">
		<li class="p-serie-children">
      <xsl:apply-templates select="." mode="h-serie-link"/>
		</li>
	</xsl:template>

	<xsl:template name="p-serie-parent">
		<xsl:param select="serie" name="serie"/>
		<div class="series-show-parrent">
      Подсерия серии «<xsl:apply-templates select="$serie" mode="h-serie-link"/>»
		</div>
	</xsl:template>

  <xsl:template match="module[@name='series' and @action='edit']" mode="p-module">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="writemodule" value="SeriesWriteModule" />
      <input type="hidden" name="id" value="{serie/@id}" />
      <div class="form-group">
        <h2 class="series-show-title">Редактирование серии «<xsl:value-of select="serie/@title"/>»</h2>
        <div class="form-field">
          <label>Родительская серия</label>
          <input name="id_parent" value="{serie/@id_parent}" />
        </div>
        <div class="form-field">
          <label>Название серии</label>
          <input name="title" value="{serie/@title}" />
        </div>
        <div class="form-field">
          <label>Описание серии</label>
          <textarea name="description">
            <xsl:value-of select="serie/@description" />
          </textarea>
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Сохранить информацию"/>
      </div>
    </form>
  </xsl:template>

  <xsl:template match="module[@name='series' and @action='new']" mode="p-module">
    <form method="post" action="">
      <input type="hidden" name="writemodule" value="SeriesWriteModule" />
      <input type="hidden" name="id" value="0" />
      <div class="form-group">
        <h2>Добавление серии</h2>
        <div class="form-field">
          <label>Название</label>
          <input name="title"/>
        </div>
        <div class="form-field">
          <label>Описание</label>
          <textarea name="description">
          </textarea>
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Сохранить информацию"/>
      </div>
    </form>
  </xsl:template>

</xsl:stylesheet>
