<?php

class Books extends Collection {

	public $className = 'Book';
	public $tableName = 'book';
	public $itemName = 'book';
	public $itemsName = 'books';
	public $cache_time = 6;

	public static function getInstance() {
		if (!Books::$books_instance) {
			Books::$books_instance = new Books();
		}
		return Books::$books_instance;
	}

	public function _before_idsToData(&$ids) {
		$this->LoadBookPersons($ids);
		return $ids;
	}

	public function _after_idsToData($data) {
		$aids = array();
		foreach ($data['books'] as $bookid => $d) {
			$book = Books::getInstance()->getByIdLoaded($bookid);
			$aid = $book->getAuthorId();
			if ($aid)
				$aids[$aid] = $aid;
		}
		if (count($aids)) {
			$persons = Persons::getInstance()->getByIdsLoaded($aids);
			foreach ($persons as $person) {
				$data['authors'][] = $person->getListData();
			}
		}else
			$data['authors'] = array();
		return $data;
	}

	public function idsToData($ids) {
		$ids = $this->_before_idsToData($ids);
		$items = $this->getByIdsLoaded($ids);
		$out = array();
		foreach ($items as $item)
			$out[$item->id] = $item->getListData(false);
		return $this->_after_idsToData(array($this->itemsName => $out));
	}

	public function LoadBookPersons($ids) {
		$this->getByIdsLoaded($ids);
		$out = array();
		$tofetch = array();
		$aids = array();
		if (is_array($ids)) {
			foreach ($ids as $id) {
				if (!isset($this->items[$id]))
					continue;
				if (!$this->items[$id]->personsLoaded) {
					$tofetch[] = (int) $id;
				}
			}
			$bookPersons = array();
			if (count($tofetch)) {
				$query = 'SELECT * FROM `book_persons` WHERE `id_book` IN (' . implode(',', $tofetch) . ')';
				$bookPersons = Database::sql2array($query);
			}
			$bookPersonsPrepared = array();
			foreach ($bookPersons as $book) {
				$bookPersonsPrepared[$book['id_book']][] = $book;
				$aids[$book['id_person']] = $book['id_person'];
			}

			Persons::getInstance()->getByIdsLoaded($aids);

			foreach ($ids as $id) {
				if (isset($this->items[$id]))
					if (isset($bookPersonsPrepared[$id])) {
						$this->items[$id]->loadPersons(isset($bookPersonsPrepared[$id]) ? $bookPersonsPrepared[$id] : array());
					} else {
						// no any persons
						$this->items[$id]->personsLoaded = true;
						//$this->items[$id]->persons = array();
					}
			}

			foreach ($ids as $id) {
				if (isset($this->items[$id]))
					$out[$id] = $this->items[$id];
			}
		}
		
		return $out;
	}

}