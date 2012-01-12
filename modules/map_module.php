<?php

class map_module extends BaseModule {

	function generateData() {
		global $current_user;

		switch ($this->action) {
			case 'show':
				switch ($this->mode) {
					case 'show_location':
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

}