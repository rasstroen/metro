<?php

class Book extends BaseObjectClass {

	public $id;
	public $persons;
	public $genres = null;
	public $series;
	public $rightsholder;
	public $files;
	public $reviews;
	public $reviewsLoaded;
	public $personsLoaded = false;
	public $genresLoaded = false;
	public $seriesLoaded = false;
	public $loaded = false;
	public $rightsholderLoaded = false;
	public $filesLoaded = false;
	public $data;
	public $readableFile;
	public $fb2parser;

	const BOOK_MARK_NONE = 0;
	const BOOK_MARK_TERRIBLE = 1;
	const BOOK_MARK_BAD = 2;
	const BOOK_MARK_NORMAL = 3;
	const BOOK_MARK_GOOD = 4;
	const BOOK_MARK_PERFECT = 5;

	const BOOK_TYPE_BOOK = 1;
	const BOOK_TYPE_MAGAZINE = 2;
	public static $book_types = array(
	    self::BOOK_TYPE_BOOK => 'book',
	    self::BOOK_TYPE_MAGAZINE => 'magazine'
	);

	const ROLE_AUTHOR = 1; // автор
	const ROLE_TRANSL = 2; // переводчик
	const ROLE_ABOUTA = 3; // об авторе
	const ROLE_ILLUST = 4; // иллюстратор
	const ROLE_SOSTAV = 5; // составитель
	const ROLE_DIRECT = 6; // директор
	const ROLE_OFORMI = 7; // оформитель
	const ROLE_EDITOR = 8; // редактор
	//
        const REVIEW_TYPE_BOOK = 0;
	//
	const BOOK_QUALITY_BEST = 5;

	function __construct($id, $data = false) {
		$this->id = $id;
		if ($data) {
			if ($data == 'empty') {
				$this->loaded = true;
				$this->personsLoaded = true;
				$this->exists = false;
			}
			$this->load($data);
		}
	}

	function setReaded() {
		global $current_user;
		/* @var $current_user CurrentUser */
		if (!$current_user->authorized)
			return;
		$time = floor(time() / 60 / 60 / 24) * 60 * 60 * 24;
		$query = 'INSERT INTO `book_readed` SET
			`id_user`=' . $current_user->id . ',
			`id_book`=' . $this->id . ',
			`time`=' . $time . '
				ON DUPLICATE KEY UPDATE
			`time`=' . $time;
		Database::query($query);
	}

	function getReadedWith() {
		$time = time() - 30 * 24 * 60 * 60;
		$query = 'SELECT DISTINCT `id_book` FROM `book_readed` WHERE `id_user` IN 
			(SELECT `id_user` FROM `book_readed` WHERE `id_book`=' . $this->id . ' AND `time`>' . $time . ') AND `id_book`<>'.$this->id;
		return array_keys(Database::sql2array($query, 'id_book'));
	}

	function getLovedCount() {
		$this->load();
		return max(0, (int) $this->data['loved_count']);
	}

	function updateLovedCount() {
		$this->load();
		$query = 'UPDATE `book` SET `loved_count`=(SELECT COUNT(1) FROM `users_loved` WHERE `id_target`=' . $this->id . ' AND `target_type`=' . Config::$loved_types['book'] . ') WHERE `id`=' . $this->id;
		Database::query($query);
	}

