<?php

/**
 * Авторизуем пользователя по логину-паролю или email-паролю
 */
class AuthWriteModule extends BaseWriteModule {

	function process() {
		$mask = array(
		    'email' => 'email',
		    'password' => 'string'
		);
		$params = Request::checkPostParameters($mask);

		// к нам ломится пользователь с логином паролем.

		global $current_user;
		/* @var $current_user CurrentUser */
		$result = $current_user->authorize_password($params['email'], $params['password']);
		if ($result !== true) {
			throw new Exception('Неверный пароль');
			$this->setWriteParameter('AuthModule', 'error', $result);
		} else {
			$current_user->save();
		}
	}

}