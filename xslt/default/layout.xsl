<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "entities.dtd">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>
	<xsl:output omit-xml-declaration="yes"/>
	<xsl:template match="&page;" mode="l-head">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>
			<xsl:value-of select="@title"></xsl:value-of>
		</title>
    <!--[if lte IE 7]>
    <style type="text/css">
      ul.dropdown ul li	{display:inline; width:100%}
    </style>
    <![endif]-->
		<script>
			<xsl:text>var exec_url ='</xsl:text>
			<xsl:value-of select="&prefix;"/>';
			<xsl:text>var user_role = '</xsl:text>
			<xsl:value-of select="&current_profile;/@role"/>';
		</script>
		<xsl:apply-templates select="&structure;/data/stylesheet" mode="h-stylesheet"/>
		<xsl:apply-templates select="&structure;/data/javascript" mode="h-javascript"/>
	</xsl:template>

	<xsl:template match="&root;">
		<head>
			<xsl:apply-templates select="&page;" mode="l-head" />
		</head>
		<body class="l-body">
			<div class="l-header">
				<xsl:apply-templates select="&root;" mode="l-header" />
			</div>
			<div class="l-wrapper">
				<div class="l-content">
					<xsl:apply-templates select="&page;/message" mode="h-message"/>
					<xsl:apply-templates select="&structure;/blocks/content/module" mode="l-content"/>
				</div>
				<div class="l-sidebar">

					<xsl:apply-templates select="&structure;/blocks/sidebar/module" mode="l-sidebar"/>
				</div>
			</div>
			<div class="l-footer">
				<xsl:apply-templates select="&root;" mode="l-footer" />
			</div>
		</body>
	</xsl:template>

	<xsl:template match="*" mode="l-header">
		<div class="l-header-module">
			<xsl:apply-templates select="&structure;/blocks/header/module" mode="l-header-module"/>
		</div>
		<xsl:apply-templates select="&page;" mode="l-header-logo" />
		<xsl:apply-templates select="&root;" mode="l-header-nav" />
	</xsl:template>

	<xsl:template match="*" mode="l-header-logo">
		<div class="l-header-logo">
			<h1>
				<a>
					<xsl:if test="@current_url!=&prefix;">
						<xsl:attribute name="href">
							<xsl:value-of select="&prefix;"/>
						</xsl:attribute>
					</xsl:if>
					<xsl:text>Метро</xsl:text>
				</a>
			</h1>
		</div>
	</xsl:template>

	<xsl:template match="*" mode="l-content">
		<div class="l-content-module">
			<xsl:apply-templates select="." mode="modules"/>
		</div>
	</xsl:template>

	<xsl:template match="*" mode="l-sidebar">
		<div class="l-sidebar-module">
			<xsl:apply-templates select="." mode="modules"/>
		</div>
	</xsl:template>

	<xsl:template match="*" mode="l-header-module">
		<xsl:apply-templates select="." mode="modules"/>
	</xsl:template>

	<xsl:template match="*" mode="modules">
		<xsl:if test="current()/@mode">
			<xsl:apply-templates select="&root;/module[@name = current()/@name and @action=current()/@action and @mode = current()/@mode]" />
		</xsl:if>
		<xsl:if test="not(current()/@mode)">
			<xsl:apply-templates select="&root;/module[@name = current()/@name and @action=current()/@action and not(@mode)]" />
		</xsl:if>
	</xsl:template>

	<xsl:template match="*" mode="l-footer">
		<xsl:apply-templates select="&root;" mode="l-footer-copy" />
		<xsl:apply-templates select="&root;" mode="l-footer-nav" />
		<xsl:apply-templates select="&root;" mode="l-footer-debug" />
	</xsl:template>

	<xsl:template match="*" mode="l-header-search">
		<div class="l-header-search">
			<form action="{&prefix;}search">
				<input name="q" type="text" class="l-header-search-field">
					<xsl:attribute name="value">
						<xsl:choose>
							<xsl:when test="&page;/variables/@q">
								<xsl:value-of select="&page;/variables/@q"/>
							</xsl:when>
							<xsl:otherwise>Ищете слона?</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
				</input>
				<input type="submit" value="Найти" class="l-header-search-button"/>
			</form>
		</div>
	</xsl:template>

	<xsl:template match="*" mode="l-header-nav">
		<div class="l-header-nav">
			<xsl:apply-templates select="&navigations;/item[@name='main']" mode="h-navigation"/>
		</div>
	</xsl:template>

	<xsl:template match="*" mode="l-footer-copy">
		<p class="l-footer-copy">&copy; rasstroen 2012</p>
	</xsl:template>

	<xsl:template match="*" mode="l-footer-nav">
		<div class="l-footer-nav">
			<xsl:apply-templates select="&navigations;/item[@name='footer']" mode="h-navigation"/>
		</div>
	</xsl:template>

	<xsl:template match="*" mode="l-footer-debug">
		
	</xsl:template>

	<xsl:template match="module[@name='users' and @action='show' and @mode='auth']" mode="p-module">
		<div class="l-user-auth">
			<xsl:choose>
				<xsl:when test="profile/@authorized = '1'">
					<xsl:apply-templates select="navigations/item" mode="h-navigation"/>
				</xsl:when>
				<xsl:otherwise>
					<form method="post">
						<input type="hidden" name="writemodule" value="AuthWriteModule"></input>
						<input class="linput" type="text" name="email"></input>
						<input class="linput" type="password" name="password"></input>
						<input id="submit" type="submit" value="Войти" name="login"/>
					</form>
					<a href="{&prefix;}register">
						<xsl:text>Регистрация</xsl:text>
					</a>
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>

</xsl:stylesheet>