	function _show() {
		$out = array();
		if ($this->loadForFullView()) {
			if ($redirect_to = $this->getDuplicateId()) {
				$book2 = Books::getInstance()->getByIdLoaded($redirect_to);
				if ($book2->loaded) {
					@ob_end_clean();
					header('Location: ' . $this->getUrl($redirect = true) . '?redirect=b_' . $this->id);
					exit();
				}
			}
			$out['id'] = $this->id;
			$langId = $this->data['id_lang'];
			foreach (Config::$langs as $code => $id_lang) {
				if ($id_lang == $langId) {
					$langCode = $code;
				}
			}
			$out['quality'] = $this->getQuality();
			$out['lang_code'] = $langCode;
			$out['lang_title'] = Config::$langRus[$langCode];
			$out['lang_id'] = $langId;
			$out['download_count'] = $this->data['download_count'];
			$title = $this->getTitle();

			$out['title'] = $title['title'];
			$out['subtitle'] = $title['subtitle'];


			$out['loved_count'] = $this->getLovedCount();

			$out['public'] = $this->isPublic();
			$out['qualities'] = array(
			    0 => array('id' => 0, 'title' => 'не оценен'),
			    1 => array('id' => 1, 'title' => 'ужасно'),
			    2 => array('id' => 2, 'title' => 'плохо'),
			    3 => array('id' => 3, 'title' => 'средне'),
			    4 => array('id' => 4, 'title' => 'хорошо'),
			    5 => array('id' => 5, 'title' => 'идеально'),
			);
			$persons = $this->getPersons();
			uasort($persons, 'sort_by_role');
			foreach ($persons as $data) {
				$tmp_person = Persons::getInstance()->getById($data['id'], $data);
				if ($tmp_person->id) {
					$out['authors'][$data['id']] = $tmp_person->getListData();
					$out['authors'][$data['id']]['role'] = $data['role'];
					$out['authors'][$data['id']]['roleName'] = $data['roleName'];
				}
			}

			$out['genres'] = $this->getGenres();
			$out['series'] = $this->getSeries();
			$out['isbn'] = $this->getISBN();
			$out['rightsholder'] = $this->getRightsholder();
			$out['annotation'] = $this->getChunkedAnnotation();
			$out['cover'] = $this->getCover();
			$out['files'] = $this->getFiles(true);

			$out['mark'] = $this->getMarkNumber();
			$out['mark_percents'] = $this->getMarkPercents();
			$out['mark_number'] = $this->getMarkRoundNumber();
			$out['path'] = $this->getUrl();
			$out['path_read'] = $this->getUrlRead();
			$out['lastSave'] = $this->data['modify_time'];
			$out['id_rightholder'] = $this->data['id_rightholder'];

			$out['path_admin'] = Config::need('www_path') . '/admin/books/' . $this->id;

			$out['year'] = (int) $this->data['year'] ? (int) $this->data['year'] : '';
			$out['book_type'] = Book::$book_types[$this->data['book_type']];
			if ($this->data['book_type'] == Book::BOOK_TYPE_MAGAZINE) {
				$out['magazine'] = Database::sql2row('SELECT * FROM `magazines` WHERE `id`=' . $this->getMagazineId());
				$out['magazine']['path'] = isset($out['magazine']['id']) ? Config::need('www_path') . '/m/' . $out['magazine']['id'] : '';
				$out['n'] = Database::sql2single('SELECT `n` FROM `book_magazines` WHERE `id_book`=' . $this->id);
			}
		}
		else
			throw new Exception('no book #' . $id . ' in database');
		return $out;
	}

	function getMagazineId() {
		$query = 'SELECT `id_magazine` FROM `book_magazines` WHERE `id_book`=' . $this->id;
		return (int) Database::sql2single($query);
	}

	function getDuplicateId() {
		if (!$this->loaded) {
			$this->load();
		}
		return (int) $this->data['is_duplicate'];
	}

	function getLangId() {
		if (!$this->loaded) {
			$this->load();
		}
		return (int) $this->data['id_lang'];
	}

	function getBasketId() {
		if (!$this->loaded) {
			$this->load();
		}
		return (int) $this->data['id_basket'];
	}

	function getUrl($redirect = false) {
		$id = $redirect ? $this->getDuplicateId() : $this->id;
		return Config::need('www_path') . '/book/' . $id;
	}

	function getUrlRead() {
		$id = $this->id;
		return Config::need('www_path') . '/book/' . $id . '/read';
	}

