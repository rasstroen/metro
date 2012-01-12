<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>
	<xsl:output omit-xml-declaration="yes"/>

	<xsl:template match="module">
    <xsl:apply-templates select="conditions/item[not(@mode='paging')]" mode="p-misc-condition"/>
    <div class="m-{@name}-{@action}">
      <xsl:apply-templates select="." mode="p-module"/>
    </div>
    <xsl:apply-templates select="conditions/item[@mode='paging']" mode="p-misc-condition"/>
	</xsl:template>

  <xsl:template match="module[@name='authors' and @action='list']" mode="p-module">
    <xsl:param name="title" select="authors/@title"/>
    <xsl:param name="amount" select="authors/@count"/>
    <h2><xsl:value-of select="$title" /> (<xsl:value-of select="$amount"/>)</h2>
    <xsl:apply-templates select="authors/item[not (position()>$amount)]" mode="p-author-list"/>
  </xsl:template>

  <xsl:template match="module[@name='books' and @action='list']" mode="p-module">
    <xsl:apply-templates select="." mode="m-books-list">
      <xsl:with-param name="mode" select="@mode"/>
      <xsl:with-param name="amount" select="books/@count"/>
    </xsl:apply-templates>
  </xsl:template>

  <xsl:template match="module[@name='books' and @action='list' and @mode='shelves']" mode="p-module">
    <xsl:apply-templates select="shelves/item" mode="m-books-list">
      <xsl:with-param name="mode" select="@mode"/>
      <xsl:with-param name="amount" select="20"/>
      <xsl:with-param name="authors" select="authors"/>
      <xsl:with-param name="owner_id" select="shelves/@user_id"/>
    </xsl:apply-templates>
  </xsl:template>

  <xsl:template mode="m-books-list" match="*">
    <xsl:param name="books" select="books"/>
    <xsl:param name="authors" select="authors"/>
    <xsl:param name="amount" select="5"/>
    <xsl:param name="mode" seleft="'def'"/>
    <xsl:param name="owner_id"/>
    <ul class="m-books-list m-books-list-{@mode}">
      <xsl:if test="$mode='shelves'">
      	<xsl:attribute name="class">m-books-list-shelves</xsl:attribute>
      </xsl:if>
      <h2 class="m-books-list-title">
        <xsl:value-of select="$books/@title"/>
        <xsl:if test="$books/@count"> (<xsl:value-of select="$books/@count"/>)</xsl:if>
      </h2>
      <xsl:apply-templates select="$books/item[not (position()>$amount)]" mode="p-book-list">
        <xsl:with-param select="$mode" name="mode"/>
        <xsl:with-param select="$authors" name="authors"/>
        <xsl:with-param name="owner_id" select="$owner_id"/>
      </xsl:apply-templates>
      <xsl:if test="$books/@link_title and $books/@link_url">
        <div class="m-books-list-link">
          <a href="{&prefix;}{$books/@link_url}"><xsl:value-of select="$books/@link_title"/></a>
        </div>
      </xsl:if>
    </ul>
  </xsl:template>

  <xsl:template match="module[@name='events' and @action='list']" mode="p-module">
    <xsl:param name="events" select="events"/>
    <xsl:param name="amount" select="events/@count"/>
    <h2><xsl:value-of select="$events/@title"/></h2>
    <xsl:apply-templates select="events/item[not (position()>$amount)]" mode="p-event-list">
      <xsl:with-param name="users" select="users"/>
      <xsl:with-param name="authors" select="authors"/>
      <xsl:with-param name="books" select="books"/>
      <xsl:with-param name="genres" select="genres"/>
      <xsl:with-param name="series" select="series"/>
    </xsl:apply-templates>
  </xsl:template>

  <xsl:template match="module[@name='forum' and @action='list']" mode="p-module">
    <xsl:for-each select="forums/item">
      <div><a href="{&prefix;}forum/{@tid}"><xsl:value-of select="@name"/></a></div>
    </xsl:for-each>
  </xsl:template>

  <xsl:template match="module[@name='genres' and @action='list']" mode="p-module">
    <xsl:param name="amount" select="5"/>
    <xsl:apply-templates select="genres/item" mode="p-genre-list"/>
  </xsl:template>

  <xsl:template match="module[@name='genres' and @action='list' and @mode='loved']" mode="p-module">
    <xsl:param name="amount" select="5"/>
    <h2 class="books-list-title">
      <xsl:value-of select="genres/@title"/>
      <xsl:if test="genres/@count"> (<xsl:value-of select="genres/@count"/>)</xsl:if>
    </h2>
    <xsl:apply-templates select="genres/item" mode="p-genre-loved"/>
    <xsl:if test="genres/@link_title and genres/@link_url">
      <div class="m-users-list-link">
        <a href="{&prefix;}{genres/@link_url}">
          <xsl:value-of select="genres/@link_title"></xsl:value-of>
        </a>
      </div>
    </xsl:if>
  </xsl:template>

  <xsl:template match="module[@name='log' and @action='list']" mode="p-module">
    <xsl:param name="logs" select="logs"/>
    <xsl:param name="amount" select="10"/>
    <h2>
      <xsl:choose>
        <xsl:when test="$logs/@title">
          <xsl:value-of select="$logs/@title"/>
        </xsl:when>
        <xsl:when test="@mode='user'">Действия пользователя
          <xsl:apply-templates select="users/item[1]" mode="h-user-link"/>
        </xsl:when>
        <xsl:when test="@mode='book'">Изменения книги
          <xsl:apply-templates select="books/item[1]" mode="h-book-link"/>
        </xsl:when>
        <xsl:when test="@mode='author'">Изменения автора
          <xsl:apply-templates select="authors/item[1]" mode="h-author-link"/>
        </xsl:when>
        <xsl:when test="@mode='serie'">Изменения серии
          <xsl:apply-templates select="series/item[1]" mode="h-serie-link"/>
        </xsl:when>
        <xsl:otherwise></xsl:otherwise>
      </xsl:choose>
    </h2>
    <form method="POST" action="{&page;/@current_url}">
      <input type="hidden" value="LogWriteModule" name="writemodule"/>
      <xsl:call-template name="log-list-form-controls"/>
      <ul class="log-list">
        <xsl:apply-templates select="logs/item[not(position()>$amount)]" mode="p-log-list">
          <xsl:with-param name="mode" select="@mode"/>
          <xsl:with-param name="users" select="users"/>
          <xsl:with-param name="authors" select="authors"/>
          <xsl:with-param name="books" select="books"/>
          <xsl:with-param name="series" select="series"/>
          <xsl:with-param name="genres" select="genres"/>
        </xsl:apply-templates>
      </ul>
      <xsl:call-template name="log-list-form-controls"/>
    </form>
  </xsl:template>

  <xsl:template name="log-list-form-controls">
    <div class="log-list-form-controls">
      <input type="submit" name="apply" value="Повторить выбранные действия"/>
      <input type="submit" name="cancel" value="Отменить выбранные действия"/>
    </div>
  </xsl:template>

  <xsl:template match="module[@name='magazines' and @action='list']" mode="p-module">
    <xsl:param name="amount" select="20"/>
    <h1>Периодика</h1>
    <xsl:apply-templates select="magazines/item[not (position()>$amount)]" mode="p-magazine-list"/>
  </xsl:template>

  <xsl:template match="module[@name='messages' and @action='list']" mode="p-module">
    <xsl:apply-templates select="messages/item" mode="p-message-list">
      <xsl:with-param select="users" name="users"/>
      <xsl:with-param select="@mode" name="mode"/>
    </xsl:apply-templates>
  </xsl:template>

  <xsl:template match="module[@name='reviews' and @action='list']" mode="p-module">
    <xsl:param name="users" select="users"/>
    <xsl:param name="books" select="books"/>
    <xsl:if test="count(reviews/item)!=0">
      <xsl:choose>
        <xsl:when test="@mode='user'">
          <xsl:apply-templates select="reviews/item" mode="p-review-user">
            <xsl:with-param name="users" select="$users"/>
            <xsl:with-param name="books" select="$books"/>
          </xsl:apply-templates>
        </xsl:when>
        <xsl:when test="@mode='rates'">
          <h2>Оценки пользователей</h2>
          <xsl:apply-templates select="reviews/item" mode="p-review-rate">
            <xsl:with-param name="users" select="$users"/>
          </xsl:apply-templates>
        </xsl:when>
        <xsl:otherwise>
          <h2>Отзывы пользователей</h2>
          <xsl:apply-templates select="reviews/item" mode="p-review-list">
            <xsl:with-param name="users" select="$users"/>
          </xsl:apply-templates>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>
  </xsl:template>

  <xsl:template match="module[@name='series' and @action='list']" mode="p-module">
    <h2 class="m-series-list-title">
      <xsl:choose>
        <xsl:when test="series/@title">
          <xsl:value-of select="series/@title"/>
        </xsl:when>
      	<xsl:otherwise>Серии</xsl:otherwise>
      </xsl:choose>
      <xsl:if test="series/@count"> (<xsl:value-of select="series/@count"/>)</xsl:if>
    </h2>
    <xsl:choose>
      <xsl:when test="@mode='search'">
        <xsl:apply-templates select="series/item" mode="p-serie-search"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:apply-templates select="series/item" mode="p-serie-list">
          <xsl:with-param select="authors" name="authors"></xsl:with-param>
        </xsl:apply-templates>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="module[@name='series' and @action='list' and @mode='loved']" mode="p-module">
    <xsl:param name="amount" select="5"/>
    <h2 class="books-list-title">
      <xsl:value-of select="series/@title"/>
      <xsl:if test="series/@count"> (<xsl:value-of select="series/@count"/>)</xsl:if>
    </h2>
    <xsl:apply-templates select="series/item" mode="p-serie-loved"/>
    <xsl:if test="series/@link_title and series/@link_url">
      <div class="m-series-list-link">
        <a href="{&prefix;}{series/@link_url}">
          <xsl:value-of select="series/@link_title"></xsl:value-of>
        </a>
      </div>
    </xsl:if>
  </xsl:template>

  <xsl:template match="module[@name='users' and @action='list']" mode="p-module">
    <xsl:param name="users" select="users"/>
    <xsl:param name="amount" select="4"/>
    <h2>
      <xsl:value-of select="$users/@title"/>
      <xsl:if test="$users/@count"> (<xsl:value-of select="$users/@count"/>)</xsl:if>
    </h2>
    <xsl:apply-templates select="users/item" mode="p-user-list"/>
    <xsl:if test="$users/@link_title and $users/@link_url">
      <div class="m-users-list-link">
        <a href="{&prefix;}{$users/@link_url}"><xsl:value-of select="$users/@link_title"/></a>
      </div>
    </xsl:if>
  </xsl:template>

  <xsl:template match="module[@name='users' and @action='list' and @mode='search']" mode="p-module">
    <h2><xsl:value-of select="users/@title"/> (<xsl:value-of select="users/@count"/>)</h2>

		<div class="m-users-list-search-form">
			<form action="" mode="get">
				<input type="text" name="s" value="{&page;/variables/@s}"/>
				<input type="submit" value="Искать"/>
			</form>
		</div>

		<table class="m-users-list-search">
			<tr>
				<th>#</th>
				<th>Ник</th>
				<th>E-mail</th>
				<th>Роль</th>
				<th>Регистрация</th>
				<th>Посл. заход</th>
				<th>Подписка</th>
			</tr>
			<xsl:apply-templates select="users/item" mode="p-user-search"/>
		</table>
  </xsl:template>

  <xsl:template match="module[@name='ocr' and @action='list']" mode="p-module">
    <xsl:apply-templates select="books/item" mode="p-ocr-list">
			<xsl:with-param name="users" select="users"/>
			<xsl:with-param name="authors" select="authors"/>
		</xsl:apply-templates>
  </xsl:template>

  <xsl:template match="module[@name='ocr' and @action='list' and @mode='book']" mode="p-module">
    <xsl:apply-templates select="ocr/item" mode="p-ocr-list-book">
      <xsl:with-param select="users" name="users"/>
      <xsl:with-param select="states" name="states"/>
      <xsl:with-param select="statuses" name="statuses"/>
    </xsl:apply-templates>
  </xsl:template>

  <xsl:template match="module[@name='contributions' and @action='list']" mode="p-module">
    <xsl:apply-templates select="contributions/item[@state='approved']" mode="p-contribution-list">
			<xsl:with-param name="authors" select="authors"/>
			<xsl:with-param name="books" select="books"/>
			<xsl:with-param name="series" select="series"/>
			<xsl:with-param name="genres" select="genres"/>
		</xsl:apply-templates>
    <xsl:apply-templates select="contributions/item[not(@state='approved')]" mode="p-contribution-list">
			<xsl:with-param name="authors" select="authors"/>
			<xsl:with-param name="books" select="books"/>
			<xsl:with-param name="series" select="series"/>
			<xsl:with-param name="genres" select="genres"/>
		</xsl:apply-templates>
  </xsl:template>

  <xsl:template match="module[@name='comments' and @action='list']" mode="p-module">
    <h2><xsl:value-of select="comments/@title"/></h2>
    <xsl:apply-templates select="comments/item" mode="p-comment-list">
			<xsl:with-param name="users" select="users"/>
		</xsl:apply-templates>
  </xsl:template>

  <xsl:template match="module[@name='statistics' and @action='list']" mode="p-module">
    <h2><xsl:value-of select="statistics/@title"/></h2>
    <xsl:apply-templates select="statistics" mode="h-statistics-period"/>
    <table class="m-statistics-list-table">
      <xsl:choose>
        <xsl:when test="@mode='books'">
          <tr><th>ID</th><th>Название книги</th><th>Скачали</th></tr>
          <xsl:apply-templates select="statistics/item" mode="p-statistic-list">
            <xsl:with-param name="books" select="books"/>
          </xsl:apply-templates>
        </xsl:when>
        <xsl:when test="@mode='authors'">
          <tr><th>ID</th><th>Автор</th><th>Скачали</th></tr>
          <xsl:apply-templates select="statistics/item" mode="p-statistic-list">
            <xsl:with-param name="authors" select="authors"/>
          </xsl:apply-templates>
        </xsl:when>
        <xsl:when test="@mode='genres'">
          <tr><th>ID</th><th>Жанр</th><th>Скачали</th></tr>
          <xsl:apply-templates select="statistics/item" mode="p-statistic-list">
            <xsl:with-param name="genres" select="genres"/>
          </xsl:apply-templates>
        </xsl:when>
        <xsl:when test="@mode='users'">
          <tr><th>Пользователь</th><th>Скачал</th></tr>
          <xsl:apply-templates select="statistics/item" mode="p-statistic-list">
            <xsl:with-param name="users" select="users"/>
          </xsl:apply-templates>
        </xsl:when>
        <xsl:when test="contains('book,author,genre,user',@mode)">
          <tr><th>Дата</th><th>Число скачиваний</th></tr>
          <xsl:apply-templates select="statistics/item" mode="p-statistic-single"/>
        </xsl:when>
      	<xsl:otherwise></xsl:otherwise>
      </xsl:choose>
    </table>
  </xsl:template>

  <xsl:template match="module[@name='partners' and @action='list']" mode="p-module">
    <h2><xsl:value-of select="partners/@title"/></h2>
		<p><a href="{&prefix;}admin/partners/new">Добавить партнёра</a></p>
    <xsl:apply-templates select="statistics" mode="h-statistics-period"/>
    <table class="m-partners-list">
      <thead><tr><th>Партнёр</th><th>Регистрации</th></tr></thead>
      <tbody><xsl:apply-templates select="partners/item" mode="p-partner-list"/></tbody>
    </table>
  </xsl:template>

  <xsl:template match="module[@name='rightholders' and @action='list']" mode="p-module">
    <h2><xsl:value-of select="rightholders/@title"/></h2>
		<p><a href="{&prefix;}admin/rightholders/new">Добавить правообладателя</a></p>
    <xsl:apply-templates select="statistics" mode="h-statistics-period"/>
    <table class="m-rightholders-list">
      <thead><tr><th>Правообладатель</th><th/></tr></thead>
      <tbody><xsl:apply-templates select="rightholders/item" mode="p-rightholder-list"/></tbody>
    </table>
  </xsl:template>

  <xsl:template match="module[@name='settings' and @action='list']" mode="p-module">
    <h2><xsl:value-of select="settings/@title"/></h2>
		<form method="post" enctype="multipart/form-data" action="">
			<input type="hidden" name="writemodule" value="SettingsWriteModule" />
			<div class="form-group">
				<xsl:apply-templates select="settings/item" mode="p-setting-list"/>
				<div class="form-control">
					<input type="submit" value="Сохранить информацию"/>
				</div>
			</div>
		</form>
  </xsl:template>

</xsl:stylesheet>
