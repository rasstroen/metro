<?php

class Search {

	private static $instance = false;
	private static $books = false;
	private static $authors = false;
	private static $series = false;
	private static $magazines = false;
	private $rightHolders = null;

	public static function getInstance() {
		if (!Search::$instance) {
			Search::$instance = new Search();
		}
		return Search::$instance;
	}

	public static function resetAllIndex() {
		$queryes = array(
		    'INSERT IGNORE INTO  `cron_solr_update_magazine` (SELECT id,0,0 from magazines)',
		    'INSERT IGNORE INTO  `cron_solr_update_serie` (SELECT id,0,0 from series)',
		    'INSERT IGNORE INTO  `cron_solr_update_book` (SELECT id,0,0 from book)',
		    'INSERT IGNORE INTO  `cron_solr_update_author` (SELECT id,0,0 from persons)',
		);

		foreach ($queryes as $query)
			Database::query($query);
	}

	public function __construct() {
		$solr_host = Config::need('solr_host', 'rusec-ws.jnpe.ru');
		$solr_port = Config::need('solr_port', 80);

		require_once(Config::need('base_path') . 'classes/Solr/Service.php');
		require_once(Config::need('base_path') . 'classes/Solr/HttpTransport/CurlNoReuse.php');
		$transportInstance = new Apache_Solr_HttpTransport_CurlNoReuse();

		self::$books = new Apache_Solr_Service($solr_host, $solr_port, '/solr/books', $transportInstance);
		self::$authors = new Apache_Solr_Service($solr_host, $solr_port, '/solr/authors', $transportInstance);
		self::$series = new Apache_Solr_Service($solr_host, $solr_port, '/solr/series', $transportInstance);
		self::$magazines = new Apache_Solr_Service($solr_host, $solr_port, '/solr/magazines', $transportInstance);
		if (self::$authors->ping()) {
			
		}
		else
			throw new Exception('Solr ping failed');
	}

