<?php

class rightholders_module extends BaseModule {

	function generateData() {
		global $current_user;
		/* @var $current_user CurrentUser */
		$current_user->can_throw('statistics_view');
		switch ($this->action) {
			case 'list':
				switch ($this->mode) {
					default:
						$this->getRightholders();
						break;
				}
				break;
			case 'show':
				switch ($this->mode) {
					default:
						$this->getRightholder();
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
	
	function getNew(){
		
	}

	function getRightholder() {
		$id = max(0, (int) $this->params['rightholder_id']);
		$query = 'SELECT * FROM `rightholders` WHERE `id`=' . $id;
		$data = Database::sql2row($query);
		$this->data['partner'] = $data;
	}
	
	function getEdit() {
		$this->getRightholder();
	}

	function getRightholders() {
		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 1;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
		$where = '';
		$order = 'ORDER BY `id` DESC ';
		$group_by = '';
		$query = 'SELECT COUNT(1) FROM `rightholders` ' . $where . ' ' . $group_by . '';
		$count = Database::sql2single($query);
		$cond->setPaging($count, $per_page, $pagingName);
		$limit = $cond->getLimit();
		$limit = ' LIMIT ' . $limit;
		$query = 'SELECT * FROM `rightholders`' . $where . ' ' . $group_by . ' ' . $order . ' ' . $limit;
		$data = Database::sql2array($query);
		foreach ($data as &$row) {
			$row['path'] = Config::need('www_path') . '/admin/rightholders/' . $row['id'];
		}
		$this->data['rightholders'] = $data;
		$this->data['rightholders']['title'] = 'Правообладатели';
		$this->data['rightholders']['count'] = $count;
		$this->data['conditions'] = $cond->getConditions();
	}

}