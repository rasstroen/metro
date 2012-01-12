<?php

class MagazineLog extends BiberLog {

	private static $fields = array(
	    'title' => 1,
	    'ISBN' => 2,
	    'id_lang' => 3, //lang_code
	    'annotation' => 4,
	    'is_cover' => 5,
	);

	public static function addLog($changedData, $oldData , $id_magazine = false) {
		if(!$id_magazine)
			throw new Exception('id_magazine missed for log save');
		foreach ($changedData as $field => $newValue) {
			if (!isset(self::$fields[$field])) {
				throw new Exception('no field #' . $field . ' for Log');
			}
			if ($oldData[$field] != $newValue)
				self::setChangedField($field, $oldData[$field], $newValue);
		}
		if(count($changedData)) {
			self::setIdField('id_magazine', $id_magazine);
		}
	}

}