<?php

/**
 * отсылаем нотификации
 * 
  о комментариях к моим записям 	+-
  об ответах на мои комментарии 	+-
  о новых личных сообщениях 	+-
  о новых поклонниках 		+-
  о событиях (подписка на событие) +-
  о комментариях к отслеживаемым книгам и авторам
  о рецензиях на отслеживаемые книги
  о новых книгах в отслеживаемых жанрах
  о новых книгах отслеживаемых авторов
 */
class Notify {

	public static function notifyNewInbox($user_ids, $id_sender) {
		global $current_user;
		$sender = Users::getById($id_sender);
		/* @var $sender User */
		$subject = 'Новое письмо!';
		if (isset($user_ids[$current_user->id]))
			unset($user_ids[$current_user->id]);
		/* @var $book Book */
		$message = 'Новое личное сообщение от пользователя <a href="' . Config::need('www_path') . '/user/' . $sender->id . '">' . $sender->getNickName() . '</a>';
		self::send($user_ids, $subject, $message, UserNotify::UN_NEW_MESSAGE, $only_email = true);
	}

	public static function notifyNewBookReview($id_book, $id_review_author) {
		global $current_user;
		$query = 'SELECT `id_user` FROM `books_review_subscribers` WHERE `id_book`=' . (int) $id_book;
		$user_ids = array_keys(Database::sql2array($query, 'id_user'));
		if (isset($user_ids[$current_user->id]))
			unset($user_ids[$current_user->id]);
		if (count($user_ids)) {
			$book = Books::getInstance()->getByIdLoaded($id_book);
			/* @var $person Person */
			$subject = 'Новый отзыв о книге ' . $book->getTitle(1);
			/* @var $book Book */
			$message = 'Добавлен отзыв о книге <a href="' . Config::need('www_path') . '/b/' . $book->id . '">' . $book->getTitle(1) . '</a>';
			self::send($user_ids, $subject, $message, UserNotify::UN_G_NEW_REVIEWS);
		}
	}

	public static function notifyAuthorNewBook($id_author, $id_book) {
		global $current_user;
		$query = 'SELECT `id_user` FROM `author_subscribers` WHERE `id_author`=' . (int) $id_author;
		$user_ids = array_keys(Database::sql2array($query, 'id_user'));
		if (isset($user_ids[$current_user->id]))
			unset($user_ids[$current_user->id]);
		if (count($user_ids)) {
			$person = Persons::getInstance()->getByIdLoaded($id_author);
			$book = Books::getInstance()->getByIdLoaded($id_book);
			/* @var $person Person */
			$subject = 'Добавлена книга автора ' . $person->getName();
			/* @var $book Book */
			$message = 'Книга <a href="/b/' . $book->id . '">' . $book->getTitle(1) . '</a> добавлена';
			self::send($user_ids, $subject, $message, UserNotify::UN_G_NEW_AUTHORS);
		}
	}

	public static function notifyGenreNewBook($id_genre, $id_book) {
		global $current_user;
		$query = 'SELECT `id_user` FROM `genre_subscribers` WHERE `id_genre`=' . (int) $id_genre;
		$user_ids = array_keys(Database::sql2array($query, 'id_user'));
		if (isset($user_ids[$current_user->id]))
			unset($user_ids[$current_user->id]);
		if (count($user_ids)) {
			$genre = Database::sql2single('SELECT `title` FROM `genre` WHERE `id`=' . $id_genre);
			$book = Books::getInstance()->getByIdLoaded($id_book);
			/* @var $person Person */
			$subject = 'Добавлена книга в жанре ' . $genre;
			/* @var $book Book */
			$message = 'Книга <a href="/b/' . $book->id . '">' . $book->getTitle(1) . '</a> добавлена';
			self::send($user_ids, $subject, $message, UserNotify::UN_G_NEW_GENRES);
		}
	}

	public static function notifySubscribe($user_id, $event_id) {
		$query = 'INSERT INTO `event_subscribers` SET `id_user`=' . $user_id . ', `id_event`=' . $event_id . '
			ON DUPLICATE KEY UPDATE `id_event`=' . $event_id;
		Database::query($query);
	}

