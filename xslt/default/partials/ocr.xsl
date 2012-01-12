<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

	<xsl:template match="*" mode="p-ocr-list">
    <xsl:param name="users" select="users"/>
    <xsl:param name="authors" select="authors"/>
		<li class="p-ocr-list">
			<h3 class="p-ocr-list-book-title"><xsl:apply-templates select="." mode="h-book-link"/></h3>
			<p class="p-ocr-list-author-name">
				<xsl:apply-templates select="$authors/item[@id=current()/@author_id]" mode="h-author-link"/>
			</p>
			<p class="p-ocr-list-status">
				<xsl:apply-templates select="statuses/item" mode="p-ocr-list-status">
					<xsl:with-param select="$users" name="users"/>
				</xsl:apply-templates>
				<xsl:choose>
					<xsl:when test="statuses/@max_status_name='make'">, почти готова</xsl:when>
					<xsl:when test="statuses/@max_status_name='read'">, надо сверстать</xsl:when>
					<xsl:when test="statuses/@max_status_name='recognize'">, надо вычитать</xsl:when>
					<xsl:when test="statuses/@max_status_name='scan'">, надо распознать</xsl:when>
					<xsl:when test="statuses/@max_status_name='get'">, надо отсканировать</xsl:when>
					<xsl:otherwise>надо найти бумажный вариант</xsl:otherwise>
				</xsl:choose>
			</p>
		</li>
	</xsl:template>

	<xsl:template match="*" mode="p-ocr-list-status">
		<xsl:param select="''" name="users"/>
		<em>
			<xsl:attribute name="title">
				<xsl:for-each select="users/item">
					<xsl:value-of select="$users/item[@id=current()/@id_user]/@nickname"/>
					<xsl:if test="not(position()=last())">, </xsl:if>
				</xsl:for-each>
			</xsl:attribute>
			<xsl:choose>
				<xsl:when test="@status_name='make' and @state_name='done'">сверстали</xsl:when>
				<xsl:when test="@status_name='make' and @state_name='process'">верстают</xsl:when>
				<xsl:when test="@status_name='read' and @state_name='done'">вычитали</xsl:when>
				<xsl:when test="@status_name='read' and @state_name='process'">вычитывают</xsl:when>
				<xsl:when test="@status_name='recognize' and @state_name='done'">распознали</xsl:when>
				<xsl:when test="@status_name='recognize' and @state_name='process'">распознают</xsl:when>
				<xsl:when test="@status_name='scan' and @state_name='done'">отсканировали</xsl:when>
				<xsl:when test="@status_name='scan' and @state_name='process'">сканируют</xsl:when>
				<xsl:when test="@status_name='get' and @state_name='done'">достали</xsl:when>
				<xsl:otherwise/>
			</xsl:choose>
		</em>
		<xsl:if test="not(position()=last())">, </xsl:if>
	</xsl:template>

  <xsl:template match="*" mode="p-ocr-list-book">
    <xsl:param select="users" name="users"/>
    <xsl:param select="states" name="states"/>
    <xsl:param select="statuses" name="statuses"/>
    <xsl:variable select="$statuses/item[@id=current()/@status]/@name" name="status"/>
    <xsl:variable select="$states/item[@id=current()/@state]/@name" name="state"/>
    <li id="user_{@id_user}_status_{@status}">
      <xsl:attribute name="class">
        <xsl:choose>
        	<xsl:when test="$state='approved'">p-ocr-list-book-approved</xsl:when>
        	<xsl:otherwise>p-ocr-list-book</xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>
      <xsl:apply-templates select="$users/item[@id=current()/@id_user]" mode="h-user-link"/>
      <xsl:text>&nbsp;</xsl:text>
      <xsl:choose>
        <xsl:when test="$status='new' and $state='new'">хочет</xsl:when>
        <xsl:when test="$status='get' and contains('done,approved',$state)">достал книгу</xsl:when>
        <xsl:when test="$status='scan' and $state='can'">может отсканировать</xsl:when>
        <xsl:when test="$status='recognize' and $state='can'">может распознать</xsl:when>
        <xsl:when test="$status='read' and $state='can'">может вычитать</xsl:when>
        <xsl:when test="$status='make' and $state='can'">может сверстать</xsl:when>
        <xsl:when test="$status='scan' and $state='process'">сканирует</xsl:when>
        <xsl:when test="$status='recognize' and $state='process'">распознаёт</xsl:when>
        <xsl:when test="$status='read' and $state='process'">вычитывает</xsl:when>
        <xsl:when test="$status='make' and $state='process'">верстает</xsl:when>
        <xsl:when test="$status='scan' and contains('done,approved',$state)">отсканировал</xsl:when>
        <xsl:when test="$status='recognize' and contains('done,approved',$state)">распознал</xsl:when>
        <xsl:when test="$status='read' and contains('done,approved',$state)">вычитал</xsl:when>
        <xsl:when test="$status='make' and contains('done,approved',$state)">сверстал</xsl:when>
        <xsl:otherwise></xsl:otherwise>
      </xsl:choose>
      <xsl:if test="&access;/ocr_edit">
        <xsl:if test="not($state='approved')"><a href="#" class="p-ocr-list-approve">+</a></xsl:if>
        <a href="#" class="p-ocr-list-delete">x</a>
      </xsl:if>
    </li>
  </xsl:template>

  <xsl:template match="module[@name='ocr' and @action='new']" mode="p-module">
		<input type="hidden" value="{ocr/@id_book}" name="book_id"/>
		<xsl:apply-templates select="ocr" mode="p-ocr-new-book-status"/>
		<xsl:apply-templates select="ocr" mode="p-ocr-new-book-futher-actions"/>
		<div class="p-ocr-new-help">
			<a class="ocr-help" href="#">Помочь в работе над книгиой</a>
		</div>
		<ul class="p-ocr-new-statuses">
			<xsl:apply-templates select="statuses/item[not(@name='new')]" mode="p-ocr-new-status">
				<xsl:with-param select="states" name="states"/>
			</xsl:apply-templates>
		</ul>
  </xsl:template>

	<xsl:template match="*" mode="p-ocr-new-book-futher-actions">
		<h3>
			<xsl:choose>
				<xsl:when test="@status_name='read' and @state_name='done'">Теперь книгу надо сверстать</xsl:when>
				<xsl:when test="@status_name='recognize' and @state_name='done'">Теперь книгу надо вычитать</xsl:when>
				<xsl:when test="@status_name='scan' and @state_name='done'">Теперь текст надо распознать</xsl:when>
				<xsl:when test="@status_name='get' and @state_name='done'">Теперь книгу надо отсканировать</xsl:when>
				<xsl:when test="not(@status_name)">Надо хотя бы найти бумажный вариант</xsl:when>
				<xsl:otherwise></xsl:otherwise>
			</xsl:choose>
		</h3>
  </xsl:template>

	<xsl:template match="*" mode="p-ocr-new-book-status">
		<h2>
			<xsl:choose>
				<xsl:when test="@status_name='make'">
					<xsl:choose>
						<xsl:when test="@state_name='done'">Ура! Книга готова!</xsl:when>
						<xsl:when test="@state_name='process'">Книгу верстают</xsl:when>
						<xsl:when test="@state_name='can'">Книгу могут сверстать</xsl:when>
						<xsl:otherwise/>
					</xsl:choose>
				</xsl:when>
				<xsl:when test="@status_name='read'">
					<xsl:choose>
						<xsl:when test="@state_name='done'">Мы вычитали книгу</xsl:when>
						<xsl:when test="@state_name='process'">Книга на вычитке</xsl:when>
						<xsl:when test="@state_name='can'">Книгу могут вычитать</xsl:when>
						<xsl:otherwise/>
					</xsl:choose>
				</xsl:when>
				<xsl:when test="@status_name='recognize'">
					<xsl:choose>
						<xsl:when test="@state_name='done'">Мы распознали текст</xsl:when>
						<xsl:when test="@state_name='process'">Текст книги распознают</xsl:when>
						<xsl:when test="@state_name='can'">Книгу могут распознать</xsl:when>
						<xsl:otherwise/>
					</xsl:choose>
				</xsl:when>
				<xsl:when test="@status_name='scan'">
					<xsl:choose>
						<xsl:when test="@state_name='done'">Мы отсканировали книгу</xsl:when>
						<xsl:when test="@state_name='process'">Книгу сканируют</xsl:when>
						<xsl:when test="@state_name='can'">Книгу могут отсканировать</xsl:when>
						<xsl:otherwise/>
					</xsl:choose>
				</xsl:when>
				<xsl:when test="@status_name='get' and @state_name='done'">Мы нашли бумажный вариант</xsl:when>
				<xsl:otherwise>У нас нет этой книги</xsl:otherwise>
			</xsl:choose>
		</h2>
	</xsl:template>

	<xsl:template match="*" mode="p-ocr-new-status">
		<xsl:param select="states" name="states"/>
		<div class="p-ocr-new-status {@name}">
			<input type="hidden" name="status_id" value="{@id}"/>
			<p class="p-ocr-new-status-title">
				<xsl:choose>
					<xsl:when test="@name='get'">Поиск: </xsl:when>
					<xsl:when test="@name='scan'">Сканирование: </xsl:when>
					<xsl:when test="@name='recognize'">Распознавание: </xsl:when>
					<xsl:when test="@name='read'">Вычитка: </xsl:when>
					<xsl:when test="@name='make'">Вёрстка: </xsl:when>
					<xsl:otherwise/>
				</xsl:choose>
			</p>
			<div class="p-ocr-new-state">
				<a href="#" id="edit-{@name}-state">не участвую</a>
				<ul class="p-ocr-new-states">
					<xsl:apply-templates select="$states/item" mode="p-ocr-new-state">
						<xsl:with-param select="@name" name="status"/>
					</xsl:apply-templates>
				</ul>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="*" mode="p-ocr-new-state">
		<xsl:param select="status" name="status"></xsl:param>
		<li class="p-ocr-new-state-li">
			<a href="#" id="{$status}-{@name}">
				<xsl:choose>
					<xsl:when test="@name='new'">не участвую</xsl:when>
					<xsl:when test="$status='get' and @name='done'">у меня есть</xsl:when>
					<xsl:when test="$status='scan' and @name='can'">могу отсканировать</xsl:when>
					<xsl:when test="$status='recognize' and @name='can'">могу распознать</xsl:when>
					<xsl:when test="$status='read' and @name='can'">могу вычитать</xsl:when>
					<xsl:when test="$status='make' and @name='can'">могу сверстать</xsl:when>
					<xsl:when test="$status='scan' and @name='process'">сканирую</xsl:when>
					<xsl:when test="$status='recognize' and @name='process'">распознаю</xsl:when>
					<xsl:when test="$status='read' and @name='process'">вычитываю</xsl:when>
					<xsl:when test="$status='make' and @name='process'">верстаю</xsl:when>
					<xsl:when test="$status='scan' and @name='done'">отсканировал</xsl:when>
					<xsl:when test="$status='recognize' and @name='done'">распознал</xsl:when>
					<xsl:when test="$status='read' and @name='done'">вычитал</xsl:when>
					<xsl:when test="$status='make' and @name='done'">сверстал</xsl:when>
					<xsl:otherwise></xsl:otherwise>
				</xsl:choose>
			</a>
			<input type="hidden" name="state_id" value="{@id}"/>
		</li>
	</xsl:template>

</xsl:stylesheet>
