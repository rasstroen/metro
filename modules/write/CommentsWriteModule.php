<?php

// пишем комментарии ocr
class CommentsWriteModule extends BaseWriteModule {

	function write() {
		global $current_user;
		if (!$current_user->authorized)
			throw new Exception('Access Denied');
		$this->addComment();
	}

	function addComment() {
		global $current_user;
		if (!$current_user->id)
			return;
		$comment = isset(Request::$post['comment']) ? Request::$post['comment'] : false;
		$comment = trim(prepare_review($comment, ''));
		if (!$comment)
			throw new Exception('comment body expected');

		$id = (int) Request::$post['id'];
		if (!$id)
			throw new Exception('target id missed');

		switch (Request::$post['type']) {
			case 'serie':
				$type = BiberLog::TargetType_serie;
				break;
			case 'author':
				$type = BiberLog::TargetType_person;
				break;
			case 'book':
				$type = BiberLog::TargetType_book;
				break;
		}

		if ($id) {
			MongoDatabase::addSimpleComment($type, $id, $current_user->id, $comment);
		}
	}

}
