<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>
	<xsl:output omit-xml-declaration="yes"/>
	<xsl:output indent="yes"/>

  <xsl:template match="module[@name='emailconfirm' and @action ='show']" mode="p-module">
    <xsl:choose>
      <xsl:when test="write/@error">
        <xsl:value-of select="write/@error" />
      </xsl:when>
      <xsl:when test="write/@success">
        <p>Вы успешно подтвердили свой почтовый ящик!</p>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="module[@name='register' and @action ='show' and not(@mode)]" mode="p-module">
    <h2>Регистрация</h2>
    <xsl:choose>
      <xsl:when test="&current_profile;/@authorized = 1"/>
      <xsl:otherwise>
        <xsl:if test="write/@result">
          <xsl:choose>
            <xsl:when test="write/@success">
              <div class="form-notice">
                Вы успешно зарегистрированы. Проверьте почтовый ящик чтобы зайти на сайт
              </div>
            </xsl:when>
            <xsl:otherwise>
              <div class="form-error">
                Возникли проблемы при попытке зарегистрироваться
              </div>
              <xsl:call-template name="p-misc-register-form"/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:if>
        <xsl:if test="not(write/@result)">
          <xsl:call-template name="p-misc-register-form"/>
        </xsl:if>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="p-misc-register-form">
    <form method="post">
      <input type="hidden" value="RegisterWriteModule" name="writemodule" />
      <div class="form-group">
        <div class="form-field">
          <label>Электронная почта</label>
          <input name="email" value="{write/@email}" />
          <p class="form-error-exp"><xsl:value-of select="write/@email_error" /></p>
        </div>
        <div class="form-field">
          <label>Пароль</label>
          <input name="password" type="text" value="" />
          <p class="form-error-exp"><xsl:value-of select="write/@password_error" /></p>
        </div>
        <div class="form-field">
          <label>Никнейм</label>
          <input name="nickname" value="{write/@nickname}" />
          <p class="form-error-exp"><xsl:value-of select="write/@nickname_error" /></p>
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Зарегистрироваться" />
      </div>
    </form>
  </xsl:template>

  <xsl:template match="*" mode="p-misc-condition">
    <ul class="p-misc-condition">
      <xsl:if test="@mode='sorting'"><div class="p-misc-condition-title">Сортировать по</div></xsl:if>
      <xsl:apply-templates select="options/item" mode="p-misc-condition-option"/>
    </ul>
  </xsl:template>

  <xsl:template match="*" mode="p-misc-condition-option">
    <li>
      <xsl:attribute name="class">
        <xsl:text>p-misc-condition-option</xsl:text>
        <xsl:if test="@current=1"> current</xsl:if>
      </xsl:attribute>
      <xsl:apply-templates select="." mode="h-variant-link"/>
    </li>
  </xsl:template>

</xsl:stylesheet>
