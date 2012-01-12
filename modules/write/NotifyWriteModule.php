<?php

class NotifyWriteModule extends BaseWriteModule {

	private $ruleNames = array(
	    'event_comment' => UserNotify::UN_EVENT_COMMENT,
	    'comment_answer' => UserNotify::UN_COMMENT_ANSWER,
	    'new_message' => UserNotify::UN_NEW_MESSAGE,
	    'new_friend' => UserNotify::UN_NEW_FRIEND,
	    'whats_new' => UserNotify::UN_WHATS_NEW,
	    //
	    'global_new_authors' => UserNotify::UN_G_NEW_AUTHORS,
	    'global_new_genres' => UserNotify::UN_G_NEW_GENRES,
	    'global_new_reviews' => UserNotify::UN_G_NEW_REVIEWS,
	    'global_objects_comments' => UserNotify::UN_G_OBJECTS_COMMENTS,
	);
	private $typeNames = array(
	    'email' => UserNotify::UNT_EMAIL,
	    'notify' => UserNotify::UNT_NOTIFY,
	);

	function write() {
		global $current_user;
		/* @var $current_user CurrentUser */

		$uid = isset(Request::$post['id']) ? Request::$post['id'] : $current_user->id;
		if (!$uid)
			throw new Exception('illegal user id');

		if ($current_user->id != $uid) {
			if ($current_user->getRole() >= User::ROLE_BIBER) {
				$editing_user = Users::getByIdsLoaded(array($uid));
				$editing_user = isset($editing_user[$uid]) ? $editing_user[$uid] : false;
			}
		}else
			$editing_user = $current_user;

		$current_user->can_throw('users_edit', $editing_user);


		foreach ($this->ruleNames as $name => $rule) {
			foreach ($this->typeNames as $typename => $type)
				if (isset(Request::$post[$name][$typename])) {
					$editing_user->setNotifyRule($rule, $type, true);
				} else {
					$editing_user->setNotifyRule($rule, $type, false);
				}
		}
		$editing_user->save();
	}

}