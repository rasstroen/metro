<?php

class users_module extends BaseModule {

	public $id;
	private $shelfCountOnMain = 5;

	function getAuth() {
		global $current_user;
		/* @var $current_user CurrentUser */
		$this->data['profile']['authorized'] = 0;
		if ($current_user->authorized) {
			// авторизован
			$this->data['profile'] = $current_user->getListData();
			list($this->data['profile']['new_messages'], $this->data['profile']['new_notifications']) = $current_user->getNewMessagesCount();
			$this->data['profile']['picture'] = $current_user->getAvatar();
			$this->data['profile']['authorized'] = 1;
		}
		return;
		$menu = array();
		$menu[0] = array('name' => 'social');
		$menu[1] = array('name' => 'user');
		// menu item - messages
		if ($current_user->authorized) {
			$menu[0]['menu_items'][0] = array(
			    'name' => 'Мои книги',
			    'path' => Config::need('www_path') . '/me/books',
			    'prev_icon_class' => 'books',
			);
			//submenu
			$menu[0]['menu_items'][0]['menu_items'][0] = array(
			    'name' => 'Я читаю',
			    'path' => Config::need('www_path') . '/user/' . $current_user->id . '/books/reading',
			    'additional' => $current_user->getCounter('polka_reading'),
			);
			$menu[0]['menu_items'][0]['menu_items'][1] = array(
			    'name' => 'Я хочу прочитать',
			    'path' => Config::need('www_path') . '/user/' . $current_user->id . '/books/to-read',
			    'additional' => $current_user->getCounter('polka_to-read'),
			);
			$menu[0]['menu_items'][0]['menu_items'][2] = array(
			    'name' => 'Я прочитал',
			    'path' => Config::need('www_path') . '/user/' . $current_user->id . '/books/read',
			    'additional' => $current_user->getCounter('polka_read'),
			);
			$menu[0]['menu_items'][1] = array(
			    'name' => 'Сообщения',
			    'path' => Config::need('www_path') . '/me/messages',
			    'prev_icon_class' => 'messages',
			    'additional' => $current_user->getCounter('new_messages'),
			);
			$menu[0]['menu_items'][1]['menu_items'][0] = array(
			    'name' => 'Личные',
			    'path' => Config::need('www_path') . '/me/messages',
			    'additional' => $current_user->getCounter('new_messages'),
			);
			$menu[0]['menu_items'][1]['menu_items'][1] = array(
			    'name' => 'Системные',
			    'path' => Config::need('www_path') . '/me/notifications',
			    'additional' => $current_user->getCounter('new_notifications'),
			);
			$menu[0]['menu_items'][2] = array(
			    'name' => 'Друзья',
			    'path' => Config::need('www_path') . '/me/wall',
			    'prev_icon_class' => 'friends'
			);
			$menu[1]['menu_items'][0] = array(
			    'name' => $current_user->getNickName(),
			    'path' => Config::need('www_path') . '/user/' . $current_user->id,
			    'next_icon_class' => 'triangle_down'
			);
			$menu[1]['menu_items'][0]['menu_items'][0] = array(
			    'name' => 'Выход',
			    'path' => Config::need('www_path') . '/logout',
			);
		}
		$this->data['navigations'] = $menu;
	}

	function generateData() {
		global $current_user;

		if (isset($this->params['user_id']) && !is_numeric($this->params['user_id'])) {
			if ($this->params['user_id'] == 'me') {
				$this->params['user_id'] = $current_user->id;
			} else {
				$query = 'SELECT `id` FROM `users` WHERE `nickname`=' . Database::escape($this->params['user_id']);
				$this->params['user_id'] = (int) Database::sql2single($query);
			}
		}

		$this->id = isset($this->params['user_id']) ? (int) $this->params['user_id'] : $current_user->id;
		$this->genre_id = isset($this->params['genre_id']) ? $this->params['genre_id'] : false;

		switch ($this->action) {
			case 'edit':
				$current_user->can_throw('users_edit', Users::getById($this->id));
				switch ($this->mode) {
					case 'notifications':
						$this->editNotifications();
						break;
					default:
						$this->getProfile($edit = true);
						break;
				}
				break;
			case 'show':
				switch ($this->mode) {
					case 'auth':
						$this->getAuth();
						break;
					case 'subscriptions':
						$this->getSubscriptions();
						break;
					default:
						$this->getProfile();
						break;
				}
				break;
			case 'list':
				switch ($this->mode) {
					case 'search':
						$this->getSearch();
						break;
					case 'friends':
						$this->getFriends();
						break;
					case 'likes':
						$this->getLikes();
						break;
					case 'followers':
						$this->getFollowers();
						break;
					case 'compare_interests':
						$this->getCompareInterests();
						break;
					default:
						throw new Exception('no mode #' . $this->mode . ' for ' . $this->moduleName);
						break;
				}
				break;
			default:
				throw new Exception('no action #' . $this->action . ' for ' . $this->moduleName);
				break;
		}
	}

