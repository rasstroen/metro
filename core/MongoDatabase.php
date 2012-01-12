<?php

class MongoDatabase {

	private static $instance = false;

	public static function getInstance() {
		if (!self::$instance) {
			$s = new Mongo(Config::need('mongohost'));
			self::$instance = $s->ls2;
			//self::getInstance()->events->remove();
			//self::getInstance()->walls->remove();
		}
		return self::$instance;
	}

	public static function ensureIndexes() {
		$index_user = array(
		    array('_id' => 1),
		    array('unique' => true, 'background' => true)
		);
		$walls_index = array(
		    array('_id' => 1),
		    array('unique' => true, 'background' => true)
		);
		$events_index = array(
		    array('time' => -1),
		    array('unique' => true, 'background' => true)
		);
	}

	// ставим атрибуты пользователю
	public static function setUserAttributes($id_user, array $attributes) {
		$attributes = array('$set' => $attributes);
		self::getInstance()->users->update(array('_id' => (int) $id_user), $attributes, array('upsert' => true));
	}

	// цепляем все аттрибуты пользователя
	public static function getUserAttributes($id_user) {
		$criteria = array('_id' => (int) $id_user);
		Log::timing('Mongo findOne id#' . $id_user);
		$result = self::getInstance()->users->findOne($criteria);
		Log::timing('Mongo findOne id#' . $id_user);
		return $result;
	}

	// цепляем конкретный аттрибут пользователя
	public static function getUserAttribute($id_user, $attribute_name, $default = false) {
		$fields = array($attribute_name);
		$criteria = array('_id' => $id_user);
		Log::timing('Mongo findOne id#' . $id_user);
		$ret = self::getInstance()->users->findOne($criteria, $fields);
		Log::timing('Mongo findOne id#' . $id_user);
		return isset($ret[$attribute_name]) ? $ret[$attribute_name] : $default;
	}

	public static function addEvent($data) {
		$res = self::getInstance()->events->insert($data);
		$eventId = $data['_id']->{'$id'};
		return $eventId;
	}

	public static function updateEvent($eventId, $data) {
		$realid = new MongoId($eventId);
		$criteria = array('_id' => $realid);
		self::getInstance()->events->update($criteria, $data);
		return $eventId;
	}

	public static function findLastEventByType($user_id, $type, $additionalCriteria = array()) {
		$time = time() - Config::need('event_freeze_time', 10 * 60 * 60);
		$attributes = array(
		    'user_id' => (int) $user_id,
		    'type' => $type,
		    'time' => array('$gt' => $time),
		);

		foreach ($additionalCriteria as $f => $v)
			$attributes[$f] = $v;
		$order = array('time' => -1);

		$result = self::getInstance()->events->find($attributes)->sort($order)->limit(1);

		if ($result) {
			foreach ($result as $res)
				return array($res['_id']->{'$id'}, $res);
		}
		else
			return array(false,false);
	}

	public static function findReviewEvent($user_id, $book_id) {
		$attributes = array(
		    'user_id' => (int) $user_id,
		    'bid' => (int) $book_id,
		    'type' => array('$in' => array(Event::EVENT_BOOKS_RATE_ADD, Event::EVENT_BOOKS_REVIEW_ADD)),
		);
		$result = self::getInstance()->events->findOne($attributes);
		if ($result)
			return $result['_id']->{'$id'};
		else
			return false;
	}

	public static function getReviewEvent($user_id, $book_id) {
		$attributes = array(
		    'user_id' => (int) $user_id,
		    'bid' => (int) $book_id,
		    'type' => array('$in' => array(Event::EVENT_BOOKS_RATE_ADD, Event::EVENT_BOOKS_REVIEW_ADD)),
		);
		$result = self::getInstance()->events->findOne($attributes);
		if ($result)
			return $result;
		else
			return false;
	}

	public static function findReviewEventData($user_id, $book_id) {
		$attributes = array(
		    'user_id' => (int) $user_id,
		    'bid' => (int) $book_id,
		    'type' => array('$in' => array(Event::EVENT_BOOKS_RATE_ADD, Event::EVENT_BOOKS_REVIEW_ADD)),
		);
		Log::timing('findReviewEventData' . $user_id . ' ' . $book_id);
		$result = self::getInstance()->events->findOne($attributes);
		Log::timing('findReviewEventData' . $user_id . ' ' . $book_id);
		if ($result)
			return $result;
		else
			return false;
	}

