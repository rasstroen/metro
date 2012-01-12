<?php


class books_module extends CommonModule {

	function setCollectionClass() {
		$this->Collection = Books::getInstance();
	}

	// some additional data for editing page
	function _edit() {
		if (isset(Request::$get['author_id'])) {
			$persons = Persons::getInstance()->getByIdsLoaded(array((int) Request::$get['author_id']));
			if (isset($persons[Request::$get['author_id']])) {
				$person = $persons[Request::$get['author_id']];
				/* @var $person Person */
				$this->data['book']['author'] = $person->getListData();
			}
		}
		foreach (Config::$person_roles as $id => $title) {
			$this->data['book']['roles'][] = array(
			    'id' => $id,
			    'title' => $title,
			);
		}
		foreach (Config::$langRus as $code => $title) {
			$this->data['book']['lang_codes'][] = array(
			    'id' => Config::$langs[$code],
			    'code' => $code,
			    'title' => $title,
			);
		}
		$query = 'SELECT * FROM `rightholders` ORDER BY `title`';
		$this->data['book']['rightholders'] = Database::sql2array($query);
		// fake html file
		if (isset($this->data['book']['files'][0]))
			unset($this->data['book']['files'][0]);
	}

	function _process($action, $mode) {
		global $current_user;

		if (isset($this->params['user_id']) && $this->params['user_id']) {
			if (in_array($this->params['user_id'], array('me', 'books')))
				$this->params['user_id'] = $current_user->id;
		}else
			$this->params['user_id'] = $current_user->id;

		switch ($action) {
			case 'show':
				switch ($mode) {
					case 'download':
						$this->getDownload();
						break;
					case 'read':
						$this->getRead();
						break;
					default:
						$this->_show($this->params['book_id']);
						break;
				}
				break;
			case 'edit':
				switch ($mode) {
					default:
						$current_user->can_throw('books_edit');
						$this->_show($this->params['book_id']);
						$this->_edit();
						$this->getBookRelations();
						break;
				}
				break;
			case 'new':
				switch ($mode) {
					default:
						$current_user->can_throw('books_edit');
						$this->_edit();
						break;
				}
				break;
			case 'list':
				switch ($mode) {
					case 'search':
						$this->getSearch();
						break;
					case 'author_books':
						$this->getBibliography();
						break;
					case 'editions':
						$this->getEditions();
						break;
					case 'translations':
						$this->getTranslations();
						break;
					case 'similar':
						$this->getSimilar();
						break;
					case 'popular':
						$this->getPopular();
						break;
					case 'new':
						$this->getNew();
						break;
					case 'loved':
						$this->getLoved();
						break;
					case 'shelves':
						$this->getShelves();
						break;
					case 'shelf':
						$this->getShelf();
						break;
					case 'bibliography':
						$this->getBibliography();
						break;
					default:
						throw new Exception('no mode #' . $this->mode . ' for ' . $this->moduleName);
						break;
				}
				break;
			default:
				throw new Exception('no action #' . $this->action . ' for ' . $this->moduleName);
				break;
		}
	}

	function getRead() {
		if (!$this->params['book_id'])
			return;
		$book = Books::getInstance()->getByIdLoaded($this->params['book_id']);
		/* @var $book Book */
		if ($book->exists) {
			$this->data['book']['table_of_contents'] = $book->getTOC();
			$this->data['book']['html'] = $book->getHTML();
			$book->setReaded();
		}
	}

	function getSearch() {
		$query_string = isset(Request::$get_normal['q']) ? trim(Request::$get_normal['q']) : false;
		$search = Search::getInstance();
		/* @var $search Search */
		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 5;
		$cond->setPaging(1000, $per_page);

		$offset = $cond->getMongoLimit();
		list($bids, $count, $hl) = $search->searchBooksByString($query_string, $offset, $per_page);
		$cond = new Conditions();
		$cond->setPaging($count, $per_page);

		Request::$get_normal['q'] = isset(Request::$get_normal['q']) ? Request::$get_normal['q'] : '';
		$this->data = $this->_idsToData($bids);
		foreach ($this->data['books'] as &$book) {
			if (isset($hl[$book['id']]))
				$book['path'] .= '#hl=' . implode(' ', $hl[$book['id']]);
			else
				$book['path'] .= '#hl=' . Request::$get_normal['q'];
		}
		$this->data['conditions'] = $cond->getConditions();
		if ($query_string)
			$this->data['books']['title'] = 'Книги по запросу «' . $query_string . '»';
		else
			$this->data['books']['title'] = 'Книги';
		$this->data['books']['count'] = $count;
	}

