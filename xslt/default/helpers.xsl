<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>
	<xsl:output omit-xml-declaration="yes"/>
	<xsl:output indent="yes"/>

  <xsl:template match="*" mode="h-lang_code-select">
    <xsl:variable select="lang_id" name="lang_id"/>
		<select name="lang_code" class="h-lang_code-select">
			<xsl:for-each select="lang_codes/item">
				<option value="{@code}">
          <xsl:if test="($lang_id=@id) or (not($lang_id) and @code='ru')"><xsl:attribute name="selected"/></xsl:if>
          <xsl:value-of select="@title"/> (<xsl:value-of select="@code"/>)
				</option>
			</xsl:for-each>
		</select>
		<input name="lang_code" class="h-lang_code-input" value="{@lang_code}" />
		<script>$('.h-lang_code-select').change(function(){$(".h-lang_code-input").val($(this).val());});</script>
  </xsl:template>

  <xsl:template match="*" mode="h-rightholders-select">
		<select name="rightholder" class="h-rightholders-select">
      <xsl:variable name="id_rightholder" select="@id"/>
      <option value="0"/>
			<xsl:for-each select="rightholders/item">
				<option value="{@id}">
          <xsl:if test="@id_rightholder=$id_rightholder"><xsl:attribute name="selected"/></xsl:if>
          <xsl:value-of select="@title"/>
				</option>
			</xsl:for-each>
		</select>
  </xsl:template>

	<xsl:template name="h-role-select">
		<xsl:param name="object" select="book"/>
		<select name="role" class="role-select">
			<xsl:for-each select="$object/roles/item">
				<option value="{@id}">
					<xsl:value-of select="@title"/>
				</option>
			</xsl:for-each>
		</select>
	</xsl:template>

	<xsl:template name="h-relation-type-select">
		<xsl:param name="object" select="book"/>
		<select name="relation_type" class="relation_type-select">
			<xsl:for-each select="$object/relation_types/item">
				<option value="{@id}"><xsl:value-of select="@name"/></option>
			</xsl:for-each>
		</select>
	</xsl:template>

	<xsl:template name="h-this-amount">
		<xsl:param name="amount"/>
		<xsl:param name="words" select="''"/>
		<xsl:param name="do_not_print_1" select="''"/>
		<xsl:variable name="mod10" select="$amount mod 10"/>
		<xsl:variable name="f5t20" select="$amount>=5 and not($amount>20)"/>
    <xsl:if test="not($amount=1 and $do_not_print_1=1)">
      <xsl:value-of select="$amount"/><xsl:text>&nbsp;</xsl:text>
    </xsl:if>
    <xsl:variable name="text">
      <xsl:choose>
        <xsl:when test="not($f5t20) and $mod10=1">
          <xsl:value-of select="substring-before($words,' ')"/>
        </xsl:when>
        <xsl:when test="not($f5t20) and (not($mod10>=5) and $mod10>1)">
          <xsl:value-of select="substring-before(substring-after($words,' '),' ')"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="substring-after(substring-after($words,' '),' ')"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:value-of select="translate($text,'_',' ')"/>
	</xsl:template>

	<xsl:template name="h-abbr-time">
		<xsl:param name="time"/>
		<xsl:if test="$time">
			<abbr class="timeago" title="{$time}">
				<xsl:value-of select="$time"/>
			</abbr>
		</xsl:if>
	</xsl:template>

	<xsl:template match="*" mode="h-book-link">
    <a href="{@path}">
			<xsl:value-of select="@title"/>
		</a>
	</xsl:template>

	<xsl:template match="*" mode="h-file-link">
    <a href="{@path}">
      <xsl:value-of select="@filetypedesc"/>, <xsl:apply-templates select="." mode="h-file-size"/>
		</a>
	</xsl:template>

  <xsl:template match="*" mode="h-file-size">
    <xsl:variable select="@size div 1024" name="kb"/>
    <xsl:variable select="$kb div 1024" name="mb"/>
    <xsl:choose>
      <xsl:when test="$mb > 1">
        <xsl:value-of select="round(100*$mb) div 100"/> МБ
      </xsl:when>
      <xsl:when test="$kb > 1">
        <xsl:value-of select="round($kb)"/> КБ
      </xsl:when>
    	<xsl:otherwise></xsl:otherwise>
    </xsl:choose>
    <xsl:if test="$mb > 1">
    </xsl:if>
  </xsl:template>

	<xsl:template match="*" mode="h-book-cover">
    <xsl:param name="size" select="''"/>
    <xsl:attribute name="img_path">
      <xsl:choose>
        <xsl:when test="$size=''"><xsl:value-of select="@path"/></xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="concat(
            substring-before(@path,'default_'),
            $size,
            substring-after(@path,'default_')
            )"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:attribute>
    <a href="{@path}">
      <img src="{@img_path}?{@lastSave}" alt="[{@title}]" />
		</a>
	</xsl:template>

	<xsl:template name="h-author-name">
		<xsl:param name="author" select="author"/>
    <xsl:param name="mode" select="''"/>

    <xsl:choose>
      <xsl:when test="$mode='short'">
        <xsl:value-of select="substring($author/@first_name,1,1)"/>.
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$author/@first_name"/>
      </xsl:otherwise>
    </xsl:choose>

		<xsl:if test="$author/@middle_name!=''">
			<xsl:text> </xsl:text>
      <xsl:choose>
        <xsl:when test="$mode='short'">
          <xsl:value-of select="substring($author/@middle_name,1,1)"/>.
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$author/@middle_name"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>

		<xsl:if test="($author/@middle_name!='') or ($author/@first_name!='')">
			<xsl:text> </xsl:text>
    </xsl:if>

		<xsl:value-of select="$author/@last_name"/>
	</xsl:template>

	<xsl:template match="*" mode="h-author-link">
		<xsl:param name="mode" select="''"/>
		<a href="{@path}">
      <xsl:call-template name="h-author-name">
        <xsl:with-param select="." name="author"/>
        <xsl:with-param select="$mode" name="mode"/>
      </xsl:call-template>
		</a>
    <xsl:if test="position()!=last()">, </xsl:if>
	</xsl:template>

	<xsl:template match="*" mode="h-author-image">
		<a href="{@path}">
      <img src="{@picture}?{@lastSave}" alt="[{@name}]" />
		</a>
	</xsl:template>

	<xsl:template match="*" mode="h-user-link">
		<a href="{@path}">
			<xsl:value-of select="@nickname"/>
		</a>
	</xsl:template>

	<xsl:template match="*" mode="h-user-image">
		<a href="{@path}">
      <img src="{@picture}?{@lastSave}" alt="[{@nickname}]" title="{@nickname}"/>
		</a>
	</xsl:template>

	<xsl:template match="*" mode="h-genre-link">
		<a href="{@path}">
			<xsl:value-of select="@title"/>
		</a>
	</xsl:template>

	<xsl:template match="*" mode="h-serie-link">
		<a href="{@path}">
			<xsl:value-of select="@title"/>
		</a>
	</xsl:template>

	<xsl:template match="*" mode="h-magazine-link">
		<a href="{@path}">
			<xsl:value-of select="@title"/>
		</a>
	</xsl:template>

  <xsl:template match="*" mode="h-variant-link">
		<a href="{@path}">
			<xsl:value-of select="@title"/>
		</a>
  </xsl:template>

  <xsl:template match="*" mode="h-stylesheet">
    <xsl:variable name="path" select="concat(&prefix;,'static/default/css/',@path,'.css')"/>
    <link href="{$path}" media="screen" rel="stylesheet" type="text/css"/>
  </xsl:template>

  <xsl:template match="*" mode="h-javascript">
    <xsl:variable name="path" select="concat(&prefix;,'static/default/js/',@path,'.js')"/>
    <script src="{$path}" type="text/javascript"></script>
  </xsl:template>

  <xsl:template match="*" mode="h-action-names">
    <xsl:param name="object" select="''"/>
    <xsl:choose>
      <xsl:when test="$object='contribution'">
        <xsl:choose>
          <xsl:when test="@action='authors_add'">Добавил нового автора</xsl:when>
          <xsl:when test="@action='authors_edit'">Отредактировал автора</xsl:when>
          <xsl:when test="@action='books_add'">Добавил новую книгу</xsl:when>
          <xsl:when test="@action='books_add_cover'">Загрузил обложку книги</xsl:when>
          <xsl:when test="@action='books_edit_cover'">Обновил обложку книги</xsl:when>
          <xsl:when test="@action='books_add_file'">Загрузил файл книги</xsl:when>
          <xsl:when test="@action='books_edit_file'">Добавил новый файл книги</xsl:when>
          <xsl:when test="@action='books_edit'">Изменил книгу</xsl:when>
          <xsl:when test="@action='genres_edit'">Изменил описание жанра</xsl:when>
          <xsl:when test="@action='series_add'">Добавил новую серию</xsl:when>
          <xsl:when test="@action='ocr_add'">Внёс вклад в работу над книгой</xsl:when>
          <xsl:when test="@action='reviews_add'">Даписал рецензию на книгу</xsl:when>
          <xsl:when test="@action='series_add'">Добавил новую серию</xsl:when>
          <xsl:when test="@action='series_concat'">Склеил серии</xsl:when>
          <xsl:when test="@action='series_edit'">Отредактировал серию</xsl:when>
          <xsl:otherwise/>
        </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <xsl:choose>
          <xsl:when test="@action='authors_add' and not(authors/item)">добавил нового автора</xsl:when>
          <xsl:when test="@action='authors_add' and authors/item">добавил
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(authors/item)" name="amount"/>
              <xsl:with-param select="'нового_автора новых_авторов новых_авторов'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@action='authors_add_picture'">загрузил фото автора</xsl:when>
          <xsl:when test="@action='authors_add_duplicate'">указал дубликат автора</xsl:when>
          <xsl:when test="@action='authors_add_relation'">указал перевод автора</xsl:when>
          <xsl:when test="@action='authors_delete_duplicate'">удалил дубликат автора</xsl:when>
          <xsl:when test="@action='authors_delete_relation'">удалил перевод автора</xsl:when>
          <xsl:when test="@action='authors_edit'">отредактировал автора</xsl:when>
          <xsl:when test="@action='authors_edit_picture'">обновил фото автора</xsl:when>
          <xsl:when test="@action='authors_edit_duplicate'">указал дубликат автора</xsl:when>
          <xsl:when test="@action='authors_edit_relation'">указал перевод автора</xsl:when>
          <xsl:when test="@action='books_add' and not(books)">добавил новую книгу</xsl:when>
          <xsl:when test="@action='books_add' and books">добавил
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(books/item)" name="amount"/>
              <xsl:with-param select="'новую_книгу новые_книги новых_книг'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@action='books_add_cover'">добавил обложку книги</xsl:when>
          <xsl:when test="@action='books_add_file' and not(books)">загрузил файл книги</xsl:when>
          <xsl:when test="@action='books_add_file' and (books)">загрузил
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(books/item)" name="amount"/>
              <xsl:with-param select="'файл_книги файла_книг файлов_книг'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@action='books_add_author'">указал автора для книги</xsl:when>
          <xsl:when test="@action='books_add_genre'">указал жанр для книги</xsl:when>
          <xsl:when test="@action='books_add_serie'">добавил книгу в серию</xsl:when>
          <xsl:when test="@action='books_add_duplicate'">указал дубликат книги</xsl:when>
          <xsl:when test="@action='books_add_relation'">указал редакцию (перевод) книги</xsl:when>
          <xsl:when test="@action='books_delete_duplicate'">удалил дубликат книги</xsl:when>
          <xsl:when test="@action='books_delete_relation'">удалил редакцию (перевод) книги</xsl:when>
          <xsl:when test="@action='books_edit' and not(books)">изменил книгу</xsl:when>
          <xsl:when test="@action='books_edit' and books">изменил
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(books/item)" name="amount"/>
              <xsl:with-param select="'книгу книги книг'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@action='books_edit_authors'">изменил состав авторов книги</xsl:when>
          <xsl:when test="@action='books_edit_cover'">обновил обложку книги</xsl:when>
          <xsl:when test="@action='books_edit_genres'">изменил список жанров книги</xsl:when>
          <xsl:when test="@action='books_edit_series'">изменил список серий книги</xsl:when>
          <xsl:when test="@action='books_edit_file'">обновил файл для книги</xsl:when>
          <xsl:when test="@action='comments_add'">добавил комментарий</xsl:when>
          <xsl:when test="@action='genres_edit'">отредактировал жанр</xsl:when>
          <xsl:when test="@action='loved_add_author' and not(authors)">добавил в любимые автора</xsl:when>
          <xsl:when test="@action='loved_add_author' and authors">добавил в любимые
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(authors/item)" name="amount"/>
              <xsl:with-param select="'автора авторов авторов'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@action='loved_add_book' and not(books)">добавил в любимые книгу</xsl:when>
          <xsl:when test="@action='loved_add_book' and books">добавил в любимые
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(books/item)" name="amount"/>
              <xsl:with-param select="'книгу книги книг'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@action='loved_add_genre' and not(genres)">добавил в любимые жанр</xsl:when>
          <xsl:when test="@action='loved_add_genre' and genres">добавил в любимые
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(genres/item)" name="amount"/>
              <xsl:with-param select="'жанр жанра жанров'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@action='loved_add_serie' and not(series)">добавил в любимые серию</xsl:when>
          <xsl:when test="@action='loved_add_serie' and series">добавил в любимые
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(series/item)" name="amount"/>
              <xsl:with-param select="'серию серии серий'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@action='magazines_add'">добавил новый журнал</xsl:when>
          <xsl:when test="@action='magazines_add_cover'">загрузил обложку журнала</xsl:when>
          <xsl:when test="@action='magazines_edit'">отредактировал информацию о журнале</xsl:when>
          <xsl:when test="@action='magazines_edit_cover'">обновил обложку журнала</xsl:when>
          <xsl:when test="@action='ocr_add'">внёс вклад в работу над книгой</xsl:when>
          <xsl:when test="@action='posts_add'">написал пост</xsl:when>
          <xsl:when test="@action='reviews_add'">написал рецензию на книгу</xsl:when>
          <xsl:when test="@action='reviews_add_rate'">оценил книгу
            <xsl:if test="@mark"> на <xsl:value-of select="@mark"/></xsl:if>
          </xsl:when>
          <xsl:when test="@action='series_add' and not(series)">добавил новую серию</xsl:when>
          <xsl:when test="@action='series_add' and series">добавил
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(series/item)" name="amount"/>
              <xsl:with-param select="'новую_серию новые_серии новых_серий'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@action='series_concat'">склеил серии</xsl:when>
          <xsl:when test="@action='series_edit' and not(series)">отредактировал серию</xsl:when>
          <xsl:when test="@action='series_edit' and series">отредактировал
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(series/item)" name="amount"/>
              <xsl:with-param select="'серию серии серий'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:when test="@action='shelf_add_book' and not(books)">добавил книгу на полку</xsl:when>
          <xsl:when test="@action='shelf_add_book' and books">добавил
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(books/item)" name="amount"/>
              <xsl:with-param select="'книгу книги книг'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
            на полку
            <xsl:if test="@shelf_title"> «<xsl:value-of select="@shelf_title"/>»</xsl:if>
          </xsl:when>
          <xsl:when test="@action='users_add_friend' and not(users)">добавил в друзья</xsl:when>
          <xsl:when test="@action='users_add_friend' and users">добавил в друзья
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="count(users/item)" name="amount"/>
              <xsl:with-param select="'пользователя пользователей пользователей'" name="words"/>
              <xsl:with-param select="1" name="do_not_print_1"/>
            </xsl:call-template>
          </xsl:when>
          <xsl:otherwise/>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="*" mode="h-message">
    <div class="h-message"><xsl:value-of select="@html"/></div>
  </xsl:template>

  <xsl:template match="*" mode="h-statistics-period">
    <div class="h-statistics-period">
      <p class="h-statistics-period-variants">
        Статистика
        <xsl:text> </xsl:text><a href="{@current_month_path}">за текущий месяц</a>
        <xsl:text> </xsl:text><a href="{@last_month_path}">за прошедший месяц</a>
      </p>
      <p class="h-statistics-period-calendar">
        в период <label for="from">c</label><input id="from" name="from" value="{&page;/variables/@from}"/><label for="to">по</label><input id="to" name="to" value="{&page;/variables/@to}"/> <a href="#" class="m-statistics-list-period-show">показать</a>
      </p>
    </div>
  </xsl:template>

  <xsl:template match="*" mode="h-navigation">
    <xsl:variable name="prefix">h-navigation-<xsl:value-of select="@name"/></xsl:variable>
    <ul class="h-navigation {$prefix}">
      <xsl:apply-templates select="menu_items/item" mode="h-navigation-item"/>
    </ul>
  </xsl:template>

  <xsl:template match="*" mode="h-navigation-item">
    <xsl:variable name="z-img">
      <xsl:value-of select="&prefix;"/>static/default/img/0.gif
    </xsl:variable>
    <li>
      <xsl:attribute name="class">
        h-navigation-item
        <xsl:if test="menu_items and ../../@dropdown='by_hover'">by_hover</xsl:if>
      </xsl:attribute>
      <xsl:value-of select="../@dropdown"></xsl:value-of>
      <xsl:if test="@prev_icon_class">
        <div class="h-navigation-item-icon h-navigation-item-icon-prev">
          <a href="{@path}">
            <xsl:if test="menu_items"><xsl:attribute name="class">dropdown</xsl:attribute></xsl:if>
            <img src="{$z-img}" alt="[{@name}]" class="i-{@prev_icon_class}"/>
          </a>
        </div>
      </xsl:if>
      <a href="{@path}">
        <xsl:if test="menu_items"><xsl:attribute name="class">dropdown</xsl:attribute></xsl:if>
        <xsl:value-of select="@name"/>
      </a>
      <em class="h-navigation-item-additional">
        <xsl:if test="@additional!='0'">
          <xsl:value-of select="@additional"/>
        </xsl:if>
      </em>
      <xsl:if test="@next_icon_class">
        <div class="h-navigation-item-icon h-navigation-item-icon-next">
          <a href="{@path}">
            <xsl:if test="menu_items"><xsl:attribute name="class">dropdown</xsl:attribute></xsl:if>
            <img src="{$z-img}" alt="[{@name}]" class="i-{@next_icon_class}"/>
          </a>
        </div>
      </xsl:if>
      <xsl:if test="menu_items">
      <ul>
        <li class="top-item">
          <xsl:if test="@prev_icon_class">
            <div class="h-navigation-item-icon h-navigation-item-icon-prev">
              <a href="{@path}"><img src="{$z-img}" alt="[{@name}]" class="i-{@prev_icon_class}"/></a>
            </div>
          </xsl:if>
          <a href="{@path}"><xsl:value-of select="@name"/></a>
          <xsl:if test="@next_icon_class">
            <div class="h-navigation-item-icon h-navigation-item-icon-next">
              <a href="{@path}"><img src="{$z-img}" alt="[{@name}]" class="i-{@next_icon_class}"/></a>
            </div>
          </xsl:if>
        </li>
        <xsl:apply-templates select="menu_items/item" mode="h-navigation-subitem"/>
      </ul>
      </xsl:if>
    </li>
  </xsl:template>

  <xsl:template match="*" mode="h-navigation-subitem">
    <li class="h-navigation-subitem">
      <a href="{@path}">
        <em class="h-navigation-subitem-link"><xsl:value-of select="@name"/></em>
        <em class="h-navigation-subitem-additional">
          <xsl:if test="@additional and @additional!='0'">
            <xsl:value-of select="@additional"/>
          </xsl:if>
        </em>
      </a>
    </li>
  </xsl:template>

</xsl:stylesheet>