	public static function addSimpleComment($type, $book_id, $user_id, $comment) {
		$criteria = array(
		    'type' => $type,
		);
		switch ($type) {
			case BiberLog::TargetType_serie:
				$criteria['serie_id'] = $book_id;
				$type_criteria = array('serie_id' => $book_id);
				break;
			case BiberLog::TargetType_person:
				$criteria['author_id'] = $book_id;
				$type_criteria = array('author_id' => $book_id);
				break;
			case BiberLog::TargetType_book:
				$criteria['book_id'] = $book_id;
				$type_criteria = array('book_id' => $book_id);
				break;
		}


		$res = self::getInstance()->comments->findOne($criteria);
		$comments = isset($res['comments']) ? $res['comments'] : array();

		$tmp = $criteria;
		$tmp['user_id'] = $user_id;
		$tmp['time'] = time();
		$tmp['comment'] = $comment;

		$comments[] = $tmp;
		uasort($comments, 'sort_by_time');
		$commentsCount = count($comments);
		self::getInstance()->comments->update($criteria, array('$set' => array(
			'commentsCount' => $commentsCount,
			'comments' => $comments,
			'type' => $type,
			$type_criteria,
			)), array('upsert' => true));
	}

	public static function getAuthorComments($book_id, $count = 40, $skip = 0) {
		return self::getBookComments($book_id, $count, $skip, BiberLog::TargetType_person);
	}

	public static function getSerieComments($book_id, $count = 40, $skip = 0) {
		return self::getBookComments($book_id, $count, $skip, BiberLog::TargetType_serie);
	}

	public static function getBookComments($book_id, $count = 40, $skip = 0, $type = BiberLog::TargetType_book) {
		$criteria = array(
		    'type' => $type,
		);

		switch ($type) {
			case BiberLog::TargetType_serie:
				$criteria['serie_id'] = $book_id;
				break;
			case BiberLog::TargetType_person:
				$criteria['author_id'] = $book_id;
				break;
			case BiberLog::TargetType_book:
				$criteria['book_id'] = $book_id;
				break;
		}

		$order = array('time' => -1);

		$out = array();

		$res = self::getInstance()->comments->find($criteria);
		$tmp = array();
		foreach ($res as $row) {
			$tmp = $row;
		}
		if (isset($tmp['comments'])) {
			$tmp['comments'] = array_slice($tmp['comments'], $skip, $count);
		}

		$count = isset($tmp['commentsCount']) ? $tmp['commentsCount'] : 0;
		return array($tmp, $count);
	}

	public static function findReviewEvents($book_id, $user_id = false, $count = 40, $skip = 0) {
		$criteria = array(
		    'type' => Event::EVENT_BOOKS_REVIEW_ADD,
		);
		if ($user_id)
			$criteria['user_id'] = (int) $user_id;

		if ($book_id)
			$criteria['bid'] = (int) $book_id;

		$order = array('time' => -1);

		$out = array();
		$res = self::getInstance()->events->find($criteria)->sort($order)->skip($skip)->limit($count);
		$tmp = array();
		foreach ($res as $row) {
			if (isset($row['type']))
				$row['type'] = Event::$event_type[$row['type']];
			$row['id'] = $row['_id']->{'$id'};
			$row['retweet_from'] = 0;
			$row['owner_id'] = $row['user_id'];
			$row['wall_time'] = $row['time'];
			$tmp[] = $row;
		}
		$tmp['count'] = $res->count();

		return $tmp;
	}

	public static function findReviewMarkEvents($book_id, $user_id = false, $count = 40, $skip = 0) {

		$criteria = array(
		    'type' => Event::EVENT_BOOKS_RATE_ADD,
		);
		if ($user_id)
			$criteria['user_id'] = (int) $user_id;

		if ($book_id)
			$criteria['bid'] = (int) $book_id;

		$order = array('time' => -1);

		$out = array();

		$res = self::getInstance()->events->find($criteria)->sort($order)->skip($skip)->limit($count);
		$tmp = array();
		foreach ($res as $row) {
			if (isset($row['type']))
				$row['type'] = Event::$event_type[$row['type']];
			$row['id'] = $row['_id']->{'$id'};
			$row['retweet_from'] = 0;
			$row['owner_id'] = $row['user_id'];
			$row['wall_time'] = $row['time'];
			$tmp[] = $row;
		}
		$tmp['count'] = $res->count();

		return $tmp;
	}

	function findBookReviews($user_ids, $book_id) {
		$out = array();
		$attributes = array(
		    'user_id' => array('$in' => $user_ids),
		    'bid' => (int) $book_id,
		    'type' => array('$in' => array(Event::EVENT_BOOKS_RATE_ADD, Event::EVENT_BOOKS_REVIEW_ADD)),
		);
		$result = self::getInstance()->events->find($attributes);
		if ($result)
			foreach ($result as $row)
				$out[$row['user_id']] = $row['_id']->{'$id'};
		return $out;
	}

