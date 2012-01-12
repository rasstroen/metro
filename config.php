<?php

class Config {

	// сколько и за какое действие даём

	private static $config = array(
	    'base_path' => '/home/metro/', // в какой директории на сервере лежит index.php
	    'www_absolute_path' => '', // например для http://localhost/hello/ это будет /hello
	    'www_path' => 'http://metro.ljrate.ru',
	    'www_domain' => 'metro.ljrate.ru',
	    'default_page_name' => 'main', // синоним для корня сайта
	    'static_path' => '/home/metro/static', // 
	    'cid' => '3KYHKUHYXJ1SWC0XOJSJQH0AUQ5BKEBST4YEBR5TYZ2FSENI',
	    'csecret' => '31MMWA0EGZHLWO2ICVFL4SJPN1PZ4LZ4GZCX2Z2TS4JLDXMU',
	    //USERS
	    'default_language' => 'ru',
	    //Register
	    'register_email_from' => 'amuhc@yandex.ru',
	    //Auth
	    'auth_cookie_lifetime' => 360000,
	    'auth_cookie_hash_name' => 'mehash_',
	    'auth_cookie_id_name' => 'meid_',
	    // Avatars
	    'avatar_upload_path' => '/home/metro/static/upload/avatars',
	    // Mongo
	    'mongohost' => 'localhost',
	    // MySQL
	    'dbuser' => 'root',
	    'dbpass' => '2912',
	    'dbhost' => 'localhost',
	    'dbname' => 'metro',
	    // MODULES
	    'writemodules_path' => '/home/metro/modules/write',
	    // THEMES
	    'default_theme' => 'default',
	    // XSLT
	    'xslt_files_path' => '/home/metro/xslt',
	    //CACHE
	    'cache_enabled' => false, // отключить/включить весь кеш
	    'cache_default_folder' => '/home/metro/cache/var',
	    // XSL CACHE
	    'xsl_cache_min_sec' => 1,
	    'xsl_cache_max_sec' => 300,
	    'xsl_cache_file_path' => './cache/xsl',
	    'xsl_cache_memcache_enabled' => false,
	    'xsl_cache_xcache_enabled' => true,
	    // XML CACHE
	    'xml_cache_min_sec' => 1,
	    'xml_cache_max_sec' => 86400,
	    'xml_cache_file_path' => './cache/xml',
	    'xml_cache_memcache_enabled' => false,
	    'xml_cache_xcache_enabled' => true,
	    // ADMIN
	    'phplib_pages_path' => '/home/metro/phplib',
	    'phplib_modules_path' => '/home/metro/phplib',
	   
	);

// получем переменную из конфига
	public static function need($var_name, $default = false) {
		if (isset(self::$config[$var_name])) {
			return self::$config[$var_name];
		}
		return $default;
	}

	public static function init($local_config = false) {
		if ($local_config) {
			foreach ($local_config as $name => $value) {
				self::$config[$name] = $value;
			}
		}
	}

}