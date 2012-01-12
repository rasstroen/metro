<?php

class SeriesWriteModule extends BaseWriteModule {

	function _glue() {
		global $current_user;
		$current_user->can_throw('books_edit');
		$id1 = isset(Request::$post['serie1_id']) ? (int) Request::$post['serie1_id'] : false;
		$id2 = isset(Request::$post['serie2_id']) ? (int) Request::$post['serie2_id'] : false;
		$is_1_main = isset(Request::$post['is_main']) ? true : false;

		if ($is_1_main) {
			$main_sid = $id1;
			$slave_sid = $id2;
		} else {
			$main_sid = $id2;
			$slave_sid = $id1;
		}

		if (!$main_sid || !$slave_sid)
			throw new Exception('illegal ids');


		$query = 'SELECT * FROM `series` WHERE `id` IN (' . $id1 . ',' . $id2 . ')';
		$series = Database::sql2array($query, 'id');
		if (count($series) != 2) {
			throw new Exception('illegal series');
		}

		if ($series[$main_sid]['is_s_duplicate']) {
			throw new Exception($main_sid . ' is duplicate of ' . $series[$main_sid]['is_s_duplicate']);
		}

		Database::query('START TRANSACTION');
		// set new parent_id for all subseries for slave
		$query = 'SELECT `id` FROM `series` WHERE `id_parent`=' . $slave_sid;
		$changed_parents = Database::sql2array($query, 'id');

		if (count($changed_parents)) {
			$query = 'UPDATE `series` SET `id_parent`=' . $main_sid . ' WHERE `id` IN (' . array_keys($changed_parents) . ')';
			Database::query($query);
		}
		// set new serie_id for all books for slave
		$query = 'SELECT `id_book` FROM `book_series` WHERE `id_series`=' . $slave_sid;
		$books = Database::sql2array($query, 'id_book');

		$query = 'UPDATE `book_series` SET `id_series`=' . $main_sid . ' WHERE `id_series`=' . $slave_sid;
		Database::query($query);

		// slave serie got is_s_duplicate flag
		$query = 'UPDATE `series` SET `is_s_duplicate`=' . $main_sid . ' WHERE `id`=' . $slave_sid;
		Database::query($query);
		// for main and slave serie - set new books count
		/* @todo */

		// writing logs
		// for each book - set new serie

		SerieLog::addGlueLog($main_sid, $slave_sid, array_keys($books), array_keys($changed_parents));
		$id_log = SerieLog::saveLog($slave_sid, BookLog::TargetType_serie, $current_user->id, BiberLog::BiberLogType_serieGlue, $copy = false);
		SerieLog::saveLogLink($id_log, $main_sid, BookLog::TargetType_serie, $current_user->id, BiberLog::BiberLogType_serieGlue, $copy = 1);
		// for subseries


		if ($books) {
			Books::getInstance()->getByIdsLoaded(array_keys($books));
			foreach ($books as $bid => $data) {
				SerieLog::saveLogLink($id_log, $bid, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_serieGlue, $copy = 1);
			}
		}
		// for slave serie subseries - set new parent_id
		if ($changed_parents) {
			foreach ($changed_parents as $sid => $data) {
				SerieLog::saveLogLink($id_log, $sid, BookLog::TargetType_serie, $current_user->id, BiberLog::BiberLogType_serieGlue, $copy = 1);
			}
		}
		$current_user->gainActionPoints(BiberLog::$actionTypes[BiberLog::BiberLogType_serieGlue], $main_sid, BiberLog::TargetType_serie);
		Database::query('COMMIT');

		ob_end_clean();
		$search = Search::getInstance();
		/* @var $search Search */
		$search->setSerieToFullUpdate($slave_sid);
		$search->setSerieToFullUpdate($main_sid);
		header('Location:' . Config::need('www_path') . '/s/' . $main_sid);
		exit();
	}