	public static function deleteWallItemsByOwnerId($user_id, $deleted_id) {
		if (!$user_id || !$deleted_id)
			return false;
		$criteria = array(
		    'user_id' => (int) $user_id,
		    'owner_id' => (int) $deleted_id,
		);
		self::getInstance()->walls->remove($criteria);
	}

	public static function deleteWallItemsByEventId($event_id) {
		if (!$event_id)
			return false;
		$attributes = array(
		    'id' => (string) $event_id,
		);
		self::getInstance()->walls->remove($attributes);
	}

	public static function removeWallItem($wall_owners, $event_id, $retweet_from = 0) {
		$attributes = array(
		    'id' => $event_id,
		    'user_id' => array('$in' => $wall_owners),
		    'retweet_from' => $retweet_from
		);
		self::getInstance()->walls->remove($attributes);
	}

	public static function pushEvents($owner_id, $user_ids, $event_id, $time, $retweet_from = 0) {
		foreach ($user_ids as $user_id) {
			if ($event_id instanceof MongoId) {
				$event_id = $event_id->{'$id'};
			}
			$attributes = array('id' => $event_id, 'user_id' => (int) $user_id, 'time' => (int) $time, 'owner_id' => (int) $owner_id, 'retweet_from' => (int) $retweet_from);
			self::getInstance()->walls->insert($attributes);
		}
	}

	public static function eventLike($id, $uid) {
		$realid = new MongoId($id);
		$criteria = array('_id' => $realid);
		$out = array();
		$res = self::getInstance()->events->find($criteria, array('bid', 'type', 'likes', 'likesCount', 'user_id'));

		foreach ($res as $row) {
			$likes = isset($row['likes']) ? $row['likes'] : array();
			if (isset($row['likes'][$uid])) {
				return false;
			} else {
				$row['likes'][$uid] = $uid;
				$row['likesCount'] = isset($row['likesCount']) ? $row['likesCount'] + 1 : 1;
				$out['event_owner'] = $row['user_id'];
				self::getInstance()->events->update($criteria, array('$set' => array('likesCount' => $row['likesCount'], 'likes' => $row['likes'])), array('upsert' => true));
			}
		}
		return $out;
	}

	public static function eventUnlike($id, $uid) {
		$realid = new MongoId($id);
		$criteria = array('_id' => $realid);
		$out = array();
		$res = self::getInstance()->events->find($criteria, array('bid', 'type', 'likes', 'likesCount', 'user_id'));
		foreach ($res as $row) {
			$likes = isset($row['likes']) ? $row['likes'] : array();
			if (!isset($row['likes'][$uid])) {
				return false;
			} else {
				unset($row['likes'][$uid]);
				$row['likesCount'] = max(0, isset($row['likesCount']) ? $row['likesCount'] - 1 : 0);
				$out['event_owner'] = $row['user_id'];
				self::getInstance()->events->update($criteria, array('$set' => array('likesCount' => $row['likesCount'], 'likes' => $row['likes'])), array('upsert' => true));
			}
			if ((int) $row['type'] == Event::EVENT_BOOKS_REVIEW_ADD) {
				
			}
		}
		return $out;
	}

	public static function getEventsLikes($ids) {
		if (!is_array($ids) || !count($ids))
			return array();
		foreach ($ids as $id) {
			$realids[] = new MongoId($id);
		}
		$criteria = array('_id' => array('$in' => $realids));
		$out = array();
		$res = self::getInstance()->events->find($criteria, array('likes', 'likesCount'));
		foreach ($res as $row) {
			$out[$row['_id']->{'$id'}] = isset($row['likes']) ? $row['likes'] : array();
			$out[$row['_id']->{'$id'}]['count'] = isset($row['likesCount']) ? $row['likesCount'] : 0;
		}
		return $out;
	}

	public static function addEventComment($post_id, $commenter_id, $comment, $reply_to_id = false) {
		$time = time();
		$comment_id = $commenter_id . '_' . $time;
		$realid = new MongoId($post_id);
		$commentsCount = 0;
		$criteria = array('_id' => $realid);
		$res = self::getInstance()->events->find($criteria, array('comments', 'commentsCount', 'user_id'));
		foreach ($res as $row) {
			$row['commentsCount'] = isset($row['commentsCount']) ? $row['commentsCount'] + 1 : 1;
			$row['post'] = $row;
			$row['comment_id'] = $comment_id;
			$row['commenter_id'] = $commenter_id;
			$commentsCount = $row['commentsCount'];
			if (!isset($row['comments']))
				$row['comments'] = array();
			if ($reply_to_id) {
				// мы вставляем коммент как ответ на коммент
				if (!isset($row['comments'][$reply_to_id])) {
					throw new Exception('no comment to reply on it');
				}
				$row['commenter_id'] = $row['comments'][$reply_to_id]['commenter_id'];
				$row['comments'][$reply_to_id]['answers'][$comment_id] = array('time' => $time, 'commenter_id' => $commenter_id, 'comment' => $comment);
			}else
				$row['comments'][$comment_id] = array('time' => $time, 'commenter_id' => $commenter_id, 'comment' => $comment);
			self::getInstance()->events->update($criteria, array('$set' => array('commentsCount' => $commentsCount, 'comments' => $row['comments'])), array('upsert' => true));
		}
		return $row;
	}

