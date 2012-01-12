<?php

// пишем комментарии
class EventsWriteModule extends BaseWriteModule {

	function write() {
		global $current_user;
		if (!$current_user->authorized)
			throw new Exception('Access Denied');
		switch (Request::$post['action']) {
			case 'comment_new':
				$this->addComment();
				break;
			case 'post_new':
				$this->addPost();
				break;
		}
	}

	function addPost() {
		global $current_user;
		if (!$current_user->id)
			return;
		$body = isset(Request::$post['body']) ? Request::$post['body'] : false;
		$subject = isset(Request::$post['subject']) ? Request::$post['subject'] : false;
		$body = prepare_review($body);
		$subject = prepare_review($subject, '');
		if (!$body)
			throw new Exception('post body missed');


		if ($body) {
			$event = new Event();
			$event->event_PostAdd($current_user->id, $body, $subject);
			$event->push();
			ob_end_clean();
			header('Location: ' . Config::need('www_path') . '/me/wall/self');
			exit;
		}
	}

	function addComment() {
		global $current_user;
		$subscribe = false;
		if (isset(Request::$post['subscribe']))
			if (Request::$post['subscribe'])
				$subscribe = true;

		if (!$current_user->id)
			return;
		$comment = isset(Request::$post['comment']) ? Request::$post['comment'] : false;
		$comment = trim(prepare_review($comment, '<em><i><strong><b><u><s>'));
		if (!$comment)
			throw new Exception('comment body expected');

		$post_id = Request::$post['id'];
		$data = array();
		if ($post_id) {
			if (isset(Request::$post['comment_id']) && ($comment_id = Request::$post['comment_id'])) {
				$data = MongoDatabase::addEventComment($post_id, $current_user->id, $comment, $comment_id);
				if ($data)
					Notify::notifyEventCommentAnswer($data['commenter_id'], $post_id, $data['comment_id']);
			} else {
				$data = MongoDatabase::addEventComment($post_id, $current_user->id, $comment);
				if ($data)
					Notify::notifyEventComment($data['user_id'], $post_id, $data['comment_id']);
			}
		}

		if ($data) {
			if ($subscribe) {
				// на своё и так и так подписаны
				if ($data['post']['user_id'] != $current_user->id) {
					$query = 'SELECT `id` FROM `events` WHERE `mongoid`=' . Database::escape($post_id);
					$intid = Database::sql2single($query);
					if ($intid) {
						/* @var $current_user User */
						$current_user->setNotifyRule(UserNotify::UN_COMMENT_ANSWER, UserNotify::UNT_NOTIFY);
						$current_user->save();
						Notify::notifySubscribe($current_user->id, $intid);
					}
				}
			}
		}
	}

}