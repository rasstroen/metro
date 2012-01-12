<?php

class statistics_module extends BaseModule {

	function generateData() {
		global $current_user;
		/* @var $current_user CurrentUser */
		$current_user->can_throw('statistics_view');
		switch ($this->action) {
			case 'list':
				switch ($this->mode) {
					case 'books':
						$this->getStatistics('book');
						break;
					case 'users':
						$this->getStatistics('user');
						break;
					case 'authors':
						$this->getStatistics('author');
						break;
					case 'genres':
						$this->getStatistics('genre');
						break;
					case 'book':
						$this->getStatistics('book', $this->params['book_id']);
						break;
					case 'user':
						$this->getStatistics('user', $this->params['user_id']);
						break;
					case 'author':
						$this->getStatistics('author', $this->params['author_id']);
						break;
					case 'genre':
						$this->getStatistics('genre', $this->params['genre_id']);
						break;
				}
				break;
		}
	}

	function getStatistics($type, $id_target = false) {
		$id_target = $id_target ? max(0, (int) $id_target) : false;
		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 1;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';


		$time_start = Request::get('from');
		$time_end = Request::get('to');

		$curtime = time();
		if (!$time_start) {
			$time_start = strtotime(date('Y-m-1 00:00:00'));
		} else {
			$time_start = strtotime(date('Y-m-d 00:00:00', strtotime($time_start)));
		}

		if (!$time_end) {
			$time_end = strtotime(date('Y-m-d 23:59:59'));
		} else {

			$time_end = strtotime(date('Y-m-d 23:59:59', strtotime($time_end)));
		}

		if ($time_end < $time_start) {
			$t = $time_end;
			$time_end = $time_start;
			$time_start = $t;
		}

		$where = 'WHERE (`time` >= ' . $time_start . ' AND `time` <= ' . $time_end . ')';
		if ($id_target)
			$where.=' AND `id_' . $type . '`=' . $id_target;

		if ($type == 'user')
			$order = 'ORDER BY COUNT(1) DESC ';
		else
			$order = 'ORDER BY `count` DESC ';
		$group_by = 'GROUP BY `id_' . $type . '`';
		if ($id_target)
			$group_by = 'GROUP BY `time`';

		$query = 'SELECT COUNT(1) FROM (SELECT 1 FROM `stat_' . $type . '_download`' . $where . ' ' . $group_by . ') s ';
		$count = Database::sql2single($query);
		$cond->setPaging($count, $per_page, $pagingName);
		$limit = $cond->getLimit();
		$limit = ' LIMIT ' . $limit;
		if ($type == 'user')
			$query = 'SELECT `id_' . $type . '`,COUNT(1) as `count` , `time` FROM `stat_' . $type . '_download`' . $where . ' ' . $group_by . ' ' . $order . ' ' . $limit;
		else
			$query = 'SELECT `id_' . $type . '`,sum(`count`) as `count` , `time` FROM `stat_' . $type . '_download`' . $where . ' ' . $group_by . ' ' . $order . ' ' . $limit;
		$statistics = Database::sql2array($query);
		$gids = array();
		$aids = array();
		$bids = array();
		$uids = array();

		foreach ($statistics as &$stat) {
			if ($type == 'genre')
				$gids[$stat['id_genre']] = $stat['id_genre'];
			if ($type == 'author')
				$aids[$stat['id_author']] = $stat['id_author'];
			if ($type == 'book')
				$bids[$stat['id_book']] = $stat['id_book'];
			if ($type == 'user')
				$uids[$stat['id_user']] = $stat['id_user'];
			$stat['time'] = date('Y/m/d', $stat['time']);
			$stat['path'] = Config::need('www_path') . '/admin/' . $type . 's/' . $stat['id_' . $type];
		}

		$this->data['genres'] = $this->getStatGenres($gids);
		if (!count($aids))
			list($this->data['books'], $aids) = $this->getStatBooks($bids);
		$this->data['authors'] = $this->getStatAuthors($aids);
		$this->data['users'] = $this->getStatUsers($uids);

		$this->data['statistics'] = $statistics;
		$this->data['statistics']['from'] = date('Y.m.d', $time_start);
		$this->data['statistics']['to'] = date('Y.m.d', $time_end);

		$title_part = '';
		if ($type == 'book') {
			$title_part = 'книг';
			$title_part_m = 'книги';
		}
		if ($type == 'author') {
			$title_part = 'книг авторов';
			$title_part_m = 'книг автора';
		}
		if ($type == 'genre') {
			$title_part = 'книг жанров';
			$title_part_m = 'книг жанра';
		}
		if ($type == 'user') {
			$title_part = ' книг пользователями';
			$title_part_m = ' книг пользователем ';
		}


		if (!$id_target)
			$this->data['statistics']['title'] = 'Статистика по скачиванию ' . $title_part . ' за период с ' . $this->data['statistics']['from'] . ' по ' . $this->data['statistics']['to'];
		else
			$this->data['statistics']['title'] = 'Статистика по скачиванию ' . $title_part_m . ' за период с ' . $this->data['statistics']['from'] . ' по ' . $this->data['statistics']['to'];
		$this->data['statistics']['count'] = $count;
		$this->data['conditions'] = $cond->getConditions();

		$this->data['statistics']['current_month_path'] = Request::$url . '?from=' . date('Y-m-01', strtotime(date('Y-m-1 00:00:00'))) . '&to=' . date('Y-m-d', strtotime(date('Y-m-d 23:59:59')) + 1);
		$this->data['statistics']['last_month_path'] = Request::$url . '?from=' . date('Y-m-01', strtotime(date('Y-m-1 00:00:00')) - 1) . '&to=' . date('Y-m-d', strtotime(date('Y-m-1 00:00:00')) - 1);
	}

