<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template mode="p-comment-list" match="*">
    <xsl:param name="users" select="users"/>
    <xsl:param name="level" select="1"/>
    <xsl:variable name="comment" select="."/>
    <xsl:variable select="$users/item[@id = $comment/@commenter_id]" name="user"></xsl:variable>
    <li class="p-comment-list">
      <div class="p-comment-list-image">
        <img src="{$user/@picture}" alt="[{$user/@nickname}]"/>
      </div>
      <div class="p-comment-list-text">
        <div class="p-comment-list-text-time">
          <xsl:call-template name="h-abbr-time">
            <xsl:with-param select="@time" name="time"/>
          </xsl:call-template>
        </div>
        <xsl:value-of select="$user/@nickname" />:
        <xsl:value-of select="@comment" disable-output-escaping="yes"/>
      </div>
    </li>
    <xsl:if test="($level = 1) and not(@type)">
      <ul class="p-comment-list-answers">
        <xsl:apply-templates select="answers/item" mode="p-comment-list">
          <xsl:with-param select="$users" name="users"></xsl:with-param>
          <xsl:with-param select="2" name="level"></xsl:with-param>
        </xsl:apply-templates>
        <xsl:call-template name="p-comment-new">
          <xsl:with-param name="comment_id" select="$comment/@id" />
          <xsl:with-param name="id" select="@parent_id" />
          <xsl:with-param name="title_text" select="'Ответить'" />
        </xsl:call-template>
      </ul>
    </xsl:if>
  </xsl:template>

  <xsl:template name="p-comment-new">
    <xsl:param name="comment_id" select="0"></xsl:param>
    <xsl:param name="id" select="@id"></xsl:param>
    <xsl:param name="title_text" select="'Оставить комментарий'"></xsl:param>
    <div class="events-list-item-comments-add">
      <a class="add-comment" href="#"><xsl:value-of select="$title_text"/></a>
    </div>
    <div class="p-comment-new" style="display:none">
      <h3><xsl:value-of select="$title_text" /></h3>
      <form method="post">
        <input type="hidden" name="id" value="{$id}" />
        <input type="hidden" name="comment_id" value="{$comment_id}" />
        <input type="hidden" name="action" value="comment_new" />
        <input type="hidden" value="EventsWriteModule" name="writemodule" />
        <div class="form-group">
          <div class="form-field">
            <textarea name="comment"/>
          </div>
          <div class="form-field">
            <label>Подписаться</label>
            <input name="subscribe" type="checkbox"/>
          </div>
        </div>
        <div class="form-control">
          <input type="submit" value="Оставить комментарий"/>
        </div>
      </form>
    </div>
  </xsl:template>

  

  <xsl:template match="module[@name='comments' and @action='new']" mode="p-module">
    <form method="post" action="">
      <input type="hidden" name="writemodule" value="CommentsWriteModule"/>
      <input type="hidden" name="id" value="{comment/@id}"/>
      <input type="hidden" name="type" value="{comment/@type}"/>
      <div class="form-group">
        <div class="form-field">
          <label>Комментарий</label>
          <textarea name="comment"/>
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Отправить комментарий"/>
      </div>
    </form>
  </xsl:template>

</xsl:stylesheet>
