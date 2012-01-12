<?php

class PersonRelations extends Relations {

	public static function addRelation($id1, $id2, $relation_type) {
		if (!isset(self::$relation_types[$relation_type]))
			throw new Exception('no such relation type #' . $relation_type);
		switch ($relation_type) {
			case self::RELATION_TYPE_TRANSLATE:case self::RELATION_TYPE_EDITION:
				return self::setEdition($id1, $id2);
				break;
			case self::RELATION_TYPE_DUPLICATE:
				return self::addDuplicate($id1, $id2);
				break;
		}
	}

	public static function getPersonRelations($id) {
		$tmp = array();
		if (isset(self::$cached_relations[$id])) {
			return self::$cached_relations[$id];
		} else {
			$person = Persons::getInstance()->getByIdLoaded($id);
			/* @var $person Person */
			$tmp = array();
			if ($bid = $person->getBasketId()) {
				$query = 'SELECT * FROM `person_basket` WHERE `id_basket`=' . $bid;
				$tmp = Database::sql2array($query, 'id_person');
			}
		}
		self::$cached_relations[$id] = $tmp;
		return $tmp;
	}

	private static function addDuplicate($id1, $id2) {
		global $current_user;
		$person_main = Persons::getInstance()->getByIdLoaded($id1);
		$person_duplicate = Persons::getInstance()->getByIdLoaded($id2);
		/* @var $person_main Person */
		/* @var $person_duplicate Person */
		if ($did = $person_main->getDuplicateId()) {
			self::setError('#' . $person_main->id . ' не может быть отредактирован - является дупликатом #' . $did);
			return false;
		}
		$id_duplicated = $person_main->getDuplicateId();
		if (!$id_duplicated) {
			$id_duplicated = $person_main->id;
		}

		if (!$id_duplicated) {
			$this->setError('no main person for duplicate');
			return false;
		}
		Persons::getInstance()->dropCache($person_main->id);
		Persons::getInstance()->dropCache($person_duplicate->id);
		PersonLog::addLog(array('is_p_duplicate' => $person_main->id), array('is_p_duplicate' => 0), $person_duplicate->id);
		PersonLog::saveLog($person_duplicate->id, BookLog::TargetType_person, $current_user->id, PersonLog::BiberLogType_personSetDuplicate);
		$query = 'UPDATE `persons` SET `is_p_duplicate`=' . $id_duplicated . ' WHERE `id`=' . $person_duplicate->id;
		Database::query($query);
		return true;
	}

	private static function delDuplicate($parent_id, $duplicated_id) {
		global $current_user;
		$person1 = Persons::getInstance()->getByIdLoaded($parent_id);
		$person2 = Persons::getInstance()->getByIdLoaded($duplicated_id);
		/* @var $person1 Person */
		/* @var $person2 Person */
		$query = 'UPDATE `persons` SET `is_p_duplicate`=0 WHERE `id`=' . $duplicated_id;
		Database::query($query);
		PersonLog::addLog(array('is_p_duplicate' => 0), array('is_p_duplicate' => $parent_id), $duplicated_id);
		PersonLog::saveLog($duplicated_id, BookLog::TargetType_person, $current_user->id, BiberLog::BiberLogType_personSetNoDuplicate);
		Persons::getInstance()->dropCache($parent_id);
		Persons::getInstance()->dropCache($duplicated_id);
		return true;
	}

	public static function delRelation($id1, $id2) {
		$person1 = Persons::getInstance()->getByIdLoaded($id1);
		$person2 = Persons::getInstance()->getByIdLoaded($id2);
		/* @var $person1 Person */
		/* @var $person2 Person */
		if ($person1->getDuplicateId() == $person2->id) {
			return self::delDuplicate($id2, $id1);
		} else if ($person2->getDuplicateId() == $person1->id) {
			return self::delDuplicate($id1, $id2);
		}else
			return self::delEdition($id1, $id2);
	}

	private static function delEdition($id1, $id2) {
		global $current_user;
		$person1 = Persons::getInstance()->getByIdLoaded($id1);
		$person2 = Persons::getInstance()->getByIdLoaded($id2);
		/* @var $person1 Person */
		/* @var $person2 Person */
		if (!$person1->getBasketId() || ($person1->getBasketId() != $person2->getBasketId())) {
			self::setError('Авторы никак не связаны!');
			return false;
		}
		try {
			$query = 'UPDATE `persons` SET `id_basket`=0 WHERE `id`=' . $person2->id;
			Database::query($query);
			$query = 'DELETE FROM `person_basket` WHERE `id_person`=' . $person2->id;
			Database::query($query);

			$query = 'SELECT * FROM `person_basket` WHERE `id_basket`=' . $person1->getBasketId();
			$relations = Database::sql2array($query);
			$to_old = array();
			foreach ($relations as $relation) {
				$to_old[$relation['id_person']] = $relation['id_person'];
			}

			$to_change[$person2->id] = $person2->id;

			$to_old[$person2->id] = $person2->id;

			$now = array('id_basket' => 0, 'deleted_relations' => $to_change, 'old_relations' => $to_old);
			$was = array('id_basket' => $person1->getBasketId(), 'deleted_relations' => array(), 'old_relations' => array());
			PersonLog::addLog($now, $was, $person2->id);
			PersonLog::saveLog(array_merge(array($person2->id), $to_old), BookLog::TargetType_person, $current_user->id, BiberLog::BiberLogType_personDelRelation);
			Persons::getInstance()->dropCache($person2->id);
			Persons::getInstance()->dropCache($person1->id);
		} catch (Exception $e) {
			self::setError($e->getMessage());
			return false;
		}
		return true;
	}

