<?php

class ForumWriteModule extends BaseWriteModule {

	function write() {
		global $current_user;
		/* @var $current_user User */
		if (!$current_user->authorized)
			throw new Exception('Access Denied');

		$action = Request::post('action');
		switch ($action) {
			case 'new_thread':
				$this->newThread();
				break;
			case 'new_comment':
				$this->newComment();
				break;
		}
	}
	
	
	function newComment(){
		$pid = 0;
		$nid = 0;
		$uid = 0;
		$subject ='';
		$comment = '';
		$hostname = '';
		$timestamp = time();
		$status = 0;
		$format = 1;
		$thread = '';
		$name = '';
		
	}

	function newThread() {
		global $current_user;
		/* @var $current_user CurrentUser */
		$title = Request::post('title');
		$message = Request::post('message');
		$message = prepare_review($message);
		$forum_id = Request::post('tid');

		if (!$message || !$title)
			throw new Exception('fill all fields properly');

		if (!$forum_id)
			throw new Exception('illegal forum id');

		if ($current_user->can_throw('books_edit')) {
			$nid = $this->doNewThread($title, $message, $current_user->id, $forum_id, time());
			if ($nid) {
				@ob_end_clean();
				header('Location:' . Config::need('www_path') . '/forum/' . $forum_id . '/' . $nid);
			}
		}
	}

	function doNewThread($title, $message, $authorId, $forum_id, $time = false) {
		// starting transaction 
		Database::query('START TRANSACTION');
		if (!$time)
			$time = time();
		// inserting thread
		// node
		$tid = $forum_id; // forum id
		$vid = 0; // version id
		$nid = 0; // node id
		// node revisions
		$query = 'INSERT INTO `node_revisions` SET
			`nid`=0,
			`uid`=' . (int) $authorId . ',
			`title`=' . Database::escape($title) . ',
			`body`=' . Database::escape($message) . ',
			`teaser`=' . Database::escape($message) . ',
			`log`=\'\',
			`timestamp`=' . $time . ',
			`format`=1';
		Database::query($query);
		$vid = Database::lastInsertId();
		// node
		$query = 'INSERT INTO `node` SET 
			`vid`=' . $vid . ',
			`type`=\'forum\',
			`title`=' . Database::escape($title) . ',
			`uid`=' . (int) $authorId . ',
			`status`=1,
			`created`=' . (int) $time . ',
			`changed`=' . (int) $time . ',
			`comment`=2,
			`promote`=0,
			`language`=\'ru\',
			`moderate`=0,
			`sticky`=0';
		Database::query($query);
		$nid = Database::lastInsertId();
		// updating node_revisions
		$query = 'UPDATE `node_revisions` SET `nid`=' . $nid . ' WHERE `vid`=' . $vid;
		Database::query($query);
		// node_comment_statistics is empty
		// term_node
		$query = 'INSERT INTO `term_node` SET 
			`nid`=' . $nid . ',
			`tid`=' . $tid . ',
			`vid`=' . $vid;
		Database::query($query);
		// commit
		Database::query('COMMIT');
		return $nid;
	}

	function addComment() {
		
	}

}