	/**
	 * Книгу надо проапдейтить в Solr. Для этого есть специальный демон, а мы просто
	 * указываем ему новую цель через таблицу в бд
	 * @param Book $book
	 */
	public function setBookToFullUpdate(Book $book) {
		if ($book) {
			Database::query('INSERT INTO `cron_solr_update_book` SET
				`id_book`=' . $book->id . ',
				`time`=' . time() . ',
				`in_process`=0
				ON DUPLICATE KEY UPDATE
				`in_process`=0');
		}
	}

	public function setAuthorToFullUpdate($id) {
		if ($id) {
			Database::query('INSERT INTO `cron_solr_update_author` SET
				`id_author`=' . $id . ',
				`time`=' . time() . ',
				`in_process`=0
				ON DUPLICATE KEY UPDATE
				`in_process`=0');
			// set all author's books to update
			$time = time();
			$query = 'INSERT INTO `cron_solr_update_book` (SELECT DISTINCT (id_book),' . $time . ',0 from `book_persons` WHERE id_person=' . $id . ')';
			Database::query($query);
		}
	}

	public function setSerieToFullUpdate($id) {
		if ($id) {
			Database::query('INSERT INTO `cron_solr_update_serie` SET
				`id_serie`=' . $id . ',
				`time`=' . time() . ',
				`in_process`=0
				ON DUPLICATE KEY UPDATE
				`in_process`=0');
			// set all series's books to update
			$time = time();
			$query = 'INSERT INTO `cron_solr_update_book` (SELECT DISTINCT (id_book),' . $time . ',0 from `book_persons` WHERE id_series=' . $id . ')';
			Database::query($query);
		}
	}

	public function setMagazineToFullUpdate($id) {
		if ($id) {
			Database::query('INSERT INTO `cron_solr_update_magazine` SET
				`id_magazine`=' . $id . ',
				`time`=' . time() . ',
				`in_process`=0
				ON DUPLICATE KEY UPDATE
				`in_process`=0');
		}
	}

	public function updateMagazinesFull($mids) {
		$documents = array();
		$query = 'SELECT * FROM `magazines` WHERE `id` IN(' . implode(',', $mids) . ')';
		$data = Database::sql2array($query);

		foreach ($data as $row) {
			/* @var $book Book */
			$documents[] = $this->prepareMagazineFull($row);
		}
		$magazines = self::$magazines;
		/* @var $books Apache_Solr_Service */
		// удаляем из индекса
		$magazines->deleteByMultipleIds($mids);
		// добавляем в индекс
		$magazines->addDocuments($documents);
		// коммитим изменения
		$magazines->commit();
		// оптимизируем поисковую базу
		$magazines->optimize();
	}

	public function updateSeriesFull($sids) {
		$documents = array();
		$query = 'SELECT * FROM `series` WHERE `id` IN(' . implode(',', $sids) . ')';
		$data = Database::sql2array($query);

		foreach ($data as $row) {
			/* @var $book Book */
			$documents[] = $this->prepareSerieFull($row);
		}
		$series = self::$series;
		/* @var $books Apache_Solr_Service */
		// удаляем из индекса
		$series->deleteByMultipleIds($sids);
		// добавляем в индекс
		$series->addDocuments($documents);
		// коммитим изменения
		$series->commit();
		// оптимизируем поисковую базу
		$series->optimize();
	}

	public function updateBooksFull($bids) {
		$documents = array();
		Books::$books_instance = false; // cached books?
		Persons::$persons_instance = false; // cached persons?
		$books = Books::getInstance()->getByIdsLoaded($bids);
		$time = time();
		foreach ($books as $book) {
			/* @var $book Book */
			$d = $this->prepareBookFull($book);
			if ($d)
				$documents[] = $d;
		}
		$books = self::$books;
		/* @var $books Apache_Solr_Service */
		// удаляем из индекса
		$books->deleteByMultipleIds($bids);
		// добавляем в индекс
		$books->addDocuments($documents);
		// коммитим изменения
		$books->commit();
		// оптимизируем поисковую базу
		$books->optimize();
	}

	public function updateAuthorsFull($aids) {
		$documents = array();
		Books::$books_instance = false; // cached books?
		Persons::$persons_instance = false; // cached persons?
		$persons = Persons::getInstance()->getByIdsLoaded($aids);
		foreach ($persons as $person) {
			/* @var $person Person */
			$documents[] = $this->prepareAuthorFull($person);
		}
		$authors = self::$authors;
		/* @var $books Apache_Solr_Service */
		// удаляем из индекса
		$authors->deleteByMultipleIds($aids);
		// добавляем в индекс
		$authors->addDocuments($documents);
		// коммитим изменения
		$authors->commit();
		// оптимизируем поисковую базу
		$authors->optimize();
	}

	function prepareMagazineFull(array $data) {
		$fields = array(
		    "id" => '',
		    "annotation" => '',
		    "title" => '',
		    "ISBN" => '',
		    "is_cover" => 'bool',
		    "first_year" => '',
		    "last_year" => '',
		    "books_count" => '',
		);

		if ($this->rightHolders === null)
			$this->loadRightHolders();

		$document->rightholder = $this->rightHolders[$data['id_rightholder']]['title'];

		$document = new Apache_Solr_Document();
		foreach ($fields as $name => $type) {
			if (isset($data[$name])) {
				if ($type == 'bool')
					$document->$name = $data[$name] > 0 ? 1 : 0;
				else
					$document->$name = $data[$name];
			}
		}
		return $document;
	}

	function prepareSerieFull(array $data) {
		$fields = array(
		    "id" => '',
		    "id_parent" => 'bool',
		    "name" => '',
		    "description" => '',
		    "title" => '',
		    "books_count" => '',
		    "is_s_duplicate" => 'bool',
		    "is_deleted" => 'bool',
		);

		if ($this->rightHolders === null)
			$this->loadRightHolders();


		$document = new Apache_Solr_Document();
		foreach ($fields as $name => $type) {
			if (isset($data[$name])) {
				if ($type == 'bool')
					$document->$name = $data[$name] > 0 ? 1 : 0;
				else
					$document->$name = $data[$name];
			}
		}
		return $document;
	}

	function prepareAuthorFull(Person $author) {
		$fields = array(
		    "id" => '',
		    "date_birth" => '',
		    "date_death" => '',
		    "avg_mark" => '',
		    "first_name" => '',
		    "middle_name" => '',
		    "last_name" => '',
		    "bio" => '',
		    "has_cover" => 'bool',
		    "is_p_duplicate" => 'bool',
		    "is_deleted" => 'bool',
		    "blocked" => 'bool',
		    "public" => 'bool',
		);
		$document = new Apache_Solr_Document();
		foreach ($fields as $name => $type) {
			if (isset($author->data[$name])) {
				if ($type == 'bool')
					$document->$name = $author->data[$name] > 0 ? 1 : 0;
				else
					$document->$name = $author->data[$name];
			}
		}
		return $document;
	}

	/**
	 * Полностью подготавливаем документ книги для solr, с жанрами, авторами и всем прочим.
	 * Ресурсоемко, использовать только демоном
	 * @param Book $book
	 */
	public function prepareBookFull(Book $book) {
		$fields = array(
		    "id" => '',
		    "id_main_file" => '',
		    "quality" => '',
		    "year" => '',
		    "mark" => '',
		    "rating" => '',
		    "book_type" => '',
		    "download_count" => '',
		    "title" => '',
		    "subtitle" => '',
		    "keywords" => '',
		    // "description" => '',
		    "is_cover" => 'bool',
		    "is_duplicate" => 'bool',
		    "is_deleted" => 'bool',
		    "is_blocked" => 'bool',
		    "is_public" => 'bool',
		);
		if (!$book->loaded)
			return false;
		$document = new Apache_Solr_Document();
		foreach ($fields as $name => $type) {
			if (isset($book->data[$name])) {
				if ($type == 'bool')
					$document->$name = $book->data[$name] > 0 ? 1 : 0;
				else
					$document->$name = $book->data[$name];
			}
		}

		// правообладатели - списочком
		if ($this->rightHolders === null)
			$this->loadRightHolders();

		$document->rightholder = $this->rightHolders[$book->data['id_rightholder']]['title'];
		// авторы
		$authors = $book->getAuthors();
		foreach ($authors as $author) {
			$document->setMultiValue("author", $author['first_name'] . ' ' . $author['last_name'] . ' ' . $author['middle_name'] . ' ');
		}
		// жанры
		$genres = $book->getGenres();
		foreach ($genres as $genre) {
			$document->setMultiValue("genre", $genre['title']);
		}
		// серии
		$series = $book->getSeries();
		foreach ($series as $serie) {
			$document->setMultiValue("serie", $serie['title']);
		}
		return $document;
	}

	/**
	 * если у книги поменялось поле, её нужно проапдейтить в solr
	 * если поменялся автор, жанр и т.п. - используем setBookToFullUpdate()
	 * @param Book $book
	 * @param boolean $update - если false, то мы уверены что этого документа в Solr нет и апдейтить ничего не будем
	 */
	public function updateBook(Book $book, $update = true) {
		// это слишком долго. лучше пусть демон это делает
		$this->setBookToFullUpdate($book);
	}

	function commitBooks() {
		Log::timingplus('solr commit');
		$books = self::$books;
		$books->commit();
		Log::timingplus('solr commit');
	}

	function optimizeBooks() {
		$books = self::$books;
		$books->optimize();
	}

	/**
	 * ищем среди названий книг, журналов и серий, ФИО авторов
	 * @param String $string поисковая строка
	 * @return array array(book_ids, serie_ids, author_ids)
	 */
	public function searchByString($string, $books = true, $authors = true, $series = true, $magazines = true) {
		$out = array(
		    'bids' => array(),
		    'aids' => array(),
		    'sids' => array(),
		);
		return $out;
	}

	public static function getHighlighted($res) {
		$found_highlight = array();
		if (isset($res->highlighting))
			foreach ($res->highlighting as $id => $fields) {
				foreach ($fields as $field => $found) {
					foreach ($found as &$txt) {
						$txt = html_entity_decode($txt, ENT_QUOTES, 'UTF-8');
						preg_match('/\<em\>(.*)\<\/em\>/isU', $txt, $matches);
						if (isset($matches[1])) {
							$match = explode(' ', strip_tags($matches[1]));
							$match = trim(str_replace("\n", '', implode(' ', $match)));
							$found_highlight[$id][] = mb_substr($match, 0, 50, 'UTF-8');
						}
					}
				}
			}
		return $found_highlight;
	}

	public function searchMagazinesByString($string, $offset=0, $limit=100) {
		if (Config::need('disable_solr')) {
			$foundids = array_keys(Database::sql2array('SELECT `id` FROM `magazines` WHERE `title` LIKE \'%' . $string . '%\' LIMIT ' . $offset . ',' . $limit, 'id'));
			$foundcnt = count($foundids);
			return array($foundids, $foundcnt, array());
		}
		Log::timingplus('solr searchMagazinesByString');
		$magazines = self::$magazines;
		$ids = array();
		$count = 0;
		/* @var $books Apache_Solr_Service */
		$string = $string ? $string : '*';
		$query = ($string == '*' ? '*:*' : 'text:' . $magazines->escape($string));

		$res = $magazines->search($query, $offset, $limit, array('fl' => 'id', 'hl' => 'true', 'hl.fl' => '*', 'usePhraseHighlighter' => 'true'));
		/* @var $res Apache_Solr_Response */
		foreach ($res->response->docs as $document) {
			$ids[$document->id] = $document->id;
		}
		$count = $res->response->numFound;
		Log::timingplus('solr searchMagazinesByString');
		return array($ids, $count, $this->getHighlighted($res));
	}

	public function searchBooksByString($string, $offset=0, $limit=100) {
		if (Config::need('disable_solr')) {
			$foundids = array_keys(Database::sql2array('SELECT `id` FROM `book` WHERE `title` LIKE \'%' . $string . '%\' LIMIT ' . $offset . ',' . $limit, 'id'));
			$foundcnt = count($foundids);
			return array($foundids, $foundcnt, array());
		}
		Log::timingplus('solr searchBooksByString');
		$books = self::$books;
		$ids = array();
		$count = 0;
		/* @var $books Apache_Solr_Service */
		$string = $string ? $string : '*';
		$query = ($string == '*' ? '*:*' : 'text:' . $books->escape($string));
		// книги
		$query.=' AND book_type:' . Book::BOOK_TYPE_BOOK;
		// дубликаты убираем
		$query.=' AND is_duplicate:false';
		// удаленные убираем
		$query.=' AND is_deleted:false';

		$res = $books->search($query, $offset, $limit, array(
		    'fl' => 'id',
		    'hl' => 'true',
		    'hl.fl' => '*',
			//'hl.useFastVectorHighlighter' => 'true',
			// 'hl.regex.slop' => '.2',
			// 'hl.fragmenter' => 'regex',
			));

		/* @var $res Apache_Solr_Response */
		foreach ($res->response->docs as $document) {
			$ids[$document->id] = $document->id;
		}
		$count = $res->response->numFound;
		Log::timingplus('solr searchBooksByString');
		return array($ids, $count, $this->getHighlighted($res));
	}

	public function searchAuthorsByString($string, $offset=0, $limit=100) {
		if (Config::need('disable_solr')) {
			$foundids = array_keys(Database::sql2array('SELECT `id` FROM `persons` WHERE `first_name` LIKE \'%' . $string . '%\' LIMIT ' . $offset . ',' . $limit, 'id'));
			$foundcnt = count($foundids);
			return array($foundids, $foundcnt, array());
		}
		Log::timingplus('solr searchAuthorsByString');
		$authors = self::$authors;
		$ids = array();
		$count = 0;
		/* @var $books Apache_Solr_Service */
		// @text field - for bio including, @name- for 1,2,3name
		$string = $string ? $string : '*';
		$query = ($string == '*' ? '*:*' : 'name:' . $authors->escape($string));
		// дубликаты убираем
		$query.=' AND is_p_duplicate:false';
		// удаленные убираем
		$query.=' AND is_deleted:false';
		$query.=' AND blocked:false';
		$res = $authors->search($query, $offset, $limit, array('fl' => 'id', 'hl' => 'true', 'hl.fl' => '*', 'usePhraseHighlighter' => 'true'));
		/* @var $res Apache_Solr_Response */
		foreach ($res->response->docs as $document) {
			$ids[$document->id] = $document->id;
		}
		$count = $res->response->numFound;
		Log::timingplus('solr searchAuthorsByString');
		return array($ids, $count, $this->getHighlighted($res));
	}

	public function searchSeriesByString($string, $offset=0, $limit=100) {
		if (Config::need('disable_solr')) {
			$foundids = array_keys(Database::sql2array('SELECT `id` FROM `series` WHERE `title` LIKE \'%' . $string . '%\' LIMIT ' . $offset . ',' . $limit, 'id'));
			$foundcnt = count($foundids);
			return array($foundids, $foundcnt, array());
		}
		Log::timingplus('solr searchSeriesByString');
		$series = self::$series;
		$ids = array();
		$count = 0;
		/* @var $books Apache_Solr_Service */
		// @text field - for bio including, @name- for 1,2,3name
		$string = $string ? $string : '*';
		$query = ($string == '*' ? '*:*' : 'text:' . $series->escape($string));
		// дубликаты убираем
		$query.=' AND books_count:true';
		// удаленные убираем
		$query.=' AND is_s_duplicate:false';
		$query.=' AND is_deleted:false';
		$res = $series->search($query, $offset, $limit, array('fl' => 'id', 'hl' => 'true', 'hl.fl' => '*', 'usePhraseHighlighter' => 'true'));

		/* @var $res Apache_Solr_Response */
		foreach ($res->response->docs as $document) {
			$ids[$document->id] = $document->id;
		}
		$count = $res->response->numFound;
		Log::timingplus('solr searchSeriesByString');
		return array($ids, $count, $this->getHighlighted($res));
	}

	private function loadRightHolders() {
		$query = 'SELECT * FROM `rightholders`';
		$this->rightHolders = Database::sql2array($query, 'id');
	}

	public function searchAutoComplete($string, $offset=0, $limit=10) {
		$result = array();
		if (!$string)
			return;
		try {
			$books = self::$books;
			$query = 'text:' . $books->escape($string);
			$query.=' AND book_type:' . Book::BOOK_TYPE_BOOK;
			$query.=' AND is_duplicate:false';
			$query.=' AND is_deleted:false';

			$res = $books->search($query, $offset, $limit, array('fl' => 'id,title,subtitle'));

			foreach ($res->response->docs as $document) {
				$result[] = array('type' => 'book', 'id' => $document->id, 'title' => $document->title . ' ' . $document->subtitle);
			}
			$authors = self::$authors;
			$query = 'name:' . $authors->escape($string);
			$query.=' AND is_p_duplicate:false';
			$query.=' AND is_deleted:false';
			$query.=' AND blocked:false';

			$res = $authors->search($query, $offset, $limit, array('fl' => 'id,first_name,last_name,middle_name'));
			foreach ($res->response->docs as $document) {
				$result[] = array('type' => 'author', 'id' => $document->id, 'title' => $document->first_name . ' ' . $document->middle_name . ' ' . $document->last_name);
			}
		} catch (Exception $e) {
			$result['error'] = $e->getMessage();
		}
		return $result;
	}

}