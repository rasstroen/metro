<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "../entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>
	
	
	<xsl:template match="module[@name='forum' and @action='new' and @mode='theme']">
		<form method="post">
			<input type="hidden" name="writemodule" value="ForumWriteModule" />
			<input type="hidden" name="action" value="new_thread" />
			<input type="hidden" name="tid" value="{forum/@tid}" />
			<i>theme title</i>
			<br/>
			<input name="title" />
			<br/>
			<i>message</i>
			<br/>
			<textarea style="width:100%;height:300px;" name="message"></textarea>
			<br/>
			<input type="submit" />
		</form>
	</xsl:template>
	
	<xsl:template match="module[@name='forum' and @action='list' and @mode='themes']" mode="p-module">
		<table class="forum-list-table">
			<tr>
				<td colspan="3">
					<a href="{@path_new_theme}">Новая тема</a>	
				</td>
			</tr>
			<tr>
				<td>Тема</td>
				<td>Ответов</td>
				<td>Дата</td>
			</tr>
			<xsl:apply-templates select="themes/item" mode="p-forum-list">
				<xsl:with-param select="users" name="users"/>
			</xsl:apply-templates>
		</table>
	</xsl:template>
  
  
	<xsl:template match="*" mode="p-forum-list">
		<xsl:param select="users" name="users"/>
		<xsl:param select="@author_id" name="author_id"/>
		<xsl:param select="@last_comment_uid" name="uid"/>
		<xsl:param select="$users/item[@id = $uid]" name="user"/>
		<tr>
			<xsl:attribute name="class">
				<xsl:text>p-forum-list </xsl:text>
				<xsl:choose>
					<xsl:when test="position() mod 2 = 1">odd</xsl:when>
					<xsl:otherwise>even</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<td>
				<hr/>
				<xsl:if test="$author_id > 0">
					<div class="p-forum-list-author">
						<xsl:apply-templates select="$users/item[@id = $author_id]" mode="h-user-link"/>
					</div>	
				</xsl:if>
				<div class="p-forum-list-title">
					<a href="{&prefix;}forum/{../@tid}/{@nid}">
						<xsl:value-of select="@title"/>
					</a>
				</div>
				
				<xsl:if test="$uid > 0">
					<div class="p-forum-list-comment">
						<xsl:text>Последний комментарий:</xsl:text>
						<xsl:value-of select="@last_comment_timestamp"/>, пользователь
						<xsl:apply-templates select="$user" mode="h-user-link"/>
					</div>
				</xsl:if>
			</td>
			<td class="p-forum-list-count">
				<xsl:value-of select="@comment_count"/>
			</td>
			<td class="p-forum-list-created">
				<xsl:value-of select="@created"/>
			</td>
			<td/>
		</tr>
	</xsl:template>

	<xsl:template match="module[@name='forum' and @action='show']" mode="p-module">
		<xsl:variable select="theme/@user_id" name="uid"/>
		<xsl:variable select="theme/users/item[@id=$uid]" name="user"/>
		<h1>
			<xsl:value-of select="theme/@title"></xsl:value-of>
		</h1>
		<div class="forum-show-back">
			<a href="{&prefix;}forum/{theme/@tid}">Назад, к списку тем</a>
		</div>
		<div class="forum-show-user">
			<div class="forum-show-user-image">
				<xsl:apply-templates select="$user" mode="h-user-image"/>
			</div>
			<xsl:apply-templates select="$user" mode="h-user-link"/>
		</div>
		<div class="forum-show-body">
			<xsl:value-of select="theme/@body" disable-output-escaping="yes"></xsl:value-of>
		</div>
		<xsl:if test="theme/comments/item">
			<ul class="forum-show-comments">
				<h2>Комментарии:</h2>
				<xsl:apply-templates select="theme/comments/item" mode="p-comment-forum">
					<xsl:with-param select="theme/users" name="users"/>
				</xsl:apply-templates>
			</ul>
		</xsl:if>
		<div>
			<form method="post">
				<input type="hidden" name="writemodule" value="ForumWriteModule" />
				<input type="hidden" name="action" value="new_comment" />
				<input type="hidden" name="tid" value="{theme/@tid}" />	
				<input type="hidden" name="theme_id" value="{theme/@theme_id}" />
				<br/>
				<textarea style="width:100%;height:300px;" name="message"></textarea>
				<br/>
				<input type="submit" />
			</form>	
		</div>
	</xsl:template>
	
	<xsl:template match="*" mode="p-comment-forum">
		<xsl:param select="users" name="users"/>
		<xsl:param select="@uid" name="uid"/>
		<xsl:variable select="$users/item[@id=$uid]" name="user"/>
		<li class="p-comment-forum">
			<hr/>
			<div>
				<a name="comment-{@cid}" style="color:#aaa;font-size:10px">
					<xsl:text>#</xsl:text>
					<xsl:value-of select="@cid" />
				</a>
			</div>
			<div class="p-comment-forum-image">
				<xsl:apply-templates select="$user" mode="h-user-image"/>
			</div>
			<div class="p-comment-forum-text">
				<div>
					<b>
						<xsl:value-of select="@subject" />	
					</b>
				</div>
				<xsl:if test="@rid > 0">
					<div>
						<a href="#comment-{@rid}" style="color:#aaa;font-size:10px">
							<xsl:text>в ответ на комментарий #</xsl:text>
							<xsl:value-of select="@rid" />
						</a>
					</div>
				</xsl:if>
				<div class="p-comment-forum-text-user">
					<xsl:apply-templates select="$user" mode="h-user-link"/>
				</div>
				<xsl:value-of select="@comment" disable-output-escaping="yes"/>
			</div>
			<xsl:if test="answers">
				<ul style="padding-left:90px;background:#fbfbfb;">
					<xsl:apply-templates select="answers/item" mode="p-comment-forum">
						<xsl:with-param select="$users" name="users"/>
					</xsl:apply-templates>	
				</ul>
			</xsl:if>
		</li>
	</xsl:template>

</xsl:stylesheet>
