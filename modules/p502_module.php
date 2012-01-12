<?php

class p502_module extends BaseModule {

	function generateData() {
		global $errorString, $errorCode, $errorDescription;
		$this->data['error'] = $errorString;
		$this->data['error_code'] = $errorCode;

		$this->data['error_description'] = $errorDescription;
		if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
			$this->data['return_path'] = $_SERVER['HTTP_REFERER'];
		}
	}

}