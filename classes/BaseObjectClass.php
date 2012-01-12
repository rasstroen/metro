<?php

class BaseObjectClass {

	public $exists = null;
	public $fieldsMap = array();
	public $id;

	function _show() {
		throw new Exception('BaseObjectClass::_show must be implemeted');
	}

	function _create($data, $tableName) {
		$q = array();
		$this->dropCache();
		foreach ($data as $field => $value) {
			if (isset($this->fieldsMap[$field])) {
				$q[] = '`' . $field . '`=' . Database::escape($value);
			}
		}
		if (count($q)) {
			Database::query('INSERT INTO `' . $tableName . '` SET ' . implode(',', $q));
			return $lid = Database::lastInsertId();
		}
	}

	function _update($data, $tableName) {
		unset($data['id']);
		$q = array();
		$this->dropCache();
		foreach ($data as $field => $value) {
			if (isset($this->fieldsMap[$field])) {
				$q[] = '`' . $field . '`=' . Database::escape($value);
			}else
				throw new Exception('_update failed: illegal field #' . $field);
		}
		if (count($q)) {
			Database::query('UPDATE `' . $tableName . '` SET ' . implode(',', $q) . ' WHERE `id`=' . $this->id);
			return $lid = Database::lastInsertId();
		}
	}

}