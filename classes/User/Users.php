<?php

class Users {

	public static $rolenames = array(
	    User::ROLE_ANON => 'Аноним',
	    User::ROLE_READER_UNCONFIRMED => 'Неподтвержденный читатель',
	    User::ROLE_VANDAL => 'Вандал',
	    User::ROLE_READER_CONFIRMED => 'Читатель',
	    User::ROLE_BIBER => 'Библиотекарь',
	    User::ROLE_SITE_ADMIN => 'Администратор сайта',
	);
	private static $users = array();
	private static $from_cache = array();
	private static $user_profile_cache_time = 60; // храним профиль любого юзера в кеше столько секунд

	public static function getById($id, $data = false) {
		if (!is_numeric($id))
			throw new Exception($id . ' illegal user id');
		if (!isset(self::$users[(int) $id])) {
			$tmp = new User($id, $data);
			self::$users[$tmp->id] = $tmp;
			unset($tmp);
		}
		return self::$users[$id];
	}

	public static function putInCache($id) {
		if (isset(self::$from_cache[$id]))
			return false;
		if (isset(self::$users[$id])) {
			if (self::$users[$id]->loaded) {
				if(self::$user_profile_cache_time)
				Cache::set('user_' . $id, self::$users[$id]->profile, self::$user_profile_cache_time);
				return true;
			}
		}
		return false;
	}

	public static function dropCache($id) {
		Cache::drop('user_' . $id);
	}

	public static function getFromCache($id) {
		if (isset(self::$users[$id])) {
			if (self::$users[$id]->loaded === true) {
				return self::$users[$id];
			}
		}
		if ($data = Cache::get('user_' . $id)) {
			self::$from_cache[$id] = true;
			$tmp = new User($id, $data);
			self::$users[$tmp->id] = $tmp;
			unset($tmp);
			return self::$users[$id];
		}
		return false;
	}

	public static function add(User $user) {
		self::$users[$user->id] = $user;
	}

	public static function getByIdsLoaded($ids) {
		$out = array();
		$tofetch = array();
		if (is_array($ids)) {
			foreach ($ids as $uid) {
				$uid = (int) $uid;
				if (!isset(self::$users[$uid])) {
					if (!self::getFromCache($uid))
						$tofetch[] = $uid;
				}
			}
			if (count($tofetch)) {
				$query = 'SELECT * FROM `users` WHERE `id` IN (' . implode(',', $tofetch) . ')';
				$data = Database::sql2array($query, 'id');
			}
			foreach ($ids as $uid) {
				if (!isset(self::$users[(int) $uid])) {
					if (isset($data[(int)$uid])) {
						$tmp = new User($uid, $data[$uid]);
						self::$users[$tmp->id] = $tmp;
						self::putInCache($tmp->id);
						unset($tmp);
					}
				}
			}

			foreach ($ids as $uid) {
				if (isset(self::$users[$uid]))
					$out[$uid] = self::$users[$uid];
			}
		}
		return $out;
	}

}