	function getEditions() {
		if (!$this->params['book_id'])
			return;
		$ids = array();
		$book = Books::getInstance()->getByIdLoaded($this->params['book_id']);
		$rels = BookRelations::getBookRelations($this->params['book_id']);
		if ($rels) {
			$ids = Database::sql2array('SELECT `id` FROM `book` WHERE `id` IN(' . implode(',', array_keys($rels)) . ') AND `is_deleted`=0 AND `id`<>' . $this->params['book_id'] . ' AND `id_lang`=' . $book->getLangId(), 'id');
		}else
			$ids = array();
		$this->data = $this->_idsToData(array_keys($ids));
		$this->data['books']['title'] = 'Редакции';
		$this->data['books']['count'] = count($ids);
	}

	function getTranslations() {
		if (!$this->params['book_id'])
			return;
		$ids = array();
		$book = Books::getInstance()->getByIdLoaded($this->params['book_id']);
		$rels = BookRelations::getBookRelations($this->params['book_id']);
		/* @var $book Book */
		if ($rels) {
			$ids = Database::sql2array('SELECT `id` FROM `book` WHERE `id` IN(' . implode(',', array_keys($rels)) . ') AND `is_deleted`=0 AND `id`<>' . $this->params['book_id'] . ' AND `id_lang`<>' . $book->getLangId(), 'id');
		}else
			$ids = array();

		$this->data = $this->_idsToData(array_keys($ids));
		$this->data['books']['title'] = 'Переводы';
		$this->data['books']['count'] = count($ids);
	}

	function getDownload() {
		if (!$this->params['book_id']) {
			return false;
		}
		$book = Books::getInstance()->getByIdLoaded($this->params['book_id']);
		/* @var $book Book */
		if (!$book->loaded)
			return false;

		$files = $book->getFiles();
		$imf = $book->data['id_main_file'];
		$path = false;
		foreach ($files as $id => $file) {
			$type = $file['filetype'];
			if ($id == $imf)
				$path = getBookDownloadUrl($id, $this->params['book_id'], $type);
			if (!$path && !$imf) {
				$path = getBookDownloadUrl($id, $this->params['book_id'], $type);
			}
		}
		if (!$path)
			throw new Exception('no main file');
		header('Location:' . $path);
		exit();
	}

	function getBookRelations() {
		if (!$this->params['book_id'])
			return;
		$book = Books::getInstance()->getByIdLoaded($this->params['book_id']);
		foreach (BookRelations::$relation_types as $id => $title) {
			$this->data['book']['relation_types'][] = array('id' => $id, 'name' => $title);
		}
		/* @var $book Book */
		if (!$book->loaded) {
			return false;
		}

		$bids = array();
		if ($basket_id = $book->getBasketId()) {
			$query = 'SELECT * FROM `book_basket` WHERE `id_basket`=' . $basket_id;
			$relations = Database::sql2array($query);
			foreach ($relations as $relation) {
				$bids[$relation['id_book']] = $relation['id_book'];
			}
			Books::getInstance()->getByIdsLoaded($bids);
			foreach ($relations as &$relation) {
				$relbook = Books::getInstance()->getByIdLoaded($relation['id_book']);
				/* @var $relbook Book */
				if ($relbook->id == $book->id)
					continue;
				$bids[$relation['id_book']] = $relation['id_book'];
				if ($book->getLangId() != $relbook->getLangId())
					$relation['type'] = BookRelations::RELATION_TYPE_TRANSLATE;
				else
					$relation['type'] = BookRelations::RELATION_TYPE_EDITION;

				$relation['relation_type_name'] = BookRelations::$relation_types[$relation['type']];
				$relation['id1'] = $book->id;
				$relation['id2'] = $relation['id_book'];
				$this->data['book']['relations'][] = $relation;
			}
		}

		$query = 'SELECT `id`,`is_duplicate` FROM `book` WHERE `is_duplicate`=' . $book->id . ' OR `id`=' . $book->id;
		$rows = Database::sql2array($query);
		if (count($rows)) {
			foreach ($rows as $row) {
				if ($row['is_duplicate']) {
					$relation = array(
					    'desc' => $row['id'] . ' is duplicate for ' . $row['is_duplicate'],
					    'id2' => (int) $row['id'],
					    'id1' => (int) $row['is_duplicate'],
					    'id_book' => (int) $row['id'],
					    'type' => BookRelations::RELATION_TYPE_DUPLICATE,
					    'relation_type_name' => BookRelations::$relation_types[BookRelations::RELATION_TYPE_DUPLICATE]
					);
					$this->data['book']['relations'][] = $relation;
					$bids[$row['id']] = $row['id'];
					$bids[$row['is_duplicate']] = $row['is_duplicate'];
				}
			}
		}
		$data = $this->_idsToData(array_keys($bids));
		$this->data['book']['relations']['books'] = $data['books'];
		$this->data['book']['relations']['authors'] = isset($data['authors']) ? $data['authors'] : array();
	}

