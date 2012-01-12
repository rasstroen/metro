<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="module[@name='magazines' and @action='show']" mode="p-module">
    <h1><xsl:value-of select="magazine/@title"/></h1>
    <xsl:if test="&access;/books_edit">
      <div class="magazine-show-edit">
        <a href="{&prefix;}magazine/{magazine/@id}/edit">Редактировать журнал</a>
      </div>
      <div class="magazine-show-edit">
        <a href="{&prefix;}book/new?m={magazine/@id}&amp;title={magazine/@title}&amp;subtitle=№ NN за YYYY год">Добавить номер</a>
      </div>
    </xsl:if>
    <div class="p-magazine-show-info">
      <p><xsl:value-of select="magazine/@issn"/></p>
      <p><xsl:value-of select="magazine/@rightholder"/></p>
    </div>
    <div class="annotation">
      <xsl:value-of select="magazine/@annotation" disable-output-escaping="yes" />
    </div>
    <ul class="p-magazine-show-years">
      <h2>Выпуски журнала</h2>
      <xsl:apply-templates select="magazine/years/item" mode="p-magazine-year"/>
    </ul>
  </xsl:template>

  <xsl:template match="*" mode="p-magazine-year">
    <li class="p-magazine-year">
      <h3><xsl:value-of select="@year"/></h3>
      <ul class="p-magazine-year-books">
        <xsl:apply-templates select="books/item" mode="p-magazine-year-books-item"/>
      </ul>
    </li>
  </xsl:template>

  <xsl:template match="*" mode="p-magazine-year-books-item">
    <xsl:choose>
      <xsl:when test="@bid">
        <a href="{&prefix;}b/{@bid}"><xsl:value-of select="@n"></xsl:value-of></a>
      </xsl:when>
      <xsl:otherwise>
        <a class="p-magazine-year-books-item-empty" href="{&prefix;}book/new?n={@n}&amp;m={../../../../@id}&amp;title={../../../../@title}&amp;subtitle=№ {@n} за {../../@year} год&amp;year={../../@year}">
          <xsl:value-of select="@n"></xsl:value-of>
        </a>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="module[@name='magazines' and @action='edit']" mode="p-module">
    <h2>Редактирование журнала «<xsl:value-of select="magazine/@title"/>»</h2>
    <xsl:apply-templates select="." mode="p-magazine-form"/>
  </xsl:template>

  <xsl:template match="module[@name='magazines' and @action='new']" mode="p-module">
    <h2>Добавление нового журнала</h2>
    <xsl:apply-templates select="." mode="p-magazine-form"/>
  </xsl:template>

  <xsl:template match="*" mode="p-magazine-form">
    <div class='p-magazine-form'>
      <form method="post" enctype="multipart/form-data" action="{&prefix;}magazine/{magazine/@id}/edit">
        <input type="hidden" name="writemodule" value="MagazineWriteModule" />
        <input type="hidden" name="id" value="{magazine/@id}" />
        <input type="hidden" name="n" value="{write/@n}" />
        <input type="hidden" name="m" value="{write/@m}" />
        <div class="form-group">
          <div class="form-field">
            <label>Название</label>
            <input name="title" value="{magazine/@title}" />
          </div>
          <div class="form-field">
            <label>ISSN</label>
            <input name="isbn" value="{magazine/@isbn}" />
          </div>
          <div class="form-field">
            <label>Язык журнала</label>
            <xsl:apply-templates select="magazine" mode="h-lang_code-select"/>
          </div>
          <div class="form-field">
            <label>Правообладатель</label>
            <input name="rightholder" value="{magazine/@rightholder}" />
          </div>
          <div class="form-field">
            <label>Анотация</label>
            <textarea name="annotation">
              <xsl:value-of select="magazine/@annotation" />
            </textarea>
          </div>
        </div>
        <div class="form-group">
          <h2>Обложка</h2>
          <img src="{magazine/@cover}?{magazine/@lastSave}" alt="[Обложка]" />
          <div class="form-field">
            <input type="file" name="cover"></input>
          </div>
        </div>
        <div class="form-control">
          <input type="submit" value="Сохранить информацию"/>
        </div>
      </form>
    </div>
  </xsl:template>

  <xsl:template mode="p-magazine-list" match="*">
    <li class="p-magazine-list">
      <h2 class="p-magazine-list-title">
        <xsl:apply-templates select="." mode="h-magazine-link"/>
      </h2>
      <div class="p-magazine-list-years">
        <xsl:value-of select="@first_year" /> &mdash; <xsl:value-of select="@last_year" />
      </div>
    </li>
  </xsl:template>


</xsl:stylesheet>
