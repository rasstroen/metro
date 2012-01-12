<?php

class RegisterWriteModule extends BaseWriteModule {

	public $email_subject = 'Регистрация на сайте';

	function write() {
		global $current_user;
		$this->setWriteParameter('register_module', 'result', false);
		$mask = array(
		    'email' => 'email',
		    'password' => array(
			'type' => 'string',
			'min_length' => 6,
			'max_length' => 16,
		    ),
		    'nickname' => array(
			'type' => 'string',
			'regexp' => '/^[A-Za-z][A-Za-z0-9_]+$/',
			'min_length' => 3,
			'max_length' => 26,
			'*' => true
		    ),
		);
		$params = Request::checkPostParameters($mask);

		$error = false;

		if ($params['email'] === false) {
			$error = true;
			$this->setWriteParameter('register_module', 'email_error', 'Введён неправильный email адрес');
		}
		if ($params['password'] === false) {
			$error = true;
			$this->setWriteParameter('register_module', 'password_error', 'Пароль должен содержать от 6 до 16 символов');
		}
		if ($params['nickname'] === false) {
			$this->setWriteParameter('register_module', 'nickname_error', 'Ник должен содержать от 3 до 20 символов латирского алфавита и цифр');
		}

		foreach ($params as $f => $v) {
			$this->setWriteParameter('register_module', $f, $v);
		}

		if ($error) {
			return false;
		}

		// не занят ли email
		$query = 'SELECT COUNT(1) FROM `users` WHERE
			`email`=\'' . $params['email'] . '\'';
		$email_twiced = Database::sql2single($query);
		if ($email_twiced) {
			$this->setWriteParameter('register_module', 'email_error', 'Такой email адрес уже используется');
			return;
		}
		// не занят ли ник. если занят, будет пока без ника - предложим поменять в лк
		if ($params['nickname']) {
			$query = 'SELECT COUNT(1) FROM `users` WHERE
			`nickname`=\'' . $params['nickname'] . '\'';
			$nickname_twiced = Database::sql2single($query);
			if ($nickname_twiced) {
				$nickname = $current_user->getAvailableNickname($params['nickname']);
				$this->setWriteParameter('register_module', 'nickname_changed', $nickname);
			}
			else
				$nickname = $params['nickname'];
		} else {
			$nickname_from_email = substr($params['email'], 0, strpos($params['email'], '@'));
			$nickname = $current_user->getAvailableNickname($nickname_from_email);
		}

		// закончили проверять параметры. теперь пишем пользователя в базу
		$r = $current_user->register($nickname, $params['email'], $params['password']);
		if ($r) {
			// мы успешно добавили пользователя в базу
			$register_url = Config::need('www_path') . '/emailconfirm/' . $current_user->id . '/' . $r;
			// теперь отсылаем ему письмо
			$data = array(
			    'email' => $params['email'],
			    'nickname' => $nickname,
			    'password' => $params['password'],
			    'register_url' => $register_url
			);
			Mailer::send(Config::need('register_email_from'), $params['email'], $nickname, $this->email_subject, $data, 'register.xsl');
			// передаем в шаблон "всё в порядке!"
			$this->setWriteParameter('register_module', 'success', $nickname);
			// выходим
			return;
		}
		// а это может случиться при регистрации 2х юзеров с одним мылом/ником одновременно или падении бд
		$this->setWriteParameter('register_module', 'error', 'database_error');
	}

}