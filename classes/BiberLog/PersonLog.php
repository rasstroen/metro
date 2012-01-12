<?php

class PersonLog extends BiberLog {

	private static $fields = array(
	    'author_lang' => 1, //lang_code
	    'bio' => 2,
	    'first_name' => 3,
	    'middle_name' => 4,
	    'last_name' => 5,
	    'homepage' => 6,
	    'wiki_url' => 7,
	    'date_birth' => 8,
	    'date_death' => 9,
	    'is_p_duplicate' => 10,
	    'id_basket' => 11,
	    'new_relations' => 12,
	    'old_relations' => 13,
	    'deleted_relations' => 14,
	    'has_cover' => 15,
            'is_p_duplicate' => 16,
	    
	);

	public static function addLog($changedData, $oldData, $id_person = false) {
		if(!$id_person)
			throw new Exception('id_person missed for log save');
		foreach ($changedData as $field => $newValue) {
			if (!isset(self::$fields[$field])) {
				throw new Exception('no field #' . $field . ' for Log');
			}
			if ($oldData[$field] != $newValue)
				self::setChangedField($field, $oldData[$field], $newValue);
		}
		if(count($changedData)) {
			self::setIdField('id_person', $id_person);
		}
	}

}