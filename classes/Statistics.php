<?php

class Statistics {

	public static function setPartnerCookie($id_partner) {
		global $current_user;
		/* @var $current_user CurrentUser */
		if ($id_partner) {
			$query = 'SELECT `id` FROM `partners` WHERE `pid`=' . Database::escape($id_partner);
			$pid = Database::sql2single($query);
			if ($pid) {
				if ($current_user) {
					$time = Config::need('cookie_lifetime_partner', 5 * 60 * 60 * 24);
					$current_user->setCookie('partner_id', $pid, time() + $time);
				}
				header('Location: ' . Request::$url, true, 302);
			}
		}
	}

	/**
	 * сохраняем факт регистрации пользователя по реферальной ссылке
	 * 
	 * @param int $id_user
	 * @param string $referer
	 * @return type 
	 */
	public static function saveUserPartnerRegister() {
		global $current_user;
		/* @var $current_user CurrentUser */
		// если есть партнерская кука
		$pid = false;
		if (isset($_COOKIE['partner_id'])) {
			if ($_COOKIE['partner_id']) {
				$pid = (int) $_COOKIE['partner_id'];
			}
		}

		if (!$pid)
			$pid = 0; // без реферера
		$time = time();
		// сохраняем факт прихода юзера от партнера
		$query = 'INSERT INTO `stat_user_partner_referer` SET 
			`id_user`=' . $current_user->id . ',
			`time`=' . $time . ',
			`id_partner`=' . $pid;
		Database::query($query);
		return true;
	}

	/**
	 * сохраняем факт скачивания книги 
	 * @param type $variables 
	 */
	public function saveUserDownloads($user_id, $book_id, $time = false) {
		Database::query('START TRANSACTION');
		// for user
		$period = 24 * 60 * 60; // каждый день
		$time_normalized = $time ? $time : (floor(time() / $period) * $period);

		// а не качал ли эту книгу юзер уже?
		$query = 'SELECT COUNT(1) FROM  `stat_user_download` WHERE 
			`id_user`=' . $user_id . ' AND
			`id_book`=' . $book_id . ' AND
			`time`>=' . $time_normalized . '';
		$cnt = Database::sql2single($query);
		if ($cnt) {
			// already have
			return;
		}

		$query = 'INSERT IGNORE INTO `stat_user_download` SET 
			`id_user`=' . $user_id . ', 
			`id_book`=' . $book_id . ',
			`time`=' . $time_normalized;
		Database::query($query);


		// for book stat
		$query = 'INSERT INTO `stat_book_download` SET 
			`id_book`=' . $book_id . ',
			`count` = 1,
			`time`=' . $time_normalized . ' ON DUPLICATE KEY UPDATE
			`count` = `count`+1';
		Database::query($query);

		// updating book download_count
		$query = 'UPDATE `book` SET `download_count`=`download_count`+1 WHERE `id`=' . $book_id;
		Database::query($query);

		// genres
		$book = Books::getInstance()->getByIdLoaded($book_id);
		/* @var $book Book */
		$genres = $book->getGenres();
		if (count($genres)) {
			foreach ($genres as $gid => $data) {
				$query = 'INSERT INTO `stat_genre_download` SET 
					`id_genre`=' . $gid . ',
					`count`=1,
					`time`=' . $time_normalized . ' ON DUPLICATE KEY UPDATE
					`count` = `count`+1';
				Database::query($query);
			}
		}
		// authors
		$authors = $book->getAuthors();
		if (count($authors)) {
			foreach ($authors as $id => $data) {
				$query = 'INSERT INTO `stat_author_download` SET 
					`id_author`=' . $data['id'] . ',
					`count`=1,
					`time`=' . $time_normalized . ' ON DUPLICATE KEY UPDATE
					`count` = `count`+1';
				Database::query($query);
			}
		}
		Database::query('COMMIT');
	}

}