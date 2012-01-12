<?php

class Magazine {

	public $id;
	public $loaded = false;
	public $books = array();
	public $data = array();

	function __construct($id) {
		$this->id = $id;
	}

	public function getPeriodMap() {
		$this->load();
		$first_year = $this->data['first_year'];
		$last_year = $this->data['last_year'];
		$out = array();
		$maxN = 1;
		foreach ($this->books as $y => $b) {
			foreach ($b as $n => $bid)
				$maxN = max($maxN, $n);
		}
		for ($year = $first_year; $year < $last_year; $year++) {
			$out[$year] = array('year' => $year);

			for ($j = 1; $j <= $maxN; $j++) {
				$out[$year]['books'][$j] = $this->getPeriodItem($year, $j, $maxN);
			}
		}
		return $out;
	}

	public static function _getUrl($id) {
		return Config::need('www_path') . '/magazine/' . $id;
	}

	public function getUrl() {
		return self::_getUrl($this->id);
	}

	function getCover($mode = '') {
		$mode = $mode == 'normal' ? '' : $mode;
		if (!$this->loaded) {
			$this->load();
		}
		if ($this->data['is_cover']) {
			return Config::need('www_path') . '/static/upload/mcovers/' . (ceil($this->id / 5000)) . '/' . $this->id . $mode . '.jpg';
		}
		return Config::need('www_path') . '/static/upload/mcovers/default' . $mode . '.jpg';
	}

	public function getPeriodItem($year, $j, $period) {
		$out['n'] = $j;
		if (isset($this->books[$year][$j])) {
			$out['bid'] = $this->books[$year][$j];
			$out['path'] = Config::need('www_path') . '/book/' . $this->books[$year][$j];
		}else
			$out['path'] = Config::need('www_path') . '/book/new?year=' . $year . '&n=' . $j . '&m=' . $this->id;

		return $out;
	}

	public function load() {
		if ($this->loaded)
			return;
		$query = 'SELECT * FROM `magazines` M LEFT JOIN `book_magazines` BM 
			ON BM.id_magazine=M.id WHERE M.`id`=' . $this->id;
		$this->data = Database::sql2row($query);

		$query = 'SELECT * FROM `book_magazines` WHERE `id_magazine`=' . $this->id;
		$books = Database::sql2array($query, 'id_book');
		foreach ($books as $row) {
			$this->books[$row['year']][$row['n']] = $row['id_book'];
		}
		$this->loaded = true;
	}

}