	function getStatBooks($ids, $opts = array(), $limit = false) {
		$person_id = isset($opts['person_id']) ? $opts['person_id'] : false;
		$books = Books::getInstance()->getInstance()->getByIdsLoaded($ids);
		Books::getInstance()->getInstance()->LoadBookPersons($ids);
		$out = array();
		$aids = array();
		/* @var $book Book */
		$i = 0;
		if (is_array($books))
			foreach ($books as &$book) {
				if ($limit && ++$i > $limit)
					return $out;
				$tmp = $book->getListData();
				list($author_id, $name) = $book->getAuthor();

				if ($author_id) {
					$aids[$author_id] = $author_id;
				}
				$tmp['path'] = Config::need('www_path') . '/admin/books/' . $book->id;
				$out[] = $tmp;
			}
		return array($out, $aids);
	}

	function getStatAuthors($aids) {
		if (!count($aids))
			return array();
		$persons = Persons::getInstance()->getByIdsLoaded($aids);
		$out = array();
		foreach ($persons as $person) {
			$out[] = $person->getListData();
		}
		foreach ($out as &$r) {
			$r['path'] = Config::need('www_path') . '/admin/authors/' . $r['id'];
		}
		return $out;
	}
	
	function getStatUsers($uids) {
		if (!count($uids))
			return array();
		$users = Users::getByIdsLoaded($uids);
		$out = array();
		foreach ($users as $user) {
			$out[] = $user->getListData();
		}
		foreach ($out as &$r) {
			$r['path'] = Config::need('www_path') . '/admin/users/stat/' . $r['id'];
		}
		return $out;
	}

	function getStatGenres($gids) {
		if (!count($gids))
			return array();
		$query = 'SELECT * FROM `genre` WHERE `id` IN(' . implode(',', $gids) . ')';
		$out = Database::sql2array($query);
		foreach ($out as &$r) {
			$r['path'] = Config::need('www_path') . '/admin/genres/' . $r['id'];
		}
		return $out;
	}

}