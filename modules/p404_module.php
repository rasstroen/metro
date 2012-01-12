<?php

class p404_module extends BaseModule {

	function generateData() {
		Request::initialize(true);
		$requested_url = $_SERVER['REQUEST_URI'];
		$this->data['error'] = 'Такой страницы не существует';
		$this->data['error_code'] = 404;
		$this->data['error_description'] = 'Страницы ' . urldecode($requested_url) . ' не существует';
		
		$realPath = Request::getRealPath();
		if($realPath){
			$this->data['error_description'].='<br/>';
			$this->data['error_description'].='Но есть <a href="'.$realPath.'">страница</a>, на которую вы вероятно хотели попасть.';
			//$this->data['error_description'].='<br/>'.Request::$path_history;
		}
		
		
		header("HTTP/1.1 404 Not Found", null, 404);
	}

}