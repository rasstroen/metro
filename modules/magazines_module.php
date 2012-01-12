<?php

class magazines_module extends BaseModule {

	function generateData() {
		global $current_user;
		$params = $this->params;
		$this->magazine_id = isset($params['magazine_id']) ? $params['magazine_id'] : 0;

		switch ($this->action) {
			case 'show':
				$this->getOne();
				break;
			case 'edit':
				$current_user->can_throw('books_edit');
				$this->getOne();
				$this->getEditingInfo();
				break;
			case 'new':
				$current_user->can_throw('books_edit');
				$this->_new();
				break;
			case 'list':
				switch ($this->mode) {
					case 'search':
						$this->getSearch();
						break;
					default:
						$this->getAll();
						break;
				}
				break;
			default:
				throw new Exception('no action #' . $this->action . ' for ' . $this->moduleName);
				break;
		}
	}

	function _new() {
		$this->data['magazine'] = array();
		foreach (Config::$langRus as $code => $title) {
			$this->data['magazine']['lang_codes'][] = array(
			    'id' => Config::$langs[$code],
			    'code' => $code,
			    'title' => $title,
			);
		}
	}

	function getSearch() {
		$query_string = isset(Request::$get_normal['q']) ? trim(Request::$get_normal['q']) : false;
		$search = Search::getInstance();
		/* @var $search Search */
		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 5;
		$cond->setPaging(1000, $per_page);

		$offset = $cond->getMongoLimit();
		list($sids, $count, $hl) = $search->searchMagazinesByString($query_string, $offset, $per_page);
		$cond = new Conditions();
		$cond->setPaging($count, $per_page);


		if ($count) {
			$query = 'SELECT * FROM `magazines` WHERE `id` IN(' . implode(',', $sids) . ')';
			$series = Database::sql2array($query);
			$this->data['magazines'] = $series;
		}
		else
			$this->data['magazines'] = array();
		$this->data['conditions'] = $cond->getConditions();
		foreach ($this->data['magazines'] as &$m) {
			if (isset($hl[$m['id']]))
				$m['path'] = Config::need('www_path') . '/m/' . $m['id'] . '#hl=' . implode(' ', $hl[$m['id']]);
			else
				$m['path'] = Config::need('www_path') . '/m/' . $m['id'] . '#hl=' . Request::$get_normal['q'];
		}
		$this->data['magazines']['title'] = 'Журналы по запросу «' . $query_string . '»';
		$this->data['magazines']['count'] = $count;
	}

	function getEditingInfo() {
		foreach (Config::$langRus as $code => $title) {
			$this->data['magazine']['lang_codes'][] = array(
			    'id' => Config::$langs[$code],
			    'code' => $code,
			    'title' => $title,
			);
		}
	}

	function getAll() {
		// вся периодика
		$query = 'SELECT `id`,`title`,`first_year`,`last_year` FROM `magazines` ORDER BY `last_year` DESC';
		$magazines = Database::sql2array($query);
		foreach ($magazines as &$m) {
			$m['path'] = Magazine::_getUrl($m['id']);
		}
		$this->data['magazines'] = $magazines;
	}

	function getOne() {
		$m = new Magazine($this->magazine_id);
		$m->load();
		$this->data['magazine']['id'] = $m->id;
		$langId = $m->data['id_lang'];
		$langCode = Config::need('default_language');
		foreach (Config::$langs as $code => $id_lang) {
			if ($id_lang == $langId) {
				$langCode = $code;
			}
		}
		$this->data['magazine']['lang_code'] = $langCode;
		$this->data['magazine']['lang_title'] = Config::$langRus[$langCode];
		$this->data['magazine']['lang_id'] = $langId;
		$this->data['magazine']['path'] = $m->getUrl();

		$this->data['magazine']['isbn'] = $m->data['ISBN'];
		$this->data['magazine']['cover'] = $m->getCover();

		$this->data['magazine']['years'] = $m->getPeriodMap();
		$this->data['magazine']['title'] = $m->data['title'];
		$this->data['magazine']['rightholder'] = $m->data['rightsholder'] ? $m->data['rightsholder'] : '';
		$this->data['magazine']['annotation'] = $m->data['annotation'];
	}

}
