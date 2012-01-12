<?php

/* Обработка входных параметров скрипта
 *
 * Разбирает POST/GET параметры, определяет, какую страницу запрашивает пользователь, преобразует
 * URI в набор параметров для страницы, вырезает специальные параметры (serxml,serxsl).
 * Обработка входных параметров для каждого модуля также происходит в лассе Request
 *
 * @author rasstroen
 *
 */

class Request {

	private static $initialized = false;
	public static $get = array();
	public static $get_normal = array();
	public static $post = array();
	public static $structureFile = 'errors/p404.xml';
	public static $url = '';
	public static $responseType = 'html';
	private static $pass;
	private static $real_path = -2;
	public static $path_history = '';
	public static $ip = 'unknown';

	public static function pass($f, $v = false) {
		if ($v === false)
			return isset(self::$pass[$f]) ? self::$pass[$f] : false;
		self::$pass[$f] = $v;
	}

	/**
	 *
	 * получаем имя переменной для сохранения в кеше xml дерева модуля
	 *
	 * @param type $moduleName
	 * @param type $moduleAction
	 * @param type $moduleMode
	 * @param type $params
	 *
	 * @return string имя переменной
	 */
	public static function getModuleUniqueHash($moduleName = '', $moduleAction = '', $moduleMode = '', $params = array()) {
		$cacheName = $moduleName . '|' . $moduleAction . '|' . $moduleMode;
		$cacheNameVar = '';
		foreach (self::$get_normal as $f => $v)
			$cacheNameVar.= $f . '=' . $v;
		foreach ($params as $f => $v)
			$cacheNameVar.= $f . '=' . $v;
		$cacheNameVar = $cacheNameVar ? '|' . md5($cacheNameVar) : '';
		return $cacheName . $cacheNameVar;
	}

	/** обрабатываем входные параметры скрипта, определяем запрашиваемую страницу
	 *
	 */
	public static function initialize($deep = false) {
		if (!$deep && self::$initialized)
			return;
		$headers = @apache_request_headers();
		if (array_key_exists('X-Forwarded-For', $headers)) {
			$hostname = $headers['X-Forwarded-For'];
		} else {
			$hostname = $_SERVER["REMOTE_ADDR"];
		}
		self::$ip = $hostname;
		self::$path_history = '';
		self::$initialized = true;
		// принимаем uri
		$e = explode('?', $_SERVER['REQUEST_URI']);
		$_SERVER['REQUEST_URI'] = $e[0];
		if (isset($e[1]))
			parse_str($e[1], $d);
		else
			$d = array();

		$prepared_get = array();
		foreach ($d as $name => &$val) {
			$val = to_utf8($val);
			$prepared_get[$name] = stripslashes($val);
		}
		self::$get_normal = self::$get = $prepared_get;
		$path_array = explode('/', self::processRuri($_SERVER['REQUEST_URI']));
		// убиваем начальный слеш
		array_shift($path_array);
		// определяем, что из этого uri является страницей
		self::$structureFile = self::getPage($path_array, $deep);
		if (self::$real_path >= 0) {
			self::$structureFile = 'errors/p404.xml';
		}
		//die(self::$structureFile);
		// разбираем параметры
		self::parse_parameters($path_array);
		if (isset($_POST))
			foreach ($_POST as $f => $v) {
				if (!is_array($v))
					self::$post[$f] = stripslashes($v);
				else
					self::$post[$f] = $v;
			}
		unset($_POST);
		unset($_GET);
		self::afterAll();
	}

	public static function afterAll() {
		
	}

	public static function is_on_main_page() {
		return(self::$structureFile === 'main.xml');
	}

	function getRealPath() {
		$uri = explode('/', $_SERVER['REQUEST_URI']);
		$i = self::$real_path;
		while ($i-- > 1)
			array_pop($uri);
		return ($real = implode('/', $uri) . '/') ? $real : Config::need('www_path');
	}

	/** по маске проверяем параметры для модуля
	 *
	 *
	 * @param array $mask - маска array('int','string','*int'), * - параметр необязателен
	 * @param array $data - массив входных данных
	 *
	 * @return array массив значений, если они соответствуют маске.
	 * "*" значения возвращаются как null если во входном массиве их не оказалось. или FALSE в случае,
	 * если вхордные параметры не соответствуют маске
	 */
	public static function checkParameters(array $data, array $mask) {
		$params = array();
		$i = 0;
		foreach ($mask as $field => $type) {
			$value = isset($data[$i]) ? $data[$i] : null;
			$params[$field] = self::checkValue($value, $type);
			if (is_null($params[$field]))
				throw new Exception('Required get field #' . $field . ' missed[' . $i . ']');
			$i++;
		}
		return $params;
	}

	/*
	 * отдает имя поля, по которому
	 */

	public static function checkPostParameters(array $mask) {
		$params = array();
		foreach ($mask as $field => $type) {
			$value = self::post($field, null);
			if (is_null($value) && !isset($type['*']))
				throw new Exception('Required post field ' . $field . ' missed');
			$params[$field] = self::checkValue($value, $type);
		}
		return $params;
	}