	public static function getEventSubscribers($int_id_event) {
		global $current_user;
		$query = 'SELECT `id_user` FROM `event_subscribers` WHERE `id_event`=' . $int_id_event;
		$uids = Database::sql2array($query, 'id_user');

		if (isset($uids[$current_user->id]))
			unset($uids[$current_user->id]);
		$uids = array_keys($uids);

		return $uids;
	}

	public static function notifyEventCommentSubscribers($int_id_event, $comment_id) {
		$uids = self::getEventSubscribers((int)$int_id_event);
		$subject = 'Новый комментарий!';
		$message = 'Новый комментарий к событию, на которое Вы подписаны! <a href="' . Config::need('www_path') . '/post/' . $int_id_event . '#comment' . $comment_id . '">смотреть</a>';
		if (count($uids)) {
			self::send($uids, $subject, $message, UserNotify::UN_G_OBJECTS_COMMENTS);
		}
	}

	/**
	 * на наш эвент оставили комментарий
	 * @param string $event_id
	 */
	public static function notifyEventComment($user_id, $event_id, $comment_id) {
		global $current_user;
		$post_id = Database::sql2single('SELECT `id` FROM `events` WHERE `mongoid`=' . Database::escape($event_id));
		if ($user_id != $current_user->id) {
			if ($post_id) {
				$message = 'Новый комментарий к событию на вашей стене! <a href="' . Config::need('www_path') . '/post/' . $post_id . '#comment' . $comment_id . '">смотреть</a>';
				$subject = 'Новый комментарий';
				self::send(array($user_id), $subject, $message, UserNotify::UN_EVENT_COMMENT);
			}
		}
		self::notifyEventCommentSubscribers($post_id, $comment_id);
	}

	public static function notifyEventCommentAnswer($user_id, $event_id, $comment_id) {
		global $current_user;
		$post_id = Database::sql2single('SELECT `id` FROM `events` WHERE `mongoid`=' . Database::escape($event_id));
		if ($user_id != $current_user->id) {
			if ($post_id) {
				$message = 'Новый ответ на Ваш комментарий к событию стене! <a href="' . Config::need('www_path') . '/post/' . $post_id . '#comment' . $comment_id . '">смотреть</a>';
				$subject = 'Ответ на Ваш комментарий';
				self::send(array($user_id), $subject, $message, UserNotify::UN_COMMENT_ANSWER);
			}
		}
		self::notifyEventCommentSubscribers($post_id, $comment_id);
	}

	public static function notifyEventAddFriend($user_id, $friend_id) {
		$users = Users::getByIdsLoaded(array($user_id, $friend_id));
		$friend = $users[$friend_id];
		/* @var $friend User */
		$friendName = $friend->getNickName();
		$url = $friend->getUrl();


		$message = 'Вас добавил в друзья пользователь <a href="' . $url . '">' . $friendName . '</a>';
		$subject = 'Новый поклонник!';
		self::send(array($user_id), $subject, $message, UserNotify::UN_NEW_FRIEND);
	}

	private static function send($user_ids, $subject, $message, $rule, $only_email = false) {
		$users = Users::getByIdsLoaded($user_ids);
		$uids = array();
		$muids = array();
		foreach ($users as $user) {
			/* @var $user User */
			$priority = 1; // todo
			if ($user->canNotify($rule, UserNotify::UNT_NOTIFY))
				$uids[$user->id] = $user->id;
			if ($user->canNotify($rule, UserNotify::UNT_EMAIL))
				$muids[$user->id] = array('priority' => $priority);
		}
		if (!$only_email && count($uids)) {
			$mwm = new MessagesWriteModule();
			$mwm->sendMessage(0, $uids, $subject, $message, time(), false, 1);
		}
		if (count($muids)) {
			// скармливаем демону
			$q = array();
			$now = time();
			$send_time = time();
			$priority = 1;
			foreach ($muids as $id => $data) {
				$q[] = '(' . $id . ',' . Database::escape($subject) . ',' . Database::escape($message) . ',' . $now . ',' . $send_time . ',' . $rule . ',' . $data['priority'] . ')';
			}
			$query = 'INSERT INTO `email_notify` (id_user,subject,message,time,send_time,type,priority) VALUES ' . implode(',', $q) . '';
			Database::query($query);
		}
	}

}