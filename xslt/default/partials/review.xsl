<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="*" mode="p-review-list">
    <xsl:param name="review" select="."/>
    <xsl:param name="users" select="users"/>
    <xsl:param name="user" select="$users/item[@id=$review/@user_id]"/>
    <xsl:param name="mode"/>
    <li class="p-review-list">
      <div class="p-review-list-image">
        <img src="{$user/@picture}" alt="[Image]"/>
      </div>
      <div class="p-review-list-text">
        <div class="p-review-list-text-time">
          <a href="{&prefix;}{@link_url}">
            <xsl:call-template name="h-abbr-time">
              <xsl:with-param select="@time" name="time"/>
            </xsl:call-template>
          </a>
        </div>
        <div class="p-review-list-text-nickname">
          <xsl:apply-templates select="$user" mode="h-user-link"/>
        </div>
        <xsl:if test="@mark > 0">
          <div class="p-review-list-text-rate">Оценка: <xsl:value-of select="@mark" /></div>
        </xsl:if>
        <div class="p-review-list-text-html">
          <xsl:value-of select="@body" disable-output-escaping="yes"/>
        </div>
        <xsl:if test="@likesCount>0">
          <div class="p-review-list-text-likes">
            Рецензия понравилась
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="@likesCount" name="amount"/>
              <xsl:with-param select="'пользователю пользователям пользователям'" name="words"/>
            </xsl:call-template>
          </div>
        </xsl:if>
      </div>
    </li>
  </xsl:template>

  <xsl:template match="*" mode="p-review-rate">
    <xsl:param name="review" select="."/>
    <xsl:param name="users" select="users"/>
    <xsl:param name="user" select="$users/item[@id=$review/@user_id]"/>
    <li class="p-review-rate">
      <div class="p-review-rate-image"><xsl:apply-templates select="$user" mode="h-user-image"/></div>
      <div class="p-review-rate-text">
        <div class="p-review-rate-text-time">
          <a href="{&prefix;}{@link_url}">
            <xsl:call-template name="h-abbr-time"><xsl:with-param select="@time" name="time"/></xsl:call-template>
          </a>
        </div>
        <div class="p-review-rate-text-title">
          <xsl:apply-templates select="$user" mode="h-user-link"/> оценил книгу на <xsl:value-of select="@mark"/>
        </div>
      </div>
    </li>
  </xsl:template>

  <xsl:template match="*" mode="p-review-user">
    <xsl:param name="review" select="."/>
    <xsl:param name="users" select="users"/>
    <xsl:param name="user" select="$users/item[@id=$review/@user_id]"/>
    <xsl:param name="books" select="books"/>
    <xsl:param name="book" select="$books/item[@id=$review/@book_id]"/>
    <li class="p-review-user">
      <div class="p-review-user-book">
        <div class="p-review-user-book-image">
          <xsl:apply-templates select="$book" mode="h-book-cover"/>
        </div>
        <p class="p-review-user-book-title">
          <xsl:apply-templates select="$book" mode="h-book-link"/>
        </p>
        <p class="p-review-user-book-author">
          <xsl:apply-templates select="$book/author" mode="h-author-link"/>
        </p>
      </div>
      <div class="p-review-user-text">
        <xsl:if test="@mark > 0">
          <div class="p-review-user-text-rate">Оценка: <xsl:value-of select="@mark" /></div>
        </xsl:if>
        <div class="p-review-user-text-html">
          <xsl:value-of select="@body" disable-output-escaping="yes"/>
        </div>
      </div>
    </li>
  </xsl:template>

  <xsl:template match="module[@name='reviews' and @action='new']" mode="p-module">
    <xsl:if test="(&current_profile;)/@id and review">
      <div class="reviews-new module">
        <h2>Оставьте отзыв</h2>
        <form method="post">
          <input type="hidden" value="ReviewsWriteModule" name="writemodule" />
          <input type="hidden" value="{review/@target_id}" name="target_id" />
          <input type="hidden" value="{review/@target_type}" name="target_type" />
          <div class="form-field">
            <label for="annotation">Текст отзыва</label>
            <textarea name="annotation">
              <xsl:value-of select="review/@body" disable-output-escaping="yes" />
            </textarea>
          </div>
          <div class="form-field">
            <label for="rate">Оценка</label>
            <select name="rate">
              <option value="0">-</option>
              <option value="1">
                <xsl:if test="review/@mark = 1">
                  <xsl:attribute name="selected">selected</xsl:attribute>
                </xsl:if>
                <xsl:text>1</xsl:text>
              </option>
              <option value="2">
                <xsl:if test="review/@mark = 2">
                  <xsl:attribute name="selected">selected</xsl:attribute>
                </xsl:if>
                <xsl:text>2</xsl:text>
              </option>
              <option value="3">
                <xsl:if test="review/@mark = 3">
                  <xsl:attribute name="selected">selected</xsl:attribute>
                </xsl:if>
                <xsl:text>3</xsl:text>
              </option>
              <option value="4">
                <xsl:if test="review/@mark = 4">
                  <xsl:attribute name="selected">selected</xsl:attribute>
                </xsl:if>
                <xsl:text>4</xsl:text>
              </option>
              <option value="5">
                <xsl:if test="review/@mark = 5">
                  <xsl:attribute name="selected">selected</xsl:attribute>
                </xsl:if>
                <xsl:text>5</xsl:text>
              </option>
            </select>
          </div>
          <div class="form-control">
            <input type="submit" value="Оставить отзыв" />
          </div>
        </form>
      </div>
    </xsl:if>
  </xsl:template>

</xsl:stylesheet>
