<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

  <xsl:template match="module[@name='users' and @action='show' and not(@mode)]" mode="p-module">
    <xsl:variable name="profile" select="profile" />
    <input type="hidden" name="id" value="{$profile/@id}" />
    <div class="p-user-show-image">
      <img src="{$profile/@picture}?{$profile/@lastSave}" alt="[Image]" />
    </div>
    <div class="p-user-show-text">
      <h1><xsl:value-of select="$profile/@nickname"/></h1>

      <div class="p-user-show-text-role">
        <xsl:value-of select="$profile/@rolename"/>
      </div>

      <xsl:if test="&access;/statistics_view">
				<div class="p-user-download-limit">
          <a href="{$profile/@path_stat}">
            Всего скачал
            <xsl:call-template name="h-this-amount">
              <xsl:with-param select="$profile/@download_count" name="amount"/>
              <xsl:with-param select="'книгу книги книг'" name="words"/>
            </xsl:call-template>.
          </a>
				</div>
      </xsl:if>

      <xsl:if test="$profile/@id=&current_profile;/@id or &access;/statistics_view">
        <div class="p-user-download-limit">
          Сегодня вы скачали
          <xsl:call-template name="h-this-amount">
            <xsl:with-param select="download_limit/@count" name="amount"/>
            <xsl:with-param select="'книгу книги книг'" name="words"/>
          </xsl:call-template>.
          Вы можете скачать ещё  <xsl:value-of select="download_limit/@available"/>.
        </div>
      </xsl:if>

			<xsl:if test="
				($profile/@id = &current_profile;/@id) or
				(&access;/users_edit and (&access;/users_edit/@max_role >= $profile/@role))
				">
				<div class="p-user-show-text-edit">
					<a href="{$profile/@path_edit}">Редактировать профиль</a>
					<a href="{$profile/@path_edit_notifications}">Настройки рассылки</a>
				</div>
			</xsl:if>

			<xsl:if test="&access;/users_edit and (&access;/users_edit/@max_role >= $profile/@role)">
				<div class="p-user-show-text-vandal">
					<a class="make-vandal" href="#">Сделать
						<xsl:choose>
							<xsl:when test="$profile/@role='20'">читателем</xsl:when>
							<xsl:otherwise>вандалом</xsl:otherwise>
						</xsl:choose>
					</a>
				</div>
			</xsl:if>

			<xsl:if test="not ($profile/@id = &current_profile;/@id)">
				<p><a href="{$profile/@path_message}">Написать сообщение</a></p>
				<xsl:if test="&access;/users_add_friends">
					<div id="friending" style="display:none"/>
					<script>profileModule_checkFriend(<xsl:value-of select="$profile/@id"/>,'friending');</script>
				</xsl:if>
			</xsl:if>

      <p><a href="{&prefix;}user/{$profile/@id}/books">Полки</a></p>
      <p><a href="{&prefix;}user/{$profile/@id}/contribution">Вклад</a></p>

      <xsl:choose>
        <xsl:when test="$profile/@id = &current_profile;/@id">
          <p><a href="{&prefix;}me/wall">Стена</a></p>
        </xsl:when>
        <xsl:otherwise>
          <p><a href="{&prefix;}user/{$profile/@id}/wall">Стена</a></p>
        </xsl:otherwise>
      </xsl:choose>

      <p>
        <xsl:text>Живет в городе</xsl:text>
        <b><xsl:value-of select="$profile/@city" disable-output-escaping="yes"/></b>
      </p>
      <p>
        <xsl:text>День рождения </xsl:text>
        <b><xsl:value-of select="$profile/@bdays" disable-output-escaping="yes"/></b>
      </p>
      <xsl:if test="$profile/@about != ''">
        <p>
          <xsl:text>Пара слов о себе:</xsl:text>
          <b><xsl:value-of select="$profile/@about" disable-output-escaping="yes"/></b>
        </p>
      </xsl:if>
      <xsl:if test="$profile/@quote != ''">
        <p>
          <xsl:text>Любимые цитаты:</xsl:text>
          <b><xsl:value-of select="$profile/@quote" disable-output-escaping="yes"/></b>
        </p>
      </xsl:if>

			<xsl:if test="&access;/logs_view">
        <a href="{&page;/@current_url}log">Изменения, сделанные пользователем</a>
      </xsl:if>

    </div>
  </xsl:template>


	<xsl:template match="module[@name='users' and @action='show' and @mode='subscriptions']" mode="p-module">
    <xsl:choose>
      <xsl:when test="subscriptions/@active">
        <h2>Подписка активна</h2>
        <p>до <xsl:value-of select="subscriptions/@end"/></p>
      </xsl:when>
    	<xsl:otherwise>Подписка закончилась</xsl:otherwise>
    </xsl:choose>
    <xsl:apply-templates select="subscriptions/item" mode="p-user-subscription-activate"/>
	</xsl:template>

  <xsl:template match="*" mode="p-user-subscription-activate">
    <a href="#" id="use_subscription">Продлить подписку на
      <xsl:call-template name="h-this-amount">
        <xsl:with-param select="@days" name="amount"/>
        <xsl:with-param select="'день дня дней'" name="words"/>
      </xsl:call-template>
    </a>
  </xsl:template>

  <xsl:template match="*" mode="p-user-list">
    <li class="p-user-list">
      <xsl:apply-templates select="." mode="h-user-image"/>
      <p class="p-user-list-name">
        <xsl:apply-templates select="." mode="h-user-link"/>
      </p>
    </li>
  </xsl:template>

  <xsl:template match="*" mode="p-user-search">
    <tr class="p-user-search">
			<td class="p-user-search-id"><xsl:value-of select="@id"/></td>
			<td><xsl:apply-templates select="." mode="h-user-link"/></td>
			<td><xsl:value-of select="@email"/></td>
			<td><xsl:value-of select="@role"/></td>
      <td class="p-user-search-last">
        <xsl:value-of select="@regTime"/>
        <em>ip: <xsl:value-of select="@regIp"/></em>
      </td>
      <td class="p-user-search-last">
        <xsl:value-of select="@lastTime"/>
        <em>ip: <xsl:value-of select="@lastIp"/></em>
      </td>
			<td><xsl:value-of select="@subscriptionEnd"/></td>
    </tr>
  </xsl:template>

  <xsl:template match="module[@name='users' and @action='edit']" mode="p-module">
    <xsl:variable name="profile" select="profile"/>
    <form method="post" enctype="multipart/form-data" action="{&prefix;}user/{$profile/@id}">
      <input type="hidden" name="writemodule" value="ProfileWriteModule" />
      <input type="hidden" name="id" value="{$profile/@id}" />
      <div class="form-group">
        <h2>Информация</h2>
        <div class="form-field">
          <label>Ник</label>
          <b><xsl:value-of select="$profile/@nickname"></xsl:value-of></b>
          <xsl:if test="$profile/@change_nickname=1">
            <p class="p-user-edit-change-nickname"><a href="#">изменить</a></p>
          </xsl:if>
          <div class="p-user-edit-change-nickname-div" style="display:none">
            <input name="nickname" value="{$profile/@nickname}"/>
            <em class="p-user-edit-is-unique"/>
            <p class="form-help">Ник можно менять только один раз, будьте аккуратны</p>
          </div>
        </div>
        <div class="form-field">
          <label>Почта</label>
          <b><xsl:value-of select="$profile/@email"></xsl:value-of></b>
        </div>
				<xsl:if test="not($profile/@id=&current_profile;/@id)">
					<div class="form-field">
						<label>Роль</label>
						<select name="role">
							<xsl:for-each select="roles/item">
								<option value="{@id}">
									<xsl:if test="$profile/@role=current()/@id"><xsl:attribute name="selected"/></xsl:if>
									<xsl:value-of select="@title" />
								</option>
							</xsl:for-each>
						</select>
					</div>
				</xsl:if>
        <div class="form-field">
          <label>Дата рождения</label>
          <input name="bday" value="{$profile/@bday}" />
        </div>
        <div class="form-field">
          <label>Аватар</label>
          <input type="file" name="picture"></input>
        </div>
        <xsl:call-template name="profile_edit_cityLoader">
          <xsl:with-param name="current_city" select="$profile/@city_id" />
        </xsl:call-template>
        <div class="form-field">
          <label for="">Пару слов о себе</label>
          <textarea name="about">
            <xsl:value-of select="$profile/@about" disable-output-escaping="yes" />
          </textarea>
        </div>
        <div class="form-field">
          <label for="">Мои любимые цитаты</label>
          <textarea name="quote">
            <xsl:value-of select="$profile/@quote" disable-output-escaping="yes" />
          </textarea>
        </div>
      </div>
      <div class="form-group">
        <h2>Контакты</h2>
        <div class="form-field">
          <label for="">Facebook</label>
          <input name="link_fb" value="{$profile/@link_fb}"></input>
        </div>
        <div class="form-field">
          <label for="">Livejournal</label>
          <input name="link_lj" value="{$profile/@link_lj}"></input>
        </div>
        <div class="form-field">
          <label for="">Vkontakte</label>
          <input name="link_vk" value="{$profile/@link_vk}"></input>
        </div>
        <div class="form-field">
          <label for="">Twitter</label>
          <input name="link_tw" value="{$profile/@link_tw}"></input>
        </div>
      </div>
      <div class="form-control">
        <input type="submit" value="Сохранить информацию"/>
      </div>
    </form>
  </xsl:template>

  <xsl:template match="module[@name='users' and @action='edit' and @mode='notifications']" mode="p-module">
    <h2>Настройка уведомлений</h2>
    <form method="post" action="">
      <input type="hidden" name="writemodule" value="NotifyWriteModule"/>
      <input type="hidden" name="id" value="{user/@id}" />
      <div class="form-group">
        <table class="p-user-edit-notify_rules">
          <thead><tr><th>Уведомлять</th><th>по email</th><th>сообщением</th></tr></thead>
          <tbody>
            <xsl:apply-templates select="notify_rules/*" mode="p-user-edit-notify_rule"/>
          </tbody>
        </table>
      </div>
      <div class="form-control">
        <input type="submit" value="Сохранить информацию"/>
      </div>
    </form>
  </xsl:template>

  <xsl:template match="*" mode="p-user-edit-notify_rule">
    <tr>
      <td>
        <xsl:choose>
        	<xsl:when test="name()='event_comment'">о комментариях к моим записям</xsl:when>
        	<xsl:when test="name()='comment_answer'">об ответах на мои комментарии</xsl:when>
        	<xsl:when test="name()='new_message'">о новых личных сообщениях</xsl:when>
        	<xsl:when test="name()='new_friend'">о новых поклонниках</xsl:when>
        	<xsl:when test="name()='whats_new'">о событиях</xsl:when>
        	<xsl:when test="name()='global_objects_comments'">о комментариях к отслеживаемым книгам и авторам</xsl:when>
        	<xsl:when test="name()='global_new_reviews'">о рецензиях на отслеживаемые книги</xsl:when>
        	<xsl:when test="name()='global_new_genres'">о новых книгах в отслеживаемых жанрах</xsl:when>
        	<xsl:when test="name()='global_new_authors'">о новых книгах отслеживаемых авторов</xsl:when>
        	<xsl:otherwise></xsl:otherwise>
        </xsl:choose>
      </td>
      <td class="p-user-edit-notify_rules-checkbox">
        <input type="checkbox">
          <xsl:attribute name="name"><xsl:value-of select="name()"/>[email]</xsl:attribute>
          <xsl:if test="email/@cant_be_changed='1'"><xsl:attribute name="disabled"/></xsl:if>
          <xsl:if test="email/@enabled='1'"><xsl:attribute name="checked"/></xsl:if>
        </input>
      </td>
      <td class="p-user-edit-notify_rules-checkbox">
        <input type="checkbox">
          <xsl:attribute name="name"><xsl:value-of select="name()"/>[notify]</xsl:attribute>
          <xsl:if test="notify/@cant_be_changed='1'"><xsl:attribute name="disabled"/></xsl:if>
          <xsl:if test="notify/@enabled='1'"><xsl:attribute name="checked"/></xsl:if>
        </input>
      </td>
    </tr>
  </xsl:template>

  <xsl:template name="profile_edit_cityLoader">
    <xsl:param name="current_city"></xsl:param>
    <div class="form-field">
      <label>Страна:</label>
      <div id="counry_div">загружаем...</div>
    </div>
    <div class="form-field">
      <label>Город:</label>
      <div id="city_div">загружаем...</div>
    </div>
    <script>
      profileModule_cityInit('counry_div','city_div','<xsl:value-of select="$current_city"/>');
    </script>
  </xsl:template>

</xsl:stylesheet>
