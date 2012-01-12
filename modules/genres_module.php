<?php

// модуль отвечает за отображение баннеров
class genres_module extends BaseModule {

	function generateData() {
		global $current_user;
		$params = $this->params;
		$this->genre_name = isset($params['genre_name']) ? $params['genre_name'] : false;
		$this->genre_id = isset($params['genre_id']) ? (int) $params['genre_id'] : false;
		$this->user_id = isset($params['user_id']) ? $params['user_id'] : $current_user->id;
		$this->author_id = isset($params['author_id']) ? (int) $params['author_id'] : $current_user->id;

		if (!is_numeric($this->user_id)) {
			$query = 'SELECT `id` FROM `users` WHERE `nickname`=' . Database::escape($this->user_id);
			$this->user_id = (int) Database::sql2single($query);
		}

		switch ($this->action) {

			case 'list':
				switch ($this->mode) {
					case 'loved':
						$this->getLoved();
						break;
					default:
						$this->data['genres'] = $this->getAll();
						break;
				}
				break;
			case 'show':
				$this->getOne();
				break;
			case 'edit':
				$current_user->can_throw('books_edit');
				$this->getOne($for_editing = true);
				break;
			default:
				throw new Exception('no action #' . $this->action . ' for ' . $this->moduleName);
				break;
		}
	}

	function getLoved() {
		if (!$this->user_id)
			return;
		$user = new User($this->user_id);
		$ids = $user->getLoved(Config::$loved_types['genre']);

		if (count($ids)) {
			$query = 'SELECT * FROM `genre` WHERE `id` IN(' . implode(',', $ids) . ')';
			$genres = Database::sql2array($query);
			foreach ($genres as &$genre) {
				$genre['path'] = Config::need('www_path') . '/genres/' . $genre['id'];
			}
			$this->data['genres'] = $genres;
		}
		$this->data['genres']['title'] = 'Любимые жанры';
		$this->data['genres']['count'] = count($ids);
		$this->data['genres']['link_title'] = 'Все любимые жанры';
		$this->data['genres']['link_url'] = 'user/' . $this->user_id . '/genres/loved';
	}

	function getOne($for_editing = false) {
		if ($this->genre_id)
			$query = 'SELECT * FROM `genre` WHERE `id`=' . Database::escape($this->genre_id);
		else
			$query = 'SELECT * FROM `genre` WHERE `name`=' . Database::escape($this->genre_name);
		$data = Database::sql2row($query);
		if (!isset($data['name']))
			return;
		$this->data['genre'] = array(
		    'name' => $data['name'],
		    'id' => $data['id'],
		    'id_parent' => $data['id_parent'],
		    'title' => $data['title'],
		    'description' => $data['description'],
		    'books_count' => $data['books_count'],
		    'path' => Config::need('www_path') . '/genres/' . $data['id'],
		    'path_edit' => Config::need('www_path') . '/genres/' . $data['id'] . '/edit',
		);

		Request::pass('genre-title', $data['title']);

		if (!$data['id_parent']) {
			$this->data['genre']['subgenres'] = $this->getAll($data['id']);
			return;
		}

		if (!$for_editing) {
			$query = 'SELECT COUNT(1) FROM `book_genre` BG JOIN `book` B ON B.id = BG.id_book WHERE BG.id_genre = ' . $data['id'] . '';
			$count = Database::sql2single($query);

			$cond = new Conditions();
			$cond->setPaging($count, 20);
			$limit = $cond->getLimit();
			$this->data['conditions'] = $cond->getConditions();

			$query = 'SELECT `id_book` FROM `book_genre` BG JOIN `book` B ON B.id = BG.id_book WHERE BG.id_genre = ' . $data['id'] . ' ORDER BY B.mark DESC LIMIT ' . $limit;
			$bids = Database::sql2array($query, 'id_book');
			$books = Books::getInstance()->getByIdsLoaded(array_keys($bids));
			Books::getInstance()->LoadBookPersons(array_keys($bids));
			$aids = array();
			foreach ($books as $book) {
				$book = Books::getInstance()->getById($book->id);
				list($aid, $aname) = $book->getAuthor(1, 1, 1); // именно наш автор, если их там много
				$this->data['genre']['books'][$book->id] = $book->getListData();
				$aids[$aid] = $aid;
			}
			if (count($aids)) {
				$persons = Persons::getInstance()->getByIdsLoaded($aids);
				foreach ($persons as $person) {
					$this->data['genre']['authors'][] = $person->getListData();
				}
			}

			$this->data['parent'] = array();
			if ($data['id_parent']) {
				$data = Database::sql2row('SELECT * FROM `genre` WHERE `id`=' . Database::escape($data['id_parent']));
				$this->data['genre']['parent'][] = array('name' => $data['name'],
				    'id' => $data['id'],
				    'id_parent' => $data['id_parent'],
				    'title' => $data['title'],
				    'description' => $data['description'],
				    'books_count' => $data['books_count'],
				    'path' => Config::need('www_path') . '/genres/' . $data['id'],
				    'path_edit' => Config::need('www_path') . '/genres/' . $data['id'] . '/edit');
			};
			
		}
	}

	function getAll($parent_id = 0) {
		if (!$parent_id)
			$query = 'SELECT id,id_parent,name,title,books_count FROM `genre` ORDER BY `id_parent`,`books_count` DESC';
		else
			$query = 'SELECT id,id_parent,name,title,books_count FROM `genre` WHERE id_parent=' . $parent_id . ' ORDER BY `id_parent`,`books_count` DESC';
		$genres = Database::sql2array($query);
		$genres_prepared = array();
		$parents = array();
		foreach ($genres as &$g) {
			$g['path'] = Config::need('www_path') . '/genres/' . $g['id'];
			$g['path_edit'] = Config::need('www_path') . '/genres/' . $g['id'] . '/edit';
			$parents[$g['id_parent']][] = $g;
		}
		if (isset($parents[$parent_id]))
			foreach ($parents[$parent_id] as $item) {
				$genres_prepared[$item['id']] = $item;
				if (isset($parents[$item['id']])) {
					foreach ($parents[$item['id']] as $item_2) {
						$genres_prepared[$item['id']]['subgenres'][$item_2['id']] = $item_2;
					}
				}
			}

		return $genres_prepared;
	}

}