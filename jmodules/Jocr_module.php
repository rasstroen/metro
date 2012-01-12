<?php

class Jocr_module extends JBaseModule {

	function process() {
		global $current_user;
		$current_user = new CurrentUser();
		switch ($_POST['action']) {
			case 'check':
				$this->check();
				break;
			case 'set':
				$this->set();
				break;
		}
	}

	function error($s = 'ошибка') {
		$this->data['success'] = 0;
		$this->data['error'] = $s;
		return;
	}

	function check() {
		global $current_user;
		$this->data['success'] = 1;
		if (!$current_user->authorized) {
			$this->error('Auth');
			return;
		}

		$id_user = $current_user->id;
		$id_book = max(0, (int) $_POST['id_book']);

		$query = 'SELECT * FROM `ocr` WHERE `id_book`=' . $id_book . ' AND `id_user`=' . $id_user . '';
		$r = Database::sql2array($query);
		$this->data['ocr'] = array();
		foreach ($r as $row) {
			$this->data['ocr'][] = array(
			    'id_book' => $row['id_book'],
			    'status' => $row['status'],
			    'state' => $row['state'],
			);
		}
	}

	function set() {
		global $current_user;

		$this->data['success'] = 1;
		if (!$current_user->authorized) {

			$this->error('Auth');
			return;
		}
		/* @var $current_user User */
		$id_user = false;
		if (isset($_POST['id_user'])) {
			if (!$current_user->can('ocr_edit')) {
				$this->error('You must be biber to do that');
				return;
			} else {
				$id_user = (int) $_POST['id_user'];
			}
		}
		$_POST['status'] = isset($_POST['status']) ? $_POST['status'] : -1;
		$_POST['state'] = isset($_POST['state']) ? $_POST['state'] : -1;
		$id_user = $id_user ? $id_user : $current_user->id;
		$id_book = max(0, (int) $_POST['id_book']);

		if (!is_numeric($_POST['status'])) {
			foreach (Ocr::$statuses as $s) {
				if ($s['name'] == $_POST['status'])
					$_POST['status'] = $s['id'];
			}
		}

		if (!is_numeric($_POST['state'])) {
			foreach (Ocr::$states as $s) {
				if ($s['name'] == $_POST['state']) {
					$_POST['state'] = $s['id'];
				}
			}
		}
		
		$user = Users::getById($id_user);
		/*@var $user User*/
		$user->load();
		

		$status = max(-1, (int) $_POST['status']);
		$state = max(-1, (int) $_POST['state']);
		try {
			Ocr::setStatus($id_user, $id_book, $status, $state);
		} catch (Exception $e) {
			$this->error($e->getMessage());
		}
		if ($state == Ocr::STATE_APPROVED) {
			$user->gainActionPoints('ocr_add', $id_book, BiberLog::TargetType_book);
		}
	}

}

