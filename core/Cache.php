<?php

class Cache {
	const CACHE_TYPE_XCACHE = 1;
	const CACHE_TYPE_MEMCACHE = 2;
	const CACHE_TYPE_FILE = 3;

	const DATA_TYPE_XML = 1;
	const DATA_TYPE_XSL = 2;
	const DATA_TYPE_VAR = 3;

	private static $cacheType;
	private static $cache_enabled = true;
	private static $min_cache_sec = 1;
	private static $max_cache_sec = 86400;
	private static $cache_folder;
	private static $inited = false;
	//  для каждой темы и языка шаблоны и xml генерируются разные
	private static $language = 'ru';
	private static $theme = 'default';

	public static function init() {
		if (self::$inited)
			return true;
		$memcache_enabled = Config::need('xsl_cache_memcache_enabled', false);
		$xcache_enabled = Config::need('xsl_cache_xcache_enabled', false);
		$filecache_enabled = false;
		
		self::$min_cache_sec = Config::need('xml_cache_min_sec');
		self::$max_cache_sec = Config::need('xml_cache_max_sec');

		if ($xcache_enabled) {
			self::$cacheType = self::CACHE_TYPE_XCACHE;
		} else if ($memcache_enabled) {
			self::$cacheType = self::CACHE_TYPE_MEMCACHE;
		} else if ($filecache_enabled) {
			self::$cacheType = self::CACHE_TYPE_FILE;
		} else
			self::$cache_enabled = false;

		self::$cache_folder = Config::need('cache_default_folder', '');
		self::$inited = true;
		global $current_user;

		/* @var $current_user CurrentUser */
		if ($current_user == null) {
			self::$language = Config::need('default_language');
			self::$theme = Config::need('default_theme');
		} else {
			self::$language = $current_user->getLanguage();
			self::$theme = $current_user->getTheme();
		}
		return true;
	}

	public static function drop($name, $datatype = self::DATA_TYPE_VAR) {
		self::init();
		if(!self::$cache_enabled) return null;
		switch (self::$cacheType) {
			case self::CACHE_TYPE_XCACHE:
				self::drop_xcache($name, $datatype);
				break;
			case self::CACHE_TYPE_MEMCACHE:

				break;
			case self::CACHE_TYPE_FILE:
				self::drop_file($name, $datatype);
				break;
		}
		return false;
	}

	public static function get($name, $datatype = self::DATA_TYPE_VAR, $cache_sec = 0) {
		self::init();
		if(!self::$cache_enabled) return null;
		Log::timingplus('cache:get');
		switch (self::$cacheType) {
			case self::CACHE_TYPE_XCACHE:
				$ret = self::get_xcache($name, $datatype);
				Log::timingplus('cache:get');
				return $ret;
				break;
			case self::CACHE_TYPE_MEMCACHE:
				Log::timingplus('cache:get');
				break;
			case self::CACHE_TYPE_FILE:
				$cache_sec = max(0, (int) $cache_sec);
				$ret = self::get_file($name, $datatype, $cache_sec);
				Log::timingplus('cache:get');
				return $ret;
				break;
		}
		return false;
	}

	public static function set($name, $value, $cache_seconds = false, $datatype = self::DATA_TYPE_VAR) {
		self::init();
		if(!self::$cache_enabled) return null;
		Log::timingplus('cache:set');
		if (!$cache_seconds)
			$cache_seconds = self::$max_cache_sec;
		switch (self::$cacheType) {
			case self::CACHE_TYPE_XCACHE:
				self::set_xcache($name, $value, $datatype, $cache_seconds);
				break;
			case self::CACHE_TYPE_MEMCACHE:
				break;
			case self::CACHE_TYPE_FILE:
				self::set_file($name, $value, $datatype);
				break;
		}
		Log::timingplus('cache:set');
	}

	private static function getFolder($datatype) {
		$folder = self::$cache_folder;
		switch ($datatype) {
			case self::DATA_TYPE_XML:
				$folder = Config::need('xml_cache_file_path');
				break;
			case self::DATA_TYPE_XSL:
				$folder = Config::need('xsl_cache_file_path');
				break;
		}
		return $folder;
	}

