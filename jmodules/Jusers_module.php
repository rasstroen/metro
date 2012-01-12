<?php

class Jusers_module extends JBaseModule {

	function process() {
		global $current_user;
		$current_user = new CurrentUser();

		if (isset($_POST['item_type']) && in_array($_POST['action'], array('subscribe', 'unsubscribe', 'check_subscription'))) {
			if ($_POST['item_type'] == 'book')
				$_POST['item_type'] = 'books_review';
			if (!in_array($_POST['item_type'], array('books_review', 'author', 'genre')))
				return false;
		}


		switch ($_POST['action']) {
			case 'add_loved':
				$this->addLoved();
				break;
			case 'check_loved':
				$this->checkLoved();
				break;
			case 'toggle_vandal':
				$this->toggle_vandal();
				break;
			case 'use_subscription':
				$this->use_subscription();
				break;
			case 'is_unique':
				$this->isNicknameUnique();
				break;
			case 'subscribe':
				$this->subscribe_on_reviews($_POST['item_type']);
				break;
			case 'unsubscribe':
				$this->unsubscribe_on_reviews($_POST['item_type']);
				break;
			case 'check_subscription':
				$this->check_subscribe_on_reviews($_POST['item_type']);
				break;
			case 'get_profile':
				$this->getProfile();
				break;
		}
	}

	function getProfile() {
		$this->ca();
		global $current_user;
		if ($current_user->load() && $current_user->loaded) {
			/* @var $current_user CurrentUser */
			$this->data = array(
			    'current_user' => array(
				'id' => $current_user->id,
				'nickname' => $current_user->getNickName(),
				'image' => $current_user->getAvatar(),
				'new_messages' => $current_user->getCounter('new_messages'),
				'new_notifications' => $current_user->getCounter('new_notifications'),
				'books' =>
				array(
				    'to_read' => $current_user->getCounter('polka_to-read'),
				    'read' => $current_user->getCounter('polka_read'),
				    'reading' => $current_user->getCounter('polka_reading'),
				)
			    )
			);
			$this->data['success'] = 1;
		}else
			$this->data['success'] = 0;
	}

	function ca() {
		global $current_user;
		$current_user = new CurrentUser();
		if (!$current_user->authorized)
			throw new Exception('au');
		return true;
	}

	function unsubscribe_on_reviews($type = 'books_review') {
		global $current_user;
		$this->ca();
		$this->data['success'] = 0;
		$id = isset($_POST['id']) ? (int) $_POST['id'] : false;
		$ttype = (($type == 'books_review') ? 'book' : $type);
		$query = 'DELETE FROM `' . $type . '_subscribers` WHERE
			`id_' . $ttype . '`=' . $id . ' AND
			`id_user`=' . $current_user->id;
		Database::query($query);

		$this->data['success'] = 1;
		$this->data['subscribed'] = 0;
	}

	function subscribe_on_reviews($type = 'books_review') {
		global $current_user;
		$this->ca();
		$this->data['success'] = 0;
		if ($this->check_subscribe_on_reviews($type))
			return $this->unsubscribe_on_reviews($type);
		$id = isset($_POST['id']) ? (int) $_POST['id'] : false;
		$ttype = (($type == 'books_review') ? 'book' : $type);
		$query = 'INSERT INTO `' . $type . '_subscribers` SET
			`id_' . $ttype . '`=' . $id . ',
			`id_user`=' . $current_user->id . '
				ON DUPLICATE KEY UPDATE `id_' . $ttype . '`=' . $id;
		Database::query($query);
		$this->data['subscribed'] = 1;
		$this->data['success'] = 1;
	}

