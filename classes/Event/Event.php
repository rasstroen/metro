<?php

class Event {
	const EVENT_BOOKS_ADD = 1;
	const EVENT_BOOKS_EDIT = 2;
	const EVENT_BOOKS_ADD_SHELF = 3;
	const EVENT_BOOKS_REVIEW_ADD = 10;
	const EVENT_BOOKS_RATE_ADD = 11;
	const EVENT_POST = 21;
	const EVENT_LOVED_ADD_AUTHOR = 31;
	const EVENT_LOVED_ADD_BOOK = 32;
	const EVENT_LOVED_ADD_GENRE = 33;
	const EVENT_LOVED_ADD_SERIE = 34;

	const EVENT_AUTHOR_ADD = 35;
	const EVENT_FRIEND_ADD = 36;

	const EVENT_SERIES_ADD = 37;
	const EVENT_SERIES_EDIT = 38;

	const EVENT_BOOKS_ADD_FILE = 40;

	public static $eventsMultTypes = array(
	    self::EVENT_BOOKS_ADD,
	    self::EVENT_BOOKS_ADD_FILE,
	    self::EVENT_AUTHOR_ADD,
	    self::EVENT_FRIEND_ADD,
	    self::EVENT_BOOKS_EDIT,
	    self::EVENT_LOVED_ADD_AUTHOR,
	    self::EVENT_LOVED_ADD_BOOK,
	    self::EVENT_LOVED_ADD_GENRE,
	    self::EVENT_LOVED_ADD_SERIE,
	    self::EVENT_BOOKS_ADD_SHELF,
	    self::EVENT_SERIES_ADD,
	    self::EVENT_SERIES_EDIT,
	);
	public static $event_type = array(
	    self::EVENT_BOOKS_ADD => 'books_add',
	    self::EVENT_BOOKS_ADD_FILE => 'books_add_file',
	    self::EVENT_AUTHOR_ADD => 'authors_add',
	    self::EVENT_FRIEND_ADD => 'users_add_friend',
	    self::EVENT_POST => 'posts_add',
	    self::EVENT_BOOKS_EDIT => 'books_edit',
	    self::EVENT_BOOKS_REVIEW_ADD => 'reviews_add',
	    self::EVENT_BOOKS_RATE_ADD => 'reviews_add_rate',
	    self::EVENT_LOVED_ADD_AUTHOR => 'loved_add_author',
	    self::EVENT_LOVED_ADD_BOOK => 'loved_add_book',
	    self::EVENT_LOVED_ADD_GENRE => 'loved_add_genre',
	    self::EVENT_LOVED_ADD_SERIE => 'loved_add_serie',
	    self::EVENT_BOOKS_ADD_SHELF => 'shelf_add_book',
	    self::EVENT_SERIES_ADD => 'series_add',
	    self::EVENT_SERIES_EDIT => 'series_edit',
	);
	public $data = array();

	function __construct() {
		$this->data = array('time' => time());
	}

	public function event_FollowingAdd($id_user, $id_friend_added) {
		$this->canPushed = true;
		$this->setUser($id_user);
		$this->setFriend($id_friend_added);
		$this->setType(self::EVENT_FRIEND_ADD);
	}

	public function event_addShelf($id_user, $id_book, $id_shelf) {
		$this->canPushed = true;
		$this->setUser($id_user);
		$this->setBook($id_book);
		$this->setShelf($id_shelf);
		$this->setType(self::EVENT_BOOKS_ADD_SHELF);
	}

	public function event_LovedAdd($id_user, $id_target, $target_type) {
		$this->canPushed = true;
		switch ($target_type) {
			case 'author':
				$this->setAuthor($id_target);
				$this->setType(self::EVENT_LOVED_ADD_AUTHOR);
				break;
			case 'book':
				$this->setBook($id_target);
				$this->setType(self::EVENT_LOVED_ADD_BOOK);
				break;
			case 'genre':
				$this->setGenre($id_target);
				$this->setType(self::EVENT_LOVED_ADD_GENRE);
				break;
			case 'serie':
				$this->setSerie($id_target);
				$this->setType(self::EVENT_LOVED_ADD_SERIE);
				break;
			default:
				throw new Exception('event_LovedAdd illegal type#' . $target_type);
		}
		$this->setUser($id_user);
	}

	public function event_BooksAdd($id_user, $id_book) {
		$this->canPushed = true;
		$this->setBook($id_book);
		$this->setType(self::EVENT_BOOKS_ADD);
		$this->setUser($id_user);
	}

	public function event_BooksAddFile($id_user, $id_book) {
		$this->canPushed = true;
		$this->setBook($id_book);
		$this->setType(self::EVENT_BOOKS_ADD_FILE);
		$this->setUser($id_user);
	}

	public function event_BooksEdit($id_user, $id_book) {
		$this->canPushed = true;
		$this->setBook($id_book);
		$this->setType(self::EVENT_BOOKS_EDIT);
		$this->setUser($id_user);
	}

	public function event_SeriesAdd($id_user, $id_serie) {
		$this->canPushed = true;
		$this->setSerie($id_serie);
		$this->setType(self::EVENT_SERIES_ADD);
		$this->setUser($id_user);
	}

	public function event_SeriesEdit($id_user, $id_serie) {
		$this->canPushed = true;
		$this->setSerie($id_serie);
		$this->setType(self::EVENT_SERIES_EDIT);
		$this->setUser($id_user);
	}

	public function event_AuthorAdd($id_user, $id_author) {
		$this->canPushed = true;
		$this->setAuthor($id_author);
		$this->setType(self::EVENT_AUTHOR_ADD);
		$this->setUser($id_user);
	}

