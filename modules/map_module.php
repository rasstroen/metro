<?php

class map_module extends BaseModule {

	function generateData() {
		global $current_user;

		switch ($this->action) {
			case 'show':
				switch ($this->mode) {
					case 'location':
						$this->getAllStations();
						break;
					default:
						break;
				}
				break;
			default:
				throw new Exception('no action #' . $this->action . ' for ' . $this->moduleName);
				break;
		}
	}
	
	function getAllStations(){
		$query = 'SELECT * FROM `metro_stations` WHERE enabled=1';
		$this->data['stations'] = Database::sql2array($query,'id');
	}

}