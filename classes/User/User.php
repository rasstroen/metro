<?php

// класс, отвечающий за юзера
//ГЛАГОЛЬ(ЗДРАВСТВУЙ МИРЪ);
//ЗОВЕРШИТЬ ОБРЯД;
class User {
	const ROLE_ANON = 0; // аноним
	const ROLE_READER_UNCONFIRMED = 10; // юзер с неподтвержденным мылом
	const ROLE_VANDAL = 20; // вандал
	const ROLE_READER_CONFIRMED = 30; // юзер с подтвержденным мылом
	const ROLE_BIBER = 40; // бибер
	const ROLE_SITE_ADMIN = 50; // админ вся руси

	public $id = 0;
	// users
	public $changed = array();
	public $profile = array();
	public $shelfLoaded = false;
	public $shelf;
	public $loaded = false;
	//users_additional
	public $profileAdditional = array(); // mongodb stored
	public $changedAdditional = array(); // mongodb stored
	public $loadedAdditional; // if mongodb document fetched
	//
	public $profile_xml = array();
	public $xml_fields = array(
	    'id',
	    'nickname',
	    'lastSave',
	    'lastLogin',
	);
	public $counters_parsed = false;
	public $counters = array(
	    'new_messages' => 0,
	    'new_notifications' => 0,
	    'polka_reading' => 0,
	    'polka_to-read' => 0,
	    'polka_read' => 0,
	);
	public $lovedLoaded = false;
	public $loved;
	public $userNotify;
	private $new_messages_count;
	private $new_notify_count;

	public function reloadNewMessagesCount() {
		if ($this->new_messages_count === null) {
			$query = 'SELECT `type`,COUNT(1) as `cnt` FROM `users_messages_index` WHERE `id_recipient`=' . $this->id . ' AND `is_new`=1 AND `is_deleted`=0
GROUP BY `type`';
			$data = Database::sql2array($query, 'type');
			$this->new_messages_count = isset($data[0]) ? $data[0]['cnt'] : 0;
			$this->new_notify_count = isset($data[1]) ? $data[1]['cnt'] : 0;
		}
		$this->setCounter('new_messages', $this->new_messages_count);
		$this->setCounter('new_notifications', $this->new_notify_count);
		$this->save();
	}

	function setCounter($name, $value) {
		$this->unparseCounters();
		if (!isset($this->counters[$name]))
			throw new Exception('illegal counter name ' . $name);
		$this->counters[$name] = max(0, (int) $value);
		$this->parseCounters();
	}

	function reloadPolkaCounters() {
		$this->unparseCounters();

		$query = 'SELECT `bookshelf_type`, COUNT(1) as `cnt` FROM `users_bookshelf` WHERE `id_user`=' . $this->id . ' GROUP BY `bookshelf_type`';
		$data = Database::sql2array($query, 'bookshelf_type');

		if (isset($data[Config::$shelfIdByNames['reading']]))
			$this->counters['polka_reading'] = max(0, (int) $data[Config::$shelfIdByNames['reading']]['cnt']);
		else
			$this->counters['polka_reading'] = 0;
		if (isset($data[Config::$shelfIdByNames['to-read']]))
			$this->counters['polka_to-read'] = max(0, (int) $data[Config::$shelfIdByNames['to-read']]['cnt']);
		else
			$this->counters['polka_to-read'] = 0;
		if (isset($data[Config::$shelfIdByNames['read']]))
			$this->counters['polka_read'] = max(0, (int) $data[Config::$shelfIdByNames['read']]['cnt']);
		else
			$this->counters['polka_read'] = 0;
		$this->parseCounters();
		$this->save();
	}

	function getCounter($name) {
		$this->unparseCounters();
		return isset($this->counters[$name]) ? (int) $this->counters[$name] : 0;
	}

	function unparseCounters() {
		$this->load();
		if ($this->counters_parsed)
			return;
		$cs = $this->getProperty('counters', '');
		$csp = explode(',', $cs);
		$i = 0;
		foreach ($this->counters as $name => &$value) {
			$value = isset($csp[$i]) ? max(0, (int) $csp[$i]) : 0;
			$i++;
		}
		$this->counters_parsed = true;
	}

	function parseCounters() {
		$this->load();
		$cs = array();
		foreach ($this->counters as $name => &$value) {
			$cs[] = max(0, (int) $value);
		}
		$this->setProperty('counters', implode(',', $cs));
	}

	function getNotifyRules() {
		return $this->userNotify->getAllUserRules();
	}

