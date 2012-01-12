<?php

class Jbooks_module extends JBaseModule {

	function process() {
		switch (trim($_POST['action'])) {
			case 'add_to_shelf':
				$this->add_to_shelf();
				break;
			case 'checkInBookshelf':
			case 'check_in_shelf':
				$this->check_in_shelf();
				break;
			case 'del_from_shelf':
				$this->del_from_shelf();
				break;
			case 'del_author':
				$this->del_author();
				break;
			case 'add_author':
				$this->add_author();
				break;
			case 'del_genre':
				$this->del_genre();
				break;
			case 'add_genre':
				$this->add_genre();
				break;
			case 'del_serie':
				$this->del_serie();
				break;
			case 'add_serie':
				$this->add_serie();
				break;
			case 'add_relation':
				$this->add_relation();
				break;
			case 'del_relation':
				$this->del_relation();
				break;
			case 'default_file':
				$this->set_default_file();
				break;
		}
	}
	
	

	function set_default_file() {
		global $current_user;
		$this->ca();
		$this->data['success'] = 0;
		$id_book = isset($_POST['id_book']) ? (int) $_POST['id_book'] : false;
		$id_file = isset($_POST['id_file']) ? (int) $_POST['id_file'] : false;
		if ($id_book && $id_file) {
			$book = Books::getInstance()->getByIdLoaded($id_book);
			/* @var $book Book */
			if ($files = $book->getFiles()) {
				if (count($files) < 2) {
					$this->data['error'] = 'Мало файлов';
					return false;
				}
				if (!isset($files[$id_file])) {
					$this->data['error'] = 'Нет файла #' . $id_file;
					return false;
				}
				$query = 'UPDATE `book` SET `id_main_file`=' . $id_file . ' WHERE `id`=' . $book->id;
				Database::query($query);
				Books::getInstance()->dropCache($book->id);
				$this->data['success'] = 1;
			}
		}
		$search = Search::getInstance();
		/* @var $search Search */
		$search->updateBook(new Book($id_book));
	}

	function ca() {
		global $current_user;
		$current_user = new CurrentUser();
		if (!$current_user->authorized)
			throw new Exception('au');
		return true;
	}

	function add_relation() {
		global $current_user;
		$this->ca();
		$id1 = isset($_POST['id']) ? (int) $_POST['id'] : false;
		$id2 = isset($_POST['book_id']) ? (int) $_POST['book_id'] : false;
		$relation_type = isset($_POST['relation_type']) ? (int) $_POST['relation_type'] : false;
		$book1 = Books::getInstance()->getByIdLoaded($id1);
		$book2 = Books::getInstance()->getByIdLoaded($id2);
		/* @var $book2 Book */
		if (!$book2->loaded || !$book1->loaded) {
			$this->data['error'] = 'Нет такой книги';
			return false;
		}
		if (!$id1 || !$id2 || !$relation_type)
			throw new Exception('id or item_id or relation_type missed');
		if (BookRelations::addRelation($id1, $id2, $relation_type)) {
			$this->data['success'] = 1;
			$this->data['item_id'] = $id2;
			$this->data['relation_type'] = BookRelations::$relation_types[$relation_type];
			/* @var $book2 Book */
			$this->data['title'] = $book2->getTitle();
		} else {
			$this->data['success'] = 0;
			$this->data['error'] = 'ошибочка: ' . BookRelations::getLastError();
		}
		$search = Search::getInstance();
		/* @var $search Search */
		$search->updateBook(new Book($id1));
		$search->updateBook(new Book($id2));
	}

	function del_relation() {
		global $current_user;
		$this->ca();
		$id1 = isset($_POST['id']) ? (int) $_POST['id'] : false;
		$id2 = isset($_POST['item_id']) ? (int) $_POST['item_id'] : false;
		if (!$id1 || !$id2)
			throw new Exception('id or item_id missed');
		if (BookRelations::delRelation($id1, $id2)) {
			$this->data['success'] = 1;
			$this->data['item_id'] = $id2;
		} else {
			$this->data['success'] = 0;
			$this->data['error'] = 'ошибочка:' . BookRelations::getLastError();
		}
		$search = Search::getInstance();
		/* @var $search Search */
		$search->updateBook(new Book($id1));
		$search->updateBook(new Book($id2));
	}

