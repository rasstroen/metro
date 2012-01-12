<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="*" mode="p-message-list">
    <xsl:param select="users" name="users"/>
    <xsl:param select="@mode" name="mode"/>
    <li class="p-message-list" id="{@thread_id}-{@id}">
      <xsl:attribute name="id">message-<xsl:value-of select="@thread_id"/><xsl:if test="$mode='thread'">-<xsl:value-of select="@id"/></xsl:if>
      </xsl:attribute>
      <xsl:attribute name="class">p-message-list <xsl:if test="@is_new=1">new</xsl:if></xsl:attribute>
      <xsl:apply-templates select="members/item" mode="p-message-list-member">
        <xsl:with-param select="$users" name="users"/>
      </xsl:apply-templates>
      <div class="p-message-list-text">
        <div class="p-message-list-text-time">
          <xsl:call-template name="h-abbr-time">
            <xsl:with-param select="@time" name="time"/>
          </xsl:call-template>
        </div>
        <p class="p-message-list-text-subject">
          <xsl:choose>
            <xsl:when test="$mode='thread'">
              <xsl:value-of select="@subject"/>
            </xsl:when>
            <xsl:otherwise>
              <a href="{&prefix;}me/messages/{@thread_id}">
                <xsl:choose>
                  <xsl:when test="not(@subject = '')"><xsl:value-of select="@subject"/></xsl:when>
                  <xsl:otherwise>Сообщение без темы</xsl:otherwise>
                </xsl:choose>
              </a>
            </xsl:otherwise>
          </xsl:choose>
        </p>
        <a class="p-message-list-text-delete" href="#">Удалить</a>
        <div class="p-message-list-text-body">
          <xsl:value-of select="@html" disable-output-escaping="yes"/>
        </div>
      </div>
    </li>
  </xsl:template>

  <xsl:template match="*" mode="p-message-list-member">
    <xsl:param select="users" name="users"/>
    <xsl:variable select="$users/item[@id=current()/@user_id]" name="user"/>
    <div class="p-message-list-member">
      <xsl:apply-templates select="$user" mode="h-user-image"/>
      <p class="p-message-list-user"><xsl:apply-templates select="$user" mode="h-user-link"/></p>
    </div>
  </xsl:template>

  <xsl:template match="module[@name='messages' and @action='new']" mode="p-module">
    <xsl:if test="(&current_profile;)/@id">
      <h2>Новое сообщение</h2>
      <form method="post">
        <input type="hidden" value="MessagesWriteModule" name="writemodule" />
        <input type="hidden" value="{message/@thread_id}" name="thread_id" />
        <input type="hidden" value="{&current_profile;/@id}" name="id_author" />
        <xsl:if test="message/@thread_id=0">
          <div class="form-field">
            <label for="subject">Тема сообщения</label>
            <input type="text" name="subject" />
          </div>
        </xsl:if>
        <div class="form-field">
          <label for="body">Текст сообщения</label>
          <textarea name="body"/>
        </div>
        <xsl:if test="message/@thread_id=0">
          <div class="form-field">
            <label for="to">Получатели</label>
            <input type="text" name="to[]" value="{&page;/variables/@to}"/>
          </div>
        </xsl:if>
        <div class="form-control">
          <input type="submit" value="Отправить сообщение" />
        </div>
      </form>
    </xsl:if>
  </xsl:template>

</xsl:stylesheet>