	function editNotifications($edit = true) {
		global $current_user;
		/* @var $current_user CurrentUser */
		/* @var $user User */
		$user = ($current_user->id === $this->id) ? $current_user : Users::getById($this->id);
		if ($edit && ($user->id != $current_user->id)) {
			$current_user->can_throw('users_edit', $user);
		}

		$this->data['notify_rules'] = $user->getNotifyRules();
		$this->data['user'] = $user->getListData();
	}

	function _list($ids, $opts = array(), $limit = false) {
		$users = Users::getByIdsLoaded($ids);
		$out = array();
		/* @var $user User */
		$i = 0;
		if (is_array($users))
			foreach ($users as $user) {
				if ($limit && ++$i > $limit)
					return $out;
				$out[] = $user->getListData();
			}
		return $out;
	}

	function getSearch() {
		$query_string = isset(Request::$get_normal['s']) ? Request::$get_normal['s'] : false;
		$query_string_prepared = ('%' . mysql_escape_string($query_string) . '%');
		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 60;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
		$where = 'WHERE `nickname` LIKE \'' . $query_string_prepared . '\' OR `email` LIKE \'' . $query_string_prepared . '\' OR `id`=\'' . $query_string_prepared . '\'';
		$order = 'ORDER BY `regTime` DESC ';
		$group_by = '';
		$query = 'SELECT COUNT(1) FROM `users` ' . $where . ' ' . $group_by . '';
		$count = Database::sql2single($query);
		$cond->setPaging($count, $per_page, $pagingName);
		$limit = $cond->getLimit();
		$limit = ' LIMIT ' . $limit;
		$query = 'SELECT * FROM `users`' . $where . ' ' . $group_by . ' ' . $order . ' ' . $limit;
		$data = Database::sql2array($query);
		foreach ($data as $row) {
			$user = new User($row['id'], $row);
			Users::add($user);
			$this->data['users'][] = $user->getListData(true);
		}
		$this->data['users']['title'] = 'Пользователи';
		$this->data['users']['count'] = $count;
		$this->data['conditions'] = $cond->getConditions();
	}

	function getSubscriptions() {
		global $current_user;
		$user = Users::getByIdsLoaded(array($this->params['user_id']));
		$user = isset($user[$this->params['user_id']]) ? $user[$this->params['user_id']] : $current_user;
		/* @var $user User */
		$subscriptions = $user->getSubscriptions();

		$this->data['subscriptions'] = $subscriptions;
		$this->data['user'] = $user->getListData();
		$this->data['user']['unused_points'] = $user->getPoints();
		$this->data['subscriptions']['active'] = $user->isSubscriptionEnabled() ? '1' : 0;
		$this->data['subscriptions']['end'] = $user->getSubscriptionEnd() ? date('Y/m/d H:i:s ', $user->getSubscriptionEnd()) : 0;
	}

	// все, кому что-то нравится
	function getLikes() {
		if (!$this->genre_id)
			return;
		$query = 'SELECT * FROM `genre` WHERE `name`=' . Database::escape($this->genre_id);
		$data = Database::sql2row($query);
		if ($data['id']) {
			
		}
	}

