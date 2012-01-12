<?php

// модуль отвечает за отображение баннеров
class events_module extends BaseModule {
	const PER_PAGE = 20;
	const MAX_EVENTS_ON_WALL = 10070;
	const MAX_EVENTS_ON_USER_WALL = 100000;

	public $user_id = false;
	public $type = false;
	public $post_id = false;

	function generateData() {
		global $current_user;
		$params = $this->params;

		$this->user_id = isset($params['user_id']) ? $params['user_id'] : false;
		$this->post_id = isset($params['post_id']) ? $params['post_id'] : false;

		$this->type = isset($params['type']) ? $params['type'] : 'self';

		switch ($this->action) {
			case 'list':
				switch ($this->mode) {
					case 'last':
						$this->getEvents($all = true);
						break;
					case 'user':
						$this->getUserEvents();
						break;
					default:
						$this->getEvents($all = false);
						break;
				}

				break;
			case 'new':
				Error::CheckThrowAuth(User::ROLE_READER_UNCONFIRMED);
				break;
			case 'show':
				$this->getEvent();
				break;
			default:
				throw new Exception('no action #' . $this->action . ' for ' . $this->moduleName);
				break;
		}
	}

	function getEvent() {
		if (!$this->post_id) {
			throw new Exception('illegal event id');
		}
		$query = 'SELECT `mongoid` FROM `events` WHERE `id`=' . (int) $this->post_id;
		$integer_id = Database::sql2single($query);
		if (!(int) $integer_id)
			return;
		if ($this->user_id) {
			$wall = MongoDatabase::getUserWallItem($integer_id, $this->user_id);
			$events = MongoDatabase::getWallEvents($wall);
		} else {
			$events = MongoDatabase::getWallEvents(array(array('id' => $integer_id)));
		}

		Request::pass('post-subject', isset($events[0]['subject']) ? $events[0]['subject'] : 'запись');
		$this->_list($events, $item = true);
	}

	function _list($events, $item = false) {
		$count = isset($events['count']) ? $events['count'] : 0;
		$count = max(0, min(100000, (int) $count));
		unset($events['count']);
		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : self::PER_PAGE;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
		if (!$item) {
			$cond->setPaging($count, $per_page, $pagingName);
			$this->data['conditions'] = $cond->getConditions();
		}
		$book_ids = array();
		$user_ids = array();
		$mongoIds = array();
		$aids = array();
		$sids = array();
		$gids = array();
		foreach ($events as &$event) {
			$mongoIds[$event['id']] = Database::escape($event['id']);
			unset($event['likes']);

			if (isset($event['wall_time']) && $event['wall_time'])
				$event['time'] = date('Y/m/d H:i', $event['wall_time']);
			else
				$event['time'] = date('Y/m/d H:i', $event['time']);
			if (isset($event['book_id'])) {
				if (is_array($event['book_id']))
					foreach ($event['book_id'] as $bid) {
						$book_ids[$bid] = $bid;
						$event['books'][$bid] = array('id' => $bid);
					}
				unset($event['book_id']);
			}

			if (isset($event['author_id'])) {
				foreach ($event['author_id'] as $aid) {
					$aids[$aid] = $aid;
					$event['authors'][$aid] = array('id' => $aid);
				}
				unset($event['author_id']);
			}

			if (isset($event['magazine_id'])) {
				foreach ($event['magazine_id'] as $mid) {
					$mids[$mid] = $mid;
					$event['magazines'][$mid] = array('id' => $mid);
				}
				unset($event['magazine_id']);
			}

			if (isset($event['shelf_id'])) {
				$event['shelf_title'] = Config::$shelves[$event['shelf_id']];
			}

			if (isset($event['serie_id'])) {
				foreach ($event['serie_id'] as $sid) {
					$sids[$sid] = $sid;
					$event['series'][$sid] = array('id' => $sid);
				}
				unset($event['serie_id']);
			}

			if (isset($event['genre_id'])) {
				foreach ($event['genre_id'] as $gid) {
					$gids[$gid] = $gid;
					$event['genres'][$gid] = array('id' => $gid);
				}
				unset($events['genre_id']);
			}

			if (isset($event['owner_id'])) {
				$user_ids[$event['owner_id']] = $event['owner_id'];
			}
			if (isset($event['friend_id'])) {
				foreach ($event['friend_id'] as $fid) {
					$user_ids[$fid] = $fid;
					$event['users'][$fid] = array('id' => $fid);
				}
				unset($event['friend_id']);
			}

			if ($event['user_id'])
				$user_ids[$event['user_id']] = $event['user_id'];
			if (isset($event['retweet_from']) && $event['retweet_from']) {
				$user_ids[$event['retweet_from']] = $event['retweet_from'];
			}
			$comments = array();
			if (isset($event['comments'])) {
				$event['comments'] = array_slice($event['comments'], -15, 15, true);
				$i = 0;
				foreach ($event['comments'] as $id => $comment) {
					$i++;
					$user_ids[$comment['commenter_id']] = $comment['commenter_id'];
					$comments[$i] = array('parent_id' => $event['id'], 'id' => $id, 'commenter_id' => $comment['commenter_id'], 'comment' => $comment['comment'], 'time' => date('Y/m/d H:i', $comment['time']));
					if (isset($comment['answers'])) {
						$j = 0;
						foreach ($comment['answers'] as $ida => $answer) {
							$user_ids[$answer['commenter_id']] = $answer['commenter_id'];
							$comments[$i]['answers'][$j++] = array('parent_id' => $event['id'], 'id' => $ida, 'commenter_id' => $answer['commenter_id'], 'comment' => $answer['comment'], 'time' => date('Y/m/d H:i', $answer['time']));
						}
					}
				}
			}
			$event['comments'] = $comments;
			if (!$this->post_id && isset($event['review'])) { // short
				$event['body'] = $event['review'];
				unset($event['review']);
			}
			if (!$this->post_id && isset($event['short'])) { // short
				$event['body'] = $event['short'];
				unset($event['short']);
			}
			$event['action'] = $event['type'];
					unset($event['type']);
		}

		if (count($mongoIds)) {
			$query = 'SELECT `id`,`mongoid` FROM `events` WHERE `mongoid` IN (' . implode(',', array_values($mongoIds)) . ')';
			$integer_ids = Database::sql2array($query, 'mongoid');
			if (count($integer_ids)) {
				foreach ($events as &$event) {
					if (isset($integer_ids[$event['id']]['id']))
						$event['link_url'] = 'posts/' . $integer_ids[$event['id']]['id'];
					
				}
			}
		}
		if (!$item) {
			$this->data['events'] = $events;

			$this->data['users'] = $this->getEventsUsers($user_ids);
			list($this->data['books'], $aids_) = $this->getEventsBooks($book_ids);
			$aids = array_merge($aids_, $aids);
			$this->data['authors'] = $this->getEventsAuthors($aids);
			$this->data['series'] = $this->getEventsSeries($sids);
			$this->data['genres'] = $this->getEventsGenres($gids);

			$this->data['events']['title'] = 'События';
			$this->data['events']['count'] = count($events);
			if ($this->type == 'not_self')
				$this->data['events']['self'] = $this->user_id;
		}else { // one event
			$this->data['event'] = array_pop($events);

			$this->data['users'] = $this->getEventsUsers($user_ids);
			list($this->data['books'], $aids_) = $this->getEventsBooks($book_ids);
			$aids = array_merge($aids_, $aids);
			$this->data['authors'] = $this->getEventsAuthors($aids);
			$this->data['series'] = $this->getEventsSeries($sids);
			$this->data['genres'] = $this->getEventsGenres($gids);

			if ($this->type == 'not_self')
				$this->data['events']['self'] = $this->user_id;
		}
	}

