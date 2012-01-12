<?php

class BookLog extends BiberLog {

	private static $fields = array(
	    'title' => 1,
	    'subtitle' => 2,
	    'ISBN' => 3,
	    'year' => 4,
	    'id_lang' => 5,
	    'description' => 6,
	    'table_of_contents' => 7,
	    'id2' => 9,
	    'relation_type' => 10,
	    'is_cover' => 11,
	    'id_person' => 12,
	    'person_role' => 13,
	    'id_genre' => 14,
	    'id_serie' => 15,
	    'id_file' => 16,
	    'filetype' => 17,
	    'is_duplicate' => 18,
	    'id_basket' => 19,
	    'new_relations' => 20,
	    'old_relations' => 21,
	    'deleted_relations' => 22,
	    'quality' => 23,
	    'id_book' => 24,
	    'id_file_author' => 25,
	    'filesize' => 26,
	    'id_main_file' => 27,
	    'id_rightholder' => 28,
	    'book_type' => 30,
	    'n' => 31,
	);

	public static function addLog($changedData, $oldData, $id_book = false) {
		if (!$id_book)
			throw new Exception('id_book missed for log save');
		foreach ($changedData as $field => $newValue) {
			if (!isset(self::$fields[$field])) {
				throw new Exception('no field #' . $field . ' for Log');
			}
			if ($oldData[$field] != $newValue) {
				self::setChangedField($field, $oldData[$field], $newValue);
			}
			if (in_array($field, array('id_file_author', 'filesize'))) {
				self::setChangedField($field, $oldData[$field], $newValue);
			}
		}
		if (count($changedData)) {
			self::setIdField('id_book', $id_book);
		}
	}

}