	function canNotify($rule, $type) {
		return $this->userNotify->can($rule, $type);
	}

	function setNotifyRule($rule, $type, $on = true) {
		return $this->userNotify->setPermission($rule, $type, $on);
	}

	function __construct($id = false, $data = false) {
		$this->loaded = false;

		if ($id && !is_numeric($id)) {
			$query = 'SELECT `id` FROM `users` WHERE `nickname`=' . Database::escape($id);
			$id = (int) Database::sql2single($query);
		}
		if ($id) {
			$this->id = max(0, $id);
		}
		if ($data)
			$this->load($data);
		$this->userNotify = new UserNotify($this);
	}

	function can($action, $target_user = false) {
		return AccessRules::can($this, $action, $target_user);
	}

	function can_throw($action, $target_user = false) {
		return AccessRules::can($this, $action, $target_user, $throwError = true);
	}

	function checkRights($right_name) {
		switch ($right_name) {
			// todo for check rights
		}
	}

	function getDownloadCount($book_id = 0) {
		$today_count = 0;
		if ($this->getRole() == User::ROLE_READER_CONFIRMED) {
			// считаем все книги, которые юзер скачал, кроме книг в ocr которых он в аллее славы
			$today_count = Database::sql2single('SELECT COUNT(1) FROM `stat_user_download` WHERE `id_user`=' . $this->id . ' AND `id_book`!=' . $book_id . ' AND `time`>' . (time() - 24 * 60 * 60));
		} else {
			// считаем все скаченные книги
			$today_count = Database::sql2single('SELECT COUNT(1) FROM `stat_user_download` WHERE `id_user`=' . $this->id . ' AND `id_book`!=' . $book_id . '  AND `time`>' . (time() - 24 * 60 * 60));
		}
		return $today_count;
	}

	function getDownloadLimit() {
		$vandal_max_count = Config::need('limit_download_vandal', 5);
		$biber_max_count = Config::need('limit_download_biber', 50);
		$admin_max_count = Config::need('limit_download_admin', 10000);
		$reader_max_count = Config::need('limit_download_reader', 10);
		/**
		 * 1. Обычный читатель не может качать больше 10 разных книг в сутки (не считаются книги, куда юзер залил неоткаченный бибером файл или стоит в аллее славы)
		 * 2. Вандал (с оплаченным абонементом) – 5 книг в сутки
		 * 3. Бибер (кроме созданных и залитых им самим книг) – 50 книг в сутки.
		 * 4. Админ – без ограничений.
		 */
		$max_count = 0;

		// vandal
		if ($this->getRole() == User::ROLE_VANDAL) {
			if ($this->isSubscriptionEnabled())
				$max_count = $vandal_max_count;
		}
		// reader
		if ($this->getRole() == User::ROLE_READER_CONFIRMED) {
			if ($this->isSubscriptionEnabled())
				$max_count = $reader_max_count;
		}
		// biber
		if ($this->getRole() == User::ROLE_BIBER) {
			$max_count = $biber_max_count;
		}
		//  admin
		if ($this->getRole() >= User::ROLE_SITE_ADMIN) {
			$max_count = $admin_max_count;
		}
		return $max_count;
	}

	function itsMyFile($id_file) {
		$query = 'SELECT `id_file_author` FROM `book_files` WHERE `id`=' . $id_file;
		return $this->id == Database::sql2single($query);
	}

	function canBookDownload($book_id, $file_id) {
		/**
		 * 1. Обычный читатель не может качать больше 10 разных книг в сутки (не считаются книги, куда юзер залил неоткаченный бибером файл или стоит в аллее славы)
		 * 2. Вандал (с оплаченным абонементом) – 5 книг в сутки
		 * 3. Бибер (кроме созданных и залитых им самим книг) – 50 книг в сутки.
		 * 4. Админ – без ограничений.
		 */
		if ($this->itsMyFile($file_id) && $this->getRole() > User::ROLE_VANDAL)
			return true;

		$max_count = $this->getDownloadLimit();
		// how much books user have download today?
		$today_count = $this->getDownloadCount($book_id);
		if ($today_count + 1 <= $max_count)
			return true;

		return array($today_count, $max_count);
	}

