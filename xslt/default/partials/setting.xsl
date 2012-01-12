<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

	<xsl:template match="*" mode="p-setting-list">
		<div class="form-field p-setting-list">
			<input type="hidden" name="id[{@id}]" value="{@id}"/>
			<p class="p-setting-list-comment"><input type="text" name="comment[{@id}]" value="{@comment}"/></p>
			<input type="text" name="name[{@id}]" value="{@name}"/>
			<input type="text" name="value[{@id}]" value="{@value}"/>
		</div>
	</xsl:template>

  <xsl:template match="module[@name='settings' and @action='new']" mode="p-module">
    <h2>Добавление настройки</h2>
		<form method="post" enctype="multipart/form-data" action="">
			<input type="hidden" name="writemodule" value="SettingsWriteModule" />
			<div class="form-group">
				<div class="form-field p-setting-list">
					<input type="hidden" name="id[0]"/>
					<div class="form-field">
						<label for="comment[0]">Описание</label>
						<input type="text" name="comment[0]"/>
					</div>
					<div class="form-field">
						<label for="name[0]">Системное название</label>
						<input type="text" name="name[0]"/>
					</div>
					<div class="form-field">
						<label for="value[0]">Значение</label>
						<input type="text" name="value[0]"/>
					</div>
				</div>
				<div class="form-control">
					<input type="submit" value="Добавить настройку"/>
				</div>
			</div>
		</form>
  </xsl:template>

</xsl:stylesheet>
