<?php

class reviews_module extends BaseModule {

	function generateData() {
		global $current_user;
		/* @var $current_user CurrentUser */
		$params = $this->params;
		$this->target_id = isset($params['target_id']) ? $params['target_id'] : 0;
		$this->target_type = isset($params['target_type']) ? $params['target_type'] : 0;
		$this->target_user = isset($params['target_user']) ? $params['target_user'] : 0;



		switch ($this->action) {
			case 'list':
				switch ($this->mode) {
					case 'rates':
						$this->getRates();
						break;

					default:
						$this->getReviews();
						break;
				}
				break;
			case 'new':
				switch ($this->mode) {
					default:
						if ($current_user->getRole() > User::ROLE_VANDAL)
							$this->getUserReview();
						break;
				}
				break;
			default:
				throw new Exception('no action #' . $this->action . ' for ' . $this->moduleName);
				break;
		}
	}

	function getUserReview() {
		global $current_user;
		if (!$current_user->authorized)
			return;
		$res = MongoDatabase::getReviewEvent($current_user->id, $this->target_id);

		$this->data = $this->_item($res);
		$this->data['review']['target_id'] = $this->target_id;
		$this->data['review']['target_type'] = $this->target_type;
		$this->data['review']['rate'] = isset($this->data['review']['rate']) ?
			$this->data['review']['rate'] :
			Database::sql2single('SELECT `rate` FROM `book_rate` WHERE `id_book` =' . $this->target_id . ' AND `id_user`=' . $current_user->id);
	}

	function _item($row) {
		$out = array();
		$usrs = array();

		$out['review'] = array(
		    'user_id' => $row['user_id'],
		    'time' => date('Y-m-d H:i', $row['time']),
		    'mark' => isset($row['mark']) ? $row['mark'] : 0,
		    'body' => isset($row['body']) ? $row['body'] : '',
		    'likesCount' => isset($row['likesCount']) ? (int) $row['likesCount'] : 0,
		);
		$usrs[$row['user_id']] = $row['user_id'];

		if (count($usrs)) {
			$users = Users::getByIdsLoaded($usrs);
			foreach ($users as $user) {
				$out['users'][] = $user->getListData();
			}
		}
		return $out;
	}

	function _list($data, $uids = false) {
		$em = new events_module($this->params, array(), $this->action, $this->mode);
		$em->_list($data);
		$data = $em->data;
		$data['reviews'] = $data['events'];
		unset($data['events']);
		return $data;
	}

	function getRates() {
		//marks
		$data = MongoDatabase::findReviewMarkEvents($this->target_id);

		$em = new events_module($this->params, array(), $this->action, $this->mode);
		$em->_list($data);
		$data = $em->data;
		$data['reviews'] = $data['events'];
		unset($data['events']);


		$this->data = $data;
		$this->data['reviews']['target_id'] = $this->target_id;
		$this->data['reviews']['target_type'] = $this->target_type;
	}

	function getReviews() {
		$res = MongoDatabase::findReviewEvents($this->target_id);
		$this->data = $this->_list($res, $uids = array());
		$this->data['reviews']['target_id'] = $this->target_id;
		$this->data['reviews']['target_type'] = $this->target_type;
	}

}