	function add_serie() {
		global $current_user;
		$this->ca();
		$id_serie = (int) $_POST['id_serie'];
		$id_book = (int) $_POST['id'];
		if ($id_serie && $id_book) {
			$query = 'SELECT `id`,`name`,`title`,`id_parent` FROM `series` WHERE id=' . $id_serie;
			$result = Database::sql2row($query);
			if (!isset($result['id'])) {
				$this->data['success'] = 0;
				$this->data['error'] = 'Нет такой серии';
				return;
			}
			$query = 'INSERT INTO `book_series` SET `id_book`=' . $id_book . ' , `id_series`=' . $id_serie . ',`position`=0';
			$r = Database::query($query, false);
			if ($r) {
				$this->data['success'] = 1;
				$this->data['item_id'] = $id_serie;
				$this->data['name'] = $result['name'];
				$this->data['title'] = $result['title'];
				BookLog::addLog(array('id_serie' => $id_serie, 'id_book' => $id_book), array('id_serie' => 0, 'id_book' => 0), $id_book);
				$log_id = BookLog::saveLog($id_book, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookEditSerie);
				BookLog::saveLogLink($log_id, $id_serie, BookLog::TargetType_serie, $current_user->id, BiberLog::BiberLogType_bookEditSerie, $copy = 1);
				// update series book_count
				$query = 'UPDATE `series` SET `books_count`=`books_count`+1 WHERE `id`=' . $result['id'] . ' OR `id`=' . $result['id_parent'];
				Database::query($query);
				$search = Search::getInstance();
				/* @var $search Search */
				$search->updateBook(new Book($id_book));
			} else
				$this->data['error'] = 'Серия уже есть в списке серий';
			return;
		}
		$this->data['item_id'] = $id_serie;
		$this->data['success'] = 0;
	}

	function del_serie() {
		global $current_user;
		$this->ca();
		$id_serie = (int) $_POST['item_id'];
		$id_book = (int) $_POST['id'];
		$query = 'SELECT `id`,`name`,`title`,`id_parent` FROM `series` WHERE id=' . $id_serie;
		$result = Database::sql2row($query);
		if (!isset($result['id'])) {
			$this->data['success'] = 0;
			$this->data['error'] = 'Нет такой серии';
			return;
		}
		if ($id_serie && $id_book) {
			$query = 'DELETE FROM `book_series` WHERE `id_book`=' . $id_book . ' AND `id_series`=' . $id_serie;
			Database::query($query);
			$this->data['success'] = 1;
			$this->data['item_id'] = $id_serie;
			BookLog::addLog(array('id_serie' => 0, 'id_book' => 0), array('id_serie' => $id_serie, 'id_book' => $id_book), $id_book);
			BookLog::saveLog($id_book, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookEditSerie);
			BookLog::saveLog($id_serie, BookLog::TargetType_serie, $current_user->id, BiberLog::BiberLogType_bookEditSerie, $copy = 1);
			// update series book_count
			$query = 'UPDATE `series` SET `books_count`=`books_count`-1 WHERE `id`=' . $result['id'] . ' OR `id`=' . $result['id_parent'];
			Database::query($query);
			$search = Search::getInstance();
			/* @var $search Search */
			$search->updateBook(new Book($id_book));
			return;
		}
		$this->data['item_id'] = $id_genre;
		$this->data['success'] = 0;
	}

	function add_genre() {
		global $current_user;
		$this->ca();
		if (is_numeric($_POST['id_genre'])) {
			$id_genre = (int) $_POST['id_genre'];
		} else {
			$id_genre = Database::sql2single('SELECT `id` FROM `genre` WHERE `name`=' . Database::escape($_POST['id_genre']));
		}

		$id_book = (int) $_POST['id'];
		if ($id_genre && $id_book) {
			$query = 'SELECT `id`,`name`,`title` FROM `genre` WHERE id=' . $id_genre . ' AND `id_parent`>0';
			$result = Database::sql2row($query);
			if (!isset($result['id'])) {
				$this->data['success'] = 0;
				$this->data['error'] = 'Нет такого жанра';
				return;
			}
			$query = 'INSERT INTO `book_genre` SET `id_book`=' . $id_book . ' , `id_genre`=' . $id_genre;
			$r = Database::query($query, false);
			if ($r) {
				$this->data['success'] = 1;
				$this->data['item_id'] = $id_genre;
				$this->data['name'] = $result['name'];
				$this->data['title'] = $result['title'];
				BookLog::addLog(array('id_genre' => $id_genre), array('id_genre' => 0), $id_book);
				BookLog::saveLog($id_book, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookEditGenre);
				$search = Search::getInstance();
				/* @var $search Search */
				$search->updateBook(new Book($id_book));
				Notify::notifyGenreNewBook($id_genre, $id_book);
			} else
				$this->data['error'] = 'Жанр уже есть в списке жанров';
			return;
		}
		$this->data['item_id'] = $id_genre;
		$this->data['success'] = 0;
	}

	function del_genre() {
		global $current_user;
		$this->ca();
		$id_genre = (int) $_POST['item_id'];
		$id_book = (int) $_POST['id'];
		if ($id_genre && $id_book) {
			$query = 'DELETE FROM `book_genre` WHERE `id_book`=' . $id_book . ' AND `id_genre`=' . $id_genre;
			Database::query($query);
			$this->data['success'] = 1;
			$this->data['item_id'] = $id_genre;
			BookLog::addLog(array('id_genre' => 0), array('id_genre' => $id_genre), $id_book);
			BookLog::saveLog($id_book, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookEditGenre);
			$search = Search::getInstance();
			/* @var $search Search */
			$search->updateBook(new Book($id_book));
			return;
		}
		$this->data['item_id'] = $id_genre;
		$this->data['success'] = 0;
	}

