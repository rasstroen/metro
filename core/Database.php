<?php

class Database {

	/** @property $instance PDO */
	private static $instance = false;

	public static function getInstance() {
		if (!self::$instance) {
			$dsn = self::getDsn();
			self::$instance = new PDO($dsn, Config::need('dbuser'), Config::need('dbpass'));
			self::query('SET NAMES utf8');
		}
		/* @var $se PDO */
		return self::$instance;
	}

	private function getDsn() {
		return 'mysql:dbname=' . Config::need('dbname') . ";host=" . Config::need('dbhost');
	}

	public static function query($query, $throwError = true) {
		Log::timingplus('query:' . $query);
		if (!$r = self::getInstance()->query($query, PDO::FETCH_ASSOC))
			if ($throwError)
				throw new Exception($query . ' ', Error::E_QUERY);
			else
				return false;
		Log::timingplus('query:' . $query);
		return $r;
	}

	public static function escape($s) {
		$s = self::getInstance()->quote($s);
		return $s;
	}

	public static function lastInsertId() {
		return self::getInstance()->lastInsertId();
	}

	public static function sql2array($query, $indexedFiled = false) {
		$out = array();
		$r = self::query($query);
		if (!$indexedFiled) {
			while ($row = $r->fetch()) {
				$out[] = $row;
			}
		} else {
			while ($row = $r->fetch()) {
				$out[$row[$indexedFiled]] = $row;
			}
		}
		return $out;
	}

	public static function sql2row($query, $fetch_type = PDO::FETCH_ASSOC) {
		return self::query($query)->fetch(PDO::FETCH_ASSOC);
	}

	public static function sql2single($query) {
		$r = self::sql2row($query, PDO::FETCH_NUM);
		if (is_array($r))
			return array_shift($r);
		return false;
	}

}
