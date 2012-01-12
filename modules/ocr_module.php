<?php

class ocr_module extends CommonModule {

	function setCollectionClass() {
		$this->Collection = Books::getInstance();
	}

	// some additional data for editing page

	function _process($action, $mode) {
		global $current_user;
		switch ($action) {
			case 'new':
				$this->getNew();
				break;
			case 'list':
				switch ($mode) {
					case 'book':
						$this->getOcrBook();
						break;
					// ocr list for book
					case 'nofile':
						$this->getOcrList_withoutFile();
						break;
					case 'badfile':
						$this->getOcrList_withBadFile();
						break;
					// global ocr list
					default:
						throw new Exception('no action #' . $this->action . ' mode#' . $this->mode . ' for ' . $this->moduleName);
						break;
				}
				break;
			default:
				throw new Exception('no action #' . $this->action . ' for ' . $this->moduleName);
				break;
		}
	}

	function setStatusesNode() {
		$this->data['statuses'] = Ocr::$statuses;
		$this->data['states'] = Ocr::$states;
	}

	function getNew() {
		$id_book = max(0, (int) $this->params['id_book']);
		if (!$id_book)
			throw new Exception('illegal book id');
		$query = 'SELECT `id_book`,`status`, `state` , `time` FROM `ocr` WHERE `id_book`=' . $id_book . ' ORDER BY status DESC, state DESC LIMIT 1';
		$this->data['ocr'] = Database::sql2row($query);
		if (isset(Ocr::$statuses[$this->data['ocr']['status']]['name']))
			$this->data['ocr']['status_name'] = Ocr::$statuses[$this->data['ocr']['status']]['name'];
		if (isset(Ocr::$statuses[$this->data['ocr']['state']]['name']))
			$this->data['ocr']['state_name'] = Ocr::$states[$this->data['ocr']['state']]['name'];
		$this->data['ocr']['id_book'] = $id_book;
		$this->setStatusesNode();
	}

	function getOcrBook() {
		$id_book = max(0, (int) $this->params['id_book']);
		if (!$id_book)
			throw new Exception('illegal book id');

		$where = '`flag`=0';

		$query = 'SELECT * FROM `ocr` WHERE `id_book`=' . $id_book;
		$ocr = Database::sql2array($query);
		$user_ids = array();
		foreach ($ocr as &$task) {
			$task['date'] = date('Y/m/d H:i:s', $task['time']);
			$user_ids[$task['id_user']] = $task['id_user'];
			$task['status_name'] = Ocr::$statuses[$task['status']]['name'];
			$task['state_name'] = Ocr::$states[$task['state']]['name'];
		}
		$this->setStatusesNode();
		$this->data['ocr'] = $ocr;

		$this->data['users'] = $this->getOcrUsers($user_ids);
	}

	/*
	 * все книги без файла
	 */

	function getOcrList_withoutFile() {
		$min_mark = 40;
		$where = '`id_main_file`=0 AND `book_type`=' . Book::BOOK_TYPE_BOOK;
		$sortings = array(
		    'add_time' => array('title' => 'по дате добавления', 'order' => 'desc'),
		);
		$this->_list($where, array(), false, $sortings);
		$bids = array();

		foreach ($this->data['books'] as $book) {
			$bids[$book['id']] = $book['id'];
		}
		$this->prepareOcrForBooks($bids);
		$this->data['books']['title'] = 'Книги без файлов';
		$this->data['books']['count'] = $this->getCountBySQL($where);
	}

	/*
	 * все книги c плохим файлом
	 */

	function getOcrList_withBadFile() {
		$min_mark = 40;
		$where = '`id_main_file`>0 AND `book_type`=' . Book::BOOK_TYPE_BOOK . ' AND `mark`<' . Book::BOOK_MARK_GOOD;
		$sortings = array(
		    'mark' => array('title' => 'по оценке', 'order' => 'ask'),
		);
		$this->_list($where, array(), false, $sortings);
		$bids = array();

		foreach ($this->data['books'] as $book) {
			$bids[$book['id']] = $book['id'];
		}
		$this->prepareOcrForBooks($bids);
		$this->data['books']['title'] = 'Книги с плохими файлами(меньше 4 оценки)';
		$this->data['books']['count'] = $this->getCountBySQL($where);
	}

