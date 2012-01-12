<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="module[@name='partners' and @action ='show']" mode="p-module">
    <h2>Партнёр «<xsl:value-of select="partner/@title"/>»</h2>
    <div class="p-partner-show">
      <xsl:apply-templates select="partner" mode="p-partner-list"></xsl:apply-templates>
    </div>
  </xsl:template>

  <xsl:template match="*" mode="p-partner-list">
    <tr class="p-partner-list">
      <td><a href="{@path}"><xsl:value-of select="@title"/></a> (<xsl:value-of select="@pid"/>)</td>
      <td><xsl:value-of select="@count"/></td>
      <td><a href="{@path_edit}">Редактировать</a></td>
    </tr>
  </xsl:template>

  <xsl:template match="module[@name='partners' and @action='new']" mode="p-module">
    <div class="p-partner-edit module">
      <h2>Добавление партнёра</h2>
      <xsl:apply-templates select="." mode="p-partner-form"/>
    </div>
  </xsl:template>

  <xsl:template match="module[@name='partners' and @action='edit']" mode="p-module">
    <div class="p-partner-edit module">
      <h2>Редактирование партнёра <xsl:value-of select="partner/@name"/></h2>
      <xsl:apply-templates select="." mode="p-partner-form"/>
    </div>
  </xsl:template>

  <xsl:template match="*" mode="p-partner-form">
    <form method="post" enctype="multipart/form-data" action="{&prefix;}partner/{partner/@id}/edit">
      <input type="hidden" name="writemodule" value="PartnersWriteModule" />
      <input type="hidden" name="id" value="{partner/@id}" />
      <div class="form-group">
        <div class="form-field">
          <label>Название</label>
          <input name="title" value="{partner/@title}" />
        </div>
        <div class="form-field">
          <label>PID</label>
          <input name="pid" value="{partner/@pid}" />
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Сохранить информацию"/>
      </div>
    </form>
  </xsl:template>

</xsl:stylesheet>
