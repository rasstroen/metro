<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>
	<xsl:output omit-xml-declaration="yes"/>
	<xsl:output indent="yes"/>
	<xsl:template match="//root/data">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />	
			</head>
			<body>
				<p>Вы зарегистрированы на сайте.</p>
				<p>Для подтверждения почтового ящика, перейдите, пожалуйста, по этой ссылке:</p>
				<p>
					<a href="{@register_url}">
						<xsl:value-of select="@register_url" />
					</a>
				</p>
				<p>После подтверждения почты cможете зайти, используя email:</p>
				<p>
					<xsl:value-of select="@email" />
				</p>
				<p>и пароль:</p>
				<p>
					<xsl:value-of select="@password" />
				</p>
				<br/>
				<p>Ваш ник 
					<xsl:value-of select="@nickname" />. Ник можно поменять 1 раз в личном кабинете на сайте
				</p>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
