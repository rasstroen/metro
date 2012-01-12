<?php

class PostWrite {

	private static $params = array();

	public static function process($moduleName) {
		$filename = Config::need('writemodules_path') . '/' . $moduleName . '.php';
		if (!is_readable($filename)) {
			throw new Exception('no module#' . $moduleName . ' can accept writing', Error::E_WRITEMODULE_MISSED);
		}
		$module = new $moduleName;
		$module->write();
	}

	public static function getWriteParameters($moduleName) {
		$moduleName.='_module';
		return isset(self::$params[$moduleName]) ? self::$params[$moduleName] : array();
	}

	public static function setWriteParameter($moduleName, $name, $value) {
		self::$params[$moduleName][$name] = $value;
	}

}