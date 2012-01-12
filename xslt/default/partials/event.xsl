<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="*" mode="p-event-list">
    <xsl:param name="users" select="users"/>
    <xsl:param name="authors" select="authors"/>
    <xsl:param name="books" select="books"/>
    <xsl:param name="genres" select="genres"/>
    <xsl:param name="series" select="series"/>

    <xsl:variable name="user" select="$users/item[@id=current()/@user_id]"/>
    <xsl:variable name="owner" select="$users/item[@id=current()/@owner_id]"/>

    <li class="p-event-list">
      <div class="p-event-list-image">
        <xsl:choose>
          <xsl:when test="$owner/@id != 0">
            <xsl:apply-templates select="$owner" mode="h-user-image"/>
          </xsl:when>
          <xsl:otherwise><xsl:apply-templates select="$user" mode="h-user-image"/></xsl:otherwise>
        </xsl:choose>
      </div>
      <div class="p-event-list-text">
        <p class="p-event-list-text-date">
          <a href="{&prefix;}{@link_url}">
            <xsl:call-template name="h-abbr-time">
              <xsl:with-param select="@time" name="time"/>
            </xsl:call-template>
          </a>
        </p>
        <p class="p-event-list-text-title">
          <xsl:if test="@retweet_from != 0"><xsl:apply-templates select="$owner" mode="h-user-link"/> понравилось, что</xsl:if>
          <xsl:apply-templates select="$user" mode="h-user-link"/>
          <xsl:text> </xsl:text>
          <xsl:apply-templates select="." mode="h-action-names"/>
        </p>
        <xsl:choose>
          <xsl:when test="contains(@action,'books_') or contains(@action,'reviews_') or contains(@action,'_add_book')">
            <xsl:apply-templates select="books/item" mode="p-event-book">
              <xsl:with-param select="$books" name="books"/>
              <xsl:with-param select="$authors" name="authors"/>
            </xsl:apply-templates>
          </xsl:when>
          <xsl:when test="contains(@action,'authors_') or @action='loved_add_author'">
            <xsl:apply-templates select="authors/item" mode="p-event-author">
              <xsl:with-param select="$authors" name="authors"/>
            </xsl:apply-templates>
          </xsl:when>
          <xsl:when test="contains(@action,'genres_') or @action='loved_add_genre'">
            <xsl:apply-templates select="genres/item" mode="p-event-genre">
              <xsl:with-param select="$genres" name="genres"/>
            </xsl:apply-templates>
          </xsl:when>
          <xsl:when test="contains(@action,'series_') or @action='loved_add_serie'">
            <xsl:apply-templates select="series/item" mode="p-event-serie">
              <xsl:with-param select="$series" name="series"/>
            </xsl:apply-templates>
          </xsl:when>
          <xsl:when test="contains(@action,'users_')">
            <xsl:apply-templates select="users/item" mode="p-event-user">
              <xsl:with-param select="$users" name="users"/>
            </xsl:apply-templates>
          </xsl:when>
          <xsl:otherwise/>
        </xsl:choose>
        <div class="p-event-list-likes" id="{@id}" name="likes"/>
        <xsl:if test="@body">
          <div class="p-event-list-text-review">
            <xsl:value-of select="@body" disable-output-escaping="yes"/>
          </div>
        </xsl:if>
        <xsl:call-template name="p-comment-new"/>
        <xsl:if test="@commentsCount">
          <ul class="p-event-list-comments">
            <h3>Последние комментарии</h3>
            <xsl:apply-templates select="comments/item" mode="p-comment-list">
              <xsl:with-param select="$users" name="users"></xsl:with-param>
            </xsl:apply-templates>
          </ul>
        </xsl:if>
      </div>
    </li>
  </xsl:template>

  <xsl:template match="*" mode="p-event-book">
    <xsl:param name="books" select="books"/>
    <xsl:param name="authors" select="authors"/>
    <xsl:variable name="book" select="$books/item[@id=current()/@id]"></xsl:variable>
    <xsl:call-template name="p-book-event">
      <xsl:with-param name="book" select="$book"/>
      <xsl:with-param name="author" select="$authors/item[@id=$book/@author_id]"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="*" mode="p-event-author">
    <xsl:param name="authors" select="authors"/>
    <xsl:call-template name="p-author-event">
      <xsl:with-param name="author" select="$authors/item[@id=current()/@id]"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="*" mode="p-event-genre">
    <xsl:param name="genres" select="genres"/>
    <xsl:apply-templates mode="h-genre-link" select="$genres/item[@id=current()/@id]"/>
  </xsl:template>

  <xsl:template match="*" mode="p-event-serie">
    <xsl:param name="series" select="series"/>
    <xsl:apply-templates mode="h-serie-link" select="$series/item[@id=current()/@id]"/>
    <xsl:if test="not(position()=last())">, </xsl:if>
  </xsl:template>

  <xsl:template match="*" mode="p-event-user">
    <xsl:param name="users" select="users"/>
    <xsl:apply-templates mode="h-user-link" select="$users/item[@id=current()/@id]"/>
  </xsl:template>

  <xsl:template match="module[@name='events' and @action='new']" mode="p-module">
    <h2>Поделиться мыслями</h2>
    <form method ="post">
      <input type="hidden" name="action" value="post_new" />
      <input type="hidden" value="EventsWriteModule" name="writemodule" />
      <div class="form-group">
        <label>Тема</label>
        <div class="form-field">
          <input name="subject"/>
        </div>
        <label>Текст поста</label>
        <div class="form-field">
          <textarea name="body"/>
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Отправить" /> или <a href="{&prefix;}me/wall/post">написать длинный пост</a>
      </div>
    </form>
  </xsl:template>

  <xsl:template match="module[@name='events' and @action='show']" mode="p-module">
    <xsl:if test="event/@subject">
      <div class="p-event-show-subject"><xsl:value-of select="event/@subject"/></div>
    </xsl:if>
    <div class="p-event-show-body">
      <xsl:apply-templates select="event" mode="p-event-list">
        <xsl:with-param select="books" name="books"/>
        <xsl:with-param select="users" name="users"/>
        <xsl:with-param select="authors" name="authors"/>
        <xsl:with-param select="series" name="series"/>
        <xsl:with-param select="genres" name="genres"/>
      </xsl:apply-templates>
    </div>
  </xsl:template>


</xsl:stylesheet>
