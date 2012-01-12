<?php

class Persons extends Collection {

	public $className = 'Person';
	public $tableName = 'persons';
	public $itemName = 'author';
	public $itemsName = 'authors';
         public $cache_time = 6;

	public static function getInstance() {
		if (!self::$persons_instance) {
			self::$persons_instance = new Persons();
		}
		return self::$persons_instance;
	}

}