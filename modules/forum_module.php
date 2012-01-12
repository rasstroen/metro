<?php

// модуль отвечает за отображение баннеров
class forum_module extends BaseModule {

	function generateData() {
		$params = $this->params;

		$this->forum_id = isset($params['forum_id']) ? $params['forum_id'] : 0;
		$this->theme_id = isset($params['theme_id']) ? $params['theme_id'] : 0;

		switch ($this->action) {
			case 'list':
				if (!$this->forum_id && !$this->theme_id)
					$this->getForumList();
				if ($this->forum_id && !$this->theme_id)
					$this->getThemesList();
				break;
			case 'show':
				$this->getTheme();
				break;
			case 'new':
				switch ($this->mode) {
					case 'theme':
						$this->newTheme();
						break;
					default:
						throw new Exception('no mode #' . $this->mode . ' for action #' . $this->action);
						break;
				}
				break;
			default:
				throw new Exception('no action #' . $this->action . ' for ' . $this->moduleName);
				break;
		}
	}

	function newTheme() {
		$this->data['forum']['tid'] = $this->forum_id;
	}

	function getTheme() {
		if (!$this->theme_id)
			return;

		$query = 'SELECT uid as user_id,title,body FROM `node_revisions` WHERE `nid`=' . $this->theme_id . ' LIMIT 1';
		$theme = Database::sql2row($query);
		if (!$theme)
			throw new Exception('Мы проебали эту тему форума');

		$theme['body'] = _bbcode_filter_process($theme['body']);
		$this->data['theme'] = $theme;
		Request::pass('theme-title', $theme['title']);

		$cond = new Conditions();
		$per_page = 0;
		if (isset($this->params['per_page']))
			$per_page = (int) $this->params['per_page'];
		$per_page = $per_page > 0 ? $per_page : 5;

		$query = 'SELECT pid=0 as pid, COUNT(1) as cnt FROM `comments_v2` WHERE `nid` = ' . $this->theme_id.' GROUP BY (pid=0)';
		$count_arr = Database::sql2array($query,'pid');
		$count = isset($count_arr[0]['cnt'])?$count_arr[0]['cnt']:0;
		$count_arr[0] = array('cnt' => $count);
		$count_with_answers = isset($count_arr[1]['cnt'])?$count_arr[1]['cnt']+$count_arr[0]['cnt']:$count_arr[0]['cnt'];
		
		$cond->setPaging($count, $per_page);
		
		$limit = $cond->getLimit();

		$query = 'SELECT rid,cid,pid,subject,comment,timestamp,uid FROM `comments_v2` WHERE `nid` = ' . $this->theme_id . ' AND `pid`=0 ORDER BY `timestamp` LIMIT '.$limit;
		$comments = Database::sql2array($query, 'cid');
		// childs?
		if (count($comments)) {
			$query = 'SELECT * FROM `comments_v2` WHERE `pid` IN(' . implode(',', array_keys($comments)) . ') ORDER BY `sort`';
			$answers = Database::sql2array($query, 'cid');
			foreach ($answers as &$answer) {
				$answer['comment'] = _bbcode_filter_process($answer['comment']);
				$answer['time'] = date('Y/m/d H:i', $answer['timestamp']);
				$comments[$answer['pid']]['answers'][] = $answer;
			}
		}
		$uids = array();
		foreach ($comments as &$comment) {
			$uids[$comment['uid']] = $comment['uid'];
			$comment['comment'] = _bbcode_filter_process($comment['comment']);
			$comment['time'] = date('Y/m/d H:i', $comment['timestamp']);
		}
		$uids[$theme['user_id']] = $theme['user_id'];
		$this->data['theme']['users'] = $this->getUsers($uids);
		$this->data['theme']['tid'] = $this->forum_id;
		$this->data['theme']['theme_id'] = $this->theme_id;
		$this->data['theme']['comments'] = $comments;
		$this->data['theme']['comments']['count'] = $count_with_answers;
		$this->data['theme']['comments']['count_nop'] = $count;
		$this->data['conditions'] = $cond->getConditions();
	}

	function getForumList() {
		$query = 'SELECT t.tid, t . * , parent
		FROM term_data t
		INNER JOIN term_hierarchy h ON t.tid = h.tid
		WHERE t.vid = 1
		ORDER BY weight, name LIMIT 40';
		$forumList = Database::sql2array($query);
		$this->data['forums'] = $forumList;
	}

	function getThemesList() {
		$querycnt = 'SELECT COUNT(1) FROM `term_node` TN 
		LEFT JOIN `node` N ON TN.nid = N.nid 
		WHERE `tid`=' . $this->forum_id;

		$count = Database::sql2single($querycnt);
		$cond = new Conditions();

		$cond->setPaging($count, 15);
		$limit = $cond->getLimit();
		$this->data['conditions'] = $cond->getConditions();

		$query = 'SELECT N.uid as author_id, NCS.last_comment_timestamp,NCS.last_comment_uid,N.title,N.nid,N.created,N.changed,comment_count,N.promote,N.sticky,N.status FROM `term_node` TN 
		LEFT JOIN `node` N ON TN.nid = N.nid 
		LEFT OUTER JOIN `node_comment_statistics` NCS ON N.nid = NCS.nid 
		WHERE `tid`=' . $this->forum_id . '
		ORDER BY `changed` DESC LIMIT ' . $limit;

		Request::pass('forum-title', Database::sql2single('SELECT name FROM `term_data` WHERE `tid`=' . $this->forum_id));
		$themesList = Database::sql2array($query);
		foreach ($themesList as &$theme) {
			$theme['comment_count'] = max(0, (int) $theme['comment_count']);
			if ($theme['last_comment_uid'])
				$uids[$theme['last_comment_uid']] = (int) $theme['last_comment_uid'];
			$uids[$theme['author_id']] = (int) $theme['author_id'];
			$theme['last_comment_timestamp'] = date('Y/m/d H:i', $theme['last_comment_timestamp']);
			$theme['created'] = date('Y/m/d H:i', $theme['created']);
		}
		$this->data['users'] = $this->getUsers($uids);
		$this->data['themes'] = $themesList;
		$this->data['themes']['tid'] = $this->forum_id;

		$this->data['path_new_theme'] = Config::need('www_path') . '/forum/' . $this->forum_id . '/new';
	}

	function getUsers($ids) {
		$users = Users::getByIdsLoaded($ids);
		$out = array();
		/* @var $user User */
		$i = 0;
		if (is_array($users))
			foreach ($users as $user) {
				$out[$user->id] = $user->getListData();
			}
		if (is_array($ids))
			foreach ($ids as $id) {
				if (!isset($out[$id])) {
					$out[$id] = array(
					    'id' => $id,
					    'picture' => Config::need('www_path') . '/static/upload/avatars/default.jpg',
					    'nickname' => 'аноним',
					);
				}
			}
		return $out;
	}

}