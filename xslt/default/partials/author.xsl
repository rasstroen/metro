<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="module[@name='authors' and @action ='show']" mode="p-module">
    <div class="p-author-show">
      <input type="hidden" name="id" value="{author/@id}"/>
      <div class="p-author-show-image"><img src="{author/@picture}" alt="[Image]"/> </div>
      <div class="p-author-show-text">
        <h1>
          <xsl:call-template name="h-author-name">
            <xsl:with-param name="author" select="author"/>
          </xsl:call-template>
        </h1>

				<xsl:if test="&current_profile;/@id">
					<div class="p-author-show-info-loved">
						<p/>
						<a href="#" class="add-loved-author">Добавить автора в любимые</a>
					</div>
					<div class="p-author-show-info-subscribed">
						<p/>
						<a href="#" class="subscribe-author">Подписаться на новые книги этого автора</a>
					</div>
				</xsl:if>

        <xsl:if test="&access;/books_edit">
          <p><a href="{&page;/@current_url}edit">Редактировать автора</a></p>
					<p><a href="{&prefix;}book/new?author_id={author/@id}">Добавить книгу этого автора</a></p>
        </xsl:if>
        <xsl:if test="&access;/logs_view"><a href="{&page;/@current_url}log">Лог изменений автора</a></xsl:if>
        <div class="p-author-show-text-bio-short">
          <noindex><xsl:value-of select="author/bio/@short" disable-output-escaping="yes"/></noindex>
        </div>
        <div class="p-author-show-text-bio-full" style="display:none">
          <xsl:value-of select="author/bio/@html" disable-output-escaping="yes"/>
        </div>
        <p><a class="p-author-show-text-bio-toggl" href="#">Показать полную биографию</a></p>
      </div>
    </div>
  </xsl:template>

  <xsl:template match="*" mode="p-author-list">
    <li class="p-author-list">
      <div class="p-author-list-image"><xsl:apply-templates select="." mode="h-author-image"/></div>
      <p class="p-author-list-name"><xsl:apply-templates select="." mode="h-author-link"/></p>
    </li>
  </xsl:template>

  <xsl:template name="p-author-event">
    <xsl:param name="author" select="author"/>
    <div class="p-author-event">
      <div class="p-author-event-image">
        <xsl:apply-templates select="$author" mode="h-author-image"/>
      </div>
      <div class="p-author-event-name">
        <xsl:apply-templates select="$author" mode="h-author-link"/>
      </div>
    </div>
  </xsl:template>

  <xsl:template match="module[@name='authors' and @action='edit']" mode="p-module">
    <div class="p-author-edit module">
      <form method="post" enctype="multipart/form-data" action="{&prefix;}author/{author/@id}/edit">
        <input type="hidden" name="writemodule" value="AuthorWriteModule" />
        <input type="hidden" name="id" value="{author/@id}" />
        <div class="form-group">
          <h2>Редактирование автора <xsl:value-of select="author/@name"></xsl:value-of>
          </h2>
          <div class="form-field">
            <label>Имя</label>
            <input name="first_name" value="{author/@first_name}" />
          </div>
          <div class="form-field">
            <label>Отчество</label>
            <input name="middle_name" value="{author/@middle_name}" />
          </div>
          <div class="form-field">
            <label>Фамилия</label>
            <input name="last_name" value="{author/@last_name}" />
          </div>
          <div class="form-field">
            <label>Годы жизни</label>
            <input name="date_birth" value="{author/@date_birth}" /> &mdash;
            <input name="date_death" value="{author/@date_death}" />
          </div>
          <div class="form-field">
            <label>Официальный сайт</label>
            <input name="homepage" value="{author/@homepage}" />
          </div>
          <div class="form-field">
            <label>Страница в Википедии</label>
            <input name="wiki_url" value="{author/@wiki_url}" />
          </div>
          <div class="form-field">
            <label>Основной язык</label>
            <xsl:apply-templates select="author" mode="h-lang_code-select"/>
          </div>
          <div class="form-field">
            <label>Биография</label>
            <textarea name="bio">
              <xsl:value-of select="author/bio/@html" />
            </textarea>
          </div>
        </div>
        <div class="form-group">
          <h2>Фотография</h2>
          <img src="{author/@picture}?{author/@lastSave}" alt="[Фото]" />
          <div class="form-field">
            <input type="file" name="picture"></input>
          </div>
        </div>
        <div class="form-control">
          <input type="submit" value="Сохранить информацию"/>
        </div>
      </form>
      <div class="form-group">
        <h2>Страницы на других языках, дубликаты</h2>
        <div class="p-author-edit-relations">
          <xsl:call-template name="p-author-edit-relation"/>
          <xsl:variable select="author/relations/authors" name="authors"/>
          <xsl:for-each select="author/relations/item">
            <xsl:call-template name="p-author-edit-relation">
              <xsl:with-param name="authors" select="$authors"/>
            </xsl:call-template>
          </xsl:for-each>
          <xsl:call-template name="p-author-edit-relation-new"/>
        </div>
      </div>
    </div>
  </xsl:template>

  <xsl:template name="p-author-edit-relation">
    <xsl:param name="authors" select="authors"/>
    <xsl:param name="author_id" select="@id2"/>
    <xsl:param name="author" select="$authors/item[@id=$author_id]"/>
    <xsl:variable name="class">
      <xsl:text>p-author-edit-relation</xsl:text>
      <xsl:choose>
        <xsl:when test="@id2"> relation-<xsl:value-of select="@id2"/></xsl:when>
        <xsl:otherwise> hidden</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <div class="{$class}">
      <a href="#" class="p-author-edit-relation-delete">Удалить</a>
      <input type="hidden" name="id_relation" value="{$author/@id}"/>
      <div class="p-author-edit-relation-type">
        <xsl:value-of select="@relation_type_name"/>
      </div>
      <xsl:text>:</xsl:text>
      <div class="p-author-edit-relation-title">
        <xsl:apply-templates select="$author" mode="h-author-link"/>
      </div>
    </div>
  </xsl:template>

  <xsl:template name="p-author-edit-relation-new">
    <xsl:call-template name="h-relation-type-select">
      <xsl:with-param name="object" select="author"/>
    </xsl:call-template>
    <input name="author_id" type="text" class="p-author-edit-relation-new-id" />
    <a href="#" class="p-author-edit-relation-new-submit">Добавить</a>
  </xsl:template>

  <xsl:template match="module[@name='authors' and @action='new']" mode="p-module">
    <form method="post" enctype="multipart/form-data" action="{&prefix;}author/new">
      <input type="hidden" name="writemodule" value="AuthorWriteModule"/>
      <div class="form-group">
        <h2>Добавление автора
        </h2>
        <div class="form-field">
          <label>Имя</label>
          <input name="first_name"/>
        </div>
        <div class="form-field">
          <label>Отчество</label>
          <input name="middle_name"/>
        </div>
        <div class="form-field">
          <label>Фамилия</label>
          <input name="last_name"/>
        </div>
        <div class="form-field">
          <label>Годы жизни</label>
          <input name="date_birth"/> &mdash;
          <input name="date_death"/>
        </div>
        <div class="form-field">
          <label>Официальный сайт</label>
          <input name="homepage"/>
        </div>
        <div class="form-field">
          <label>Страница в Википедии</label>
          <input name="wiki_url"/>
        </div>
        <div class="form-field">
          <label>Основной язык</label>
          <xsl:apply-templates select="author" mode="h-lang_code-select"/>
        </div>
        <div class="form-field">
          <label>Биография</label>
          <textarea name="bio"/>
        </div>
      </div>
      <div class="form-group">
        <h2>Фотография</h2>
        <div class="form-field">
          <input type="file" name="picture"></input>
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Сохранить информацию"/>
      </div>
    </form>
  </xsl:template>

</xsl:stylesheet>