	function getReadableFile() {
		if ($this->readableFile !== null)
			return $this->readableFile;
		/*
		  1 => 'fb2',
		  2 => 'txt',
		  3 => 'fbz',
		  4 => 'html',
		  5 => 'htm',
		  6 => 'rtf',
		  7 => 'epub',
		  8 => 'mobi',
		  9 => 'pdf',
		  10 => 'djvu',
		  11 => 'doc'
		 */
		$this->loadFiles();
		$found_type = 0;
		$id_file = 0;
		foreach ($this->files as $file) {
			//fb2
			if (!$found_type && $file['filetype'] == 1) {
				$found_type = 1;
				$id_file = $file['id'];
			}
		}

		if (!$found_type)
			throw new Exception('no any readable files for this book');

		$realPath = getBookFilePath($id_file, $this->id, $found_type, Config::need('files_path'));
		global $dev_mode;
		if (!is_readable($realPath)) {
			if ($dev_mode)
				throw new Exception('Sorry, file ' . $realPath . ' doesn\'t exists');
			else
				throw new Exception('Sorry, file  doesn\'t exists');
		}

		$this->readableFile = array($realPath, $found_type, $id_file);
		return $this->readableFile;
	}

	/**
	 *
	 * @param boolean $force_fb2 - false. емли тру, читаем fb2 и парсим его, если нет - пытаемся взять уже распарсенный с диска
	 * @return string тело книжки 
	 */
	function getToc($force_fb2 = false) {
		$out = '';
		list($file, $type, $id_file) = $this->getReadableFile();
		switch ($type) {
			case 1:// fb2
				// а мы не парсили ещё html из этого файла?
				$filename_toc = getBookFilePathFB2Toc($id_file, $this->id, $type, Config::need('files_path'));
				if (!$force_fb2 && file_exists($filename_toc)) {
					Log::logHtml($filename_toc . ' loaded from disk');
					$out = file_get_contents($filename_toc);
				} else {
					$fb2Parser = $this->getFb2Parser();
					/* @var $fb2Parser FB2Parser */
					$out = $fb2Parser->getTOCXsl();
					file_put_contents($filename_toc, $out);
				}
				break;
			case 2; //txt
				break;
			case 4:case 5:// htm html
				break;
		}
		return $out;
	}

	function getHTML($force_fb2 = true) {
		$out = '';
		list($file, $type, $id_file ) = $this->getReadableFile();
		switch ($type) {
			case 1:// fb2
				$filename_htm = getBookFilePathFB2Html($id_file, $this->id, $type, Config::need('files_path'));
				if (!$force_fb2 && file_exists($filename_htm)) {
					$out = file_get_contents($filename_htm);
					Log::logHtml($filename_htm . ' loaded from disk');
				} else {
					$fb2Parser = $this->getFb2Parser();
					/* @var $fb2Parser FB2Parser */
					$out = $fb2Parser->getHTML();
					file_put_contents($filename_htm, $out);
				}
				break;
			case 2; //txt
				break;
			case 4:case 5:// htm html
				break;
		}
		return $out;
	}

	function getHTMLDownload($force_fb2 = true) {
		$out = '';
		list($file, $type, $id_file ) = $this->getReadableFile();
		switch ($type) {
			case 1:// fb2
				$filename_htm = getBookFilePathFB2HtmlDownload($id_file, $this->id, $type, Config::need('files_path'));
				if (!$force_fb2 && file_exists($filename_htm)) {
					$out = file_get_contents($filename_htm);
					Log::logHtml($filename_htm . ' loaded from disk');
				} else {
					$fb2Parser = $this->getFb2Parser();
					/* @var $fb2Parser FB2Parser */
					$out = $fb2Parser->getHTMLDownload();
					file_put_contents($filename_htm, $out);
				}
				break;
			case 2; //txt
				break;
			case 4:case 5:// htm html
				break;
		}
		return $out;
	}