	public function checkValue($value, $type) {
		$min_length = 0;
		$max_length = 0;
		$regexp = false;
		$optional = false;
		if (is_array($type)) {
			$min_length = isset($type['min_length']) ? (int) $type['min_length'] : 0;
			$max_length = isset($type['max_length']) ? (int) $type['max_length'] : 0;
			$regexp = isset($type['regexp']) ? $type['regexp'] : false;
			$optional = isset($type['*']) ? true : false;
			$type = $type['type'];
		}
		switch ($type) {
			case 'email':
				if (!valid_email_address($value))
					return false;
				break;
			case 'string':
				if (!$value && $optional)
					return '';
				if (!$value)
					return false;
				if ($min_length)
					if (mb_strlen(trim($value), 'UTF-8') < $min_length)
						return false;
				if ($max_length)
					if (mb_strlen(trim($value), 'UTF-8') > $max_length)
						return false;
				if ($regexp)
					if (!preg_match($regexp, $value))
						return false;
				break;
			case 'int':
				if ($value == null) {
					if ($optional)
						return false;
				}
				if (!is_numeric($value)) {
					$value = (int) $value;
				}
				break;
			case '':
				break;
		}
		return $value;
	}

	/*
	 *
	 */

	public static function getAllParameters() {
		self::initialize();
		return self::$get;
	}

	/*
	 *
	 */

	public static function get($offset, $default = false) {
		self::initialize();
		if (isset(self::$get[$offset]))
			return self::$get[$offset];
		return $default;
	}

	/*
	 *
	 */

	public static function post($name, $default = false) {
		return isset(self::$post[$name]) ? self::$post[$name] : $default;
	}

	/*
	 *
	 */

	private static function set($offset, $value) {
		self::$get[$offset] = $value;
	}

	/*
	 *
	 */

	private static function parse_parameters(array $path_array) {
		$s = array_shift($path_array);
		self::$url = self::specialParameters($s) ? $s . '/' : '';
		$i = 0;
		foreach ($path_array as $value) {
			$value = self::specialParameters($value); // если это служебное, изымаем из параметров
			if ($value) {
				self::$url.=urldecode($value) . '/';
				self::set($i++, $value);
			}
		}
		self::$url = Config::need('www_path') . '/' . self::$url;
	}

	/*
	 *
	 */

	private static function specialParameters($value) {
		switch ($value) {
			case 'serxml':
				// мы хотим получить контент в xml
				self::$responseType = 'xml';
				$value = false;
				break;
			case 'serxsl':
				// хотим посмотреть xsl шаблон
				self::$responseType = 'xsl';
				$value = false;
				break;
			case 'serxmlc':
				// мы хотим получить контент в xml
				self::$responseType = 'xmlc';
				$value = false;
				break;
			case 'serxslc':
				// хотим посмотреть xsl шаблон
				self::$responseType = 'xslc';
				$value = false;
				break;
			case 'logout':
				// выходим
				$value = false;
				if (!isset($_POST['writemodule']))
					$_POST['writemodule'] = 'LogoutWriteModule';
				break;
			case 'emailconfirm': // на эту страницу попадают из письма, соответственно нужно
				// специально указать, что будет использоваться модуль записи
				if (!isset($_POST['writemodule']))
					$_POST['writemodule'] = 'EmailConfirmWriteModule';
				break;
		}
		return $value;
	}

	/*
	 *
	 */

	// обрабатываем строку запрос в соотв с настройками (корень сайта не в корне домена и т.п.)
	private static function processRuri($uri) {
		$root = Config::need('www_absolute_path', false);
		if ($root) {
			$uri = str_replace($root, '', $uri);
		}
		return $uri;
	}

	/*
	 *
	 */

	private static function getPage(array $path, $deep = false) {

		$parts = array();
		foreach ($path as $path_part) {
			$part = '';
			if ($path_part && self::specialParameters($path_part)) {
				if (is_numeric($path_part)) {
					$part = '%d';
				} else {
					$part = $path_part;
				}
				$parts[] = $part;
			}
		}
		$path_mapped = implode('/', $parts);
		if (!$deep) {
			$end = count($parts) - 2;
		}else
			$end = -1;

		$path_mapped = $path_mapped ? $path_mapped : '/';

		if (!isset(Map::$map[$path_mapped])) {
			$path_mapped_reduce = $parts;
			for ($i = count($parts); $i > $end; $i--) {
				self::$real_path++;
				// path with %s/some
				$pt = $path_mapped_reduce;
				if (isset($pt[count($pt) - 2])) {
					$pt[count($pt) - 2] = '%s';
					$reduced_uri = implode('/', $pt);
					if ($structureFile = self::getPageByPath($reduced_uri, $path_mapped)) {
						self::$real_path--;
						return $structureFile;
					}
				}
				unset($path_mapped_reduce[$i]);
				// path with *
				$reduced_uri = implode('/', $path_mapped_reduce) . '/*';
				if ($structureFile = self::getPageByPath($reduced_uri, $path_mapped)) {
					self::$real_path--;
					return $structureFile;
				}
				// path with %s
				$reduced_uri = implode('/', $path_mapped_reduce) . '/%s';
				if ($structureFile = self::getPageByPath($reduced_uri, $path_mapped)) {
					self::$real_path--;
					return $structureFile;
				}
				// path
				$reduced_uri = implode('/', $path_mapped_reduce);
				if ($structureFile = self::getPageByPath($reduced_uri, $path_mapped)) {
					return $structureFile;
				}
			}
		} else {
			return Map::$map[$path_mapped];
		}
		return 'errors/p404.xml';
	}

	private static function getPageByPath($path, $path_mapped) {
		$path = $path ? $path : '/';
		self::$path_history.=$path . '<br/>';
		if ($path == '/') {
			if (count($path_mapped) > 0) {
				return 'errors/p404.xml';
			}
		}
		if (isset(Map::$sinonim[$path])) {
			return Map::$map[Map::$sinonim[$path]];
		}
		if (isset(Map::$map[$path])) {
			return Map::$map[$path];
		}
		return false;
	}

	public static function headerCookie($name, $value, $expire, $path, $domain, $secure, $httponly) {
		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}

}