	function getBibliography() {
		$author_id = (int) $this->params['author_id'];
		if (!$author_id)
			return;
		$ids = Database::sql2array('SELECT `id` FROM `book` B LEFT JOIN `book_persons` BP ON BP.id_book = B.id WHERE
			B.`is_deleted` = 0 AND
			BP.`id_person`=' . $author_id . '
			AND BP.`person_role`=' . Book::ROLE_AUTHOR, 'id');
		$this->data = $this->_idsToData(array_keys($ids), 10);



		$person = Persons::getInstance()->getByIdLoaded($author_id);
		$this->data['person'] = $person->getListData();

		$this->data['books']['title'] = 'Книги автора';
		$this->data['books']['count'] = count($ids);
		$this->data['books']['link_url'] = 'a/' . $author_id . '/bibliography';
		$this->data['books']['link_title'] = 'Вся библиография';
	}

	function getNew() {
		$ago = time() - 2 * 31 * 24 * 60 * 60;
		$where = '`is_deleted`=0 AND `add_time`>' . $ago;
		$sortings = array(
		    'add_time' => array('title' => 'по дате добавления', 'order' => 'desc'),
		);


		if (Request::is_on_main_page()) {
			$this->_list($where, array(), false, $sortings);
		}else
			$this->_list($where, $sortings, false, $sortings);

		$this->data['books']['title'] = 'Новые книги';
		$this->data['books']['count'] = $this->getCountBySQL($where);
		if (Request::is_on_main_page()) {
			$this->data['books']['link_title'] = 'Все новые книги';
			$this->data['books']['link_url'] = 'new/';
		}
	}

	function getSimilar() {

		if (!$this->params['book_id'])
			return;
		$book = Books::getInstance()->getByIdLoaded($this->params['book_id']);

		$ids = $book->getReadedWith();
		if (!count($ids))
			return;

		$where = '`is_deleted`=0 AND `id` IN(' . implode(',', $ids) . ')';

		$this->_list($where);
		$this->data['books']['title'] = 'С этой книгой читают';
		$this->data['books']['count'] = $this->getCountBySQL($where);
	}

	function getPopular() {
		$min_mark = 40;
		$where = '`is_deleted`=0 AND `mark`>' . $min_mark;
		$sortings = array(
		    'add_time' => array('title' => 'по дате добавления'),
		    'rating' => array('title' => 'по популярности'),
		);
		$dsortings = array(
		    'rating' => array('title' => 'по популярности', 'order' => 'desc'),
		);
		$this->_list($where, $sortings, false, $dsortings);
		$this->data['books']['title'] = 'Популярные книги';
		$this->data['books']['count'] = $this->getCountBySQL($where);
	}

	function getLoved() {
		if (!$this->params['user_id'])
			return;
		$user = new User($this->params['user_id']);
		if (!$user)
			return;

		$ids = $user->getLoved(Config::$loved_types['book']);
		$this->data = $this->_idsToData($ids, isset($this->params['limit']) ? (int) $this->params['limit'] : false);

		if (isset($this->params['limit']) && $this->params['limit']) {
			$this->data['books']['title'] = 'Любимые книги';
			$this->data['books']['count'] = count($ids);
			$this->data['books']['link_title'] = 'Все любимые книги';
			$this->data['books']['link_url'] = 'user/' . $this->params['user_id'] . '/books/loved';
		}
	}

	function getShelf() {
		if ($this->params['shelf_type'] == 'loved')
			return $this->getLoved();
		$shelfCurrent = isset(Config::$shelfIdByNames[$this->params['shelf_type']]) ? Config::$shelfIdByNames[$this->params['shelf_type']] : false;
		if ($shelfCurrent === false)
			return;
		global $current_user;
		/* @var $current_user CurrentUser */
		/* @var $user User */

		$user = ($current_user->id === $this->params['user_id']) ? $current_user : Users::getById($this->params['user_id']);
		$bookShelf = $user->getBookShelf();

		$sort_type = Request::get(3, 'time');
		$sort_function = 'sort_by_add_time';
		switch ($sort_type) {
			case 'genre':
				$sort_function = 'sort_by_genre';
				break;
			case 'mark':
				$sort_function = 'sort_by_mark';
				break;
			default:
				$sort_function = 'sort_by_add_time';
				break;
		}

		foreach ($bookShelf as $shelf => &$books)
			if ($shelf == $shelfCurrent)
				uasort($books, $sort_function);

		$bookIds = array();
		foreach ($bookShelf as $shelf => $ids) {
			if ($shelf == $shelfCurrent)
				foreach ($ids as $bookId => $data)
					$bookIds[$bookId] = $bookId;
		}
		// все эти книжки нужно подгрузить
		Books::getInstance()->getByIdsLoaded($bookIds);
		Books::getInstance()->LoadBookPersons($bookIds);
		$shelfcounter = array($shelfCurrent => 0);
		foreach ($bookShelf as $shelf => $ids) {
			if ($shelf == $shelfCurrent)
				foreach ($ids as $bookId => $data) {
					$book = Books::getInstance()->getById($bookId);
					if (isset($shelfcounter[$shelf]))
						$shelfcounter[$shelf]++;
					else
						$shelfcounter[$shelf] = 1;
					if ($shelfcounter[$shelf] > 10)
						continue;
					/* @var $book Book */
					list($author_id, $author_name) = $book->getAuthor();
					$this->data['books'][$bookId] = $book->getListData(false, 'small');
					if ($author_id) {
						$r = Persons::getInstance()->getByIdsLoaded($author_id);
						$r = Persons::getInstance()->getById($author_id);
						$this->data['authors'][$author_id] = $r->getListData();
					}
				}
		}
		$this->data['books']['count'] = (int) $shelfcounter[$shelfCurrent];
		$this->data['books']['title'] = Config::$shelves[$shelfCurrent];
	}

	function getShelves() {
		global $current_user;
		/* @var $current_user CurrentUser */
		/* @var $user User */
		$user = ($current_user->id === $this->params['user_id']) ? $current_user : Users::getById($this->params['user_id']);
		if (!$user->loaded)
			$user = $current_user;
		if (!$user->loaded)
			return;
		$bookShelf = $user->getBookShelf();
		foreach ($bookShelf as $shelf => &$books)
			uasort($books, 'sort_by_add_time');
		$bookIds = array();
		foreach ($bookShelf as $shelf => $ids) {
			foreach ($ids as $bookId => $data)
				$bookIds[$bookId] = $bookId;
		}
		// все эти книжки нужно подгрузить
		Books::getInstance()->getByIdsLoaded($bookIds);
		Books::getInstance()->LoadBookPersons($bookIds);
		$shelfcounter = array(1 => 0, 2 => 0, 3 => 0);
		foreach ($bookShelf as $shelf => $ids) {
			foreach ($ids as $bookId => $data) {
				$book = Books::getInstance()->getById($bookId);
				if (isset($shelfcounter[$shelf]))
					$shelfcounter[$shelf]++;
				else
					$shelfcounter[$shelf] = 1;
				if ($shelfcounter[$shelf] > 10)
					continue;
				/* @var $book Book */

				list($author_id, $author_name) = $book->getAuthor();
				$this->data['shelves'][$shelf]['books'][$bookId] = $book->getListData(false, 'small');
				if ($author_id) {
					$r = Persons::getInstance()->getByIdsLoaded($author_id);
					$r = Persons::getInstance()->getById($author_id);
					$this->data['authors'][$author_id] = $r->getListData();
				}
			}
		}
		foreach (Config::$shelves as $id => $title) {
			$this->data['shelves'][$id]['books']['count'] = (int) $shelfcounter[$id];
			$this->data['shelves']['user_id'] = $this->params['user_id'];
			$this->data['shelves'][$id]['books']['title'] = $title;
			$this->data['shelves'][$id]['books']['link_title'] = 'Перейти на полку «' . $title . '»';
			$this->data['shelves'][$id]['books']['link_url'] = 'user/' . $this->params['user_id'] . '/books/' . Config::$shelfNameById[$id];
		}
	}

}