	function getEventsSeries($sids) {
		if (!count($sids))
			return array();
		$query = 'SELECT id,name,title FROM `series` WHERE `id` IN(' . implode(',', $sids) . ')';
		$out = Database::sql2array($query);
		foreach ($out as &$r) {
			$r['path'] = Config::need('www_path') . '/s/' . $r['id'];
		}
		return $out;
	}

	function getEventsGenres($gids) {
		if (!count($gids))
			return array();
		$query = 'SELECT * FROM `genre` WHERE `id` IN(' . implode(',', $gids) . ')';
		$out = Database::sql2array($query);
		foreach ($out as &$r) {
			$r['path'] = Config::need('www_path') . '/g/' . $r['id'];
		}
		return $out;
	}

	function getEvents($all = false) {
		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : self::PER_PAGE;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';

		if (!$all) {
			$cond->setPaging(self::MAX_EVENTS_ON_USER_WALL, $per_page, $pagingName);
			$limit = $cond->getMongoLimit();
			if (isset($this->params['select']) && $this->params['select'] == 'self') { // выбрали "только свои записи" на "моей стене"
				$wall = MongoDatabase::getUserWall((int) $this->user_id, $limit, $per_page, 'self');
			}else
				$wall = MongoDatabase::getUserWall((int) $this->user_id, $limit, $per_page, $this->type);
		}else {
			$cond->setPaging(self::MAX_EVENTS_ON_WALL, $per_page, $pagingName);
			$limit = $cond->getMongoLimit();
			// показываем просто последнюю активность
			$events = MongoDatabase::getWallLastEvents($per_page, $limit);
			$this->_list($events);
			return;
		}
		$events = MongoDatabase::getWallEvents($wall);
		$this->_list($events);
	}

	function getUserEvents($all = false) {
		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : self::PER_PAGE;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
		$cond->setPaging(self::MAX_EVENTS_ON_WALL, $per_page, $pagingName);
		$limit = $cond->getMongoLimit();
		// показываем просто последнюю активность
		$events = MongoDatabase::findReviewEvents(false, $this->user_id, $per_page, $limit);
		$this->_list($events);
	}

	function getEventsAuthors($aids) {
		if (!count($aids))
			return array();
		$persons = Persons::getInstance()->getByIdsLoaded($aids);
		$out = array();
		foreach ($persons as $person) {
			$out[] = $person->getListData();
		}
		return $out;
	}

	function getEventsUsers($ids) {
		$users = Users::getByIdsLoaded($ids);
		$out = array();
		/* @var $user User */
		if (is_array($users))
			foreach ($users as $user) {
				$out[] = $user->getListData();
			}
		return $out;
	}

	function getEventsBooks($ids, $opts = array(), $limit = false) {
		$person_id = isset($opts['person_id']) ? $opts['person_id'] : false;
		$books = Books::getInstance()->getByIdsLoaded($ids);
		Books::getInstance()->LoadBookPersons($ids);
		$out = array();
		$aids = array();
		/* @var $book Book */
		$i = 0;
		if (is_array($books))
			foreach ($books as $book) {
				if ($limit && ++$i > $limit)
					return $out;
				$out[] = $book->getListData();
				list($author_id, $name) = $book->getAuthor();

				if ($author_id) {
					$aids[$author_id] = $author_id;
				}
			}
		return array($out, $aids);
	}

}