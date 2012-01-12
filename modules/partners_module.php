<?php

class partners_module extends BaseModule {

	function generateData() {
		global $current_user;
		/* @var $current_user CurrentUser */
		$current_user->can_throw('statistics_view');
		switch ($this->action) {
			case 'list':
				switch ($this->mode) {
					default:
						$this->getPartners();
						break;
				}
				break;
			case 'show':
				switch ($this->mode) {
					default:
						$this->getPartner();
						break;
				}
				break;
				break;
			case 'new':
				switch ($this->mode) {
					default:
						$this->getNew();
						break;
				}
				break;
			case 'edit':
				switch ($this->mode) {
					default:
						$this->getEdit();
						break;
				}
				break;
		}
	}

	function getNew() {
		
	}

	function getPartner() {
		$id = max(0, (int) $this->params['partner_id']);
		$query = 'SELECT * FROM `partners` WHERE `id`=' . $id;
		$data = Database::sql2row($query);
		$this->data['partner'] = $data;
	}

	function getEdit() {
		$this->getPartner();
	}

	function getPartners() {
		$cond = new Conditions();
		$per_page = 0;
		$id_target = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 1;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
		$time_start = Request::get('from');
		$time_end = Request::get('to');

		$curtime = time();
		if (!$time_start) {
			$time_start = strtotime(date('Y-m-1 00:00:00'));
		} else {
			$time_start = strtotime(date('Y-m-d 00:00:00', strtotime($time_start)));
		}

		if (!$time_end) {
			$time_end = strtotime(date('Y-m-d 23:59:59'));
		} else {

			$time_end = strtotime(date('Y-m-d 23:59:59', strtotime($time_end)));
		}

		if ($time_end < $time_start) {
			$t = $time_end;
			$time_end = $time_start;
			$time_start = $t;
		}

		$where = 'WHERE ((`time` >= ' . $time_start . ' AND `time` <= ' . $time_end . ') OR `time` IS NULL  ) AND `partners`.`id`>0';
		if ($id_target)
			$where.=' AND `id_' . $type . '`=' . $id_target;
		$order = 'ORDER BY `count` DESC ';
		$join = 'RIGHT JOIN `partners` ON SUPR.`id_partner`=`partners`.`id`';
		$join2 = 'RIGHT JOIN `partners` ON SUPR.`id_partner`=`partners`.`id`';
		$group_by = 'GROUP BY partners.`id`';
		$query = 'SELECT COUNT(1) FROM `partners`';
		$count = Database::sql2single($query);
		$cond->setPaging($count, $per_page, $pagingName);
		$limit = $cond->getLimit();

		$limit = 'LIMIT ' . $limit;
		$query = 'SELECT *, COUNT(1) as `count` FROM `stat_user_partner_referer` SUPR ' . $join . ' ' . $where . ' ' . $group_by . ' HAVING `count`>0 ' . $order . ' ' . $limit;
		$data = Database::sql2array($query, 'id');
		$ids = array();

		foreach ($data as &$row) {
			$row['path'] = Config::need('www_path') . '/admin/partners/' . $row['id'];
			$row['partner_link'] = Config::need('www_path') . '/?pid=' . $row['pid'];
			$row['path_edit'] = Config::need('www_path') . '/admin/partners/' . $row['id'] . '/edit';
			if (!$row['time'])
				$row['count'] = 0;
			$ids[$row['id']] = $row['id'];
		}

		if (count($ids)) {
			$query = 'SELECT * , 0 as `count` FROM `partners` WHERE `id` NOT IN(' . implode(',', $ids) . ')  ' . $order;
			$nostat = Database::sql2array($query);
			$i = 0;
			foreach ($nostat as &$row) {
				$row['path'] = Config::need('www_path') . '/admin/partners/' . $row['id'];
				$row['partner_link'] = Config::need('www_path') . '/?pid=' . $row['pid'];
				$row['path_edit'] = Config::need('www_path') . '/admin/partners/' . $row['id'] . '/edit';
				$data[$row['id']] = $row;
			}
		}
		uasort($data, 'sort_by_count');
		$this->data['partners'] = $data;
		$this->data['partners']['title'] = 'Партнёры';
		$this->data['partners']['count'] = $count;
		$this->data['conditions'] = $cond->getConditions();

		$this->data['statistics']['current_month_path'] = Request::$url . '?from=' . date('Y-m-01', strtotime(date('Y-m-1 00:00:00'))) . '&to=' . date('Y-m-d', strtotime(date('Y-m-d 23:59:59')) + 1);
		$this->data['statistics']['last_month_path'] = Request::$url . '?from=' . date('Y-m-01', strtotime(date('Y-m-1 00:00:00')) - 1) . '&to=' . date('Y-m-d', strtotime(date('Y-m-1 00:00:00')) - 1);
	}

}