	/**
	 *
	 * @param int $book_id
	 */
	function onBookDownload($book_id) {
		// проверяем, не в аллее славы ли скачивающий книгу
		$query = 'SELECT COUNT(1) FROM `ocr` WHERE `id_book`=' . $book_id . ' AND `id_user`=' . $this->id . ' AND `state`=' . Ocr::STATE_APPROVED;
		$is_in_ocr_approved = (int) Database::sql2single($query);
		if ($is_in_ocr_approved) {
			// we not calcing if user downloads their own book
		} else {
			$stat = new Statistics();
			$stat->saveUserDownloads($this->id, $book_id);
			$this->incDownloadCount();
		}
	}

	/**
	 * можно сменить ник?
	 * @return int 1 - можно, 2 - нельзя
	 */
	function checkNickChanging() {
		$this->load();
		$check = false;
		// даем менять раз в год
		$nickChangePeriod = 60 * 60 * 24 * 31 * 12;
		if (time() - $this->getProperty('nickModifyTime', 0) > $nickChangePeriod) {
			// прошло времени больше, чем требуется для повторной смены ника
			$check = true;
		}
		return $check ? 1 : 0;
	}

	/**
	 * РАБОТА С ПОДПИСКОЙ
	 */

	/**
	 * получаем текущее количество поинтов юзера
	 * @return type
	 */
	function getPoints() {
		$this->load();
		return $this->getProperty('points', 0);
	}

	/**
	 * отнимаем поинты
	 * @param type $sum
	 */
	function decPoints($sum) {
		$this->load();
		if ((int) $sum < 1)
			throw new Exception('Cant decrease points to #' . $sum);
		$sum = min(100000, (int) $sum);

		$old = $this->getProperty('points', 0);
		if ($old < $sum) {
			// уходим в минус
		}

		$new = (int) ($old - $sum);

		$this->setProperty('points', $new);
		$this->save();
	}

	/**
	 * добавляем поинтов
	 * @param type $sum
	 */
	function addPoints($sum) {
		$this->load();
		if ((int) $sum < 1)
			throw new Exception('Cant increase points to #' . $sum);
		$sum = min(100000, (int) $sum);
		$old = $this->getProperty('points', 0);
		$new = $old + $sum;
		$this->setProperty('points', $new);
		$this->save();
	}

	/**
	 * выставляем поинтов
	 * @param type $sum
	 */
	function setPoints($sum) {
		$this->load();
		if ((int) $sum < 0)
			throw new Exception('Cant set points to #' . $sum);
		$sum = min(1000000, (int) $sum);
		$this->setProperty('points', $sum);
		$this->save();
	}

	/**
	 * Выдаем юзеру 1 оффер c количеством дней, на которые ему хватает поинтов для последующего заюзывания
	 */
	function makeSubscriptionOffer() {
		$this->load();
		$days = 0;
		// забираем список текущих отгруженных подписок
		$haved = $this->getSubscriptions();
		// вычисляем количество поинтов по всем подпискам
		foreach ($haved as $haved_sub) {
			// по текущим смотрим, сколько дней в сумме они стоят
			$days += $haved_sub['days'];
		}
		// делим очки на цену 1 дня
		$old_points = $this->getPoints();
		$daycost = Config::need('subscription_day_cost', 20);

		$additional_days = floor($old_points / $daycost);
		$left_points = $old_points - $daycost * $additional_days;
		if ($additional_days) {
			// очков хватает ещё на день или больше
			$query = 'INSERT INTO `users_subscriptions` SET `id_user`=' . $this->id . ', `update_time`=' . time() . ',
				`days` = ' . ($days + $additional_days) . ' ON DUPLICATE KEY UPDATE `update_time`=' . time() . ', `days`=' . ($days + $additional_days);
			Database::query($query);
			// отбираем поинты
			$this->setPoints($left_points);
		}
	}

	/**
	 * выдаем поинты за действие
	 * @param type $action
	 */
	function gainActionPoints($action, $id_target, $target_type) {
		if (!$id_target || !$target_type)
			throw new Exception('no target for gainActionPoints');
		if (!isset(Config::$points[$action]))
			throw new Exception('action #' . $action . ' is not in reward list');
		$points = Config::$points[$action]['points'];
		// сохраняем для истории
		$query = 'INSERT INTO `users_points_history` SET
			`id_action`=' . Config::$points[$action]['id'] . ',
			`id_user`=' . $this->id . ',
			`id_target`=' . $id_target . ',
			`target_type`=' . $target_type . ',
			`points`=' . $points . ',
			`time`=' . time() . '
				ON DUPLICATE KEY UPDATE
			`time`=`time`';
		Database::query($query);
		if (Database::lastInsertId()) {
			// добавляем поинты
			$this->addPoints($points);
			// перерасчитываем подарки
			$this->makeSubscriptionOffer();
			return true;
		} else {
			// already got points for this action. do nothing
			return false;
		}
	}

