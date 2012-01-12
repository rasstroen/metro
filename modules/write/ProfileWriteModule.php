<?php

class ProfileWriteModule extends BaseWriteModule {

	function write() {
		global $current_user;
		/* @var $current_user CurrentUser */



		$mask = array(
		    'id' => 'int',
		    'bday' => 'string',
		    'city_id' => 'int',
		    'nickname' => array(
			'type' => 'string',
			'regexp' => '/^[A-Za-z][A-Za-z0-9_]+$/',
			'min_length' => 3,
			'max_length' => 26,
			'*' => true,
		    ),
		    'role' => array(
			'type' => 'int',
			'*' => true,
		    ),
		    'link_fb' => array(
			'type' => 'string',
			'*' => true,
		    ),
		    'link_vk' => array(
			'type' => 'string',
			'*' => true,
		    ),
		    'link_lj' => array(
			'type' => 'string',
			'*' => true,
		    ),
		    'link_tw' => array(
			'type' => 'string',
			'*' => true,
		    ),
		    'quote' => array(
			'type' => 'string',
			'*' => true,
		    ),
		    'about' => array(
			'type' => 'string',
			'*' => true,
		    ),
		);
		$params = Request::checkPostParameters($mask);


		$uid = isset($params['id']) ? $params['id'] : 0;
		if (!$uid)
			throw new Exception('illegal user id');

		if ($current_user->id != $params['id']) {
			if ($current_user->getRole() >= User::ROLE_BIBER) {
				$editing_user = Users::getByIdsLoaded(array($params['id']));
				$editing_user = isset($editing_user[$params['id']]) ? $editing_user[$params['id']] : false;
			}
		}else
			$editing_user = $current_user;

		$current_user->can_throw('users_edit', $editing_user);



		if ($editing_user) {
			if (trim($params['nickname']) != $editing_user->getNickName()) {
				if (!$editing_user->checkNickChanging()) {
					throw new Exception('You can\'t change your nickname');
				}
			}
			//avatar
			if (isset($_FILES['picture']) && $_FILES['picture']['tmp_name']) {
				$filename = Config::need('avatar_upload_path') . '/' . $editing_user->id . '.jpg';
				$upload = new UploadAvatar($_FILES['picture']['tmp_name'], 100, 100, "simple", $filename);
				if ($upload->out)
					$editing_user->setProperty('picture', 1);
				else {
					throw new Exception('cant copy file to ' . $filename, 100);
				}
			}
			if ($editing_user->getRole() < User::ROLE_SITE_ADMIN) {
				if ($current_user->getRole() == User::ROLE_BIBER) {
					if (($new_role = (int) $params['role']) !== false) {
						foreach (Users::$rolenames as $id => $name) {
							if ($id == $new_role) {
								if ($new_role < User::ROLE_SITE_ADMIN) {
									$editing_user->setRole($new_role);
								}
							}
						}
					}
				}
				if ($current_user->getRole() > User::ROLE_BIBER) {
					if (($new_role = (int) $params['role']) !== false) {
						foreach (Users::$rolenames as $id => $name) {
							if ($id == $new_role) {
								if ($new_role <= User::ROLE_SITE_ADMIN) {
									$editing_user->setRole($new_role);
								}
							}
						}
					}
				}
			}
			if (trim($params['nickname']) != $current_user->getNickName())
				$editing_user->setProperty('nickModifyTime', time());
			//bday
			$editing_user->setProperty('bday', max(0, (int) @strtotime($params['bday'])));
			$editing_user->setProperty('nickname', trim($params['nickname']));
			// city
			$editing_user->setProperty('city_id', $params['city_id']);
			// facebook etc
			$editing_user->setPropertySerialized('link_fb', $params['link_fb']);
			$editing_user->setPropertySerialized('link_vk', $params['link_vk']);
			$editing_user->setPropertySerialized('link_tw', $params['link_tw']);
			$editing_user->setPropertySerialized('link_lj', $params['link_lj']);

			$params['quote'] = htmlspecialchars($params['quote']);
			$params['about'] = htmlspecialchars($params['about']);

			$editing_user->setPropertySerialized('quote', $params['quote']);
			$editing_user->setPropertySerialized('about', $params['about']);

			$editing_user->save();
			// после редактирования профиля надо посбрасывать кеш со страницы профиля
			// и со страницы редактирования профиля
			// кеш в остальных модулях истечет сам
			Users::dropCache($editing_user->id);
		}
		else
			Error::CheckThrowAuth(User::ROLE_SITE_ADMIN);
	}

}