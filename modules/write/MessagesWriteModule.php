<?php

class MessagesWriteModule extends BaseWriteModule {

	function write() {
		global $current_user;
		/* @var $current_user User */
		if (!$current_user->authorized)
			throw new Exception('Access Denied');

		$id_author = $current_user->id;
		$to_users_ = isset(Request::$post['to']) ? Request::$post['to'] : array();

		if (!is_array($to_users_))
			$to_users_ = array($to_users_);

		foreach ($to_users_ as $id) {
			$to_users[$id] = $id;
		}

		foreach ($to_users as $id) {
			if (strstr($id, ',')) {
				$t_to_users = explode(',', $id);
				foreach ($t_to_users as $n) {
					$to_users_p[trim($n)] = trim($n);
				}
			}
			else
				$to_users_p[trim($id)] = trim($id);
		}

		$to_users = $to_users_p;
		if (isset($to_users[$current_user->id]))
			throw new Exception('self mailing');

		if (isset($to_users[$current_user->getNickName()]))
			throw new Exception('self mailing');

		$loaded = array();

		foreach ($to_users as $id) {
			$tmp = new User($id);
			$tmp->load();
			$loaded[$tmp->id] = $tmp;
		}

		foreach ($loaded as $key => $u)
			$to_users[$key] = $key;

		$subject = isset(Request::$post['subject']) ? Request::$post['subject'] : 'Без темы';
		$body = isset(Request::$post['body']) ? Request::$post['body'] : false;
		$subject = prepare_review($subject, '');
		$body = prepare_review($body, '');
		if (!$body)
			throw new Exception('body!');
		$time = time();
		$thread_id = isset(Request::$post['thread_id']) ? Request::$post['thread_id'] : false;

		if ($thread_id) {
			// а можно ли писать в этот тред этому человеку?
			$query = 'SELECT DISTINCT id_recipient FROM `users_messages_index` WHERE `thread_id`=' . $thread_id;
			$usrs = Database::sql2array($query);
			$found = false;
			$to_users = array();
			if ($usrs) {
				foreach ($usrs as $usr) {
					if ($usr['id_recipient'] == $current_user->id)
						$found = true;
					$to_users[$usr['id_recipient']] = $usr['id_recipient'];
				}
			}
			if (!$found)
				throw new Exception('You cant post to thread #' . $thread_id);
		}

		$to_users[$current_user->id] = $current_user->id;

		$body = texttourl($body);
		$this->sendMessage($id_author, $to_users, $subject, $body, $time, $thread_id);
	}

	function sendMessage($id_author, $to_users, $subject, $body, $time, $thread_id = false, $type = 0) {
		global $current_user;
		if (!is_array($to_users))
			throw new Exception('$to_users must be an array');
		Database::query('START TRANSACTION');
		$query = 'INSERT INTO `users_messages` SET
			`id_author`=' . (int) $id_author . ',
			`time`=' . $time . ',
			`subject`=' . Database::escape($subject) . ',
			`html`=' . Database::escape($body);
		Database::query($query);
		// если есть тред - пишем в тот же тред
		$lastId = Database::lastInsertId();
		$thread_id = $thread_id ? $thread_id : $lastId;
		if ($thread_id) {
			$q = array();
			foreach ($to_users as $receiver_id) {
				if (!(int) $receiver_id)
					continue;
				$to_user = new User($receiver_id);
				$to_user->reloadNewMessagesCount();
				$is_new = ($receiver_id == $id_author) ? 0 : 1;
				$q[] = '(' . (int) $lastId . ',' . (int) $thread_id . ',' . (int) $receiver_id . ',' . (int) $is_new . ',0,' . (int) $type . ')';
			}
			if (count($q)) {
				$query = 'INSERT INTO `users_messages_index`(message_id,thread_id,id_recipient,is_new,is_deleted,type) VALUES ' . implode(',', $q);
				Database::query($query);
			}
		}
		// increase counters
		$receivers = Users::getByIdsLoaded($to_users);
		foreach ($receivers as $receiver) {
			/* @var $receiver User */
			if ($type == 0)
				$receiver->setCounter('new_messages', $receiver->getCounter('new_messages') + 1);
			else
				$receiver->setCounter('new_notifications', $receiver->getCounter('new_notifications') + 1);
			$receiver->save();
		}

		if ($type == 0 && $current_user) { // не нотифай
			Notify::notifyNewInbox($to_users, $id_author);
		}
		Database::query('COMMIT');
	}

}