	/**
	 * удаляем отгруженную подписку юзера
	 * @param type $id
	 */
	function delSubscription($id) {
		$query = 'DELETE FROM `users_subscriptions` WHERE `id_user`=' . $this->id . ' LIMIT 1';
		Database::query($query);
	}

	/**
	 *
	 * @param type $id используем подписку
	 */
	function useSubscription() {
		$this->load();
		$days = 0;
		$subs = $this->getSubscriptions();
		if (count($subs)) {
			foreach ($subs as $subscription) {
				$days = $subscription['days'];
			}
		}
		if ($days) {
			$subscription_end = max(time(), $this->getSubscriptionEnd()) + $days * 24 * 60 * 60 + 5; // 5 seconds from php to humanity with love
			$this->setProperty('subscriptionEnd', $subscription_end);
			$this->save();
			$this->delSubscription();
			return $subscription_end;
		}else
			throw new Exception('not enough days for prolong');
	}

	/**
	 * отдаем список отгруженных подарков - подписок юзера
	 */
	function getSubscriptions() {
		$this->load();
		$query = 'SELECT * FROM `users_subscriptions` WHERE `id_user`=' . $this->id;
		return Database::sql2array($query);
	}

	/**
	 * когда заканчивается подписка?
	 */
	function getSubscriptionEnd() {
		$this->load();
		return (int) $this->getProperty('subscriptionEnd');
	}

	/**
	 * проверяем, включена ли подписка
	 */
	function isSubscriptionEnabled() {
		$this->load();
		if ($this->getSubscriptionEnd() < time())
			return false;
		return true;
	}

	/**
	 * // КОНЕЦ РАБОТЫ С ПОДПИСКОЙ
	 */

	/**
	 *
	 * @return type
	 */
	function loadLoved() {
		if ($this->lovedLoaded)
			return true;
		$this->loved = array();
		$this->lovedLoaded = true;
		$query = 'SELECT * FROM `users_loved` WHERE `id_user`=' . $this->id;
		$res = Database::sql2array($query);
		foreach ($res as $row) {
			$this->loved[$row['target_type']][$row['id_target']] = $row['id_target'];
		}
	}

	function getLoved($type) {
		if (!$this->lovedLoaded) {
			$this->loadLoved();
		}
		return isset($this->loved[(int) $type]) ? $this->loved[(int) $type] : array();
	}

	function incDownloadCount($inc = 1) {
		$inc = max(0, (int) $inc);
		$value = $this->getProperty('totalDownloadCount', 0) + $inc;
		$this->setProperty('totalDownloadCount', $value);
		return true;
	}

	function getListData($full = false) {
		$out = array(
		    'id' => $this->id,
		    'picture' => $this->getAvatar(),
		    'nickname' => $this->getNickName(),
		    'lastSave' => $this->profile['lastSave'],
		    'path' => $this->getUrl(),
		    'role' => $this->getRole(),
		    'totalDownloadCount' => (int) $this->profile['totalDownloadCount'],
		);
		if ($full) {
			$out['lastIp'] = $this->profile['lastIp'];
			$out['subscriptionEnd'] = $this->profile['subscriptionEnd'] > time() ? date('Y/m/d H:i:s', $this->profile['subscriptionEnd']) : 'отключена';
			$out['regIp'] = $this->profile['regIp'];
			$out['regTime'] = date('Y/m/d H:i:s', $this->profile['regTime']);
			$out['email'] = $this->profile['email'];
			$out['lastLogin'] = date('Y/m/d H:i:s', $this->profile['lastLogin']);
		}
		return $out;
	}

	function getUrl() {
		return Config::need('www_path') . '/user/' . $this->id;
	}

	function checkInBookshelf($_id_book) {
		$shelf = $this->getBookShelf();
		foreach ($shelf as $shelf_id => $data) {
			foreach ($data as $id_book => $data) {
				if ($id_book == $_id_book)
					return $shelf_id;
			}
		}
		return false;
	}

	function getBookShelf() {
		if ($this->shelfLoaded)
			return $this->shelf;
		$query = 'SELECT * FROM `users_bookshelf` WHERE `id_user`=' . $this->id;
		$array = Database::sql2array($query);
		$out = array();
		foreach ($array as $row) {
			$out[$row['bookshelf_type']][$row['id_book']] = $row;
		}
		$this->shelfLoaded = true;
		$this->shelf = $out;
		return $this->shelf;
	}