	function check_subscribe_on_reviews($type = 'books_review') {
		global $current_user;
		$this->ca();
		$this->data['success'] = 0;
		$id = isset($_POST['id']) ? (int) $_POST['id'] : false;
		$ttype = (($type == 'books_review') ? 'book' : $type);
		$query = 'SELECT COUNT(1) as cnt FROM `' . $type . '_subscribers` WHERE
			`id_' . $ttype . '`=' . $id . ' AND
			`id_user`=' . $current_user->id;
		$res = Database::sql2single($query);
		$this->data['subscribed'] = (int) $res;

		$this->data['success'] = 1;
		return $this->data['subscribed'];
	}

	function isNicknameUnique() {
		global $current_user;
		$this->data['success'] = 1;
		if (!$current_user->authorized) {
			$this->error('Auth');
			return;
		}
		$nickname = isset($_POST['nickname']) ? $_POST['nickname'] : false;

		$mask = array(
		    'nickname' => array(
			'type' => 'string',
			'regexp' => '/^[A-Za-z][A-Za-z0-9_]+$/',
			'min_length' => 3,
			'max_length' => 26,
		    ),
		);
		Request::initialize();
		try {
			$params = Request::checkPostParameters($mask);
		} catch (Exception $e) {
			$this->error($e->getMessage());
			return;
		}
		$nickname = trim($params['nickname']);
		if ($nickname) {
			$query = 'SELECT COUNT(1) as cnt FROM `users` WHERE `nickname`=' . Database::escape($nickname) . ' AND `id` <> ' . $current_user->id;
			$cnt = Database::sql2single($query);
			if ($cnt) {
				$this->error('already_taken');
				return;
			}else
				return true;
		} else {
			$this->error('Illegal nickname ^[A-Za-z][A-Za-z0-9_]+$');
			return;
		}
	}

	function error($s = 'ошибка') {
		$this->data['success'] = 0;
		$this->data['error'] = $s;
		return;
	}

	function use_subscription() {
		global $current_user;
		$this->data['success'] = 0;
		if (!$current_user->authorized) {
			$this->error('Auth');
			return;
		}
		/* @var $current_user CurrentUser */
		$res = false;
		try {
			$res = $current_user->useSubscription();
		} catch (Exception $e) {
			$this->error($e->getMessage());
			return;
		}

		if (!$res) {
			$this->error('Не хватает баллов для подписки');
			return;
		}

		$this->data['subscription_end'] = date('Y/m/d H:i:s', $res);
		$this->data['subscription_days'] = -floor((time() - $res) / 24 / 60 / 60);
		$this->data['success'] = 1;
	}

	function toggle_vandal() {
		global $current_user;
		$this->data['success'] = 0;
		if (!$current_user->authorized) {
			$this->error('Auth');
			return;
		}

		if ($current_user->getRole() < User::ROLE_BIBER) {
			$this->error('Must be biber');
			return;
		}

		$target_id = isset($_POST['id']) ? (int) $_POST['id'] : false;
		if (!$target_id) {
			$this->error('Illegal id');
			return;
		}

		/* @var $target_user CurrentUser */
		$target_user = Users::getByIdsLoaded(array($target_id));
		if (!isset($target_user[$target_id])) {
			$this->error('No user #' . $target_id);
			return;
		}


		$target_user = $target_user[$target_id];

		if ($target_id == $current_user->id) {
			$this->error('Онанизм');
			return;
		}

		$oldRole = $target_user->getRole();

		if ($oldRole < User::ROLE_VANDAL) {
			$this->error('Too small role');
			return;
		}
		if ($oldRole >= User::ROLE_BIBER) {
			$this->error('Too large role');
			return;
		}

		if ($oldRole == User::ROLE_VANDAL) {
			$query = 'UPDATE `users` SET `role`=' . User::ROLE_READER_CONFIRMED . ' WHERE `id`=' . $target_user->id;
			Database::query($query);
			$this->data['user_role'] = User::ROLE_READER_CONFIRMED;
			$this->data['success'] = 1;
			Users::dropCache($target_user->id);
			return;
		}

		if ($oldRole < User::ROLE_SITE_ADMIN) {
			$query = 'UPDATE `users` SET `role`=' . User::ROLE_VANDAL . ' WHERE `id`=' . $target_user->id;
			Database::query($query);
			$this->data['user_role'] = User::ROLE_VANDAL;
			$this->data['success'] = 1;
			Users::dropCache($target_user->id);
			return;
		}

		$this->data['user_role'] = $oldRole;
		$this->data['error'] = '?';
	}

