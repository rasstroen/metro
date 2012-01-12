<?php

// класс, отвечающий за текущего юзера
class CurrentUser extends User {

	public $new_messages_cachetime = 60;
	public $xml_fields = array(// в отличие от всех юзеров для текущего мы можем выдавать больше данных
	    'id',
	    'email',
	    'role',
	    'nickname',
	    'lastSave',
	);
	public $authorized = false;
	public $hash = '';
	

	function __construct($failed = false) {
		parent::__construct();
		if (!$failed)
			$this->uid = $this->authorize_cookie();
	}

	public function getProperty($field, $default = false) {
		return isset($this->profile[$field]) ? $this->profile[$field] : $default;
	}

	public function getXMLInfo() {
		$this->load();
		$out = $this->profile_xml;
		list($out['new_messages'], $out['new_notifications']) = $this->getNewMessagesCount();
		$out['change_nickname'] = $this->checkNickChanging();
		return $out;
	}

	public function getNewMessagesCount() {
		return array($this->getCounter('new_messages'), $this->getCounter('new_notify'));
	}

	

	public function logout() {
		$this->authorized = false;
		$this->id = 0;
		$this->setAuthCookie($value, true);
	}

	// именно залогинились
	public function onLogin() {
		$hash = md5($this->id . ' ' . time() . ' ' . rand(1, 1000));
		$query = 'INSERT INTO `users_session` SET
			`user_id`=' . $this->id . ',
			`session`=\'' . $hash . '\',
			`expires`=' . (time() + Config::need('auth_cookie_lifetime')) . '
			ON DUPLICATE KEY UPDATE
			`session`=\'' . $hash . '\',
			`expires`=' . (time() + Config::need('auth_cookie_lifetime'));
		Database::query($query);
		Cache::drop('auth_' . $this->id);
		$this->setProperty('lastLogin', time());
		$this->setProperty('lastIp', Request::$ip);
		$this->setAuthCookie($hash);
		$this->reloadNewMessagesCount();
		$this->save();
	}

	public function getAvailableNickname($nickname, $additional = '') {
		$nickname = trim($nickname) . $additional;
		$query = 'SELECT `nickname` FROM `users` WHERE `nickname` LIKE \'' . $nickname . '\' LIMIT 1';
		$row = Database::sql2single($query);
		if ($row && $row['nickname']) {
			return $this->getAvailableNickname($nickname, $additional . rand(1, 99));
		}
		return $nickname;
	}

	public function setCookie($name, $value, $time = false) {
		if (!$time)
			$time = time() + 5 * 24 * 60 * 60;
		Request::headerCookie($name, $value, $time, '/', Config::need('www_domain'), false, true);
	}

	private function setAuthCookie($value, $delete = false) {
		if ($delete) {
			$time = time() - 1;
		} else {
			$time = time() + Config::need('auth_cookie_lifetime');
		}
		Request::headerCookie(Config::need('auth_cookie_hash_name'), $value, $time, '/', Config::need('www_domain'), false, true);
		Request::headerCookie(Config::need('auth_cookie_id_name'), $this->id, time() + Config::need('auth_cookie_lifetime'), '/', Config::need('www_domain'), false, true);
	}

	// авторизуем пользователя по кукам
	public function authorize_cookie() {

		$auth_cookie_name = Config::need('auth_cookie_hash_name');
		$auth_uid_name = Config::need('auth_cookie_id_name');
		$xcache_cookie = 'auth_' . (int) $_COOKIE[$auth_uid_name];
		$to_cache = true;
		if (isset($_COOKIE[$auth_cookie_name]) && isset($_COOKIE[$auth_uid_name])) {
			if ($row = Cache::get($xcache_cookie)) {
				$row = unserialize($row);
				$to_cache = false;
			} else {
				$query = 'SELECT `session`,`expires` FROM `users_session` WHERE `user_id`=' . (int) $_COOKIE[$auth_uid_name];
				$row = Database::sql2row($query);
			}
			if ($row) {
				if ($row['session'] == $_COOKIE[$auth_cookie_name] && $row['expires'] > time()) {
					$this->id = (int) $_COOKIE[$auth_uid_name];
					$this->load();
					$this->authorized = true;
					if ($to_cache)
						Cache::set($xcache_cookie, serialize($row), Config::need('auth_cookie_lifetime'));
				}
			}
			else {

				setcookie($_COOKIE[$auth_cookie_name], false, time() - 10);
				exit(0);
			}
		} else
			return false;
	}

	// авторизуем пользователя по логину и паролю
	public function authorize_password($email, $password, $md5used = false) {
		$row = Database::sql2row('SELECT * FROM `users` WHERE 
			(`email`=\'' . $email . '\' OR 
			`nickname`=\'' . $email . '\')');
		if (!$row) {
			// нет такого пользователя
			return 'user_missed';
		}

		$password = $md5used ? $password : md5($password);
		if ($row) {
			if ($password != $row['password']) {
				return 'user_password';
			}
		}
		$this->load($row);
		$this->authorized = true;
		$this->onLogin();
		return true;
	}

}