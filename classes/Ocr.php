<?php

class Ocr {
	const STATUS_NEW = 0;
	const STATE_NEW = 0;


	const STATE_APPROVED = 4;

	public static $statuses = array(
	    0 => array('id' => 0, 'title' => '-', 'name' => 'new'),
	    1 => array('id' => 1, 'title' => 'достать', 'name' => 'get'),
	    2 => array('id' => 2, 'title' => 'сканировать', 'name' => 'scan'),
	    3 => array('id' => 3, 'title' => 'распознать', 'name' => 'recognize'),
	    4 => array('id' => 4, 'title' => 'вычитать', 'name' => 'read'),
	    5 => array('id' => 5, 'title' => 'сверстать', 'name' => 'make'),
	);
	public static $states = array(
	    0 => array('id' => 0, 'title' => '-', 'name' => 'new'),
	    1 => array('id' => 1, 'title' => 'могу', 'name' => 'can'),
	    2 => array('id' => 2, 'title' => 'делаю', 'name' => 'process'),
	    3 => array('id' => 3, 'title' => 'сделал', 'name' => 'done'),
	    self::STATE_APPROVED => array('id' => self::STATE_APPROVED, 'title' => 'подтверждено', 'name' => 'approved'),
	);
	public static $comments = array(
	    1 => array(
		1 => 'Может достать',
		2 => 'Достаёт',
		3 => 'Достал',
		4 => 'Попал в аллею славы за то, что достал',
	    ),
	    2 => array(
		1 => 'Может отсканировать',
		2 => 'Сканирует',
		3 => 'Отсканировал',
		4 => 'Попал в аллею славы за то, что отсканировал',
	    ),
	    3 => array(
		1 => 'Может распознать',
		2 => 'Распознаёт',
		3 => 'Распознал',
		4 => 'Попал в аллею славы за то, что распознал',
	    ),
	    4 => array(
		1 => 'Может вычитать',
		2 => 'Вычитывает',
		3 => 'Вычитал',
		4 => 'Попал в аллею славы за то, что вычитал',
	    ),
	    5 => array(
		1 => 'Может сверстать',
		2 => 'Верстает',
		3 => 'Сверстал',
		4 => 'Попал в аллею славы за то, что сверстал',
	    ),
	);

	public static function getMessagePart($status, $state) {
		$mes = false;
		if (isset(self::$comments[$status][$state]))
			return self::$comments[$status][$state];
		return $mes;
	}

	public static function afterBookCreate($id_book, $id_user) {
		$query = 'INSERT INTO `ocr` SET 
			`id_book`=' . $id_book . ',
			`id_user`=' . $id_user . ',
			`time`=' . time() . ',
			`flag`=0,
			`status`=' . self::STATUS_NEW . ',
			`state`=' . self::STATE_NEW;
		Database::query($query);
	}

	public static function setStatus($id_user, $id_book, $status, $state) {
		global $current_user;
		$book = Books::getInstance()->getByIdLoaded($id_book);
		/* @var $book Book */
		if ($book->getQuality() >= BOOK::BOOK_QUALITY_BEST)
			throw new Exception('book quality is best, you cant fix states');




		if (!isset(self::$statuses[$status]))
			throw new Exception('no status #' . $status);
		if (!isset(self::$states[$state]))
			throw new Exception('no status #' . $state);

		$can_comment = false;

		if ($state > 0) {
			$query = 'SELECT `time` FROM `ocr` WHERE  `id_book`=' . $id_book . ' AND `id_user`=' . $id_user . ' AND `status`=' . $status . ' AND `state`=' . $state;
			$last_time = Database::sql2single($query);
			if (time() - $last_time > 24 * 60 * 60) {
				$can_comment = true;
			}
		}

		if ($state == 0 && $status !== 0)
		// delete
			$query = 'DELETE FROM `ocr` WHERE  `id_book`=' . $id_book . ' AND `id_user`=' . $id_user . ' AND `status`=' . $status . '';

		else
		// upsert
			$query = 'INSERT INTO `ocr` SET `id_book`=' . $id_book . ', `id_user`=' . $id_user . ', `status`=' . $status . ',`state`=' . $state . ',`time`=' . time() . '
			ON DUPLICATE KEY UPDATE
			`time`=' . time() . ', `state`=' . $state;



		if (!Database::query($query, false))
			throw new Exception('Duplicating #book ' . $id_book . ' #status' . $status . ' #state' . $state);



		if ($state == 0)
			$comment = 'User ' . $current_user->id . ' drop status ' . $status . ' state ' . $state . ' user_id ' . $id_user;
		else
			$comment = 'User ' . $current_user->id . ' set status ' . $status . ' state ' . $state . ' user_id ' . $id_user;

		$comUser = Users::getById($id_user);
		/* @var $comUser User */
		if ($can_comment && ($part = self::getMessagePart($status, $state))) {
			$comment = mb_strtolower($part, 'UTF-8') . ' книгу';
			MongoDatabase::addSimpleComment(BiberLog::TargetType_book, $id_book, $id_user, $comment);
		}
	}

}