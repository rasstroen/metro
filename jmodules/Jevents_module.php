<?php

class Jevents_module extends JBaseModule {

	function process() {
		switch ($_POST['action']) {

			case 'getLikes':
				$this->getLikes();
				break;
			case 'likeEvent':
				$this->likeEvent(1);
				break;
			case 'unlikeEvent':
				$this->likeEvent(0);
				break;
		}
	}

	function getLikes() {
		global $current_user;
		$current_user = new CurrentUser();
		if (!$current_user->id)
			return;
		$ids = isset($_POST['wall'])?$_POST['wall']:array();
		$realids = array();
		foreach ($ids as $id) {
			$realids[$id] = $id;
		}
		$likes = MongoDatabase::getEventsLikes(array_keys($realids));
		foreach ($likes as $id => $likesArray) {
			if (isset($likesArray[$current_user->id]))
				$likes[$id]['can'] = 1;
			else
				$likes[$id]['can'] = 0;
		}
		$this->data['likes'] = $likes;
	}

	function likeEvent($like = 1) {
		global $current_user;
		$current_user = new CurrentUser();
		if (!$current_user->id)
			return;
		$id = $_POST['id'];
		if ($like)
			$res = MongoDatabase::eventLike($id, $current_user->id);
		else
			$res = MongoDatabase::eventUnlike($id, $current_user->id);
		$this->data['result'] = $res;

		$event_owner = $res['event_owner'];
		if ($res && $like && $id && ($event_owner != $current_user->id)) {
			// перетаскиваем запись к себе на стену
			$followerIds = $current_user->getFollowers();
			$followerIds[$current_user->id] = $current_user->id;
			MongoDatabase::pushEvents($current_user->id, $followerIds, $id, time(), $event_owner);
		}

		if (!$like && $id && ($event_owner != $current_user->id)) {
			// разонравилась. удаляем эту запись с моей стены
			$followerIds = $current_user->getFollowers();
			$followerIds[$current_user->id] = $current_user->id;
			MongoDatabase::removeWallItem(array_keys($followerIds), $id, $event_owner);
		}
	}

}