	function _new() {
		global $current_user;
		$current_user->can_throw('books_edit');
		$parent_id = isset(Request::$post['id_parent']) ? Request::$post['id_parent'] : false;
		$parent_id = max(0, (int) $parent_id);


		$title = isset(Request::$post['title']) ? Request::$post['title'] : false;
		$description = isset(Request::$post['description']) ? Request::$post['description'] : false;


		if ($parent_id) {
			$query = 'SELECT `id` FROM `series` WHERE `id`=' . $parent_id;
			if (!Database::sql2single($query))
				throw new Exception('No such parent');
		}

		if (!$title)
			throw new Exception('Empty title');

		$description = prepare_review($description);
		$title = prepare_review($title, '');
		$new = array(
		    'description' => $description,
		    'title' => $title,
		    'id_parent' => (int) $id_parent,
		);
		$old = array(
		    'description' => '',
		    'title' => '',
		    'id_parent' => 0,
		);

		Database::query('START TRANSACTION');
		$query = 'INSERT INTO `series` SET `id_parent`=' . $parent_id . ',`title`=' . Database::escape($title) . ', `description`=' . Database::escape($description);
		Database::query($query);
		$id = Database::lastInsertId();
		if (!$id)
			throw new Exception('Cant save serie');

		SerieLog::addLog($new, $old, $id);
		SerieLog::saveLog($id, BookLog::TargetType_serie, $current_user->id, BiberLog::BiberLogType_serieNew);
		Database::query('COMMIT');

		$event = new Event();
		$event->event_SeriesAdd($current_user->id, $id);
		$event->push();

		ob_end_clean();
		header('Location:' . Config::need('www_path') . '/s/' . $id);
		Database::query('COMMIT');
		$current_user->gainActionPoints(BiberLog::$actionTypes[BiberLog::BiberLogType_serieNew], $sid, BiberLog::TargetType_serie);

		$search = Search::getInstance();
		/* @var $search Search */
		$search->setSerieToFullUpdate($id);
		exit();
	}

	function write() {
		global $current_user;
		if (!$current_user->authorized)
			throw new Exception('Access Denied');

		$id = isset(Request::$post['id']) ? Request::$post['id'] : 0;
		$id = max(0, (int) $id);
		if (isset(Request::$post['serie1_id'])) {
			$this->_glue();
			return;
		}
		if (!$id) {
			$this->_new();
			return;
		}

		$query = 'SELECT * FROM `series` WHERE `id`=' . $id;
		$old = Database::sql2row($query);
		if (!$old || !$old['id'])
			throw new Exception('no such serie #' . $id);

		$parent_id = isset(Request::$post['id_parent']) ? Request::$post['id_parent'] : false;
		$parent_id = max(0, (int) $parent_id);
		if (!$id)
			throw new Exception('Illegal id');

		$title = isset(Request::$post['title']) ? Request::$post['title'] : false;
		$description = isset(Request::$post['description']) ? Request::$post['description'] : false;


		if ($parent_id == $id)
			throw new Exception('Illegal parent');

		if ($parent_id) {
			$query = 'SELECT `id` FROM `series` WHERE `id`=' . $parent_id;
			if (!Database::sql2single($query))
				throw new Exception('No such parent');
		}

		if (!$title)
			throw new Exception('Empty title');

		$description = prepare_review($description);
		$title = prepare_review($title, '');
		$new = array(
		    'description' => $description,
		    'title' => $title,
		    'id_parent' => (int) $id_parent,
		);
		Database::query('START TRANSACTION');
		SerieLog::addLog($new, $old, $id);
		SerieLog::saveLog($id, BookLog::TargetType_serie, $current_user->id, BiberLog::BiberLogType_serieEdit);

		$query = 'UPDATE `series` SET `id_parent`=' . $parent_id . ',`title`=' . Database::escape($title) . ', `description`=' . Database::escape($description) . ' WHERE `id`=' . $id;
		Database::query($query);
		Database::query('COMMIT');

		$event = new Event();
		$event->event_SeriesEdit($current_user->id, $id);
		$event->push();

		$search = Search::getInstance();
		/* @var $search Search */
		$search->setSerieToFullUpdate($id);
	}

}