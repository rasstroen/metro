<?php

class BaseWriteModule {

	private static $passParameters = array();

	function write() {
		$this->checkWritePermisssions();
		$this->process();
		$this->dropCaches();
	}

	protected function checkWritePermisssions() {
		
	}

	protected function process() {
		
	}

	protected function dropCaches() {
		
	}

	function setWriteParameter($moduleName, $name, $value) {
		PostWrite::setWriteParameter($moduleName, $name, $value);
	}

}