	function getFb2Parser() {
		if ($this->fb2parser)
			return $this->fb2parser;
		list($file, $type) = $this->getReadableFile();
		$this->fb2parser = new FB2Parser($file, $this->id);
		return $this->fb2parser;
	}

// грузим некоторые данные книги одним запросом
	function loadForFullView() {
// book
// book_genre
// genre
// book_persons
// persons
		$this->genres = array();
		$query = 'SELECT
			B.*,B.id as bid ,B.id_basket as bid_basket , B.is_deleted as bis_deleted,
			\'separator_genre\' as `separator_genre`,
			G.`id` as `id_genre`, G.`title` as `g_title`, G.`id_parent` as `gid_parent`, G.`name` as `gname`,
			\'separator_person\' as `separator_person`,
			P.`id` as `person_id`, P.*,
			\'separator_end\' as `separator_end`,
			BP.`person_role` as `person_role`
			FROM `book` B
			LEFT JOIN `book_genre` BG ON BG.id_book = B.id
			LEFT JOIN `genre` G ON G.id = BG.id_genre
			LEFT JOIN `book_persons` BP ON BP.id_book = B.id
			LEFT JOIN `persons` P ON P.id = BP.id_person
			WHERE B.id=' . $this->id . '
		';

		$res = Database::sql2array($query);


		if (!count($res))
			return false;
		$fieldsfor = 'book';

		foreach ($res as $row) {
			foreach ($row as $f => $v) {
				if ($f == 'separator_book') {
					$fieldsfor = 'genre';
					continue;
				} else
				if ($f == 'separator_genre') {
					$fieldsfor = 'genre';
					continue;
				} else
				if ($f == 'separator_person') {
					$fieldsfor = 'person';
					continue;
				} else
				if ($f == 'separator_end') {
					$fieldsfor = false;
					continue;
				}

				if ($fieldsfor == 'book') {
					if ($f == 'bid')
						$this->data['id'] = $v; else
					if ($f == 'bid_basket')
						$this->data['id_basket'] = $v; else
					if ($f == 'bis_deleted')
						$this->data['is_deleted'] = $v; else
						$this->data[$f] = $v;
				} else
				if ($fieldsfor == 'person') {
					if ($row['person_id']) {
						if ($f == 'person_id') {
							$f = 'id';
							$this->persons[$v]['role'] = $row['person_role'];
							$this->persons[$v]['roleName'] = $this->getPersonRoleName($row['person_role']);
						}
						$this->persons[$row['person_id']][$f] = $v;
					}
				} else
				if ($fieldsfor == 'genre') {
					if ($f == 'g_title')
						$f = 'title';
					if ($f == 'gid_parent')
						$f = 'id_parent';
					if ($f == 'id_genre')
						$f = 'id';
					if ($f == 'gname')
						$f = 'name';
					if ($row['id_genre']) {
						$this->genres[$row['id_genre']]['path'] = Config::need('www_path') . '/genres/' . $row['gname'];
						$this->genres[$row['id_genre']][$f] = $v;
					}
				}
			}
		}
		if (is_array($this->persons))
			$this->persons = array_values($this->persons); else
			$this->persons = array();

// одним запросом несколько лоадов
		$this->loaded = true;
		$this->genresLoaded = true;
		$this->personsLoaded = true;
		return true;
	}

	function load($data = false) {
		if ($this->loaded || $this->exists === false || !$this->id)
			return false;
		if (!$data) {
			$query = 'SELECT * FROM `book` WHERE `id`=' . $this->id;
			$this->data = Database::sql2row($query);
		} else
			$this->data = $data;

		if (isset($data['is_deleted']) && $data['is_deleted']) {
			$this->exists = false;
		} else {
			$this->exists = true;
		}
		$this->loaded = true;
	}

