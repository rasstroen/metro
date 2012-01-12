<?php

class RightholdersWriteModule extends BaseWriteModule {

	function write() {
		global $current_user;
		/* @var $current_user CurrentUser */
		if (!($id = Request::post('id'))) {
			$this->_new();
			return;
		}

		$title = trim(Request::post('title'));
		if (!$title)
			throw new Exception('title missed');

		$query = 'UPDATE `rightholders` SET `title`=' . Database::escape($title) . ' WHERE `id`=' . $id;
		Database::query($query);
		@ob_end_clean();
		header('Location: /admin/rightholders/' . $id);
		exit();
	}

	function _new() {
		$title = trim(Request::post('title'));
		if (!$title)
			throw new Exception('title missed');

		$query = 'INSERT INTO `rightholders` SET `title`=' . Database::escape($title);
		Database::query($query);
		@ob_end_clean();
		header('Location: /admin/rightholders/' . Database::lastInsertId());
		exit();
	}

}