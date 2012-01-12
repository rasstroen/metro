<?php

class contributions_module extends BaseModule {

	function generateData() {
		global $current_user;
		/* @var $current_user CurrentUser */

		switch ($this->action) {
			case 'list':
				switch ($this->mode) {
					default:
						$this->getUserContribution();
						break;
				}
				break;
		}
	}

	function getUserContribution() {
		global $current_user;
		$uid = $this->params['user_id'];
		$user = new User($uid);
		$user->load();
		$count = Database::sql2single('SELECT COUNT(1) FROM  `users_points_history` WHERE `id_user`=' . $user->id);

		//по книгам, по дате, по типам действий
		$sortings = array(
		    'time' => array('title' => 'по дате'),
		    'id_target' => array('title' => 'по книге'),
		    'id_action' => array('title' => 'по типу действий'),
		);
		$dsortings = array(
		    'time' => array('title' => 'по дате' , 'order' => 'desc'),
		);
		$cond = new Conditions();
		$cond->setPaging($count, isset($this->params['per_page']) ? (int) $this->params['per_page'] : 40);
		$cond->setSorting($sortings, $dsortings);
		$order = 'ORDER BY '.$cond->getSortingField().' '.$cond->getSortingOrderSQL();
		
		
		$limit = $cond->getLimit();
		$this->data['conditions'] = $cond->getConditions();


		$query = 'SELECT * FROM `users_points_history` WHERE `id_user`=' . $user->id . ' '.$order.' LIMIT ' . $limit;
		$contributions = Database::sql2array($query);

		$bids = array();
		$aids = array();
		$sids = array();
		$mids = array();
		$gids = array();
		$uids = array($user->id);

		$tmp = array();
		foreach (Config::$points as $name => $p) {
			$tmp[$p['id']] = $name;
		}

		foreach ($contributions as &$contribution) {
			switch ($contribution['target_type']) {
				case BiberLog::TargetType_book:
					$contribution['id_book'] = $contribution['id_target'];
					$bids[$contribution['id_target']] = $contribution['id_target'];
					break;
				case BiberLog::TargetType_person:
					$contribution['id_author'] = $contribution['id_target'];
					$aids[$contribution['id_target']] = $contribution['id_target'];
					break;
				case BiberLog::TargetType_magazine:
					$contribution['id_magazine'] = $contribution['id_target'];
					$mids[$contribution['id_target']] = $contribution['id_target'];
					break;
				case BiberLog::TargetType_serie:
					$contribution['id_serie'] = $contribution['id_target'];
					$sids[$contribution['id_target']] = $contribution['id_target'];
					break;
				case BiberLog::TargetType_genre:
					$contribution['id_genre'] = $contribution['id_target'];
					$gids[$contribution['id_target']] = $contribution['id_target'];
					break;
				default:
					throw new Exception('cant process type #' . $contribution['target_type'] . ' for contribution');
					break;
			}
			$contribution['action'] = $tmp[$contribution['id_action']];
			unset($contribution['id_action']);
			unset($contribution['id_target']);
			unset($contribution['target_type']);
			if (!$current_user->can('logs_view'))
				unset($contribution['points']);
			$contribution['date'] = date('Y/m/d H:i:s', $contribution['time']);
			unset($contribution['time']);
		}
		$this->data['contributions'] = $contributions;

		$aaids = array();
		if (count($bids))
			list($this->data['books'], $aaids) = $this->getContributionBooks($bids);
		if (count($aaids))
			foreach ($aaids as $aid)
				$aids[$aid] = $aid;
		if (count($aids))
			$this->data['authors'] = $this->getContributionAuthors($aids);
		if (count($mids))
			$this->data['magazines'] = $this->getContributionMagazines($mids);
		if (count($sids))
			$this->data['series'] = $this->getContributionSeries($sids);
		if (count($gids))
			$this->data['genres'] = $this->getContributionGenres($gids);
		if (count($uids))
			$this->data['users'] = $this->getContributionUsers($uids);
	}

	function getContributionAuthors($aids) {
		if (!count($aids))
			return array();
		$persons = Persons::getInstance()->getByIdsLoaded($aids);
		$out = array();
		foreach ($persons as $person) {
			$out[] = $person->getListData();
		}
		return $out;
	}

	function getContributionUsers($ids) {
		$users = Users::getByIdsLoaded($ids);
		$out = array();
		/* @var $user User */
		if (is_array($users))
			foreach ($users as $user) {
				$out[] = $user->getListData();
			}
		return $out;
	}

	function getContributionBooks($ids) {
		$person_id = isset($opts['person_id']) ? $opts['person_id'] : false;
		$books = Books::getInstance()->getInstance()->getByIdsLoaded($ids);
		Books::getInstance()->getInstance()->LoadBookPersons($ids);
		$out = array();
		$aids = array();
		/* @var $book Book */
		if (is_array($books))
			foreach ($books as $book) {
				$out[] = $book->getListData();
				list($author_id, $name) = $book->getAuthor();

				if ($author_id) {
					$aids[$author_id] = $author_id;
				}
			}
		return array($out, $aids);
	}

	function getContributionMagazines($mids) {
		// вся периодика
		$query = 'SELECT `id`,`title`,`first_year`,`last_year` FROM `magazines` WHERE `id` IN (' . implode(',', $mids) . ')';
		$magazines = Database::sql2array($query);
		foreach ($magazines as &$m) {
			$m['path'] = Magazine::_getUrl($m['id']);
		}
		return $magazines;
	}

	function getContributionSeries($sids) {
		$query = 'SELECT * FROM `series` WHERE `id` IN(' . implode(',', $sids) . ')';
		$series = Database::sql2array($query);
		foreach ($series as &$serie) {
			$serie['path'] = Config::need('www_path') . '/series/' . $serie['id'];
		}
		return $series;
	}
	
	function getContributionGenres($sids) {
		$query = 'SELECT * FROM `genre` WHERE `id` IN(' . implode(',', $sids) . ')';
		$series = Database::sql2array($query);
		foreach ($series as &$serie) {
			$serie['path'] = Config::need('www_path') . '/genres/' . $serie['id'];
		}
		return $series;
	}

}