	function AddBookShelf($id_book, $id_shelf) {
		$id_book = max(0, (int) $id_book);
		$id_shelf = max(0, (int) $id_shelf);
		$time = time();
		$query = 'INSERT INTO `users_bookshelf` SET `id_user`=' . $this->id . ',`id_book`=' . $id_book . ', `bookshelf_type`=' . $id_shelf . ', `add_time`=' . $time . '
			ON DUPLICATE KEY UPDATE `id_book`=' . $id_book . ', `bookshelf_type`=' . $id_shelf . ', `add_time`=' . $time . '';
		Database::query($query);
		$this->shelf[$id_shelf][$id_book] = array(
		    'id_user' => $this->id,
		    'id_book' => $id_book,
		    'bookshelf_type' => $id_shelf,
		    'add_time' => $time
		);
		$event = new Event();
		$event->event_addShelf($this->id, $id_book, $id_shelf);
		$event->push();
	}

	// кто меня читает
	function setFollowers(array $array) {
		$this->loadAdditional();
		$this->changedAdditional['followers'] = $this->profileAdditional['followers'] = $array;
	}

	// кого я читаю
	function setFollowing(array $array) {
		$this->loadAdditional();
		$this->changedAdditional['following'] = $this->profileAdditional['following'] = $array;
	}

	// вернуть тех, кого я читаю
	function getFollowing() {
		$this->loadAdditional();
		return isset($this->profileAdditional['following']) ? $this->profileAdditional['following'] : array();
	}

	// вернуть всех, кто меня читает
	function getFollowers() {
		$this->loadAdditional();
		return isset($this->profileAdditional['followers']) ? $this->profileAdditional['followers'] : array();
	}

	// когда юзера зафрендили
	function onNewFollower($followed_by_id) {
		Notify::notifyEventAddFriend($this->id, $followed_by_id);
	}

	// когда юзер зафрендил кого-либо
	function onNewFollowing($i_now_follow_id) {
		// все друзья кроме свежедобавленного должны узнать об этом!
		$event = new Event();
		$event->event_FollowingAdd($this->id, $i_now_follow_id);
		$event->push(array($i_now_follow_id));
		// а я получаю всю ленту свежедобавленного друга (последние 50 эвентов хотя бы) к себе на стену
		$wall = MongoDatabase::getUserWall($i_now_follow_id, 0, 50, 'self');
		foreach ($wall as $wallItem) {
			if (isset($wallItem['_id']))
				MongoDatabase::pushEvents($i_now_follow_id, array($this->id), (string) $wallItem['id'], $wallItem['time']);
		}
	}

	/**
	 * меня удалили из друзей
	 * @param type $id_friend_delete_me
	 */
	public function onDeletedFromFriend($id_friend_delete_me) {
		
	}

	/**
	 * я удалил из друзей
	 * @param type $id_deleted_friend
	 */
	public function onDeleteFriend($id_deleted_friend) {
		// и удаляю все записи этого друга у себя со стены
		MongoDatabase::deleteWallItemsByOwnerId($this->id, $id_deleted_friend);
	}

	public function getTheme() {
		return Config::need('default_theme');
	}

	public function getNickName() {
		$this->load();
		return $this->getProperty('nickname');
	}

	public function getAvatar() {
		$this->load();
		$pic = $this->getProperty('picture') ? $this->id . '.jpg' : 'default.jpg';
		return Config::need('www_path') . '/static/upload/avatars/' . $pic;
	}

	public function getLanguage() {
		return Config::need('default_language');
	}

	private function onRegister() {
		// а не по партнерке ли регистрация?
		Statistics::saveUserPartnerRegister();
	}

	function register($nickname, $email, $password) {
		Database::query('START TRANSACTION');
		$hash = md5($email . $nickname . $password . time());
		$query = 'INSERT INTO `users` SET
			`email`=\'' . $email . '\',
			`password`=\'' . md5($password) . '\',
			`nickname`=\'' . $nickname . '\',
			`regTime`=' . time() . ',
			`role`=\'' . User::ROLE_READER_UNCONFIRMED . '\',
			`regIp`=' . Database::escape(Request::$ip) . ',
			`hash` = \'' . $hash . '\'';
		if (Database::query($query)) {
			$this->id = Database::lastInsertId();
			if ($this->id) {
				$this->onRegister();
				Database::query('COMMIT');
				return $hash;
			}
		}
		Database::query('COMMIT');
		return false;
	}

