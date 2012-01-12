<?php

class LogWriteModule extends BaseWriteModule {

	function write() {
		global $current_user;
		/* @var $current_user CurrentUser */
		$current_user->can_throw('logs_view');
		$log_array = Request::$post['log'];
		$to_parse_log = array();
		foreach ($log_array as $logid => $on) {
			$logid = max(0, (int) $logid);
			$to_parse_log[$logid] = $logid;
		}
		if (!count($to_parse_log))
			throw new Exception('no log to process');

		$query = 'SELECT * FROM `biber_log` WHERE `id` IN(' . implode(',', $to_parse_log) . ')';
		$logdata = Database::sql2array($query);

		if (!$logdata || !count($logdata))
			throw new Exception('no log in db to process');

		foreach ($logdata as $data)
			BiberLog::undo($data['id'], $data, isset(Request::$post['apply']) ? true : false);
	}

}