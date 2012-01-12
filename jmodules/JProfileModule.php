<?php

class JProfileModule extends JBaseModule {

	function process() {
		switch ($_POST['action']) {
			case 'init':
				$this->init();
				break;
			case 'getCityList':
				$this->getCityList();
				break;
			case 'addFriend':
				$this->addFriend();
				break;
			case 'checkFriend':
				$this->checkFriend();
				break;
			case 'removeFriend':
				$this->removeFriend();
				break;
		}
	}

	function init() {
		$city = isset($_POST['city_id']) ? (int) $_POST['city_id'] : 1;
		$this->data['city_id'] = $city;
		$this->data['country_id'] = (int) Database::sql2single('SELECT `country_id` FROM `lib_city` WHERE `id`=' . $city);
		if (!$this->data['country_id']) {
			$this->data['country_id'] = 1;
			$this->data['city_id'] = Database::sql2single('SELECT `id` FROM `lib_city` WHERE `country_id`=' . $this->data['country_id'] . ' LIMIT 1');
		}
		$this->data['country_list'] = Database::sql2array('SELECT `id`,`name` FROM `lib_country` ORDER BY `name` ');
		$this->data['city_list'] = Database::sql2array('SELECT `id`,`name` FROM `lib_city` WHERE `country_id`=' . $this->data['country_id'] . ' LIMIT 1000', 'id');
	}

	function getCityList() {
		$country = isset($_POST['country_id']) ? (int) $_POST['country_id'] : 1;
		$this->data['city_list'] = Database::sql2array('SELECT `id`,`name` FROM `lib_city` WHERE `country_id`=' . $country . ' LIMIT 1000', 'id');
		$this->data['country_id'] = $country;
		$this->data['city_id'] = Database::sql2single('SELECT `id` FROM `lib_city` WHERE `country_id`=' . $country . ' LIMIT 1');
	}

	function checkFriend() {
		$id = max(0, (int) $_POST['id']);
		$current_user = new CurrentUser();
		$this->data['result'] = -1;
		if ($current_user->authorized) {
			if ($current_user->id != $id) {
				$user_following = $current_user->getFollowing();
				if (isset($user_following[$id])) {
					$this->data['result'] = 1;
				} else {
					$this->data['result'] = 0;
				}
			}
		}
	}

	function addFriend() {
		$id = max(0, (int) $_POST['id']);
		$current_user = new CurrentUser();
		if ($current_user->authorized) {
			if ($current_user->id != $id) {
				$user_following = $current_user->getFollowing();
				$friend = Users::getById($id);
				/* @var $friend User */
				$friend_followers = $friend->getFollowers();

				$user_following[$id] = $id;
				$friend_followers[$current_user->id] = $current_user->id;

				$current_user->setFollowing($user_following);
				$friend->setFollowers($friend_followers);

				$friend->onNewFollower($current_user->id);
				$current_user->onNewFollowing($id);

				$friend->save();
				$current_user->save();
			}
		}
	}

	function removeFriend() {
		$id = max(0, (int) $_POST['id']);
		$current_user = new CurrentUser();
		if ($current_user->authorized) {
			if ($current_user->id != $id) {
				$user_following = $current_user->getFollowing();
				$friend = Users::getById($id);
				/* @var $friend User */
				$friend_followers = $friend->getFollowers();

				if (isset($user_following[$id]))
					unset($user_following[$id]);
				if (isset($friend_followers[$current_user->id]))
					unset($friend_followers[$current_user->id]);

				$current_user->setFollowing($user_following);
				$friend->setFollowers($friend_followers);

				$friend->onDeletedFromFriend($current_user->id);
				$current_user->onDeleteFriend($id);

				$friend->save();
				$current_user->save();
			}
		}
	}

}