	function prepareOcrForBooks($bids) {

		$max_status = array();
		$max_state = array();

		if (count($bids)) {
			$query = 'SELECT * FROM `ocr` WHERE `id_book` IN(' . implode(',', $bids) . ')';
			$ocr = Database::sql2array($query);
		}
		else
			$ocr = array();
		$uids = array();
		foreach ($ocr as $ocr_row) {
			$max_status[$ocr_row['id_book']] = 0;
			$max_state[$ocr_row['id_book']] = 0;
			$status = $ocr_row['status'];
			$state = $ocr_row['state'];
			// if it was status with bigger state - ignore

			if ($max_status[$ocr_row['id_book']] < $status) {
				$max_status[$ocr_row['id_book']] = $status;
				if ($max_state[$ocr_row['id_book']] < $state)
					$max_state[$ocr_row['id_book']] = $state;
			}

			if (isset($this->data['books'][$ocr_row['id_book']]['statuses'][$status]['state'])) {
				if ($this->data['books'][$ocr_row['id_book']]['statuses'][$status]['state'] > $state)
					continue;
				if ($this->data['books'][$ocr_row['id_book']]['statuses'][$status]['state'] > $state)
					$this->data['books'][$ocr_row['id_book']]['statuses'][$status] = array();
			}else {
				$this->data['books'][$ocr_row['id_book']]['statuses'][$status]['status'] = $status;
				$this->data['books'][$ocr_row['id_book']]['statuses'][$status]['state'] = $state;
			}
			$this->data['books'][$ocr_row['id_book']]['statuses'][$status]['status_name'] = Ocr::$statuses[$ocr_row['status']]['name'];
			$this->data['books'][$ocr_row['id_book']]['statuses'][$status]['state_name'] = Ocr::$states[$ocr_row['state']]['name'];
			$this->data['books'][$ocr_row['id_book']]['statuses'][$status]['users'][] =
				array('id_user' => $ocr_row['id_user'], 'date' => date('Y/m/d H:i:s', $ocr_row['time']));
			$uids[$ocr_row['id_user']] = $ocr_row['id_user'];

			if (isset($max_status[$ocr_row['id_book']])) {
				$this->data['books'][$ocr_row['id_book']]['statuses']['max_status'] = $max_status[$ocr_row['id_book']];
				$this->data['books'][$ocr_row['id_book']]['statuses']['max_state'] = $max_state[$ocr_row['id_book']];
				$this->data['books'][$ocr_row['id_book']]['statuses']['max_status_name'] = Ocr::$statuses[$max_status[$ocr_row['id_book']]]['name'];
				$this->data['books'][$ocr_row['id_book']]['statuses']['max_state_name'] = Ocr::$states[$max_state[$ocr_row['id_book']]]['name'];
			}
		}



		$this->data['users'] = $this->getOcrUsers($uids);
	}

	/**
	 * вынимаем все книги в ocr
	 */
	function getOcrListOld() {
		/**
		 * paging & sorting
		 */
		$where = '`flag`=0';
		$sortings = array(
		    'ocr.time' => array('title' => 'по дате добавления'),
		    'rating' => array('title' => 'по популярности'),
		);
		$default_sortings = array(
		    'ocr.time' => array('title' => 'по дате добавления', 'order' => 'desc'),
		);

		$count = (int) Database::sql2single('SELECT COUNT(1) FROM (SELECT 1 FROM `ocr` WHERE ' . $where . ' GROUP BY `id_book`) c ');

		$limit = false;
		$order = false;
		$sorting_order = false;
		$cond = new Conditions();
		if ($this->ConditionsEnabled) {
			// пейджинг, сортировка
			if ($sortings || $default_sortings) {
				$cond->setSorting($sortings, $default_sortings);
				$order = $cond->getSortingField();
				$sorting_order = $cond->getSortingOrderSQL();
			}
			$per_page = isset($this->params['per_page']) ? $this->params['per_page'] : 0;
			$limit_parameter = isset($this->params['limit']) ? $this->params['limit'] : 0;
			$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
			if ($per_page) {
				$cond->setPaging($count, $per_page, $pagingName);
				$limit = $cond->getLimit();
			}
			if ($limit_parameter) {
				$cond->setLimit($limit_parameter);
				$limit = $cond->getLimit();
			}
		}

		$query = 'SELECT `id_book` FROM `ocr`
			LEFT JOIN `book` B ON B.id = `ocr`.`id_book`
			WHERE ' . $where . '
			GROUP BY `id_book` ' .
			($order ? ('ORDER BY ' . $order . ' ' . $sorting_order) : '') . '
			LIMIT ' . $limit . '';
		// получили список книг
		$bids = Database::sql2array($query, 'id_book');
		// получаем статусы полученных книг
		$query = 'SELECT * FROM `ocr` WHERE `id_book` IN(' . implode(',', array_keys($bids)) . ') ORDER BY `time` DESC';
		$tasks = Database::sql2array($query);
		$task_book = array();

		foreach ($tasks as &$task) {
			$task['date'] = date('Y/m/d H:i:s', $task['time']);
			$user_ids[$task['id_user']] = $task['id_user'];
			$task_book[$task['id_book']][] = $task;
		}

		$this->data = $this->_idsToData(array_keys($bids));
		foreach ($this->data['books'] as &$book) {
			if (isset($task_book[$book['id']])) {
				$book['statuses'] = $task_book[$book['id']];
			}
		}

		$this->data['books']['title'] = 'Книги в работе';
		$this->data['books']['count'] = $count;
		$this->data['users'] = $this->getOcrUsers($user_ids);

		$this->setStatusesNode();
		$this->data['conditions'] = $cond->getConditions();
	}

	function getOcrUsers($ids) {
		$users = Users::getByIdsLoaded($ids);
		$out = array();
		/* @var $user User */
		if (is_array($users))
			foreach ($users as $user) {
				$out[] = $user->getListData();
			}
		return $out;
	}

}