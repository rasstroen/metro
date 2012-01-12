<?php

class Jsearch_module extends JBaseModule {

	public $cache_time = 10; // seconds to store result

	function process() {
		global $current_user;
		$current_user = new CurrentUser();
		$this->data['success'] = 0;
		switch ($_POST['action']) {
			case 'search':
				$this->search();
				break;
		}
	}

	function ca() {
		global $current_user;
		$current_user = new CurrentUser();
		if (!$current_user->authorized)
			throw new Exception('au');
		return true;
	}

	function search() {
		global $current_user;
		$result = array();
		//$this->ca();
		$string = trim($_POST['s']);
		if ($string) {
			if ($this->cache_time && ($result = Cache::get('autocomp_' . $string))) {
				$this->data['cached'] = true;
			} else {
				$result = Search::getInstance()->searchAutoComplete($string);
				if ($this->cache_time)
					Cache::set('autocomp_' . $string, $result, $this->cache_time);
				$this->data['cached'] = false;
			}
		}
		$this->data['result'] = $result;
		$this->data['success'] = 1;
	}

}