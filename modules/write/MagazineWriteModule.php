<?php

class MagazineWriteModule extends BaseWriteModule {

	function _new() {
		global $current_user;
		Request::$post['lang_code'] = Config::$langs[Request::$post['lang_code']];

		$fields = array(
		    'title' => 'title',
		    'isbn' => 'ISBN',
		    'lang_code' => 'id_lang', //lang_code
		    'annotation' => 'annotation'
		);


		$to_update = array();


		foreach ($fields as $field => $magazinefield) {
			if (!isset(Request::$post[$field])) {
				throw new Exception('field missed #' . $field);
			}

			$to_update[$magazinefield] = Request::$post[$field];
		}

		$q = array();
		foreach ($to_update as $field => &$value) {
			if (in_array($field, array('ISBN', 'year'))) {
				$value = is_numeric($value) ? $value : 0;
			}
			$q[] = '`' . $field . '`=' . Database::escape($value) . '';
		}

		if (count($q)) {
			$query = 'INSERT INTO `magazines` SET ' . implode(',', $q);
			Database::query($query);
			$lid = Database::lastInsertId();
			MagazineLog::addLog($to_update, array(), $lid);
			MagazineLog::saveLog($lid, BookLog::TargetType_magazine, $current_user->id, BiberLog::BiberLogType_magazineNew);

			$search = Search::getInstance();
			/* @var $search Search */
			$search->setMagazineToFullUpdate($lid);
		}

		if (isset($_FILES['cover']) && $_FILES['cover']['tmp_name']) {
			$folder = Config::need('static_path') . '/upload/mcovers/' . (ceil($lid / 5000));
			@mkdir($folder);
			chmod($folder, 755);
			$filename = $folder . '/' . $lid . '.jpg';
			$upload = new UploadAvatar($_FILES['cover']['tmp_name'], 100, 100, "simple", $filename);
			if ($upload->out)
				$to_update['is_cover'] = 1;
			else {
				throw new Exception('cant copy file to ' . $filename, 100);
			}
		}


		ob_end_clean();
		header('Location:' . Config::need('www_path') . '/m/' . $lid);
		exit();
	}

	function write() {
		global $current_user;
		if (!$current_user->authorized)
			throw new Exception('Access Denied');
		$id = isset(Request::$post['id']) ? (int) Request::$post['id'] : false;
		if (!$id) {
			$this->_new();
			return;
		}


		$magazine = new Magazine($id);
		$magazine->load();

		Request::$post['lang_code'] = Config::$langs[Request::$post['lang_code']];

		$fields = array(
		    'title' => 'title',
		    'isbn' => 'ISBN',
		    'lang_code' => 'id_lang', //lang_code
		    'annotation' => 'annotation'
		);


		$to_update = array();
		if (isset($_FILES['cover']) && $_FILES['cover']['tmp_name']) {
			$folder = Config::need('static_path') . '/upload/mcovers/' . (ceil($magazine->id / 5000));
			@mkdir($folder);
			chmod($folder, 755);
			$filename = $folder . '/' . $magazine->id . '.jpg';
			$upload = new UploadAvatar($_FILES['cover']['tmp_name'], 100, 100, "simple", $filename);
			if ($upload->out)
				$to_update['is_cover'] = 1;
			else {
				throw new Exception('cant copy file to ' . $filename, 100);
			}
		}

		foreach ($fields as $field => $magazinefield) {
			if (!isset(Request::$post[$field])) {
				throw new Exception('field missed #' . $field);
			}
			if ($magazine->data[$magazinefield] !== Request::$post[$field]) {
				$to_update[$magazinefield] = Request::$post[$field];
			}
		}

		$q = array();
		foreach ($to_update as $field => &$value) {
			if (in_array($field, array('ISBN', 'year'))) {
				$value = is_numeric($value) ? $value : 0;
			}
			$q[] = '`' . $field . '`=' . Database::escape($value) . '';
		}

		if (count($q)) {
			$query = 'UPDATE `magazines` SET ' . implode(',', $q) . ' WHERE `id`=' . $magazine->id;
			Database::query($query);
			MagazineLog::addLog($to_update, $magazine->data, $magazine->id);
			MagazineLog::saveLog($magazine->id, BookLog::TargetType_magazine, $current_user->id, BiberLog::BiberLogType_magazineEdit);

			$search = Search::getInstance();
			/* @var $search Search */
			$search->setMagazineToFullUpdate($magazine->id);
		}
		ob_end_clean();
		header('Location:' . Config::need('www_path') . '/m/' . $magazine->id);
		exit();
	}

}