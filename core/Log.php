<?php

class Log {

	private static $html_log = array();
	private static $last_action = false;
	private static $actions = array();

	public static function timing($actionName) {
		global $dev_mode;
		if (!$dev_mode)
			return;
		
		if (!isset(self::$actions[$actionName]) || is_array(self::$actions[$actionName])) {
			self::$actions[$actionName] = microtime(true);
		}else
			self::$actions[$actionName] = array((microtime(true) - self::$actions[$actionName]), $actionName);
	}

	public static function timingplus($actionName) {		
		global $dev_mode;
		if (!$dev_mode)
			return;
		if (!isset(self::$actions[$actionName])) {
			self::$actions[$actionName] = microtime(true);
		} else {
			self::$actions[$actionName.'_sum'] = array((microtime(true) - self::$actions[$actionName]), $actionName);
			unset(self::$actions[$actionName]);
		}
	}

	public static function logHtml($message) {
		global $dev_mode;
		if (!$dev_mode)
			return;
		self::$html_log[] = '<!-- ' . $message . ' -->';
	}

	public static function getHtmlLog() {
		global $dev_mode;
		if (!$dev_mode)
			return;
		$total = self::$actions['total'][0]; 
		$sum = - $total;
		foreach (self::$actions as &$act) {
			$sum +=$act[0];
			$act = sprintf('%.5f', $act[0]) . '[' . $act[1] . ']';
		}
		return implode("\n", self::$html_log) . "\n<!-- \n" . implode("\n", self::$actions) . "\n".sprintf('%.5f', $sum)." ms calced /".sprintf('%.5f', $total)."ms total\n-->";
	}

}