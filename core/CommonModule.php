<?php

// класс, генерирующий xml для вывода объектов,списков объектов,редактирования и создания объектов
class CommonModule extends BaseModule {
	/* @var $Collection Collection */

	protected $Collection = null;
	public $ConditionsEnabled = false;

	function generateData() {
		$this->_before_process();
		$this->checkConditions();
		$this->setCollectionClass();
		$this->_process($this->action, $this->mode);
		$this->_after_process();
	}

	function checkConditions() {
		if (isset($this->params['per_page'])) {
			if ($this->params['per_page']) {
				$this->ConditionsEnabled = true;
			}
		}
		if (isset($this->params['limit'])) {
			if ($this->params['limit']) {
				$this->ConditionsEnabled = true;
			}
		}
	}

	function prepareSelect($fields = '*', $where = false, $order = false, $limit = false) {
		$limit = $limit ? 'LIMIT ' . $limit : '';
		$order = $order ? 'ORDER BY ' . $order : '';
		$where = $where ? 'WHERE ' . $where : '';
		$query = 'SELECT ' . $fields . ' FROM ' . $this->Collection->tableName . ' ' . $where . ' ' . $order . ' ' . $limit;
		return $query;
	}

	function getCountBySQL($where = false) {
		if (isset($this->cachedCount[$where]))
			return $this->cachedCount[$where];
		$where = $where ? 'WHERE ' . $where : '';
		$query = 'SELECT COUNT(1) FROM ' . $this->Collection->tableName . ' ' . $where;
		$this->cachedCount[$where] = max(0, (int) Database::sql2single($query));
		return $this->cachedCount[$where];
	}

	// для объектов какого класса мы выполняем действия?
	function setCollectionClass() {
		throw new Exception('setCollectionClass must be overriden');
	}

	function getParam($field, $default = false) {
		return isset($this->params[$field]) ? $this->params[$field] : $default;
	}

	// выполняем действия
	function _process($action, $mode) {
		throw new Exception('CommonModule::_process must be overriden');
	}

	// выводим объект
	function _show($id) {
		$object = $this->Collection->getByIdLoaded($id);
		if (!$object->exists) {
			throw new Exception('К сожалению, такого у нас в базе совсем нет');
		}
		$this->data[$this->Collection->itemName] = $object->_show();
	}

	/**
	 * Возвращает данные
	 * @param type $where
	 * @param type $sortings
	 * @return type 
	 */
	function _list_get($where, $sortings = false) {
		return $this->_list($where, $sortings, $return = true);
	}

	/**
	 * устанавливает $this->data[items_name]
	 * @param type $where
	 * @param type $sortings
	 * @param type $return 
	 */
	function _list($where, $sortings = false, $return = false, $default_sortings = false) {
		$limit = false;
		$order = false;
		$sorting_order = false;
		$cond = new Conditions();
		if ($this->ConditionsEnabled) {
			// пейджинг, сортировка
			if ($sortings || $default_sortings) {
				$cond->setSorting($sortings, $default_sortings);
				
				$order = $cond->getSortingField();
				$sorting_order = $cond->getSortingOrderSQL();
			}
			$per_page = isset($this->params['per_page']) ? $this->params['per_page'] : 0;
			$limit_parameter = isset($this->params['limit']) ? $this->params['limit'] : 0;
			$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
			if ($per_page) {
				$cond->setPaging($this->getCountBySQL($where), $per_page, $pagingName);
				$limit = $cond->getLimit();
			}
			if ($limit_parameter) {
				$cond->setLimit($limit_parameter);
				$limit = $cond->getLimit();
			}
		}
		$query = $this->prepareSelect('id', $where, $order ? ($order . ' ' . $sorting_order) : '', $limit);
		$ids = Database::sql2array($query, 'id'); // нашли объекты, которые хотим вывести

		if ($return) {
			$this->data['conditions'] = $cond->getConditions();
			return $this->_idsToData(array_keys($ids)); // отдаем массив
		}
		else
			$this->data = $this->_idsToData(array_keys($ids)); // отдаем массив
		$this->data['conditions'] = $cond->getConditions();
		return true;
	}

	/**
	 * возвращает массив listData() объектов
	 * @param type $ids
	 * @param type $limit
	 * @return type 
	 */
	function _idsToData($ids, $limit = 100) {
		if ($limit) {
			$ids = array_slice($ids, 0, $limit);
		}
		return $this->Collection->idsToData($ids);
	}

	// выводим список объектов по id
	function _list_($ids) {
		$objects = $this->Collection->getByIdsLoaded($ids);
	}

	// выводим данные для редактирования
	function _edit() {
		
	}

	// выводим данные для создания нового объекта
	function _new() {
		
	}

	// выполняется перед обработкой
	function _before_process() {
		
	}

	// выполняется после обработки
	function _after_process() {
		
	}

}