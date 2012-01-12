<?php

class AuthorWriteModule extends BaseWriteModule {

	private static $cover_sizes = array(
	    array(50, 65, true), // small_
	    array(70, 95, true), // default_
	    array(140, 190, true), // big_
	    array(500, 500, true), // orig_
	);

	function newAuthor() {
		global $current_user;
		$current_user->can_throw('books_edit');
		// добавляем книгу
		$fields = array(
		    'lang_code' => 'author_lang', //lang_code
		    'bio' => 'bio',
		    'first_name' => 'first_name',
		    'middle_name' => 'middle_name',
		    'last_name' => 'last_name',
		    //'id_user' => 'id_user',
		    'homepage' => 'homepage',
		    'wiki_url' => 'wiki_url',
		    'date_birth' => 'date_birth',
		    'date_death' => 'date_death'
		);

		Request::$post['lang_code'] = Config::$langs[Request::$post['lang_code']];

		if (!Request::$post['first_name'] || !Request::$post['last_name'])
			throw new Exception('no author\'s name');
		if (!Request::$post['lang_code'])
			throw new Exception('no author\'s language');
		$to_update = array();

		foreach ($fields as $field => $personfield) {
			if (!isset(Request::$post[$field])) {
				throw new Exception('field missed #' . $field);
			}
			$to_update[$personfield] = Request::$post[$field];
		}

		$q = array();
		if (count($to_update))
			$to_update['authorlastSave'] = time();

		foreach ($to_update as $field => $value) {
			if ($field == 'date_birth' || $field == 'date_death') {
				$value = getDateFromString($value);
			}
			$person = new Person();
			if ($field == 'bio') {
				list($full, $short) = $person->processBio($value);
				$q[] = '`bio`=' . Database::escape($full) . '';
				$q[] = '`short_bio`=' . Database::escape($short) . '';
			} else {
				$q[] = '`' . $field . '`=' . Database::escape($value) . '';
			}
		}

		if (count($q)) {
			$q[] = '`a_add_time`=' . time();
			$query = 'INSERT INTO `persons` SET ' . implode(',', $q);
			Database::query($query);
			$lid = Database::lastInsertId();
			if ($lid) {
				if (isset($_FILES['picture']) && $_FILES['picture']['tmp_name']) {
					$folder = Config::need('static_path') . '/upload/authors/' . (ceil($lid / 5000));
					@mkdir($folder);


					$query = 'INSERT INTO `person_covers` SET `id_person`=' . $lid;
					Database::query($query);
					$cover_id = Database::lastInsertId();

					$filename_normal = $folder . '/default_' . $lid . '_' . $cover_id . '.jpg';
					$filename_small = $folder . '/small_' . $lid . '_' . $cover_id . '.jpg';
					$filename_big = $folder . '/big_' . $lid . '_' . $cover_id . '.jpg';
					$filename_orig = $folder . '/orig_' . $lid . '_' . $cover_id . '.jpg';

					$to_update['has_cover'] = $cover_id;

					$thumb = new Thumb();
					$thumb->createThumbnails($_FILES['picture']['tmp_name'], array($filename_small, $filename_normal, $filename_big , $filename_orig), self::$cover_sizes);


					$query = 'UPDATE `persons` SET `has_cover`=' . $cover_id . ' WHERE `id`=' . $lid;
					Database::query($query);
				}
				unset($to_update['authorlastSave']);
				PersonLog::addLog($to_update, array(), $lid);
				PersonLog::saveLog($lid, BookLog::TargetType_person, $current_user->id, BiberLog::BiberLogType_personNew);

				$event = new Event();
				$event->event_AuthorAdd($current_user->id, $lid);
				$event->push();

				$search = Search::getInstance();
				/* @var $search Search */
				$search->setAuthorToFullUpdate($lid);

				ob_end_clean();
				Persons::getInstance()->dropCache($lid);
				$current_user->gainActionPoints(BiberLog::$actionTypes[BiberLog::BiberLogType_personNew], $lid, BiberLog::TargetType_person);
				header('Location:' . Config::need('www_path') . '/a/' . $lid);
				exit();
			}
		}
	}

