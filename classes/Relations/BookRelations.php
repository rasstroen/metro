<?php

class BookRelations extends Relations {

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

	public static function getBookRelations($id) {
		$tmp = array();
		if (isset(self::$cached_relations[$id])) {
			return self::$cached_relations[$id];
		} else {
			$book = Books::getInstance()->getByIdLoaded($id);
			/* @var $book Book */
			$tmp = array();
			if ($bid = $book->getBasketId()) {
				$query = 'SELECT * FROM `book_basket` WHERE `id_basket`=' . $bid;
				$tmp = Database::sql2array($query, 'id_book');
			}
		}
		self::$cached_relations[$id] = $tmp;
		return $tmp;
	}

	private static function addDuplicate($id1, $id2) {
		global $current_user;
		$book_main = Books::getInstance()->getByIdLoaded($id1); // эта книга главная
		$book_duplicate = Books::getInstance()->getByIdLoaded($id2); // это дубликат

		if ($id1 == $id2) {
			self::setError('Онанизм');
			return;
		}
		/* @var $book1 Book */
		/* @var $book2 Book */
		if ($did = $book_duplicate->getDuplicateId()) {
			self::setError('#' . $book1->id . ' не может быть отредактирована - является дупликатом книги #' . $did);
			return false;
		}

		/* @var $book_main Book */
		/* @var $book_duplicate Book */
		$id_duplicated = $book_main->getDuplicateId();
		if (!$id_duplicated) {
			$id_duplicated = $book_main->id;
		}

		if (!$id_duplicated) {
			$this->setError('no main book for duplicate');
			return false;
		}
		BookLog::addLog(array('is_duplicate' => $book_main->id), array('is_duplicate' => 0), $book_duplicate->id);
		BookLog::saveLog($book_duplicate->id, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookSetDuplicate);
		$query = 'UPDATE `book` SET `is_duplicate`=' . $id_duplicated . ' WHERE `id`=' . $book_duplicate->id;
		Database::query($query);
		Books::getInstance()->dropCache($book_duplicate->id);
		Books::getInstance()->dropCache($book_main->id);
		return true;
	}

	private static function delDuplicate($parent_id, $duplicated_id) {
		global $current_user;
		$book1 = Books::getInstance()->getByIdLoaded($parent_id);
		$book2 = Books::getInstance()->getByIdLoaded($duplicated_id);
		/* @var $book1 Book */
		/* @var $book2 Book */
		$query = 'UPDATE `book` SET `is_duplicate`=0 WHERE `id`=' . $duplicated_id;
		Database::query($query);
		BookLog::addLog(array('is_duplicate' => 0), array('is_duplicate' => $parent_id), $duplicated_id);
		BookLog::saveLog($duplicated_id, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookSetNoDuplicate);
		Books::getInstance()->dropCache($book2->id);
		Books::getInstance()->dropCache($book1->id);
		return true;
	}

	public static function delRelation($id1, $id2) {
		$book1 = Books::getInstance()->getByIdLoaded($id1);
		$book2 = Books::getInstance()->getByIdLoaded($id2);
		/* @var $book1 Book */
		/* @var $book2 Book */
		if ($book1->getDuplicateId() == $book2->id) {
			return self::delDuplicate($id2, $id1);
		} else if ($book2->getDuplicateId() == $book1->id) {
			return self::delDuplicate($id1, $id2);
		}else
			return self::delEdition($id1, $id2);
	}