	function getCompareInterests() {
		$ids = Database::sql2array('SELECT id FROM users LIMIT 50', 'id');
		$this->data['users'] = $this->_list(array_keys($ids), array(), 15);
		$this->data['users']['link_url'] = 'user/' . $this->params['user_id'] . '/compare';
		$this->data['users']['link_title'] = 'Все единомышленники';
		$this->data['users']['title'] = 'Люди с похожими интересами';
		$this->data['users']['count'] = count($ids);
	}

	function getFriends() {
		global $current_user;
		$user = Users::getById($this->params['user_id']);
		$followingids = $user->getFollowing();
		$this->data['users'] = $this->_list($followingids, array(), 10);
		$this->data['users']['link_url'] = 'user/' . $this->params['user_id'] . '/friends';
		$this->data['users']['link_title'] = 'Все друзья';
		$this->data['users']['title'] = 'Друзья';
		$this->data['users']['count'] = count($followingids);
	}

	function getFollowers() {
		global $current_user;
		/* @var $user User */
		$user = Users::getById($this->params['user_id']);
		$followersids = $user->getFollowers();
		$this->data['users'] = $this->_list($followersids, array(), 10);
		$this->data['users']['link_url'] = 'user/' . $this->params['user_id'] . '/followers';
		$this->data['users']['link_title'] = 'Все поклонники';
		$this->data['users']['title'] = 'Поклонники';
		$this->data['users']['count'] = count($followersids);
	}

	function getProfile($edit =false) {

		global $current_user;


		/* @var $current_user CurrentUser */
		/* @var $user User */
		$user = ($current_user->id === $this->id) ? $current_user : Users::getById($this->id);

		if ($edit && ($user->id != $current_user->id)) {
			$current_user->can_throw('users_edit', $user);
		}

		if ($edit) {
			foreach (Users::$rolenames as $id => $role)
				$this->data['roles'][] = array('id' => $id, 'title' => $role);
		}
		try {
			$user->load();
		} catch (Exception $e) {
			throw new Exception('Пользователя не существует');
		}
		if ($user->loaded) {
			
		}
		else
			return;
		$this->data['profile'] = $user->getXMLInfo();


		$this->data['profile']['role'] = $user->getRole();
		$this->data['profile']['lang'] = $user->getLanguage();
		$this->data['profile']['city_id'] = $user->getProperty('city_id');
		$this->data['profile']['city'] = Database::sql2single('SELECT `name` FROM `lib_city` WHERE `id`=' . (int) $user->getProperty('city_id'));
		$this->data['profile']['picture'] = $user->getAvatar();
		$this->data['profile']['rolename'] = $user->getRoleName();
		$this->data['profile']['bday'] = $user->getBday(date('d-m-Y'), 'd-m-Y');
		$this->data['profile']['path'] = $user->getUrl();
		$this->data['profile']['path_edit'] = $user->getUrl() . '/edit';

		$this->data['profile']['bdays'] = $user->getBday('неизвестно', 'd.m.Y');
		// additional
		$this->data['profile']['link_fb'] = $user->getPropertySerialized('link_fb');
		$this->data['profile']['link_vk'] = $user->getPropertySerialized('link_vk');
		$this->data['profile']['link_tw'] = $user->getPropertySerialized('link_tw');
		$this->data['profile']['link_lj'] = $user->getPropertySerialized('link_lj');

		$this->data['profile']['quote'] = $user->getPropertySerialized('quote');
		$this->data['profile']['about'] = $user->getPropertySerialized('about');
		$this->data['profile']['change_nickname'] = $user->checkNickChanging();
		$this->data['profile']['download_count'] = $user->profile['totalDownloadCount'];
//		$this->data['profile']['path_message'] = Config::need('www_path').'/me/messages?to='.$user->id;
		$this->data['profile']['path_message'] = Config::need('www_path') . '/user/' . $user->getNickName() . '/contact';
		$this->data['profile']['path_edit_notifications'] = Config::need('www_path') . '/user/me/edit_notifications';
		$this->data['profile']['path_stat'] = Config::need('www_path') . '/admin/users/stat/' . $user->id;


		$this->data['download_limit'] = array(
		    'count' => $dc = $user->getDownloadCount(),
		    'limit' => $dl = $user->getDownloadLimit(),
		    'available' => max(0, $dl - $dc),
		);
	}

	/**
	 *
	 */
}
