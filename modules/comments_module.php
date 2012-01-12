<?php

// комменты 1 уровня для ocr
class comments_module extends BaseModule {

	function generateData() {
		global $current_user;
		/* @var $current_user CurrentUser */

		switch ($this->action) {
			case 'new':
				$this->getNew();
				break;
			case 'list':
				switch ($this->mode) {
					case 'book':
						$this->getBookContributionComments();
						break;
					case 'author':
						$this->getAuthorContributionComments();
						break;
					case 'serie':
						$this->getSerieContributionComments();
						break;
					default:
						$this->getBookContributionComments();
						break;
				}
				break;
		}
	}

	function getNew() {
		$id_book = isset($this->params['book_id']) ? (int) $this->params['book_id'] : false;
		$id_author = isset($this->params['author_id']) ? (int) $this->params['author_id'] : false;
		$id_serie = isset($this->params['serie_id']) ? (int) $this->params['serie_id'] : false;
		$id = (int)max($id_author,$id_book,$id_serie);
		if (!$id)
			return;

		$this->data['comment'] = array(
		    'id' => $id,
		    'type' => $this->params['type'],
		);
	}

	function getAuthorContributionComments() {
		global $current_user;
		/* @var $current_user CurrentUser */

		$id_author = isset($this->params['author_id']) ? (int) $this->params['author_id'] : false;
		if (!$id_author)
			throw new Exception('author  not exists');
		$author = Persons::getInstance()->getByIdLoaded($id_author);
		/* @var $author Person */
		if (!$author->exists)
			throw new Exception('author #' . $author->id . ' not exists');


		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 20;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
		$cond->setPaging(1000, $per_page, $pagingName);
		$limit = $cond->getMongoLimit();
		list($comments, $count) = MongoDatabase::getAuthorComments($id_author, $per_page, $limit);
		
		$uids = array();
		$comments['comments'] = isset($comments['comments']) ? $comments['comments'] : array();
		foreach ($comments['comments'] as &$comment) {
			$comment['commenter_id'] = $comment['user_id'];
			$comment['type'] = 'author';
			$comment['time'] = date('Y/m/d H:i:s', $comment['time']);
			$uids[$comment['user_id']] = $comment['user_id'];
		}
		$cond = new Conditions();
		$cond->setPaging($count, $per_page, $pagingName);
		$this->data['conditions'] = $cond->getConditions();

		$this->data['comments'] = isset($comments['comments']) ? $comments['comments'] : array();
		$this->data['comments']['title'] = 'Обсуждение автора ' . $author->getName() . '';
		$this->data['comments']['count'] = $count;

		$this->data['users'] = $this->getCommentsUsers($uids);
	}

	function getSerieContributionComments() {
		global $current_user;
		/* @var $current_user CurrentUser */

		$id_serie = isset($this->params['serie_id']) ? (int) $this->params['serie_id'] : false;
		if (!$id_serie)
			return;

		$data = Database::sql2row('SELECT * FROM `series` WHERE `id`=' . $id_serie);
		if (!count($data))
			throw new Exception('serie #' . $id_serie . ' not exists');

		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 20;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
		$cond->setPaging(1000, $per_page, $pagingName);
		$limit = $cond->getMongoLimit();
		list($comments, $count) = MongoDatabase::getSerieComments($id_serie, $per_page, $limit);
		$uids = array();
		$comments['comments'] = isset($comments['comments']) ? $comments['comments'] : array();
		foreach ($comments['comments'] as &$comment) {
			$comment['commenter_id'] = $comment['user_id'];
			$comment['type'] = 'serie';
			$comment['time'] = date('Y/m/d H:i:s', $comment['time']);
			$uids[$comment['user_id']] = $comment['user_id'];
		}
		$cond = new Conditions();
		$cond->setPaging($count, $per_page, $pagingName);
		$this->data['conditions'] = $cond->getConditions();

		$this->data['comments'] = isset($comments['comments']) ? $comments['comments'] : array();
		$this->data['comments']['title'] = 'Обсуждение серии «' . $data['title'] . '»';
		$this->data['comments']['count'] = $count;

		$this->data['users'] = $this->getCommentsUsers($uids);
	}

	function getBookContributionComments() {
		global $current_user;
		/* @var $current_user CurrentUser */

		$id_book = isset($this->params['book_id']) ? (int) $this->params['book_id'] : false;
		if (!$id_book)
			return;
		$book = Books::getInstance()->getByIdLoaded($id_book);
		/* @var $book Book */
		if (!$book->exists)
			throw new Exception('book #' . $book->id . ' not exists');


		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 20;
		$pagingName = isset($this->params['paging_parameter_name']) ? $this->params['paging_parameter_name'] : 'p';
		$cond->setPaging(1000, $per_page, $pagingName);
		$limit = $cond->getMongoLimit();
		list($comments, $count) = MongoDatabase::getBookComments($id_book, $per_page, $limit);
		$uids = array();
		$comments['comments'] = isset($comments['comments']) ? $comments['comments'] : array();
		foreach ($comments['comments'] as &$comment) {
			$comment['commenter_id'] = $comment['user_id'];
			$comment['type'] = 'book';
			$comment['time'] = date('Y/m/d H:i:s', $comment['time']);
			$uids[$comment['user_id']] = $comment['user_id'];
		}
		$cond = new Conditions();
		$cond->setPaging($count, $per_page, $pagingName);
		$this->data['conditions'] = $cond->getConditions();

		$this->data['comments'] = isset($comments['comments']) ? $comments['comments'] : array();
		$this->data['comments']['title'] = 'Обсуждение книги «' . $book->getTitle(true) . '»';
		$this->data['comments']['count'] = $count;

		$this->data['users'] = $this->getCommentsUsers($uids);
	}

	function getCommentsUsers($ids) {
		$users = Users::getByIdsLoaded($ids);
		$out = array();
		/* @var $user User */
		if (is_array($users))
			foreach ($users as $user) {
				$out[] = $user->getListData();
			}
		return $out;
	}

}