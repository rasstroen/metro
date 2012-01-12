<?php

class settings_module extends BaseModule {

	function generateData() {
		global $current_user;
		/* @var $current_user CurrentUser */
		$current_user->can_throw('statistics_view');
		switch ($this->action) {
			case 'list':
				switch ($this->mode) {
					default:
						$this->getSettings();
						break;
				}
				break;
			case 'new':
				break;
		}
	}
	
	function getSettings() {
		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 1;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
		$where = '';
		$order = 'ORDER BY `id` DESC ';
		$group_by = '';
		$query = 'SELECT COUNT(1) FROM `settings` ' . $where . ' ' . $group_by . '';
		$count = Database::sql2single($query);
		$cond->setPaging($count, $per_page, $pagingName);
		$limit = $cond->getLimit();
		$limit = ' LIMIT ' . $limit;
		$query = 'SELECT * FROM `settings`' . $where . ' ' . $group_by . ' ' . $order . ' ' . $limit;
		$data = Database::sql2array($query);
		
		$this->data['settings'] = $data;
		$this->data['settings']['title'] = 'Настройки';
		$this->data['settings']['count'] = $count;
		$this->data['conditions'] = $cond->getConditions();
	}

}