	// отправляем в xml информацию о пользователе
	public function setXMLAttibute($field, $value) {
		if (in_array($field, $this->xml_fields))
			$this->profile_xml[$field] = $value;
	}

	// отдаем информацию по пользователю для отображения в xml
	public function getXMLInfo() {
		$this->load();
		$out = $this->profile_xml;
		return $out;
	}

	// грузим дополнительню информацию
	public function loadAdditional($rowData = false) {
		if ($this->loadedAdditional)
			return true;
		$this->loadedAdditional = true;
		$this->profileAdditional = MongoDatabase::getUserAttributes($this->id);
		return;
	}

	// грузим информацию по пользователю
	public function load($rowData = false) {
		if ($this->loaded)
			return true;
		if (!$rowData) {
			if (!$this->id) {
				$this->setXMLAttibute('auth', 0);
			} else {
				if ($cachedUser = Users::getFromCache($this->id)) {
					$this->profile = $cachedUser->profile;
					foreach ($this->profile as $field => $value) {
						$this->setXMLAttibute($field, $value);
					}
					$this->profileAdditional = $cachedUser->profileAdditional;
					$this->loaded = true;
					return;
				} else {
					$rowData = Database::sql2row('SELECT * FROM `users` WHERE `id`=' . $this->id);
				}
			}
		}
		if (!$rowData) {
			// нет юзера в базе
			throw new Exception('Такого пользователя #' . $this->id . ' не существует', Error::E_USER_NOT_FOUND);
		}

		$this->id = (int) $rowData['id'];

		foreach ($rowData as $field => $value) {
			if ($field == 'serialized') {
				$arr = json_decode($value, true);
				if (is_array($arr))
					foreach ($arr as $field => $value) {
						$this->setPropertySerialized($field, $value, $save = false);
						$this->setXMLAttibute($field, $value);
					}
			}
			// все данные в profile
			$this->setProperty($field, $value, $save = false);
			// данные для xml - в xml
			$this->setXMLAttibute($field, $value);
		}
		Users::add($this);
		$this->loaded = true;
		Users::putInCache($this->id);
		return;
	}

	public function setRole($role) {
		$this->setProperty('role', $role);
		$this->setProperty('hash', '');
	}

	public function getRole() {
		return (int) $this->getProperty('role', User::ROLE_ANON);
	}

	public function getBdayString($default = 'неизвестно') {
		if ($this->getProperty('bday')) {
			
		} else {
			return $default;
		}
	}

	public function getBday($default = 0, $format = 'Y-m-d') {
		return date($format, (int) $this->getProperty('bday', $default));
	}

	public function getRoleName($id = false) {
		if (!$id)
			$id = $this->getRole();
		return isset(Users::$rolenames[$id]) ? Users::$rolenames[$id] : User::ROLE_READER_UNCONFIRMED;
	}

	public function setPropertySerialized($field, $value, $save = true) {
		$this->loadAdditional();
		if (!$save)
			$this->profileAdditional[$field] = $value;
		else
			$this->profileAdditional[$field] = $this->changedAdditional[$field] = $value;
	}

	public function setProperty($field, $value, $save = true) {
		if (!$save)
			$this->profile[$field] = $value;
		else
			$this->profile[$field] = $this->changed[$field] = $value;
	}

	public function getProperty($field, $default = false) {
		$this->load();
		return isset($this->profile[$field]) ? $this->profile[$field] : $default;
	}

	public function getPropertySerialized($field, $default = false) {
		$this->loadAdditional();
		return isset($this->profileAdditional[$field]) ? $this->profileAdditional[$field] : $default;
	}

	function __destruct() {
		
	}

	function save() {
		// дополнительные поля
		if (count($this->changedAdditional) && $this->id) {
			MongoDatabase::setUserAttributes($this->id, $this->changedAdditional);
		}
		// основные поля
		if (count($this->changed) && $this->id) {
			$this->changed['lastSave'] = time();
			foreach ($this->changed as $f => $v)
				$sqlparts[] = '`' . $f . '`=\'' . mysql_escape_string($v) . '\'';
			$sqlparts = implode(',', $sqlparts);
			$query = 'INSERT INTO `users` SET `id`=' . $this->id . ',' . $sqlparts . ' ON DUPLICATE KEY UPDATE ' . $sqlparts;
			Database::query($query);
		}
		Users::dropCache($this->id);
	}

}