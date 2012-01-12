<?php

class Jmessages_module extends JBaseModule {

	function process() {
		switch ($_POST['action']) {
			case 'del_thread':
				$this->del_thread();
				break;
			case 'del_message':
				$this->del_message();
				break;
		}
	}

	function ca() {
		global $current_user;
		$current_user = new CurrentUser();
		if (!$current_user->authorized)
			throw new Exception('authorization');
		return true;
	}

	function del_thread() {
		global $current_user;
		/* @var $current_user CurrentUser */
		$this->ca();
		$id = max(0, (int) $_POST['id']);
		$query = 'UPDATE `users_messages_index` SET `is_deleted` = 1 WHERE `thread_id`=' . (int) $id . ' AND `id_recipient`=' . $current_user->id;
		if (Database::query($query, false))
			$this->data['success'] = 1;else
			$this->data['success'] = 0;
		$current_user->reloadNewMessagesCount();
		$current_user->save();
	}

	function del_message() {
		global $current_user;
		$this->ca();
		$id = max(0, (int) $_POST['id']);
		$query = 'UPDATE `users_messages_index` SET `is_deleted` = 1 WHERE `message_id`=' . $id . ' AND `id_recipient`=' . $current_user->id;
		if (Database::query($query, false))
			$this->data['success'] = 1;else
			$this->data['success'] = 0;
		$current_user->reloadNewMessagesCount();
		$current_user->save();
	}

}