	function write() {
		global $current_user;
		$current_user->can_throw('books_edit');
		$id = isset(Request::$post['id']) ? (int) Request::$post['id'] : false;

		if (!$id) {
			$this->newAuthor();
			return;
		}


		$person = Persons::getInstance()->getByIdLoaded($id);
		if (!$person)
			return;
		$savedData = $person->data;
		/* @var $book Book */

		$fields = array(
		    'lang_code' => 'author_lang', //lang_code
		    'bio' => 'bio',
		    'first_name' => 'first_name',
		    'middle_name' => 'middle_name',
		    'last_name' => 'last_name',
		    //'id_user' => 'id_user',
		    'homepage' => 'homepage',
		    'wiki_url' => 'wiki_url',
		    'date_birth' => 'date_birth',
		    'date_death' => 'date_death'
		);

		if (!Request::$post['first_name'] || !Request::$post['last_name'])
			throw new Exception('no author\'s name');
		if (!Request::$post['lang_code'])
			throw new Exception('no author\'s language');

		Request::$post['lang_code'] = Config::$langs[Request::$post['lang_code']];
		$to_update = array();
		if (isset($_FILES['picture']) && $_FILES['picture']['tmp_name']) {
			$folder = Config::need('static_path') . '/upload/authors/' . (ceil($person->id / 5000));
			@mkdir($folder);

			// inserting new cover
			$query = 'INSERT INTO `person_covers` SET `id_person`=' . $person->id;
			Database::query($query);
			$cover_id = Database::lastInsertId();

			// generating file names
			$filename_normal = $folder . '/default_' . $person->id . '_' . $cover_id . '.jpg';
			$filename_small = $folder . '/small_' . $person->id . '_' . $cover_id . '.jpg';
			$filename_big = $folder . '/big_' . $person->id . '_' . $cover_id . '.jpg';
			$filename_orig= $folder . '/orig_' . $person->id . '_' . $cover_id . '.jpg';

			$to_update['has_cover'] = $cover_id;

			$thumb = new Thumb();
			$thumb->createThumbnails($_FILES['picture']['tmp_name'], array($filename_small, $filename_normal, $filename_big , $filename_orig), self::$cover_sizes);

			if ($savedData['has_cover'])
				$current_user->gainActionPoints('authors_add_cover', $person->id, BiberLog::TargetType_person);
			else
				$current_user->gainActionPoints('authors_edit_cover', $person->id, BiberLog::TargetType_person);
		}

		foreach ($fields as $field => $personfield) {
			if (!isset(Request::$post[$field])) {
				throw new Exception('field missed #' . $field);
			}
			if ($person->data[$personfield] != Request::$post[$field]) {
				$to_update[$personfield] = Request::$post[$field];
			}
		}

		$q = array();
		if (count($to_update))
			$to_update['authorlastSave'] = time();


		foreach ($to_update as $field => &$value) {
			if ($field == 'date_birth' || $field == 'date_death') {
				$value = getDateFromString($value);
			}

			if ($field == 'bio') {
				list($full, $short) = $person->processBio($value);
				$q[] = '`bio`=' . Database::escape($full) . '';
				$q[] = '`short_bio`=' . Database::escape($short) . '';
				$value = $person->data['bio'] = $full;
				$person->data['short_bio'] = $short;
			} else {
				$q[] = '`' . $field . '`=' . Database::escape($value) . '';
				$person->data[$field] = $value;
			}
		}

		if (count($q)) {
			$query = 'UPDATE `persons` SET ' . implode(',', $q) . ' WHERE `id`=' . $person->id;
			Database::query($query);
			unset($to_update['authorlastSave']);
			PersonLog::addLog($to_update, $savedData, $person->id);
			PersonLog::saveLog($person->id, BiberLog::TargetType_person, $current_user->id, BiberLog::BiberLogType_personEdit);
			Persons::getInstance()->dropCache($person->id);
			$current_user->gainActionPoints(BiberLog::$actionTypes[BiberLog::BiberLogType_personEdit], $person->id, BiberLog::TargetType_person);

			$search = Search::getInstance();
			/* @var $search Search */
			$search->setAuthorToFullUpdate($person->id);
		}
		ob_end_clean();
		header('Location:' . Config::need('www_path') . '/a/' . $person->id);
		exit();
	}

}