	public function event_PostAdd($id_user, $body, $subject) {
		$this->canPushed = true;
		$this->setType(self::EVENT_POST);
		$this->setUser($id_user);
		$this->setBody($body);
		$this->setSubject($subject);
		$this->setShortBody($body);
	}

	public function event_BookReviewAdd($id_user, $data) {
		$this->canPushed = true;
		$this->setBook($data['target_id']);
		$this->setMark($data['rate'] - 1);
		$this->setBody($data['comment']);
		$reviewHTML = close_dangling_tags(_substr($data['comment'], 200));
		$this->setReview($reviewHTML);
		$this->setType(self::EVENT_BOOKS_REVIEW_ADD);
		$this->setUser($id_user);
	}

	public function event_BookRateAdd($id_user, $data) {
		$this->canPushed = true;
		$this->setBook($data['target_id']);
		$this->setMark($data['rate'] - 1);
		$reviewHTML = close_dangling_tags(_substr($data['comment'], 200));
		$this->setType(self::EVENT_BOOKS_RATE_ADD);
		$this->setUser($id_user);
	}

	// выставляем текст
	private function setBody($body) {
		if ($body)
			$this->data['body'] = $body;
	}

	// выставляем subject
	private function setSubject($subject) {
		if ($subject)
			$this->data['subject'] = $subject;
	}

	// выставляем текст сниппета
	private function setShortBody($body) {
		$this->data['short'] = close_dangling_tags(trim(_substr($body, 900)));
	}

	private function setFriend($id_friend) {
		$this->data['friend_id'][] = $id_friend;
	}

	// выставляем тип
	private function setTargetType($type) {
		$this->data['target_type'] = $type;
	}

	// выставляем тип
	private function setUser($id) {
		$this->data['user_id'] = $id;
	}

	// выставляем тип
	private function setReview($html) {
		$this->data['review'] = $html;
	}

	// оценка
	private function setMark($mark) {
		if ($mark)
			$this->data['mark'] = $mark;
	}

	// выставляем тип
	private function setType($type) {
		$this->data['type'] = $type;
	}

	// цепляем книгу
	private function setBook($id) {
		if ($id)
			$this->data['book_id'][$id] = $id;
		$this->data['bid'] = $id;
	}

	// цепляем полку
	private function setShelf($id) {
		if ($id)
			$this->data['shelf_id'] = $id;
	}

	private function setSerie($id) {
		if ($id)
			$this->data['serie_id'][$id] = $id;
	}

	private function setGenre($id) {
		if ($id)
			$this->data['genre_id'][$id] = $id;
	}

	// цепляем автора
	private function setAuthor($id) {
		if ($id)
			$this->data['author_id'][$id] = $id;
	}

	// цепляем журнал
	private function setMagazine($id) {
		if ($id)
			$this->data['magazine_id'][$id] = $id;
	}

	public function push($walls_disabled = array()) {
		global $current_user;
		if (!$this->canPushed)
			return;

		$eventId = false;
		// ревью обновляем
		if (($this->data['type'] == self::EVENT_BOOKS_REVIEW_ADD) || ($this->data['type'] == self::EVENT_BOOKS_RATE_ADD)) {
			// ищем старую
			$eventId = MongoDatabase::findReviewEvent($current_user->id, $this->data['bid']);
			if ($eventId) {
				// есть старая? нужно удалить запись на стене со ссылкой на старую запись со всех стен
				MongoDatabase::deleteWallItemsByEventId($eventId);
				MongoDatabase::updateEvent($eventId, $this->data);
			}
		}

		// а если был такой эвент недавно, с тем же типом
		// то обновляем эфент, добавляя туда объекты
		if (in_array($this->data['type'], self::$eventsMultTypes)) {
			// находим эвент с таким типом
			$additionalCriteria = array();
			if ($this->data['type'] == self::EVENT_BOOKS_ADD_SHELF)
				$additionalCriteria['shelf_id'] = $this->data['shelf_id'];
			list($eventId, $data) = MongoDatabase::findLastEventByType($this->data['user_id'], $this->data['type'], $additionalCriteria);
			if ($eventId) {
				// нашли эвент!
				$old_time = isset($data['time']) ? $data['time'] : time();
				foreach ($this->data as $field => $value) {
					if (!isset($data[$field]))
						$data[$field] = $value;
					if (is_array($value)) {
						foreach ($value as $val) {
							if (is_array($data[$field]))
								$data[$field][$val] = $val;
						}
					}
				}
				$data['time'] = $old_time;
				MongoDatabase::deleteWallItemsByEventId($eventId);
				MongoDatabase::updateEvent($eventId, $data);
			}
		}
		$eventDbId = 0;

		if (!$eventId) {
			$eventId = MongoDatabase::addEvent($this->data);
			$query = 'INSERT INTO `events` SET `mongoid`=' . Database::escape($eventId);
			Database::query($query, false);
			$eventDbId = Database::lastInsertId();
			if (!$eventDbId) {
				throw new Exception('cant push event id to database');
			}
		}


		if ($eventId) {
			$user = Users::getById($this->data['user_id']);
			/* @var $user User */
			$followerIds = $user->getFollowers();
			$followerIds[$user->id] = $user->id;
			foreach ($walls_disabled as $id) {
				if (isset($followerIds[$id]))
					unset($followerIds[$id]);
			}
			MongoDatabase::pushEvents($this->data['user_id'], $followerIds, $eventId, $this->data['time']);
		}
		return $eventDbId;
	}

}