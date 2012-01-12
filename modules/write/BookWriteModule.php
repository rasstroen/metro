<?php

class BookWriteModule extends BaseWriteModule {

	private static $cover_sizes = array(
	    array(50, 65, true), // small_
	    array(70, 95, true), // default_
	    array(140, 190, true), // big_
	    array(500, 500, true), // orig_
	);

	function newBook() {
// добавляем книгу
		global $current_user;
		/* @var $current_user CurrentUser */
		$fields = array(
		    'title' => 'title',
		    'subtitle' => 'subtitle',
		    'isbn' => 'ISBN',
		    'year' => 'year',
		    'lang_code' => 'id_lang', //lang_code
		    'annotation' => 'description',
		    'rightholder' => 'id_rightholder',
		);

		Request::$post['lang_code'] = Config::$langs[Request::$post['lang_code']];
		Request::$post['title'] = trim(prepare_review(Request::$post['title'], ''));
		Request::$post['annotation'] = trim(prepare_review(Request::$post['annotation'], false, '<img>'));

		if (!Request::$post['title']) {
			throw new Exception('title missed');
		}

		foreach ($fields as $field => $bookfield) {
			if (!isset(Request::$post[$field])) {
				throw new Exception('field missed #' . $field);
			}

			$to_update[$bookfield] = Request::$post[$field];
		}


		if (Request::$post['n'] && Request::$post['m']) {
			$to_update['book_type'] = Book::BOOK_TYPE_MAGAZINE;
			$to_update['title'] = Database::sql2single('SELECT `title` FROM `magazines` WHERE `id`=' . (int) Request::$post['m']);
		}

		$q = array();
		foreach ($to_update as $field => $value) {
			if (in_array($field, array('year'))) {
				$value = (int) $value;
			}
			$q[] = '`' . $field . '`=' . Database::escape($value) . '';
		}

		if (count($q)) {
			$q[] = '`add_time`=' . time();
			$query = 'INSERT INTO `book` SET ' . implode(',', $q);
			Database::query($query);
			if ($lid = Database::lastInsertId()) {
				if (Request::$post['n'] && Request::$post['m']) {
					// журнал - вставляем
					$query = 'INSERT INTO `book_magazines` SET `id_book`=' . $lid . ',id_magazine=' . (int) Request::$post['m'] . ',`year`=' . $to_update['year'] . ',`n`=' . (int) Request::$post['n'];
					Database::query($query, false);
					$query = 'UPDATE `magazines` SET `books_count`=(SELECT COUNT(1) FROM `book_magazines` WHERE `id_magazine`=' . (int) Request::$post['m'] . ')
						WHERE `id`=' . (int) Request::$post['m'];
					Database::query($query);
				}

				if (isset(Request::$post['author_id']) && Request::$post['author_id']) {
					$query = 'INSERT INTO `book_persons` SET `id_book`=' . $lid . ',`id_person`=' . (int) Request::$post['author_id'] . ',`person_role`=' . Book::ROLE_AUTHOR;
					Database::query($query, false);
					BookLog::addLog(array('id_person' => (int) Request::$post['author_id'], 'person_role' => Book::ROLE_AUTHOR), array('id_person' => 0, 'person_role' => 0), $lid);
					BookLog::saveLog($lid, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookEditPerson);
					Notify::notifyAuthorNewBook((int) Request::$post['author_id'], $lid);
				}

				if (isset($_FILES['cover']) && $_FILES['cover']['tmp_name']) {
					$folder = Config::need('static_path') . '/upload/covers/' . (ceil($lid / 5000));
					@mkdir($folder);

// inserting new cover
					$query = 'INSERT INTO `book_covers` SET `id_book`=' . $lid;
					Database::query($query);
					$cover_id = Database::lastInsertId();

// generating file names
					$filename_normal = $folder . '/default_' . $lid . '_' . $cover_id . '.jpg';
					$filename_small = $folder . '/small_' . $lid . '_' . $cover_id . '.jpg';
					$filename_big = $folder . '/big_' . $lid . '_' . $cover_id . '.jpg';
					$filename_orig = $folder . '/orig_' . $lid . '_' . $cover_id . '.jpg';

					$to_update['is_cover'] = $cover_id;
					$thumb = new Thumb();
					$thumb->createThumbnails($_FILES['cover']['tmp_name'], array($filename_small, $filename_normal, $filename_big, $filename_orig), self::$cover_sizes);

					$query = 'UPDATE `book` SET `is_cover`=' . $cover_id . ' WHERE `id`=' . $lid;
					Database::query($query);

					$current_user->gainActionPoints('books_add_cover', $lid, BiberLog::TargetType_book);
				}

// file loading
				if (isset($_FILES['file']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name']) {
					$filetype_ = explode('.', $_FILES['file']['name']);
					$filetype_ = isset($filetype_[count($filetype_) - 1]) ? $filetype_[count($filetype_) - 1] : '';
					$fts = Config::need('filetypes');
					$filetype = false;
					foreach ($fts as $ftid => $ftname)
						if ($ftname == $filetype_)
							$filetype = $ftid;


					if (!$filetype)
						throw new Exception('wrong filetype:' . $filetype_);

					$destinationDir = Config::need('files_path') . DIRECTORY_SEPARATOR . getBookFileDirectory($book->id, $filetype);
					@mkdir($destinationDir, 0755);
// добавляем запись в базу
					$filesize = $_FILES['file']['size'];

					$query = 'INSERT INTO `book_files` SET
				`id_book`=' . $book->id . ',
				`filetype`=' . $filetype . ',
				`id_file_author`=' . $current_user->id . ',
				`modify_time`=' . time() . ',
				`filesize`=' . $filesize;
					Database::query($query);
					$id_file = Database::lastInsertId();
					BookLog::addLog(array('id_file' => $id_file, 'filetype' => $filetype, 'id_file_author' => $current_user->id, 'filesize' => $filesize), array('id_file' => 0, 'filetype' => 0, 'id_file_author' => 0, 'filesize' => 0), $lid);
					$to_update_ = array();
					if ($id_file) {
						$to_update_['id_main_file'] = $id_file;

						$destinationFile = getBookFilePath($id_file, $book->id, $filetype, Config::need('files_path'));
						if (!move_uploaded_file($_FILES['file']['tmp_name'], $destinationFile))
							throw new Exception('Cant save file to ' . $destinationFile);


						if ($filetype == 1) { // FB2
							$parser = new FB2Parser($destinationFile);
							$parser->parseDescription();
							$toc = $parser->getTOCHTML();
							$to_update_['description'] = $parser->getProperty('annotation');
							$to_update_['title'] = $parser->getProperty('book-title');
							$to_update_['table_of_contents'] = $toc;
						}
						$q = array();
						foreach ($to_update_ as $field => &$value) {
							if (in_array($field, array('year'))) {
								$value = is_numeric($value) ? $value : 0;
							}
							$q[] = '`' . $field . '`=' . Database::escape($value) . '';
						}
						if (count($q)) {
							$query = 'UPDATE `book` SET ' . implode(',', $q) . ' WHERE `id`=' . $lid;
							Database::query($query);
							BookLog::addLog($to_update_, $book->data, $book->id);
						}
						$to_update = array_merge($to_update, $to_update_);
					}
				} else {
// it is no any files!
					Ocr::afterBookCreate($lid, $current_user->id);
				}

				BookLog::addLog($to_update, array(), $lid);
				BookLog::saveLog($lid, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookNew);

				ob_end_clean();
				$event = new Event();
				$event->event_BooksAdd($current_user->id, $lid);
				$event->push();
				$search = Search::getInstance();
				/* @var $search Search */
				$search->updateBook(new Book($lid));
				Books::getInstance()->dropCache($lid);
				header('Location:' . Config::need('www_path') . '/b/' . $lid);
				Database::query('COMMIT');

				$current_user->gainActionPoints('books_new_nofile', $lid, BiberLog::TargetType_book);
				exit();
			}
		}
	}

	function write() {
		global $current_user;
		$points_gained = false;
		/* @var $current_user CurrentUser */
		Database::query('START TRANSACTION');
		$current_user->can_throw('books_edit');

		if (!isset(Request::$post['lang_code']) || !(Request::$post['lang_code']))
			throw new Exception('field missed #lang_code');

		$id = isset(Request::$post['id']) ? (int) Request::$post['id'] : false;


		if (Request::post('isbn')) {
			Request::$post['isbn'] = extractISBN(Request::$post['isbn']);
		}


		if (!$id) {
			$this->newBook();
			return;
		}


		$books = Books::getInstance()->getByIdsLoaded(array($id));
		$book = is_array($books) ? $books[$id] : false;
		if (!$book)
			return;
		/* @var $book Book */

		$fields = array(
		    'title' => 'title',
		    'subtitle' => 'subtitle',
		    'isbn' => 'ISBN',
		    'year' => 'year',
		    'lang_code' => 'id_lang', //lang_code
		    'annotation' => 'description',
		    'rightholder' => 'id_rightholder',
		);

		Request::$post['lang_code'] = Config::$langs[Request::$post['lang_code']];
		Request::$post['annotation'] = trim(prepare_review(Request::$post['annotation'], false, '<img>'));
		Request::$post['title'] = trim(prepare_review(Request::$post['title'], ''));
		Request::$post['year'] = (int) Request::$post['year'];

		$magazineData = array();
		if ($book->data['book_type'] == Book::BOOK_TYPE_MAGAZINE) {
			$magazineData = Database::sql2row('SELECT * FROM `magazines` M LEFT JOIN book_magazines BM ON BM.id_magazine=M.id WHERE BM.id_book=' . $book->id);
			$book->data['n'] = max(0, $magazineData['n']);
			$book->data['year'] = $magazineData['year'];
			Request::$post['n'] = (isset(Request::$post['n']) && Request::$post['n']) ? Request::$post['n'] : $magazineData['n'];
		}
		$to_update_m = array();


		$to_update = array();
		if (isset(Request::$post['quality'])) {
			if ($book->data['quality'] != (int) Request::$post['quality'])
				$to_update['quality'] = (int) Request::$post['quality'];
		}
		if (isset(Request::$post['n'])) {
			if (isset($book->data['n']) && $book->data['n'] != (int) Request::$post['n']) {
				$to_update_m['n'] = (int) Request::$post['n'];
				Request::$post['title'] = $magazineData['title'];
				Request::$post['subtitle'] = '№ ' . $to_update_m['n'] . ' за ' . Request::$post['year'] . ' год';
			}
			if (isset($book->data['year']) && $book->data['year'] != (int) Request::$post['year']) {
				$to_update_m['n'] = (int) Request::$post['n'];
				Request::$post['title'] = $magazineData['title'];
				Request::$post['subtitle'] = '№ ' . $to_update_m['n'] . ' за ' . Request::$post['year'] . ' год';
			}
		}
		if (isset($_FILES['cover']) && $_FILES['cover']['tmp_name']) {
			$folder = Config::need('static_path') . '/upload/covers/' . (ceil($book->id / 5000));
			@mkdir($folder);


// inserting new cover
			$query = 'INSERT INTO `book_covers` SET `id_book`=' . $book->id;
			Database::query($query);
			$cover_id = Database::lastInsertId();

// generating file names
			$filename_normal = $folder . '/default_' . $book->id . '_' . $cover_id . '.jpg';
			$filename_small = $folder . '/small_' . $book->id . '_' . $cover_id . '.jpg';
			$filename_big = $folder . '/big_' . $book->id . '_' . $cover_id . '.jpg';
			$filename_orig = $folder . '/orig_' . $book->id . '_' . $cover_id . '.jpg';

			$to_update['is_cover'] = $cover_id;

			$thumb = new Thumb();
			$thumb->createThumbnails($_FILES['cover']['tmp_name'], array($filename_small, $filename_normal, $filename_big, $filename_orig), self::$cover_sizes);
			if ($book->data['is_cover'])
				$current_user->gainActionPoints('books_edit_cover', $book->id, BiberLog::TargetType_book);
			else
				$current_user->gainActionPoints('books_add_cover', $book->id, BiberLog::TargetType_book);
			$points_gained = true;
		}
// file loading
		if (isset($_FILES['file']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name']) {
			$filetype_ = explode('.', $_FILES['file']['name']);
			$filetype_ = isset($filetype_[count($filetype_) - 1]) ? $filetype_[count($filetype_) - 1] : '';
			$fts = Config::need('filetypes');
			$filetype = false;
			foreach ($fts as $ftid => $ftname)
				if ($ftname == $filetype_)
					$filetype = $ftid;


			if (!$filetype)
				throw new Exception('wrong filetype:' . $filetype_);

			$destinationDir = Config::need('files_path') . DIRECTORY_SEPARATOR . getBookFileDirectory($book->id, $filetype);
			@mkdir($destinationDir, 0755);
// добавляем запись в базу
			$filesize = $_FILES['file']['size'];
			$query = 'SELECT * FROM `book_files` WHERE `id_book`=' . $book->id;
			$files = Database::sql2array($query, 'filetype');
// replacing file
			if (isset($files[$filetype])) {
				$old_id_file = $files[$filetype]['id'];
				$old_id_file_author = $files[$filetype]['id_file_author'];
				$old_filesize = $files[$filetype]['filesize'];
				$query = 'DELETE FROM `book_files` WHERE `id`=' . $old_id_file;
				Database::query($query);

				$query = 'INSERT IGNORE INTO `book_files` SET
				`id_book`=' . $book->id . ',
				`filetype`=' . $filetype . ',
				`id_file_author`=' . $current_user->id . ',
				`modify_time`=' . time() . ',
				`filesize`=' . $filesize;
				Database::query($query);
				$id_file = Database::lastInsertId();
				BookLog::addLog(array('id_file' => $id_file, 'filetype' => $filetype, 'id_file_author' => $current_user->id, 'filesize' => $filesize), array('id_file' => $old_id_file, 'filetype' => 0, 'id_file_author' => $old_id_file_author, 'filesize' => $old_filesize), $book->id);
				Database::query($query);
				$current_user->gainActionPoints('books_edit_file', $book->id, BiberLog::TargetType_book);
			} else {
				$query = 'INSERT INTO `book_files` SET
				`id_book`=' . $book->id . ',
				`filetype`=' . $filetype . ',
				`id_file_author`=' . $current_user->id . ',
				`modify_time`=' . time() . ',
				`filesize`=' . $filesize;
				Database::query($query);
				$id_file = Database::lastInsertId();
				BookLog::addLog(array('id_file' => $id_file, 'filetype' => $filetype, 'id_file_author' => $current_user->id, 'filesize' => $filesize), array('id_file' => 0, 'filetype' => 0, 'id_file_author' => 0, 'filesize' => 0), $book->id);

				$current_user->gainActionPoints('books_add_file', $book->id, BiberLog::TargetType_book);
			}
			if ($id_file) {
				$points_gained = true;
				if (!$book->data['id_main_file'] || isset($files[$filetype])) {
					$to_update['id_main_file'] = $id_file;
				}
				$destinationFile = getBookFilePath($id_file, $book->id, $filetype, Config::need('files_path'));
				if (!move_uploaded_file($_FILES['file']['tmp_name'], $destinationFile))
					throw new Exception('Cant save file to ' . $destinationFile);

				// event for new File
				$event = new Event();
				$event->event_BooksAddFile($current_user->id, $book->id);
				$event->push();
				if ($filetype == 1) { // FB2
					$parser = new FB2Parser($destinationFile);
					$parser->parseDescription();
					$toc = $parser->getTOCHTML();
					Request::$post['annotation'] = $parser->getProperty('annotation');
					Request::$post['title'] = $parser->getProperty('book-title');
					$to_update['table_of_contents'] = $toc;
				}
			}
		}


		foreach ($fields as $field => $bookfield) {
			if (!isset(Request::$post[$field])) {
				throw new Exception('field missed #[' . $field . ']');
			}
			if ($book->data[$bookfield] != Request::$post[$field]) {
				$to_update[$bookfield] = Request::$post[$field];
			}
		}

		$q = array();


		foreach ($to_update as $field => &$value) {
			$q[] = '`' . $field . '`=' . Database::escape($value) . '';
		}
		$push_event = true;

		if (count($q)) {
			if ((count($to_update) == 1))
				foreach ($to_update as $kk => $vv)
					if ($kk == 'id_main_file')
						$push_event = false;
			$query = 'UPDATE `book` SET ' . implode(',', $q) . ' WHERE `id`=' . $book->id;
			Database::query($query);
			if (count($to_update_m)) {
				$to_update['n'] = $to_update_m['n'];
			}
			BookLog::addLog($to_update, $book->data, $book->id);
			foreach ($to_update as $f => $v)
				$book->data[$f] = $v;
			$search = Search::getInstance();
			/* @var $search Search */
			$search->updateBook($book);
			if ($push_event) {
				$event = new Event();
				$event->event_BooksEdit($current_user->id, $book->id);
				$event->push();
			}
			if (!$points_gained)
				$current_user->gainActionPoints('books_edit', $book->id, BiberLog::TargetType_book);
		}
		BookLog::saveLog($book->id, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookEdit);
		Books::getInstance()->dropCache($book->id);

		if (count($to_update_m)) {
			if ($to_update_m['n'] && $book->data['book_type'] == Book::BOOK_TYPE_MAGAZINE) {
				Database::query('UPDATE `book_magazines` SET `n`=' . $to_update_m['n'] . ',`year`=' . (int) $book->data['year'] . ' WHERE `id_book`=' . $book->id);
			}
		}
		ob_end_clean();
		header('Location:' . Config::need('www_path') . '/b/' . $book->id);
		Database::query('COMMIT');
		exit();
	}

}