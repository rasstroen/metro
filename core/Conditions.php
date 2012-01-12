<?php

class Conditions {

	public $paging;
	private $perPage;
	private $currentPage;
	private $totalCount;
	//
	public $sorting;
	public $limit;
	//
	private $url;
	private $pagingParameterName = 'p';
	private $defaultSortingField = '';
	private $defaultSortingOrder = '';

	function __construct() {
		$this->url = Request::$get_normal;
	}

	private function getCurrentPage() {
		$p = isset(Request::$get_normal[$this->pagingParameterName]) ? (int) Request::$get_normal[$this->pagingParameterName] : 1;
		if ($p > $this->getLastPage())
			$p = $this->getLastPage();
		if ($p < 1)
			$p = 1;
		return $p;
	}

	private function getLastPage() {
		return ceil($this->totalCount / max(1, $this->perPage));
	}

	function getLimit() {
		if ($this->limit)
			return $this->limit;
		return (($this->currentPage - 1) * $this->perPage) . ' , ' . $this->perPage;
	}

	function setLimit($limit) {
		$this->limit = max(0, (int) $limit);
	}

	function getMongoLimit() {
		if ($this->limit)
			return 0;
		return (($this->currentPage - 1) * $this->perPage);
	}

	function setSorting($options, $default = false) {
		if ($default) {
			foreach ($default as $fieldName => $data) {
				$this->defaultSortingField = $fieldName;
				if (isset($data['order'])) {
					$this->defaultSortingOrder = $data['order'];
				}
			}
		}

		$sf = $this->getSortingField();
		$other = ($this->getSortingOrder() != 'desc') ? 'desc' : 'asc';
		foreach ($options as $name => $option) {
			$this->sorting[$name] = $option;
			if (!$this->defaultSortingField) {
				$this->defaultSortingField = $name;
				if (isset($option['order'])) {
					$this->defaultSortingOrder = $option['order'];
				}
			}
		}
		$sf = $this->getSortingField();

		if (is_array($this->sorting))
			foreach ($this->sorting as $name => &$option) {
				if ($sf == $name) {
					$option['current'] = 1;
					$option['path'] = $this->preparePath(array(array('sort' => $name), array('order' => $other)));
				} else {
					$option['path'] = $this->preparePath(array(array('sort' => $name), array('order' => 'asc')));
				}
			}
	}

	function getSortingField() {
		$p = isset(Request::$get_normal['sort']) ? Request::$get_normal['sort'] : '';
		if (!$p || !isset($this->sorting[$p])) {
			return $this->defaultSortingField;
		}
		return $p;
	}

	function getSortingOrderSQL() {
		$p = isset(Request::$get_normal['order']) ? Request::$get_normal['order'] : $this->defaultSortingOrder;
		$p = ($p == 'desc') ? 'DESC' : 'ASC';
		return $p;
	}

	function setPaging($count, $perPage, $pagingParameterName = 'p') {
		$this->pagingParameterName = $pagingParameterName;
		$this->totalCount = $count;
		$this->perPage = $perPage;
		$this->currentPage = $this->getCurrentPage();
		$this->addPage(1);
		for ($i = $this->currentPage - 10; $i < $this->currentPage + 10; $i++)
			$this->addPage($i);
		$this->addPage($this->getLastPage());
	}

	private function preparePath($arr) {
		$path = Request::$get_normal;
		foreach ($arr as $i) {
			foreach ($i as $f => $v) {
				$path[$f] = $v;
				$out = array();
				foreach ($path as $f => $v) {
					if ($f == $this->pagingParameterName)
						if ($v == 1) {
							// url for first page can be without ?p=1 parameter
						}else
							$out[] = $f . '=' . $v;
				}
			}
		}
		$path = implode('&', $out);
		if ($path)
			return Request::$url . '?' . $path;
		else
			return Request::$url;
	}

	private function getSortingOrder() {
		$p = isset(Request::$get_normal['order']) ? Request::$get_normal['order'] : $this->defaultSortingOrder;
		$p = ($p == 'asc') ? 'asc' : 'desc';
		return $p;
	}

	private function addPage($id) {
		if (($id = (int) $id) < 1)
			return;
		if ($id > $this->getLastPage())
			return;
		$this->paging[$id] = array(
		    'title' => $id,
		    'path' => $this->preparePath(array(array($this->pagingParameterName => $id))),
		);
		if ($id == $this->getLastPage())
			$this->paging[$id]['last'] = 1;
		if ($id == 1)
			$this->paging[$id]['first'] = 1;

		if ($id == $this->getCurrentPage() + 1)
			$this->paging[$id]['next'] = 1;

		if ($id == $this->getCurrentPage() - 1)
			$this->paging[$id]['prev'] = 1;

		if ($id == $this->getCurrentPage())
			$this->paging[$id]['current'] = 1;
	}

	function getConditions() {
		$out = array();
		if (count($this->paging) > 1)
			$out[] = array('mode' => 'paging', 'options' => $this->paging);
		if ($this->sorting)
			$out[] = array('mode' => 'sorting', 'options' => array_values($this->sorting));
		return $out;
	}

}