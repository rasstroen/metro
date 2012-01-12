<?php

class Series {

	private static $series = array();

	public static function getById($id, $data = false) {
		if (!is_numeric($id))
			throw new Exception($id . ' illegal book id');
		if (!isset(self::$persons[(int) $id])) {
			$tmp = new Person($id, $data);
			self::$persons[$tmp->id] = $tmp;
			unset($tmp);
		}
		return self::$persons[$id];
	}

	public static function getByIdLoaded($id) {
		$tmp = self::getByIdsLoaded(array($id));
		return isset($tmp[$id]) ? $tmp[$id] : false;
	}

	public static function getByIdsLoaded($ids) {
		$out = array();
		$tofetch = array();
		if (is_array($ids)) {
			foreach ($ids as $uid) {
				if (!isset(self::$persons[(int) $uid])) {
					$tofetch[] = $uid;
				}
			}
			if (count($tofetch)) {
				$query = 'SELECT * FROM `persons` WHERE `id` IN (' . implode(',', $tofetch) . ')';
				$data = Database::sql2array($query, 'id');
			}
			foreach ($ids as $id) {
				if (!isset(self::$persons[(int) $id])) {
					if (isset($data[$id])) {
						$tmp = new Person($id, $data[$id]);
						self::$persons[$tmp->id] = $tmp;
						unset($tmp);
					} else {
						//self::$persons[$id] = new Person($id); // todo
					}
				}
			}

			foreach ($ids as $id) {
				if (isset(self::$persons[$id]))
					$out[$id] = self::$persons[$id];
			}
		}
		return $out;
	}

}