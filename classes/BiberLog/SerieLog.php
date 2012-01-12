<?php

class SerieLog extends BiberLog {

	private static $fields = array(
	    'title' => 1,
	    'description' => 2,
	    'id_parent' => 3,
	);

	public static function addGlueLog($main_sid, $slave_sid, $books, $changed_parents) {
		self::setIdField('id_serie', $main_sid);
		self::setChangedField('slave', 0, $slave_sid);
		self::setChangedField('master', 0, $main_sid);
		self::setChangedField('books', array(), $books);
		self::setChangedField('subseries', array(), $changed_parents);
	}

	public static function addLog($changedData, $oldData, $id_serie = false) {
		if (!$id_serie)
			throw new Exception('id_serie missed for log save');
		foreach ($changedData as $field => $newValue) {
			if (!isset(self::$fields[$field])) {
				throw new Exception('no field #' . $field . ' for Log');
			}
			if ($oldData[$field] != $newValue) {
				self::setChangedField($field, $oldData[$field], $newValue);
			}
		}
		if (count($changedData)) {
			self::setIdField('id_serie', $id_serie);
		}
	}

}