	/**
	 *
	 * @param array $wall - массив id эвентов
	 * @return type 
	 */
	public static function getWallEvents($wall) {
		$count = isset($wall['count']) ? $wall['count'] : 0;
		unset($wall['count']);
		if (!count($wall))
			return array();

		foreach ($wall as $wall_event) {
			if (isset($wall_event['id']))
				$ids[$wall_event['id']] = new MongoId($wall_event['id']);
		}
		if (!count($ids))
			return array();

		$criteria = array('_id' => array('$in' => $ids));
		$out = array();
		Log::timing('mongo getWallEvents');
		$res = self::getInstance()->events->find($criteria)->sort(array('time' => -1));
		Log::timing('mongo getWallEvents');
		$tmp = array();
		foreach ($res as $row) {
			if (isset($row['type']))
				$row['type'] = Event::$event_type[$row['type']];
			$row['id'] = $row['_id']->{'$id'};
			$tmp[$row['id']] = $row;
		}

		foreach ($wall as $wall_event) {
			if (isset($p[$wall_event['id']]))
				continue;
			$p[$wall_event['id']] = 1;
			isset($tmp[$wall_event['id']]) ? ($item = $tmp[$wall_event['id']]) : false;
			$item['retweet_from'] = isset($wall_event['retweet_from']) ? $wall_event['retweet_from'] : 0;
			$item['owner_id'] = isset($wall_event['owner_id']) ? $wall_event['owner_id'] : 0;
			$item['wall_time'] = isset($wall_event['time']) ? $wall_event['time'] : false;
			$out[] = $item;
		}
		$out['count'] = $count;
		return $out;
	}

	// все записи со стен, без репостов
	public static function getWallLastEvents($count = 40, $skip = 0) {
		$order = array('time' => -1);
		$criteria = array();
		$out = array();
		Log::timing('mongo getWallLastEvents count#' . $count . ' skip#' . $skip);
		$res = self::getInstance()->events->find($criteria)->sort($order)->skip($skip)->limit($count);
		$tmp = array();
		foreach ($res as $row) {
			if (!isset($row['user_id']))
				continue;
			if (isset($row['type']))
				$row['type'] = Event::$event_type[$row['type']];
			$row['id'] = $row['_id']->{'$id'};
			$row['retweet_from'] = 0;
			$row['owner_id'] = $row['user_id'];
			$row['wall_time'] = $row['time'];
			$tmp[] = $row;
		}
		$tmp['count'] = $res->count();
		Log::timing('mongo getWallLastEvents count#' . $count . ' skip#' . $skip);
		return $tmp;
	}

	/**
	 *
	 * @param type $id
	 * @param type $user_id
	 * @return type 
	 */
	public static function getUserWallItem($id, $user_id) {
		$criteria = array('user_id' => (int) $user_id);
		$criteria['id'] = $id;
		$out = array();
		$res = self::getInstance()->walls->find($criteria)->limit(1);
		foreach ($res as $row) {
			$out[] = $row;
		}
		return $out;
	}

	public static function getUserWall($id_user, $offset = 0, $count = 40, $self = false) {
		$criteria = array('user_id' => $id_user);
		if ($self == 'not_self') {
			// не показываем собственные записи
			$criteria['owner_id'] = array('$ne' => $id_user);
		}
		if ($self == 'self') {
			// показываем только собственные
			$criteria['owner_id'] = $id_user;
		}
		$order = array('time' => -1);
		$out = array();
		Log::timing('mongo getUserWall $offset#' . $offset . ' $count#' . $count);
		$res = self::getInstance()->walls->find($criteria)->sort($order)->skip($offset)->limit($count);
		foreach ($res as $row) {
			$out[] = $row;
		}
		$out['count'] = $res->count();
		Log::timing('mongo getUserWall $offset#' . $offset . ' $count#' . $count);
		return $out;
	}

	// удаляем аттрибут
	public static function deleteUserAttribute($id_user, $attribute_name) {
		
	}

	// добавляем пост пользователю на стену
	public static function addUserPost($id_user, $id_owner, $time, $post_type, $attributes) {
		
	}

}