	function checkLoved() {
		global $current_user;
		/* @var $current_user CurrentUser */
		if (!$current_user->authorized) {
			$this->error('Auth');
			return;
		}

		$item_type = isset($_POST['item_type']) ? $_POST['item_type'] : false;
		$item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : false;
		if (!$item_type || !$item_id) {
			$this->error('item_id or item_type missed');
			return;
		}

		if (!isset(Config::$loved_types[$item_type])) {
			$this->error('illegal item_type#' . $item_type);
			return;
		}

		$query = 'SELECT COUNT(1) as cnt FROM `users_loved` WHERE `id_target`=' . $item_id . ' AND `target_type`=' . Config::$loved_types[$item_type] . ' AND `id_user`=' . $current_user->id;
		if (Database::sql2single($query, false)) {
			$this->data['success'] = 1;
			$this->data['in_loved'] = 1;
			return;
		} else {
			$this->data['success'] = 1;
			$this->data['in_loved'] = 0;
		}
	}

	function addLoved() {
		global $current_user;
		$event = new Event();
		/* @var $current_user CurrentUser */
		if (!$current_user->authorized) {
			$this->error('Auth');
			return;
		}

		$item_type = isset($_POST['item_type']) ? $_POST['item_type'] : false;
		$item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : false;
		if (!$item_type || !$item_id) {
			$this->error('item_id or item_type missed');
			return;
		}

		if (!isset(Config::$loved_types[$item_type])) {
			$this->error('illegal item_type#' . $item_type);
			return;
		}

		$query = 'INSERT INTO `users_loved` SET `id_target`=' . $item_id . ',`target_type`=' . Config::$loved_types[$item_type] . ',`id_user`=' . $current_user->id;
		if (Database::query($query, false)) {
			$this->data['success'] = 1;
			$this->data['item_id'] = $item_id;
			$this->data['in_loved'] = 1;
			$event->event_LovedAdd($current_user->id, $item_id, $item_type);
			$event->push();
			if ($item_type == 'book') {
				$time = time();
				// inserting a new mark
				$query = 'INSERT INTO `book_rate` SET `id_book`=' . $item_id . ',`id_user`=' . $current_user->id . ',`rate`=5,`time`=' . $time . ' ON DUPLICATE KEY UPDATE
				`rate`=5 ,`time`=' . $time . ',`with_review`=0';
				Database::query($query);
				//recalculating rate
				$query = 'SELECT COUNT(1) as cnt, SUM(`rate`) as rate FROM `book_rate` WHERE `id_book`=' . $item_id;
				$res = Database::sql2row($query);
				$book_mark = round($res['rate'] / $res['cnt'] * 10);
				$book = Books::getInstance()->getById($item_id);
				/* @var $book Book */
				$book->updateLovedCount();
				$query = 'UPDATE `book` SET `mark`=' . $book_mark . ' WHERE `id`=' . $item_id;
				Database::query($query);
			}
			return;
		} else {
			$query = 'DELETE FROM `users_loved` WHERE `id_target`=' . $item_id . ' AND `target_type`=' . Config::$loved_types[$item_type] . ' AND `id_user`=' . $current_user->id;
			if (Database::query($query, false)) {
				$this->data['success'] = 1;
				$this->data['item_id'] = $item_id;
				$this->data['in_loved'] = 0;
				if ($item_type == 'book') {
					$book = Books::getInstance()->getById($item_id);
					/* @var $book Book */
					$book->updateLovedCount();
				}
				return;
			} else {
				$this->data['success'] = 0;
			}
		}
	}

}