	function loadGenres() {
		if ($this->genresLoaded)
			return false;
		$query = 'SELECT `id_genre` FROM `book_genre` WHERE `id_book`=' . $this->id;
		$genres = Database::sql2array($query, 'id_genre');

		$ids = array_keys($genres);
		if (count($ids)) {
			$query = 'SELECT * FROM `genre` WHERE `id` IN (' . implode(',', $ids) . ')';
			$genresLib = Database::sql2array($query, 'id');

			foreach ($genres as $gen) {
				if (isset($genresLib[$gen['id_genre']])) {
					if ($genresLib[$gen['id_genre']]['id']) {
						if ($gen) {
							$this->genres[$gen['id_genre']] = $genresLib[$gen['id_genre']];
							$this->genres[$gen['id_genre']]['path'] = Config::need('www_path') . '/genres/' . $genresLib[$gen['id_genre']]['name'];
						}
					}
				}
			}
		} else
			$this->genres = array();
		$this->genresLoaded = true;
	}

	function loadSeries() {
		if ($this->seriesLoaded)
			return false;
		$query = 'SELECT `id_series` FROM `book_series` WHERE `id_book`=' . $this->id;
		$series = Database::sql2array($query, 'id_series');

		$ids = array_keys($series);
		if (count($ids)) {
			$query = 'SELECT * FROM `series` WHERE `id` IN (' . implode(',', $ids) . ')';
			$seriesLib = Database::sql2array($query, 'id');

			foreach ($series as $ser) {
				$this->series[$ser['id_series']] = $seriesLib[$ser['id_series']];
				$this->series[$ser['id_series']]['path'] = Config::need('www_path') . '/series/' . $ser['id_series'];
			}
		} else
			$this->series = array();
		$this->seriesLoaded = true;
	}

	function loadReviews() {
		if ($this->reviewsLoaded)
			return false;
		$query = 'SELECT `id_user`,`comment`,`time`,`rate` FROM `reviews` WHERE `id_target`=' . $this->id . ' AND `target_type`=' . self::REVIEW_TYPE_BOOK;
		$reviews = Database::sql2array($query);
		$uids = array();
		foreach ($reviews as $review) {
			$uids[] = $review['id_user'];
		}
		if ($uids)
			$users = Users::getByIdsLoaded($uids);

		global $current_user;
		/* @var $current_user CurrentUser */
		foreach ($reviews as &$review) {
			if (isset($users[$review['id_user']])) {
				$review['nickname'] = $users[$review['id_user']]->getProperty('nickname', 'аноним');
				$review['picture'] = $users[$review['id_user']]->getProperty('picture') ? $users[$review['id_user']]->id . '.jpg' : 'default.jpg';
			} else {
				$review['nickname'] = 'аноним';
				$review['picture'] = 'default.jpg';
			}
		}
		$this->reviews = $reviews;
		$this->reviewsLoaded = true;
	}

	function loadRightsholder() {
		if (!$this->loaded) {
			$this->load();
		}
		if ($this->rightsholderLoaded) {
			return false;
		}
		if ($this->data['id_rightholder']) {
			$query = 'SELECT * FROM `rightholders` WHERE `id`=' . $this->data['id_rightholder'];
			$this->rightsholder = Database::sql2row($query);
			if (!is_array($this->rightsholder))
				$this->rightsholder = array();
		}else
			$this->rightsholder = array();


		$this->rightsholderLoaded = true;
	}

	function loadFiles() {
		if ($this->filesLoaded) {
			return false;
		}
		$query = 'SELECT * FROM `book_files` WHERE `id_book` = ' . $this->id;
		$res = Database::sql2array($query);
		$this->files = is_array($res) ? $res : array();
	}

