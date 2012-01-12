<?php

class ReviewsWriteModule extends BaseWriteModule {

	function write() {
		global $current_user;
		if (!$current_user->authorized)
			throw new Exception('Access Denied');

		$data = array(
		    'target_id' => max(0, (int) Request::$post['target_id']),
		    'target_type' => max(0, (int) Request::$post['target_type']),
		    'comment' => prepare_review(Request::$post['annotation']),
		    'rate' => min(6, max(0, (int) Request::$post['rate'])) + 1,
		);


		$event = new Event();
		$time = time();

		//$old = MongoDatabase::findReviewEventData($current_user->id, $data['target_id']);
		//$with_review = (isset($old['body']) && $old['body']) ? 1 : 0;
		$with_review = 0;
		// upsert rate into database
		if ($data['rate']) {
			$query = 'INSERT INTO `book_rate` SET `with_review`=' . $with_review . ', `id_book`=' . $data['target_id'] . ',`id_user`=' . $current_user->id . ',`rate`=' . ($data['rate'] - 1) . ',`time`=' . $time . ' ON DUPLICATE KEY UPDATE
				`rate`=' . ($data['rate'] - 1) . ',`time`=' . $time . ',`with_review`=' . $with_review . '';
			Database::query($query);
			//recalculating rate
			$query = 'SELECT COUNT(1) as cnt, SUM(`rate`) as rate FROM `book_rate` WHERE `id_book`=' . $data['target_id'];
			$res = Database::sql2row($query);
			$book_mark = round($res['rate'] / $res['cnt'] * 10);
			$query = 'UPDATE `book` SET `mark`=' . $book_mark . ' WHERE `id`=' . $data['target_id'];
			Database::query($query);
		}
		// insert data into mongo
		if (!$data['comment']) {
			unset($data['comment']);
		}

		if (isset($data['comment']) && $data['comment']) {
			$event->event_BookReviewAdd($current_user->id, $data);
			Notify::notifyNewBookReview($data['target_id'], $current_user->id);
		} else
		if ($data['rate'] > 1)
			$event->event_BookRateAdd($current_user->id, $data);

		$event->push();
	}

}