	private static function delEdition($id1, $id2) {
		global $current_user;
		$book1 = Books::getInstance()->getByIdLoaded($id1);
		$book2 = Books::getInstance()->getByIdLoaded($id2);
		/* @var $book1 Book */
		/* @var $book2 Book */
		if (!$book1->getBasketId() || ($book1->getBasketId() != $book2->getBasketId())) {
			self::setError('Книги никак не связаны!');
			return false;
		}
		try {
			$query = 'UPDATE `book` SET `id_basket`=0 WHERE `id`=' . $book2->id;
			Database::query($query);
			$query = 'DELETE FROM `book_basket` WHERE `id_book`=' . $book2->id;
			Database::query($query);

			$query = 'SELECT * FROM `book_basket` WHERE `id_basket`=' . $book1->getBasketId();
			$relations = Database::sql2array($query);
			$to_old = array();
			foreach ($relations as $relation) {
				$to_old[$relation['id_book']] = $relation['id_book'];
			}

			$to_change[$book2->id] = $book2->id;

			$to_old[$book2->id] = $book2->id;

			$now = array('id_basket' => 0, 'deleted_relations' => $to_change, 'old_relations' => $to_old);
			$was = array('id_basket' => $book1->getBasketId(), 'deleted_relations' => array(), 'old_relations' => array());
			BookLog::addLog($now, $was, $book2->id);
			BookLog::saveLog(array_merge(array($book2->id), $to_old), BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookDelRelation);
			Books::getInstance()->dropCache($book2->id);
			Books::getInstance()->dropCache($book1->id);
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
			$book1 = Books::getInstance()->getByIdLoaded($id1);
			$book2 = Books::getInstance()->getByIdLoaded($id2);
			/* @var $book1 Book */
			/* @var $book2 Book */
			if ($book2->getLangId() != Config::$langs['ru']) {
				//if ($book1->getLangId() != $book2->getLangId()) {
				$type = self::RELATION_TYPE_TRANSLATE;
			}

			Database::query('START TRANSACTION');
			if ($id1 == $id2) {
				self::setError('Онанизмъ!');
				return false;
			}
			if ($book1->getBasketId() && ($book1->getBasketId() == $book2->getBasketId())) {
				self::setError('Книги уже связаны!');
				return false;
			}
			// смотрим книги.
			$basket_id = max($book1->getBasketId(), $book2->getBasketId());
			$basket_old = 0;
			if (!$basket_id) {
				$query = 'INSERT INTO `basket` SET `time`=' . time();
				Database::query($query);
				$basket_id = Database::lastInsertId();
			}
			$to_change = array();
			$to_old = array();
			if ($basket_id == $book1->getBasketId()) {
				if ($book2->getBasketId()) {
					$basket_old = $book2->getBasketId();
					// из 2 корзины всё в первую
					$query = 'SELECT * FROM `book_basket` WHERE `id_basket`=' . $book2->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_change[$relation['id_book']] = $relation['id_book'];
					}

					$query = 'SELECT * FROM `book_basket` WHERE `id_basket`=' . $book1->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_old[$relation['id_book']] = $relation['id_book'];
					}


					$query = 'UPDATE `book_basket` SET `id_basket`=' . $basket_id . ' WHERE `id_basket`=' . $book2->getBasketId();
					Database::query($query);
					$query = 'UPDATE `book` SET `id_basket`=' . $basket_id . ' WHERE `id_basket`=' . $book2->getBasketId();
					Database::query($query);
				} else {
					$query = 'SELECT * FROM `book_basket` WHERE `id_basket`=' . $book1->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_old[$relation['id_book']] = $relation['id_book'];
					}
				}
			} else
			if ($basket_id == $book2->getBasketId()) {
				if ($book1->getBasketId()) {
					$basket_old = $book1->getBasketId();
					// из 1 корзины всё во вторую
					$query = 'SELECT * FROM `book_basket` WHERE `id_basket`=' . $book1->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_change[$relation['id_book']] = $relation['id_book'];
					}

					$query = 'SELECT * FROM `book_basket` WHERE `id_basket`=' . $book2->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_old[$relation['id_book']] = $relation['id_book'];
					}

					$query = 'UPDATE `book_basket` SET `id_basket`=' . $basket_id . ' WHERE `id_basket`=' . $book1->getBasketId();
					Database::query($query);
					$query = 'UPDATE `book` SET `id_basket`=' . $basket_id . ' WHERE `id_basket`=' . $book1->getBasketId();
					Database::query($query);
				} else {
					$query = 'SELECT * FROM `book_basket` WHERE `id_basket`=' . $book2->getBasketId();
					$relations = Database::sql2array($query);
					foreach ($relations as $relation) {
						$to_old[$relation['id_book']] = $relation['id_book'];
					}
				}
			}
			// 2 книги в корзинку
			if ($book1->getBasketId() != $basket_id) {
				$to_change[$book1->id] = $book1->id;
				//$to_old[$book2->id] = $book2->id;
			}
			if ($book2->getBasketId() != $basket_id) {
				$to_change[$book2->id] = $book2->id;
				//$to_old[$book1->id] = $book1->id;
			}
			$query = 'UPDATE `book` SET `id_basket`=' . $basket_id . ' WHERE `id` IN (' . $book1->id . ',' . $book2->id . ')';
			Database::query($query);

			// сохраняем в лог создание релейшна для книг, у которых поменялся id_basket
			if (count($to_change)) {
				$now = array('id_basket' => $basket_id, 'new_relations' => $to_change, 'old_relations' => $to_old);
				$was = array('id_basket' => $basket_old, 'new_relations' => array(), 'old_relations' => array());
				BookLog::addLog($now, $was, $book1->id);
				$ids = array_merge($to_change, $to_old);
				BookLog::saveLog($ids, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookAddRelation);
			}
			foreach ($ids as $id)
				Books::getInstance()->dropCache($id);

			$query = 'REPLACE INTO `book_basket`(`id_book`,`id_basket`) VALUES (' . $book1->id . ',' . $basket_id . '),(' . $book2->id . ',' . $basket_id . ')';
			Database::query($query);
			Database::query('COMMIT');
		} catch (Exception $e) {
			self::setError($e->getMessage());
			return false;
		}
		return true;
	}

}