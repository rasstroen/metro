<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="module[@name='rightholders' and @action ='show']" mode="p-module">
    <h2>Правообладатель «<xsl:value-of select="rightholder/@title"/>»</h2>
    <div class="p-rightholder-show">
      <xsl:apply-templates select="rightholder" mode="p-rightholder-list"></xsl:apply-templates>
    </div>
  </xsl:template>

  <xsl:template match="*" mode="p-rightholder-list">
    <tr class="p-rightholder-list">
      <td><a href="{@path}"><xsl:value-of select="@title"/></a></td>
      <td><xsl:value-of select="@count"/></td>
      <td><a href="{@path_edit}">Редактировать</a></td>
    </tr>
  </xsl:template>

  <xsl:template match="module[@name='rightholders' and @action='new']" mode="p-module">
    <div class="p-rightholder-edit module">
      <h2>Добавление правообладателя</h2>
      <xsl:apply-templates select="." mode="p-rightholder-form"/>
    </div>
  </xsl:template>

  <xsl:template match="module[@name='rightholders' and @action='edit']" mode="p-module">
    <div class="p-rightholder-edit module">
      <h2>Редактирование правообладателя <xsl:value-of select="rightholder/@name"/></h2>
      <xsl:apply-templates select="." mode="p-rightholder-form"/>
    </div>
  </xsl:template>

  <xsl:template match="*" mode="p-rightholder-form">
    <form method="post" enctype="multipart/form-data" action="{&prefix;}rightholder/{rightholder/@id}/edit">
      <input type="hidden" name="writemodule" value="RightholdersWriteModule" />
      <input type="hidden" name="id" value="{rightholder/@id}" />
      <div class="form-group">
        <div class="form-field">
          <label>Название</label>
          <input name="title" value="{rightholder/@title}" />
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Сохранить информацию"/>
      </div>
    </form>
  </xsl:template>

</xsl:stylesheet>