	function loadPersons($persons = false) {

		if ($this->personsLoaded) {
			return false;
		}

		if (($persons !== false)) {

			$pid = array();
			foreach ($persons as $person) {
				$pid[] = $person['id_person'];
			}
			Persons::getInstance()->getByIdsLoaded($pid);
		} else {
			// связи
			$query = 'SELECT `person_role`,`id_person` FROM `book_persons` WHERE `id_book`=' . $this->id;
			$persons = Database::sql2array($query, 'id_person');
			// профили
			$personProfiles = array();
			if (count($persons)) {
				$ids = array_keys($persons);
				Persons::getInstance()->getByIdsLoaded($ids);
			}
		}

		foreach ($persons as $person) {
			$personItem = Persons::getInstance()->getByIdLoaded($person['id_person']);
			if ($personItem->loaded) {
				$personProfiles[$person['id_person']] = $personItem->getListData();
				$personProfiles[$person['id_person']]['role'] = $person['person_role'];
				$personProfiles[$person['id_person']]['roleName'] = $this->getPersonRoleName($person['person_role']);
				$this->persons[] = $personProfiles[$person['id_person']];
			}
		}

		$this->personsLoaded = true;
	}

	function getPersonRoleName($id_role) {
		switch ($id_role) {
			case self::ROLE_ABOUTA:
				return 'биограф';
				break;
			case self::ROLE_AUTHOR:
				return 'автор';
				break;
			case self::ROLE_DIRECT:
				return 'директор';
				break;
			case self::ROLE_EDITOR:
				return 'редактор';
				break;
			case self::ROLE_ILLUST:
				return 'иллюстратор';
				break;
			case self::ROLE_OFORMI:
				return 'оформитель';
				break;
			case self::ROLE_SOSTAV:
				return 'составитель';
				break;
			case self::ROLE_TRANSL:
				return 'переводчик';
				break;
		}
	}

	function getRightsholder() {
		if (!$this->rightsholderLoaded) {
			$this->loadRightsholder();
		}
		return $this->rightsholder;
	}

	function getTitle($asString = false) {
		if (!$this->loaded) {
			$this->load();
		}
		if ($asString) {
			return$this->data['subtitle'] ?
				$this->data['title'] . ' ' . $this->data['subtitle'] :
				$this->data['title'];
		}
		return array(
		    'title' => $this->data['title'],
		    'subtitle' => $this->data['subtitle']
		);
	}

	function getPersons() {
		if ($this->persons == null) {
			$this->loadPersons();
		}
		return $this->persons;
	}

	function getGenres() {
		if (!$this->genresLoaded) {
			$this->loadGenres();
		}
		return $this->genres;
	}

	function getSeries() {
		if ($this->series == null) {
			$this->loadSeries();
		}
		return $this->series;
	}

	function getISBN() {
		if (!$this->loaded) {
			$this->load();
		}
		return $this->data['ISBN'];
	}

	function getIzdatel() {
		
	}

	function getChunkedAnnotation() {
		if (!$this->loaded) {
			$this->load();
		}
		return
			array('short' => $short = close_dangling_tags(trim(_substr($this->data['description'], 400))), 'html' => $this->data['description']);
	}

	function getAnnotation() {
		if (!$this->loaded) {
			$this->load();
		}
		return $this->data['description'];
	}

	function getRating() {
		
	}

	function getQuality() {
		if (!$this->loaded) {
			$this->load();
		}
		return isset($this->data['quality']) ? (int) $this->data['quality'] : 0;
	}

	function getTranslations() {
		
	}

	function isPublic() {
		if (!$this->loaded) {
			$this->load();
		}
		return $this->data['is_public'];
	}

	function getEditions() {
		
	}

	function getAuthors() {
		$out = array();
		$this->loadPersons();
		if (count($this->persons))
			foreach ($this->persons as $person) {
				if ($person['role'] == self::ROLE_AUTHOR) {
					$out[] = $person;
				}
			}
		return $out;
	}

	function getAuthor($first_name = true, $last_name = true, $middle_name = true, $id = false) {
		$this->loadPersons();
		if (is_array($this->persons))
			foreach ($this->persons as $person) {
				if ($person['role'] == self::ROLE_AUTHOR && (!$id || $id == $person['id'])) { {
						$string = '';
						if ($first_name)
							$string.=' ' . $person['first_name'];
						if ($middle_name)
							$string.=' ' . $person['middle_name'];
						if ($last_name)
							$string.=' ' . $person['last_name'];
						return array($person['id'], trim($string));
					}
				}
			}
		return array('0', 'неизвестен');
	}

