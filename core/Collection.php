<?php

class Collection {

	public $className = 'SomeObject';
	public $tableName = 'SomeObjectTable';
	public $itemName = 'some_object';
	public $itemsName = 'some_objects';
	public static $books_instance = false;
	public static $persons_instance = false;
	public $items = array();
	public $from_cache = array();
	public $cache_time = 60;

	protected static function getInstance() {
		throw new Exception('Collection::getInstance must be overriden');
	}

	public function putInCache($id, $force = false) {
		if (isset($this->from_cache[$id]))
			return false;
		if (isset($this->items[$id])) {
			if ($force || $this->items[$id]->loaded) {
				$cachedData = $this->items[$id]->data;
				if (!$cachedData && $force) {
					// несуществующий в базе объект
					$cachedData = 'empty';
				}
				Cache::set($this->itemName . '_' . $id, $cachedData, $this->cache_time);
				return true;
			}
		}
		return false;
	}

	public function getFromCache($id) {
		if (isset($this->items[$id])) {
			if ($this->items[$id]->loaded === true) {
				return $this->items[$id];
			}
		}
		if ($data = Cache::get($this->itemName . '_' . $id)) {
			$this->from_cache[$id] = true;
			$tmp = new $this->className($id, $data);
			$this->items[$tmp->id] = $tmp;
			unset($tmp);
			return $this->items[$id];
		}
		return false;
	}

	public function dropCache($id) {
		Cache::drop($this->itemName . '_' . $id);
	}

	public static function add($classExem) {
		$this->items[$classExem->id] = $classExem;
	}

	public function getById($id, $data = false) {
		if (!is_numeric($id) || !$id)
			throw new Exception($id . ' illegal item id');
		if (!isset($this->items[(int) $id])) {
			$tmp = new $this->className($id, $data);
			$this->items[$tmp->id] = $tmp;
			unset($tmp);
		}
		return $this->items[$id];
	}

	public function getByIdLoaded($id) {
		$r = $this->getByIdsLoaded(array($id));
		return isset($r[$id]) ? $r[$id] : false;
	}

	public function _before_idsToData(&$ids) {
		return $ids;
	}

	public function idsToData($ids) {
		$ids = $this->_before_idsToData($ids);
		$items = $this->getByIdsLoaded($ids);
		$out = array();
		foreach ($items as $item) {
			if ($item)
				$out[$item->id] = $item->getListData();
		}
		return $this->_after_idsToData(array($this->itemsName => $out));
	}

	public function _after_idsToData($out) {
		return $out;
	}

	public function getByIdsLoaded($ids) {
		$out = array();
		$tofetch = array();
		if (is_array($ids)) {
			foreach ($ids as $id) {
				if (!$id)
					continue;
				if (!isset($this->items[(int) $id])) {
					if (!$this->getFromCache($id))
						$tofetch[] = $id;
				}
			}
			if (count($tofetch)) {
				$query = 'SELECT * FROM ' . $this->tableName . ' WHERE `id` IN (' . implode(',', $tofetch) . ')';
				$data = Database::sql2array($query, 'id');
			}
			foreach ($ids as $id) {
				if (!$id)
					continue;
				if (!isset($this->items[(int) $id])) {
					if (isset($data[$id])) {
						$tmp = new $this->className($id, $data[$id]);
						$this->items[$tmp->id] = $tmp;
						$this->putInCache($tmp->id);
						unset($tmp);
					} else {
						$this->items[$id] = new $this->className($id); // todo
						$this->putInCache($id, $force = true);
					}
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