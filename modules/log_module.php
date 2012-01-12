<?php

// модуль отвечает за отображение баннеров
class log_module extends BaseModule {

	function generateData() {
		global $current_user;
		/*@var $current_user CurrentUser*/
		$current_user->can_throw('logs_view');
		$params = $this->params;
		$this->target_type = isset($params['target_type']) ? $params['target_type'] : false;
		$this->id_target = isset($params['id_target']) ? $params['id_target'] : 0;

		switch ($this->action) {
			case 'list':
				switch ($this->mode) {
					default:
						$this->getLog();
						break;
				}
				break;
			case 'show':

				break;
			default:
				throw new Exception('no action #' . $this->action . ' for ' . $this->moduleName);
				break;
		}
	}

	function getLog() {
		if ($this->target_type == 'user') {
			$query = 'SELECT COUNT(DISTINCT(id_log)) FROM `biber_log_index` WHERE `id_user`=' . $this->id_target . ' ';
		} else
		if ($this->target_type == 'all') {
			$query = 'SELECT COUNT(DISTINCT(id_log)) FROM `biber_log_index` WHERE `is_copy`=0';
		} else {
			$query = 'SELECT COUNT(1) FROM `biber_log_index` WHERE `target_type`=' . $this->target_type . ' AND `id_target`=' . $this->id_target . ' ';
		}
		$count = min(1000, Database::sql2single($query));

		$cond = new Conditions();
		$cond->setPaging($count, 10);
		$this->data['conditions'] = $cond->getConditions();
		$limit = $cond->getLimit();

		if ($this->target_type == 'user') {
			$query = 'SELECT * FROM `biber_log_index` WHERE
            `id_user`=' . $this->id_target . ' GROUP BY id_log
            ORDER BY `time` DESC LIMIT ' . $limit;
		} else
		if ($this->target_type == 'all') {
			$query = 'SELECT * FROM `biber_log_index` WHERE `is_copy`=0 GROUP BY id_log
            ORDER BY `time` DESC LIMIT ' . $limit;
		} else {
			$query = 'SELECT * FROM `biber_log_index` WHERE
            `target_type`=' . $this->target_type . ' AND
            `id_target`=' . $this->id_target . '
            ORDER BY `time` DESC LIMIT ' . $limit;
		}


		$book_ids = array();
		$person_ids = array();
		$serie_ids = array();
		$magazine_ids = array();
		$uids = array();

		if ($this->target_type == BiberLog::TargetType_book)
			$book_ids[$this->id_target] = $this->id_target;

		if ($this->target_type == BiberLog::TargetType_person)
			$person_ids[$this->id_target] = $this->id_target;

		if ($this->target_type == 'user')
			$uids[$this->id_target] = $this->id_target;

		$arr = array();
		$arri = Database::sql2array($query, 'id_log');
		$to_fetch_log = array();
		foreach ($arri as $row) {
			$to_fetch_log[(int) $row['id_log']] = (int) $row['id_log'];
		}
		if (count($to_fetch_log)) {
			$query = 'SELECT * FROM `biber_log` WHERE `id` IN (' . implode(',', $to_fetch_log) . ') ORDER BY `time` DESC';
			$arr = Database::sql2array($query);
			foreach ($arr as &$rowx) {
				foreach ($arri[$rowx['id']] as $f => $v) {
					$rowx[$f] = $v;
				}
			}
		}



		foreach ($arr as &$row) {
			$book_id_s = 0;
			$uids[$row['id_user']] = $row['id_user'];
			$vals = unserialize($row['data']);
			if (isset($vals['id1'])) {
				$book_ids[$vals['id1'][0]] = $vals['id1'][0];
				$book_ids[$vals['id1'][1]] = $vals['id1'][1];
			}
			if (isset($vals['id2'])) {
				$book_ids[$vals['id2'][0]] = $vals['id2'][0];
				$book_ids[$vals['id2'][1]] = $vals['id2'][1];
			}

			if (isset($vals['id_person'])) {
				if(isset($vals['id_person'][0]))$person_ids[$vals['id_person'][0]] = (int) $vals['id_person'][0];
				if(isset($vals['id_person'][1]))$person_ids[$vals['id_person'][1]] = (int) $vals['id_person'][1];
			}

			if (isset($vals['is_duplicate'])) {
				$book_ids[$vals['is_duplicate'][0]] = $vals['is_duplicate'][0];
				$book_ids[$vals['is_duplicate'][1]] = $vals['is_duplicate'][1];
			}

			$book_id = 0;
			$person_id = 0;
			$serie_id = 0;

			$values = array();

			foreach ($vals as $field => $v) {
				if (!is_array($v)) {
					if($field == 'id_book'){
						$book_id = $v;
						$book_ids[$v] = $v;
					}
					if($field == 'id_person'){
						$person_id = $v;
						$person_ids[$v] = $v;
					}
					if($field == 'id_serie'){
						$serie_id = $v;
						$serie_ids[$v] = $v;
					}
					if($field == 'id_magazine'){
						$serie_id = $v;
						$magazine_ids[$v] = $v;
					}
					continue;
				}
				$tmp = array();
				if ($row['target_type'] == BiberLog::TargetType_book) {
					if ($field == 'new_relations') {
						foreach ($v[1] as $new_relation_id) {
							$book_ids[$new_relation_id] = $new_relation_id;
							$tmp[] = array('book_id' => $new_relation_id);
						}
						$values['new_relations'] = $tmp;
					} else
					if ($field == 'old_relations') {
						foreach ($v[1] as $new_relation_id) {
							$book_ids[$new_relation_id] = $new_relation_id;
							$tmp[] = array('book_id' => $new_relation_id);
						}
						$values['old_relations'] = $tmp;
					} else
					if ($field == 'deleted_relations') {
						foreach ($v[1] as $new_relation_id) {
							$book_ids[$new_relation_id] = $new_relation_id;
							$tmp[] = array('book_id' => $new_relation_id);
						}
						$values['deleted_relations'] = $tmp;
					}
					else
						$values[] = array('name' => $field, 'old' => $v[0], 'new' => $v[1]);
				} else
				if ($row['target_type'] == BiberLog::TargetType_person) {
					if ($field == 'new_relations') {
						foreach ($v[1] as $new_relation_id) {
							$person_ids[$new_relation_id] = (int) $new_relation_id;
							$tmp[] = array('author_id' => $new_relation_id);
						}
						$values['new_relations'] = $tmp;
					} else
					if ($field == 'old_relations') {
						foreach ($v[1] as $new_relation_id) {
							$person_ids[$new_relation_id] = (int) $new_relation_id;
							$tmp[] = array('author_id' => $new_relation_id);
						}
						$values['old_relations'] = $tmp;
					} else
					if ($field == 'deleted_relations') {
						foreach ($v[1] as $new_relation_id) {
							$person_ids[$new_relation_id] = (int) $new_relation_id;
							$tmp[] = array('author_id' => $new_relation_id);
						}
						$values['deleted_relations'] = $tmp;
					}
					else
						$values[] = array('name' => $field, 'old' => $v[0], 'new' => $v[1]);
				}
				else
				if ($row['target_type'] == BiberLog::TargetType_magazine) {
					$values[] = array('name' => $field, 'old' => $v[0], 'new' => $v[1]);
				} else
				if ($row['target_type'] == BiberLog::TargetType_serie) {
					if ($field == 'id_book') {
						$book_id_s = $v[0] ? $v[0] : $v[1];
						if ($book_id_s)
							$book_ids[$book_id_s] = $book_id_s;
						continue;
					}
					$values[] = array('name' => $field, 'old' => $v[0], 'new' => $v[1]);
				}
			}


			



			if (in_array($row['target_type'], array(BiberLog::TargetType_book))) {
				$book_ids[$row['id_target']] = $row['id_target'];
				$book_id = $row['id_target'];
			}
			if (in_array($row['target_type'], array(BiberLog::TargetType_person))) {
				$person_ids[(int) $row['id_target']] = (int) $row['id_target'];
				$person_id = $row['id_target'];
			}
			if (in_array($row['target_type'], array(BiberLog::TargetType_serie))) {
				$serie_id = $row['id_target'];
				$serie_ids[$row['id_target']] = $row['id_target'];
			}
			if (in_array($row['target_type'], array(BiberLog::TargetType_magazine))) {
				$magazine_id = $row['id_target'];
				$magazine_ids[$row['id_target']] = $row['id_target'];
			}
			$this->data['logs'][] = array(
			    'id' => $row['id'],
			    'book_id' => max($book_id, $book_id_s),
			    'author_id' => $person_id,
			    'serie_id' => $serie_id,
			    'time' => date('Y/m/d H:i:s', $row['time']),
			    'action' => BiberLog::$actionTypes[$row['action_type']],
			    'id_user' => $row['id_user'],
			    'values' => $values,
			    'applied' => $row['undo'] ? 0 : 1,
			);
		}
		$users = Users::getByIdsLoaded($uids);
		foreach ($users as $user) {
			$this->data['users'][$user->id] = $user->getListData();
		}

		if (count($serie_ids)) {
			$query = 'SELECT id,name,title FROM `series` WHERE `id` IN(' . implode(',', $serie_ids) . ')';
			$out = Database::sql2array($query);
			foreach ($out as &$r) {
				$r['path'] = Config::need('www_path') . '/s/' . $r['id'];
			}
			$this->data['series'] = $out;
		}
		if (count($book_ids))
			$this->data['books'] = $this->getLogBooks($book_ids);
		if (count($person_ids))
			$this->data['authors'] = $this->getLogPersons($person_ids);

		foreach (Config::$langRus as $code => $title) {
			$this->data['lang_codes'][] = array(
			    'id' => Config::$langs[$code],
			    'code' => $code,
			    'title' => $title,
			);
		}
	}

	function getLogPersons($ids) {
		$persons = Persons::getInstance()->getByIdsLoaded($ids);
		$out = array();

		if (is_array($persons))
			foreach ($persons as $person) {
				$out[] = $person->getListData();
			}
		return $out;
	}

	function getLogBooks($ids, $opts = array(), $limit = false) {
		$person_id = isset($opts['person_id']) ? $opts['person_id'] : false;
		$books = Books::getInstance()->getByIdsLoaded($ids);
		Books::getInstance()->LoadBookPersons($ids);
		$out = array();
		/* @var $book Book */
		$i = 0;
		if (is_array($books))
			foreach ($books as $book) {
				if ($limit && ++$i > $limit)
					return $out;
				$out[] = $book->getListData();
			}
		return $out;
	}

}