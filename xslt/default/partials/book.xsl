<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="*" mode="p-book-list">
    <xsl:param name="mode"/>
    <xsl:param name="prefix"/>
    <xsl:param name="authors" select="authors"/>
    <xsl:param name="owner_id"/>
    <xsl:variable name="author" select="$authors/item[@id=current()/@author_id]"/>
    <xsl:variable name="fprefix">
      <xsl:choose>
        <xsl:when test="$prefix">-<xsl:value-of select="$prefix"/></xsl:when>
        <xsl:otherwise/>
      </xsl:choose>
    </xsl:variable>
    <li class="p-book-list{$fprefix}" id="book-{@id}">
      <xsl:variable select="$mode='shelves' or $mode='shelf'" name="is_shelf"></xsl:variable>
      <xsl:if test="$is_shelf and (&current_profile;/@id = $owner_id)">
        <div class="p-book-list{$fprefix}-del">
          <a href="#" class="del-from-shelf">x</a>
        </div>
      </xsl:if>
      <div class="p-book-list{$fprefix}-image">
        <xsl:apply-templates select="." mode="h-book-cover"/>
      </div>
      <div class="{$fprefix}p-book-list{$fprefix}-info">
        <div class="{$fprefix}p-book-list-info-title">
          <xsl:apply-templates select="." mode="h-book-link"/>
        </div>
        <xsl:if test="not($mode='author')">
          <div class="{$fprefix}p-book-list{$fprefix}-info-author">
            <xsl:apply-templates select="$author" mode="h-author-link"/>
          </div>
        </xsl:if>
      </div>
    </li>
  </xsl:template>

  <xsl:template name="p-book-event">
    <xsl:param name="book" select="book"/>
    <xsl:param name="author" select="author"/>
    <div class="p-book-event">
      <div class="p-book-event-image">
        <xsl:apply-templates select="$book" mode="h-book-cover"/>
      </div>
      <div class="p-book-event-name">
        <xsl:apply-templates select="$book" mode="h-book-link"/>
      </div>
      <div class="p-book-event-author">
        <xsl:apply-templates select="$author" mode="h-author-link"/>
      </div>
    </div>
  </xsl:template>

  <xsl:template match="module[@name='books' and @action='show']" mode="p-module">
    <input type="hidden" name="id" value="{book/@id}" />
    <div class="p-book-show-image">
      <img src="{book/@cover}" alt="Обложка книги «{book/@title}»"/>
    </div>
    <div class="p-book-show-info">

      <h1 class="p-book-show-title"><xsl:value-of select="book/@title"/></h1>

      <xsl:if test="book/@subtitle and 0">
        <h2 class="p-book-show-subtitle"><xsl:value-of select="book/@subtitle"/></h2>
      </xsl:if>

      <xsl:apply-templates select="book/authors" mode="p-book-show-authors"/>

      <xsl:if test="book/genres/item">
        <div class="p-book-show-genres">
          <xsl:apply-templates select="book/genres/item" mode="h-genre-link"/>
        </div>
      </xsl:if>

      <div class="p-book-show-details">
        <xsl:for-each select="book/authors/item[not(@role='1')]">
          <em>
            <xsl:if test="position()=1">
              <xsl:attribute name="class">capitalized</xsl:attribute>
            </xsl:if>
            <xsl:value-of select="@roleName"/>
          </em>
          <xsl:text> &mdash; </xsl:text>
          <xsl:apply-templates select="." mode="h-author-link"/>
          <xsl:if test="position()!=last()">, </xsl:if>
        </xsl:for-each>

        <xsl:if test="book/rightsholder/@title">
          <p>Издатель &mdash; &laquo;<xsl:value-of select="book/rightsholder/@title"/>&raquo;</p>
        </xsl:if>

        <xsl:if test="book/@isbn!=''">
          <p>ISBN <xsl:value-of select="book/@isbn"/></p>
        </xsl:if>
      </div>

      <xsl:apply-templates select="." mode="p-book-show-download"/>

      <xsl:if test="book/annotation/@html != ''">
        <div class="p-book-show-annotation">
          <xsl:if test="book/annotation/@short">
            <div class="p-book-show-annotation-short">
              <xsl:value-of select="book/annotation/@short" disable-output-escaping="yes"/>
              <a href="" class="show-annotation">Читать дальше</a>
            </div>
          </xsl:if>
          <div class="p-book-show-annotation-full">
            <xsl:value-of select="book/annotation/@html" disable-output-escaping="yes"/>
          </div>
        </div>
      </xsl:if>

      <div class="p-book-show-loved add-loved-book">
        <a href="#">Любимая книга</a>
        <div class="p-book-show-loved-image">
          <a href="#">
            <img class="i-love" src="{&prefix;}static/default/img/0.gif" alt="[Любимая]"/>
          </a>
        </div>
        <a href="#">
          <em class="p-book-show-loved-count loved-count">
            <xsl:if test="book/@loved_count!='0'"><xsl:value-of select="book/@loved_count"/></xsl:if>
          </em>
        </a>
      </div>

      <div class="p-book-show-reviews-count">
        <div class="p-book-show-reviews-count-image">
          <a href=""><img class="i-review" src="{&prefix;}static/default/img/0.gif" alt="[Рецензии]" /></a>
        </div>
        <a href="">
          <xsl:call-template name="h-this-amount">
            <xsl:with-param select="25" name="amount"/>
            <xsl:with-param select="'рецензия рецензии рецензий'" name="words"/>
          </xsl:call-template>
        </a>
      </div>


      <xsl:if test="book/magazine">
        <h3>
          <a href="{book/magazine/@path}">
            Все выпуски журнала «<xsl:value-of select="book/magazine/@title"/>»
          </a>
        </h3>
      </xsl:if>

			<xsl:if test="&access;/statistics_view">
				<a href="{book/@path_admin}">Скачали
					<xsl:call-template name="h-this-amount">
						<xsl:with-param select="book/@download_count" name="amount"/>
						<xsl:with-param select="'раз раза раз'" name="words"/>
				</xsl:call-template></a>
			</xsl:if>

			<xsl:if test="&current_profile;/@id">

				<div class="p-book-show-info-subscribe">
					<p/>
					<a href="#" class="subscribe-book">Подписаться на рецензии</a>
				</div>

				<xsl:if test="&access;/books_edit">
					<p><a href="{&page;/@current_url}edit">Редактировать книгу</a></p>
					<p><a href="{&page;/@current_url}ocr">Сканирование и вычитка</a></p>
				</xsl:if>

				<xsl:if test="&access;/logs_view">
					<a href="{&page;/@current_url}log">Лог изменений книги</a>
				</xsl:if>

			</xsl:if>

    </div>
  </xsl:template>

  <xsl:template match="*" mode="p-book-show-download">
    <div class="p-book-show-download">
      <ul>
        <li class="p-book-show-download-li">
          <div class="p-book-show-download-image">
            <a href=""><img class="i-download" src="{&prefix;}static/default/img/0.gif" alt="[Скачать]"/></a>
          </div>
          <ul class="h-navigation h-navigation-book-download">
            <li class="h-navigation-item">
              <a href="" class="p-book-show-download-link dropdown">Скачать</a>
              <ul>
                <li class="top-item">
                  <a class="p-book-show-download-link" href="">Скачать</a>
                </li>
                <xsl:apply-templates select="book/files/item" mode="p-book-show-download-file"/>
              </ul>
            </li>
          </ul>
        </li>
        <li class="p-book-show-download-li">
          <div class="p-book-show-download-image">
            <a href=""><img class="i-shelf" src="{&prefix;}static/default/img/0.gif" alt="[Забрать]"/></a>
          </div>
          <ul class="h-navigation h-navigation-book-download">
            <li class="h-navigation-item">
              <a href="" class="p-book-show-download-link dropdown">Забрать себе</a>
              <ul class="p-book-show-download-shelves">
                <li class="top-item">
                  <a class="p-book-show-download-link" href="">Забрать себе</a>
                </li>
                <li class="h-navigation-subitem">
                  <a href="" class="h-navigation-subitem-link">
                    <input checked="" class="p-book-show-download-link-radio" type="radio" name="book-shelf" value="1"/>
                    <em class="h-navigation-subitem-link">Я читаю</em>
                  </a>
                </li>
                <li class="h-navigation-subitem">
                  <a href="" class="h-navigation-subitem-link">
                    <input class="p-book-show-download-link-radio" type="radio" name="book-shelf" value="3"/>
                    <em class="h-navigation-subitem-link">Я хочу прочитать</em>
                  </a>
                </li>
                <li class="h-navigation-subitem">
                  <a href="" class="h-navigation-subitem-link">
                    <input class="p-book-show-download-link-radio" type="radio" name="book-shelf" value="2"/>
                    <em class="h-navigation-subitem-link">Я прочитал</em>
                  </a>
                </li>
                <li class="h-navigation-subitem">
                  <a href="" class="p-book-show-download-remove">
                    <em class="h-navigation-subitem-link">Убрать совсем</em>
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </li>
        <xsl:if test="book/files/item[@filetypedesc='fb2']">
          <li class="p-book-show-download-li">
            <div class="p-book-show-download-image">
              <a href="{book/@path_read}">
                <img class="i-read" src="{&prefix;}static/default/img/0.gif" alt="[Читать]"/>
              </a>
            </div>
            <a href="{book/@path_read}" class="p-book-show-download-link">Читать</a>
          </li>
        </xsl:if>
        <div style="clear:both"></div>
      </ul>
      <xsl:if test="&current_profile;/download_limit">
        <xsl:variable select="&current_profile;/download_limit/@available" name="available"/>
        <xsl:choose>
          <xsl:when test="$available > 0 and not($available > 3)">
            Вы можете скачать ещё
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="$available" name="amount"/>
              <xsl:with-param select="'книгу книги книг'" name="words"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="$available=0">
            Сегодня вы больше не можете скачивать книги. Попробуйте завтра.
          </xsl:when>
          <xsl:otherwise/>
        </xsl:choose>
      </xsl:if>
    </div>
  </xsl:template>

  <xsl:template match="*" mode="p-book-show-download-file">
    <li class="h-navigation-subitem p-book-show-download-file">
      <a href="{@path}">
        <em class="h-navigation-subitem-link"><xsl:value-of select="@filetypedesc"/></em>
        <em class="h-navigation-subitem-additional"><xsl:apply-templates select="." mode="h-file-size"/></em>
      </a>
    </li>
  </xsl:template>

  <xsl:template match="*" mode="p-book-show-authors">
    <div class="p-book-show-authors">
      <xsl:choose>
        <xsl:when test="count(item[@role='1'])>1">
          <xsl:apply-templates select="item[@role='1' and not(position()>2)]" mode="h-author-link">
            <xsl:with-param name="mode" select="'short'"/>
          </xsl:apply-templates>
        </xsl:when>
        <xsl:otherwise>
          <xsl:apply-templates select="item[@role='1' and not(position()>2)]" mode="h-author-link"/>
        </xsl:otherwise>
      </xsl:choose>
      <xsl:if test="count(item[@role='1'])>2">
        <em class="show-other">
          <xsl:text> </xsl:text>
          <a href="" >и другие</a>
        </em>
        <div class="p-book-show-authors-other">,
          <xsl:apply-templates select="item[@role='1' and position()>2]" mode="h-author-link">
            <xsl:with-param name="mode" select="'short'"/>
          </xsl:apply-templates>
        </div>
      </xsl:if>
    </div>
  </xsl:template>

  <xsl:template match="module[@name='books' and @action='edit']" mode="p-module">
    <xsl:variable select="book" name="book"/>
    <form method="post" enctype="multipart/form-data" action="{&prefix;}book/{book/@id}/edit">
      <input type="hidden" name="writemodule" value="BookWriteModule" />
      <input type="hidden" name="id" value="{book/@id}" />
      <div class="form-group">
        <h2>Редактирование книги «<xsl:value-of select="book/@title"/>»
        </h2>
        <xsl:if test="&access;/books_edit_quality">
          <div class="form-field">
            <label>Качество</label>
            <select name="quality">
              <xsl:for-each select="book/qualities/item">
                <option value="{@id}">
                  <xsl:if test="$book/@quality = current()/@id"><xsl:attribute name="selected"/></xsl:if>
                  <xsl:value-of select="@title" />
                </option>
              </xsl:for-each>
            </select>
          </div>
        </xsl:if>
        <div class="form-field">
          <label>Название</label>
          <input name="title" value="{book/@title}" />
        </div>
        <div class="form-field">
          <label>Доп. инфо</label>
          <input name="subtitle" value="{book/@subtitle}" />
        </div>
        <div class="form-field">
          <label>ISBN</label>
          <input name="isbn" value="{book/@isbn}" />
        </div>
        <xsl:if test="book/@n">
          <div class="form-field">
            <label>Номер выпуска</label>
            <input type="text" name="n" value="{book/@n}"/>
          </div>
        </xsl:if>
        <div class="form-field">
          <label>Год издания</label>
          <input name="year" value="{book/@year}" />
        </div>
        <div class="form-field">
          <label>Язык книги</label>
          <xsl:apply-templates select="book" mode="h-lang_code-select"/>
        </div>
        <div class="form-field">
          <label>Правообладатель</label>
          <xsl:apply-templates select="book" mode="h-rightholders-select"/>
        </div>
        <div class="form-field">
          <label>Анотация</label>
          <textarea name="annotation">
            <xsl:value-of select="book/annotation/@full" />
          </textarea>
        </div>
      </div>
      <div class="form-group">
        <h2>Файлы</h2>
        <xsl:apply-templates select="book/files/item" mode="p-book-edit-file"/>
        <div class="form-field">
          <label>Добавить файл</label>
          <input type="file" name="file"></input>
        </div>
      </div>
      <div class="form-group">
        <h2>Обложка</h2>
        <img src="{book/@cover}?{book/@lastSave}" alt="[Обложка]" />
        <div class="form-field">
          <input type="file" name="cover"></input>
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Сохранить информацию"/>
      </div>
    </form>
    <div class="form-group">
      <h2>Авторы</h2>
      <div class="p-book-edit-authors">
        <xsl:call-template name="p-book-edit-author"/>
        <xsl:for-each select="book/authors/item">
          <xsl:call-template name="p-book-edit-author"/>
        </xsl:for-each>
        <xsl:call-template name="p-book-edit-author-new"/>
      </div>
    </div>
    <div class="form-group">
      <h2>Жанры</h2>
      <div class="p-book-edit-genres">
        <xsl:call-template name="p-book-edit-genre"/>
        <xsl:for-each select="book/genres/item">
          <xsl:call-template name="p-book-edit-genre"/>
        </xsl:for-each>
        <xsl:call-template name="p-book-edit-genre-new"/>
      </div>
    </div>
    <div class="form-group">
      <h2>Серии</h2>
      <div class="p-book-edit-series">
        <xsl:call-template name="p-book-edit-serie"/>
        <xsl:for-each select="book/series/item">
          <xsl:call-template name="p-book-edit-serie"/>
        </xsl:for-each>
        <xsl:call-template name="p-book-edit-serie-new"/>
      </div>
    </div>
    <div class="form-group">
      <h2>Переводы, редакции, дубликаты</h2>
      <div class="p-book-edit-relations">
        <xsl:call-template name="p-book-edit-relation"/>
        <xsl:variable select="book/relations/books" name="books"/>
        <xsl:for-each select="book/relations/item">
          <xsl:call-template name="p-book-edit-relation">
            <xsl:with-param name="books" select="$books"/>
          </xsl:call-template>
        </xsl:for-each>
        <xsl:call-template name="p-book-edit-relation-new"/>
      </div>
    </div>
  </xsl:template>

  <xsl:template match="*" mode="p-book-edit-file">
    <div class="p-book-edit-file">
      <input type="radio" value="{@id_file}" name="book_file" id="book_file_{@id_file}">
        <xsl:if test="@is_default = 1"><xsl:attribute name="checked"/></xsl:if>
      </input>
      <xsl:value-of select="@filetypedesc"/> (<xsl:value-of select="@size"/> байт)
    </div>
  </xsl:template>

  <xsl:template name="p-book-edit-author">
    <xsl:variable name="class">
      <xsl:text>p-book-edit-author</xsl:text>
      <xsl:choose>
        <xsl:when test="@id"> author-<xsl:value-of select="@id"/></xsl:when>
        <xsl:otherwise> hidden</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <div class="{$class}">
      <a href="#" class="p-book-edit-author-delete">Удалить</a>
      <input type="hidden" name="id_author" value="{@id}"/>
      <div class="p-book-edit-author-role">
        <xsl:value-of select="@roleName"/>
      </div>
      <xsl:text>:</xsl:text>
			<div class="p-book-edit-author-name">
				<a href="{@path}">
					<xsl:call-template name="h-author-name">
						<xsl:with-param name="author" select="."/>
					</xsl:call-template>
				</a>
			</div>
    </div>
  </xsl:template>

  <xsl:template name="p-book-edit-genre">
    <xsl:variable name="class">
      <xsl:text>p-book-edit-genre</xsl:text>
      <xsl:choose>
        <xsl:when test="@id"> genre-<xsl:value-of select="@id"/></xsl:when>
        <xsl:otherwise> hidden</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <div class="{$class}">
      <a href="#" class="p-book-edit-genre-delete">Удалить</a>
      <input type="hidden" name="id_genre" value="{@id}"/>
      <div class="p-book-edit-genre-title">
				<a href="{@path}">
					<xsl:value-of select="@title"/>
				</a>
      </div>
    </div>
  </xsl:template>

  <xsl:template name="p-book-edit-serie">
    <xsl:variable name="class">
      <xsl:text>p-book-edit-serie</xsl:text>
      <xsl:choose>
        <xsl:when test="@id"> serie-<xsl:value-of select="@id"/></xsl:when>
        <xsl:otherwise> hidden</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <div class="{$class}">
      <a href="#" class="p-book-edit-serie-delete">Удалить</a>
      <input type="hidden" name="id_serie" value="{@id}"/>
      <div class="p-book-edit-serie-title">
        <xsl:value-of select="@title"/>
      </div>
    </div>
  </xsl:template>

  <xsl:template name="p-book-edit-relation">
    <xsl:param name="books" select="books"/>
    <xsl:param name="book_id" select="@id2"/>
    <xsl:param name="book" select="$books/item[@id=$book_id]"/>
    <xsl:variable name="class">
      <xsl:text>p-book-edit-relation</xsl:text>
      <xsl:choose>
        <xsl:when test="@id2"> relation-<xsl:value-of select="@id2"/></xsl:when>
        <xsl:otherwise> hidden</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <div class="{$class}">
      <a href="#" class="p-book-edit-relation-delete">Удалить</a>
      <input type="hidden" name="id_relation" value="{$book/@id}"/>
      <div class="p-book-edit-relation-type">
        <xsl:value-of select="@relation_type_name"/>
      </div>
      <xsl:text>:</xsl:text>
      <div class="p-book-edit-relation-title">
        <xsl:apply-templates select="$book" mode="h-book-link"/>
      </div>
    </div>
  </xsl:template>

  <xsl:template name="p-book-edit-author-new">
    <div class="p-book-edit-author-new">
      <xsl:call-template name="h-role-select">
        <xsl:with-param name="object" select="book"/>
      </xsl:call-template>
      <input name="id_author" type="text" class="p-book-edit-author-new-id" />
      <a href="#" class="p-book-edit-author-new-submit">Добавить</a>
    </div>
  </xsl:template>

  <xsl:template name="p-book-edit-relation-new">
    <div class="p-book-edit-relation-new">
      <xsl:call-template name="h-relation-type-select">
        <xsl:with-param name="object" select="book"/>
      </xsl:call-template>
      <input name="book_id" type="text" class="p-book-edit-relation-new-id" />
      <a href="#" class="p-book-edit-relation-new-submit">Добавить</a>
    </div>
  </xsl:template>

  <xsl:template name="p-book-edit-genre-new">
    <div class="p-book-edit-genre-new">
      <input name="id_genre" type="text" class="p-book-edit-genre-new-id" />
      <a href="#" class="p-book-edit-genre-new-submit">Добавить</a>
    </div>
  </xsl:template>

  <xsl:template name="p-book-edit-serie-new">
    <div class="p-book-edit-serie-new">
      <input name="id_serie" type="text" class="p-book-edit-serie-new-id" />
      <a href="#" class="p-book-edit-serie-new-submit">Добавить</a>
    </div>
  </xsl:template>

  <xsl:template match="module[@name='books' and @action='new']" mode="p-module">
    <form method="post" enctype="multipart/form-data" action="{&prefix;}book/new">
      <input type="hidden" name="writemodule" value="BookWriteModule" />
      <input type="hidden" name="m" value="{&page;/variables/@m}" />
      <input type="hidden" name="author_id" value="{book/author/@id}" />
      <div class="form-group">
        <h2>Добавление книги
          <xsl:if test="book/author">
            автора «<xsl:apply-templates select="book/author" mode="h-author-link"/>»
          </xsl:if>
        </h2>
        <div class="form-field">
          <label>Название</label>
          <input name="title" value="{&page;/variables/@title}"/>
        </div>
        <div class="form-field">
          <label>Доп. инфо</label>
          <input name="subtitle" value="{&page;/variables/@subtitle}"/>
        </div>
        <div class="form-field">
          <label>ISBN</label>
          <input name="isbn"/>
        </div>
        <div class="form-field">
          <label>Год издания</label>
          <input name="year" value="{&page;/variables/@year}" />
        </div>
        <xsl:if test="&page;/variables/@m">
          <xsl:choose>
            <xsl:when test="&page;/variables/@n">
              <input type="hidden" name="n" value="{&page;/variables/@n}"/>
            </xsl:when>
            <xsl:otherwise>
              <div class="form-field">
                <label>Номер выпуска</label>
                <input type="text" name="n"/>
              </div>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:if>
        <div class="form-field">
          <label>Язык книги</label>
          <xsl:apply-templates select="book" mode="h-lang_code-select"/>
        </div>
        <div class="form-field">
          <label>Правообладатель</label>
          <xsl:apply-templates select="book" mode="h-rightholders-select"/>
        </div>
        <div class="form-field">
          <label>Анотация</label>
          <textarea name="annotation">
            <xsl:value-of select="book/@annotation" />
          </textarea>
        </div>
      </div>
      <div class="form-group">
        <h2>Обложка</h2>
        <div class="form-field">
          <input type="file" name="cover"></input>
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Сохранить информацию"/>
      </div>
    </form>
  </xsl:template>

  <xsl:template match="module[@name='books' and @action='show' and @mode='read']" mode="p-module">
    <xsl:value-of select="book/@html" disable-output-escaping="yes"/>
  </xsl:template>
</xsl:stylesheet>