	private static function drop_file($name, $datatype = self::DATA_TYPE_XML) {
		if ($datatype == self::DATA_TYPE_XSL)
			$filename = self::getFolder($datatype) . '/' . self::$theme . '_' . self::$language . '_' . $name . '.xsl';
		else if ($datatype == self::DATA_TYPE_XML)
			$filename = self::getFolder($datatype) . '/' . self::$theme . '_' . self::$language . '_' . $name;
		else
			$filename = self::getFolder($datatype) . $name;
		@unlink($filename);
	}

	private static function drop_xcache($name, $datatype = self::DATA_TYPE_XML) {
		global $current_user;
		/* @var $current_user CurrentUser */
		if ($datatype == self::DATA_TYPE_XSL)
			$filename = 'xsl_' . self::$theme . '_' . self::$language . '_' . $name . '.xsl';
		else if ($datatype == self::DATA_TYPE_XML)
			$filename = 'xml_' . $current_user->getRole() . '|' . self::$theme . '|' . self::$language . '|' . $name;
		else
			$filename = 'var_' . $name;
		@xcache_unset($filename);
		Log::logHtml($filename . ' deleted from xcache');
	}

	private static function get_file($name, $datatype = self::DATA_TYPE_XML, $cache_sec = 0) {
		if ($datatype == self::DATA_TYPE_XSL)
			$filename = self::getFolder($datatype) . '/' . self::$theme . '_' . self::$language . '_' . $name . '.xsl';
		else if ($datatype == self::DATA_TYPE_XML)
			$filename = self::getFolder($datatype) . '/' . self::$theme . '_' . self::$language . '_' . $name;
		else
			$filename = self::getFolder($datatype) . $name;


		if (is_readable($filename)) {
			$cache_sec = self::normalizeCacheTime($cache_sec);
			if ($cache_sec) {
				$mtime = filemtime($filename);
				if (time() - $mtime > $cache_sec) {
					return false;
				}
			}
			return file_get_contents($filename);
		}
		return false;
	}

	private static function get_xcache($name, $datatype = self::DATA_TYPE_XML) {
		global $current_user;
		/* @var $current_user CurrentUser */
		if ($datatype == self::DATA_TYPE_XSL)
			$filename = 'xsl_' . self::$theme . '_' . self::$language . '_' . $name . '.xsl';
		else if ($datatype == self::DATA_TYPE_XML)
			$filename = 'xml_' . $current_user->getRole() . '|' . self::$theme . '|' . self::$language . '|' . $name;
		else
			$filename = 'var_' . $name;
		$var = xcache_get($filename);

		if (!is_null($var))
			Log::logHtml($filename . ' got from xcache');
		else
			Log::logHtml($filename . ' got from xcache: null');
		return $var;
	}

	private static function get_memcache($name) {
		
	}

	private static function set_xcache($name, $value, $datatype = self::DATA_TYPE_VAR, $cache_seconds) {
		global $current_user;
		/* @var $current_user CurrentUser */
		if ($datatype == self::DATA_TYPE_XSL)
			$filename = 'xsl_' . self::$theme . '_' . self::$language . '_' . $name . '.xsl';
		else if ($datatype == self::DATA_TYPE_XML)
			$filename = 'xml_' . $current_user->getRole() . '|' . self::$theme . '|' . self::$language . '|' . $name;
		else
			$filename = 'var_' . $name;
		Log::logHtml($filename . ' put into xcache for '.$cache_seconds.' s.');
		xcache_set($filename, $value, $cache_seconds);
	}

	private static function set_file($name, $value, $datatype = self::DATA_TYPE_VAR) {
		if ($datatype == self::DATA_TYPE_XSL)
			$filename = self::getFolder($datatype) . '/' . self::$theme . '_' . self::$language . '_' . $name . '.xsl';
		else if ($datatype == self::DATA_TYPE_XML)
			$filename = self::getFolder($datatype) . '/' . self::$theme . '_' . self::$language . '_' . $name;
		else
			$filename = self::getFolder($datatype) . $name;
		file_put_contents($filename, $value);
	}

	private static function set_memcache($name, $value, $cache_seconds = false) {
		
	}

	private static function normalizeCacheTime($cache_sec) {
		return min(self::$max_cache_sec, max(self::$min_cache_sec, $cache_sec));
	}

}