	function getAuthorId($id = false) {
		$this->loadPersons();
		if (is_array($this->persons))
			foreach ($this->persons as $person) {
				if ($person['role'] == self::ROLE_AUTHOR && (!$id || $id == $person['id'])) { {
						return $person['id'];
					}
				}
			}
		return 0;
	}

	/**
	 *
	 * @param string $mode small/big/normal
	 * @return string full url
	 */
	function getCover($mode = 'default') {
		if (!$mode)
			$mode = 'default';
		
		$mode = $mode ? $mode . '_' : '';
		if (!$this->loaded) {
			$this->load();
		}
		if ($this->data['is_cover']) {
			return Config::need('www_path') . '/static/upload/covers/' . (ceil($this->id / 5000)) . '/' . $mode . '' . $this->id . '_' . $this->data['is_cover'] . '.jpg';
		}
		return Config::need('www_path') . '/static/upload/covers/default.jpg';
	}

	function getReviews() {
		if (!$this->reviewsLoaded)
			$this->loadReviews();
		return $this->reviews;
	}

	function getFiles($fake_html = false) {
		if (!$this->loaded)
			$this->load();

		if (!$this->data['id_main_file']) {
			// fuck yeah! we are caching if it's any file too
			return array();
		}

		$ft = Config::need('filetypes');
		if (!$this->filesLoaded) {
			$this->loadFiles();
		}
		$out = array();
		$htmlfound = false;
		$fb2found = false;
		foreach ($this->files as $filerow) {
			if (in_array($filerow['filetype'], array(4, 5))) {
				// html
				$htmlfound = true;
			}
			if (in_array($filerow['filetype'], array(1))) {
				// html
				$fb2found = $filerow;
			}
			$out[$filerow['id']] = array(
			    'id_file' => $filerow['id'],
			    'filetype' => $filerow['filetype'],
			    'filetypedesc' => $ft[$filerow['filetype']],
			    'is_default' => ($filerow['id'] == $this->data['id_main_file']) ? 1 : 0,
			    'size' => $filerow['filesize'],
			    'modify_time' => $filerow['modify_time'],
			    'path' => getBookDownloadUrl($filerow['id'], $this->id, $filerow['filetype']),
			);
		}

		if ($fake_html && $fb2found && !$htmlfound) {
			// мы хотим список с html, т.к. есть fb2 из которого этот html можно получить
			$out[0] = array(
			    'id_file' => 0,
			    'filetype' => 4,
			    'filetypedesc' => $ft[4],
			    'is_default' => 0,
			    'size' => $fb2found['filesize'],
			    'modify_time' => $fb2found['modify_time'],
			    'path' => getBookDownloadUrl($fb2found['id'], $this->id, $fb2found['filetype']) . '?html',
			);
		}
		return $out;
	}

	function getMarkNumber() {
		$this->load();
		return round($this->data['mark']) / 10;
	}

	function getMarkRoundNumber() {
		$this->load();
		return round($this->data['mark'] / 10);
	}

	function getMarkPercents() {
		$this->load();
		return ($this->data['mark'] * 2);
	}

	function getListData($person_id = false, $coverSize = false) {
		list($aid, $aname) = $this->getAuthor(1, 1, 1, $person_id); // именно наш автор, если их там много
		$out = array(
		    'id' => $this->id,
		    'cover' => $this->getCover($coverSize),
		    'title' => $this->getTitle(true),
		    'author_id' => $aid,
		    'lastSave' => $this->data['modify_time'],
		    'download_count' => $this->data['download_count'],
		    'path' => $this->getUrl(),
		    'mark' => $this->getMarkNumber(),
		    'mark_percents' => $this->getMarkPercents(),
		    'mark_number' => $this->getMarkRoundNumber(),
		);
		return $out;
	}

}