	function add_author() {
		global $current_user;
		$this->ca();
		$id_person = (int) $_POST['id_author'];
		$id_book = (int) $_POST['id'];
		$id_role = (int) $_POST['id_role'];
		if ($id_role && $id_person && $id_book) {
			$persons = Persons::getInstance()->getByIdsLoaded(array($id_person));
			if (!isset($persons[$id_person]) || !$persons[$id_person]->id) {
				$this->data['item_id'] = $id_person;
				$this->data['success'] = 0;
				$this->data['error'] = 'Нет такого автора';
				return;
			}
			$query = 'INSERT INTO `book_persons` SET `id_book`=' . $id_book . ' , `id_person`=' . $id_person . ', `person_role`=' . $id_role;
			$r = Database::query($query, false);
			if ($r) {
				$this->data['success'] = 1;
				$this->data['item_id'] = $id_person;
				$this->data['name'] = $persons[$id_person]->getName();
				$this->data['role'] = Config::$person_roles[$id_role];
				BookLog::addLog(array('id_person' => $id_person, 'person_role' => $id_role), array('id_person' => 0, 'person_role' => 0), $id_book);
				$log_id = BookLog::saveLog($id_book, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookEditPerson);
				BookLog::saveLogLink($log_id, $id_person, BookLog::TargetType_person, $current_user->id, BiberLog::BiberLogType_bookEditPerson, $copy = 1);
				$search = Search::getInstance();
				/* @var $search Search */
				$search->updateBook(new Book($id_book));
				Notify::notifyAuthorNewBook($id_person, $id_book);
			}
			else
				$this->data['error'] = 'Автор уже есть в списке авторов';
			return;
		}
		$this->data['item_id'] = $id_person;
		$this->data['success'] = 0;
	}

	function del_author() {
		global $current_user;
		$this->ca();
		$id_person = (int) $_POST['item_id'];
		$id_book = (int) $_POST['id'];
		$query = 'SELECT `person_role` FROM `book_persons` WHERE `id_book`=' . $id_book . ' AND `id_person`=' . $id_person;
		$old_role = Database::sql2single($query);
		if ($old_role) {
			if ($id_person && $id_book) {
				$query = 'DELETE FROM `book_persons` WHERE `id_book`=' . $id_book . ' AND `id_person`=' . $id_person;
				Database::query($query);
				$this->data['success'] = 1;
				$this->data['item_id'] = $id_person;
				BookLog::addLog(array('id_person' => 0, 'person_role' => 0), array('id_person' => $id_person, 'person_role' => $old_role), $id_book);
				BookLog::saveLog($id_book, BookLog::TargetType_book, $current_user->id, BiberLog::BiberLogType_bookEditPerson);
				$search = Search::getInstance();
				/* @var $search Search */
				$search->updateBook(new Book($id_book));
				return;
			}
		}else
			$this->data['error'] = 'Нет такого автора';
		$this->data['item_id'] = $id_person;
		$this->data['success'] = 0;
	}

	function del_from_shelf() {
		global $current_user;
		$this->ca();
		$this->data['success'] = 0;
		$bookId = max(0, (int) $_POST['id']);
		if ($bookId) {
			if ($current_user->authorized) {
				if ($shelf_id = $current_user->checkInBookshelf($bookId)) {
					$this->data['success'] = 1;
					$query = 'DELETE FROM `users_bookshelf` WHERE `id_user`=' . $current_user->id . ' AND `id_book`=' . $bookId;
					$this->data['q'] = $query;
					Database::query($query);
					$current_user->reloadPolkaCounters();
					$current_user->save();
				}else
					$this->data['success'] = 0;
			}
		}
	}

	function check_in_shelf() {
		global $current_user;
		$this->ca();
		$bookId = max(0, (int) $_POST['id']);
		$this->data['in'] = 1;
		if ($bookId) {
			if ($current_user->authorized) {
				if ($shelf_id = $current_user->checkInBookshelf($bookId)) {
					$this->data['shelf_id'] = $shelf_id;
				}else
					$this->data['shelf_id'] = 0;
			}
		}
		$this->data['success'] = 1;
	}

	function add_to_shelf() {
		global $current_user;
		/*@var $current_user CurrentUser*/
		$this->ca();
		$bookId = max(0, (int) $_POST['id']);
		$shelf = max(0, (int) $_POST['shelf_id']);
		$this->data['shelf_id'] = $shelf;
		$this->data['id'] = $bookId;
		if ($bookId && $shelf) {
			if ($current_user->authorized) {
				$book = new Book($bookId);
				if ($book->getTitle()) {
					$this->data['title'] = trim(implode(' ', $book->getTitle()));
					$current_user->AddBookShelf($book->id, $shelf);
					$this->data['success'] = 1;
					$current_user->reloadPolkaCounters();
					$current_user->save();
				}
			}
		}
	}

}