	private static function setEdition($id1, $id2) {
		global $current_user;
		$type = self::RELATION_TYPE_EDITION;
		try {
			$person1 = Persons::getInstance()->getByIdLoaded($id1);
			$person2 = Persons::getInstance()->getByIdLoaded($id2);
			/* @var $person1 Person */
			/* @var $person2 Person */
			if ($person2->getLangId() != Config::$langs['ru']) {
				$type = self::RELATION_TYPE_TRANSLATE;
			}
			Database::query('START TRANSACTION');
			if ($id1 == $id2) {
				self::setError('Онанизмъ!');
				return false;
			}
			if ($person1->getBasketId() && ($person1->getBasketId() == $person2->getBasketId())) {
				self::setError('Книги уже связаны!');
				return false;
			}
			// смотрим книги.
			$basket_id = max($person1->getBasketId(), $person2->getBasketId());
			$basket_old = 0;
			if (!$basket_id) {
				$query = 'INSERT INTO `pbasket` SET `time`=' . time();
				Database::query($query);
				$basket_id = Database::lastInsertId();
			}
			$to_change = array();
			$to_old = array();
			if ($basket_id == $person1->getBasketId()) {
				if ($person2->getBasketId()) {
					$basket_old = $person2->getBasketId();
					// из 2 корзины всё в первую
					$query = 'SELECT * FROM `person_basket` WHERE `id_basket`=' . $person2->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_change[$relation['id_person']] = $relation['id_person'];
					}

					$query = 'SELECT * FROM `person_basket` WHERE `id_basket`=' . $person1->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_old[$relation['id_person']] = $relation['id_person'];
					}

					$query = 'UPDATE `person_basket` SET `id_basket`=' . $basket_id . ' WHERE `id_basket`=' . $person2->getBasketId();
					Database::query($query);
					$query = 'UPDATE `persons` SET `id_basket`=' . $basket_id . ' WHERE `id_basket`=' . $person2->getBasketId();
					Database::query($query);
				} else {
					$query = 'SELECT * FROM `person_basket` WHERE `id_basket`=' . $person1->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_old[$relation['id_person']] = $relation['id_person'];
					}
				}
			} else
			if ($basket_id == $person2->getBasketId()) {
				if ($person1->getBasketId()) {
					$basket_old = $person1->getBasketId();
					// из 1 корзины всё во вторую
					$query = 'SELECT * FROM `person_basket` WHERE `id_basket`=' . $person1->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_change[$relation['id_person']] = $relation['id_person'];
					}

					$query = 'SELECT * FROM `person_basket` WHERE `id_basket`=' . $person2->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_old[$relation['id_person']] = $relation['id_person'];
					}

					$query = 'UPDATE `person_basket` SET `id_basket`=' . $basket_id . ' WHERE `id_basket`=' . $person1->getBasketId();
					Database::query($query);
					$query = 'UPDATE `persons` SET `id_basket`=' . $basket_id . ' WHERE `id_basket`=' . $person1->getBasketId();
					Database::query($query);
				} else {
					$query = 'SELECT * FROM `person_basket` WHERE `id_basket`=' . $person2->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_old[$relation['id_person']] = $relation['id_person'];
					}
				}
			}
			// 2 книги в корзинку
			if ($person1->getBasketId() != $basket_id) {
				$to_change[$person1->id] = $person1->id;
			}
			if ($person2->getBasketId() != $basket_id) {
				$to_change[$person2->id] = $person2->id;
			}
			$query = 'UPDATE `persons` SET `id_basket`=' . $basket_id . ' WHERE `id` IN (' . $person1->id . ',' . $person2->id . ')';
			Database::query($query);

			// сохраняем в лог создание релейшна для книг, у которых поменялся id_basket
			if (count($to_change)) {
				$now = array('id_basket' => $basket_id, 'new_relations' => $to_change, 'old_relations' => $to_old);
				$was = array('id_basket' => $basket_old, 'new_relations' => array(), 'old_relations' => array());
				PersonLog::addLog($now, $was, $person1->id);
				$ids = array_merge($to_change, $to_old);
				PersonLog::saveLog($ids, PersonLog::TargetType_person, $current_user->id, BiberLog::BiberLogType_personAddRelation);
			}
			foreach ($ids as $id)
				Persons::getInstance()->dropCache($id);
			
			$query = 'REPLACE INTO `person_basket`(`id_person`,`id_basket`) VALUES (' . $person1->id . ',' . $basket_id . '),(' . $person2->id . ',' . $basket_id . ')';
			Database::query($query);
			Database::query('COMMIT');
		} catch (Exception $e) {
			self::setError($e->getMessage());
			return false;
		}
		return true;
	}

}