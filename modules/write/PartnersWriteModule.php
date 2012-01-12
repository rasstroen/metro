<?php

class PartnersWriteModule extends BaseWriteModule {

	function write() {
		global $current_user;
		/* @var $current_user CurrentUser */
		if (!($id = Request::post('id'))) {
			$this->_new();
			return;
		}

		$title = trim(Request::post('title'));
		$pid = trim(Request::post('pid'));
		if (!$title)
			throw new Exception('title missed');
		if (!$pid)
			throw new Exception('pid missed');

		$query = 'UPDATE `partners` SET `pid`='.Database::escape($pid).',`title`=' . Database::escape($title) . ' WHERE `id`=' . $id;
		Database::query($query);
		@ob_end_clean();
		header('Location: /admin/partners/' . $id);
		exit();
	}

	function _new() {
		$title = trim(Request::post('title'));
		$pid = trim(Request::post('pid'));
		if (!$title)
			throw new Exception('title missed');
		if (!$pid)
			throw new Exception('pid missed');

		$query = 'INSERT INTO `partners` SET `pid`='.Database::escape($pid).',`title`=' . Database::escape($title);
		Database::query($query);
		@ob_end_clean();
		header('Location: /admin/partners/' . Database::lastInsertId());
		exit();
	}

}