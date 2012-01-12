<?php

class JBaseModule{
	public $data;
	
	function __construct() {
		$this->process();
	}
	
	function process(){
		throw new Exception('must be implemented');
	}
	
	function getJson(){
		if(is_array($this->data) || is_object($this->data))
		return json_encode($this->data);
	}
}