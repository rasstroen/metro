<?php

class Person extends BaseObjectClass {

	public $id;
	public $persons;
	public $loaded = false;
	public $data;
	public $cache_time = 6;

	function __construct($id, $data = false) {
		$this->id = $id;
		if ($data) {
			if ($data == 'empty') {
				$this->loaded = true;
				$this->exists = false;
			}
			$this->load($data);
		}
	}

	function load($data = false) {
		if ($this->loaded || $this->exists === false || !$this->id)
			return false;
		if (!$data) {
			$query = 'SELECT * FROM `persons` WHERE `id`=' . $this->id;
			$this->data = Database::sql2row($query);
		}else
			$this->data = $data;
		if (isset($data['is_deleted']) && $data['is_deleted']) {
			$this->exists = false;
		} else {
			$this->exists = true;
		}
		$this->loaded = true;
	}

	function _show() {
		$out = array();
		$this->load();
		if ($redirect_to = $this->getDuplicateId()) {
			$person2 = Persons::getInstance()->getByIdLoaded($redirect_to);
			if ($person2->loaded) {
				@ob_end_clean();
				header('Location: ' . $this->getUrl($redirect = true).'?redirect=a_'.$this->id);
				exit();
			}
		}
		$out['picture'] = $this->getPicture();

		$langId = $this->data['author_lang'] ? $this->data['author_lang'] : 136;
		foreach (Config::$langs as $code => $id_lang) {
			if ($id_lang == $langId) {
				$langCode = $code;
			}
		}

		$out['lang_code'] = $langCode;
		$out['id'] = $this->id;
		$out['lang_title'] = Config::$langRus[$langCode];
		$out['lang_id'] = $langId;

		$out['first_name'] = $this->data['first_name'];
		$out['last_name'] = $this->data['last_name'];
		$out['middle_name'] = $this->data['middle_name'];
		$out['avg_mark'] = $this->getAvgMark();

		$date_b = timestamp_to_ymd($this->data['date_birth']);
		$date_b = (int) $date_b[0] ? (digit2($date_b[2]) . '.' . digit2($date_b[1]) . '.' . $date_b[0]) : '';

		$date_d = timestamp_to_ymd($this->data['date_death']);
		$date_d = (int) $date_d[0] ? (digit2($date_d[2]) . '.' . digit2($date_d[1]) . '.' . $date_d[0]) : '';

		$out['date_birth'] = $date_b;
		$out['date_death'] = $date_d;
		$out['wiki_url'] = $this->data['wiki_url'];
		$out['homepage'] = $this->data['homepage'];

		$out['bio']['html'] = $this->data['bio'];
		$out['bio']['short'] = $this->data['short_bio'];
		$out['lastSave'] = $this->data['authorlastSave'];

		return $out;
	}

	function getDuplicateId() {
		if (!$this->loaded) {
			$this->load();
		}
		return (int) $this->data['is_p_duplicate'];
	}

	function getLangId() {
		if (!$this->loaded) {
			$this->load();
		}
		return (int) $this->data['author_lang'];
	}

	function getBasketId() {
		if (!$this->loaded) {
			$this->load();
		}
		return (int) $this->data['id_basket'];
	}

	function getUrl($redirect = false) {
		$id = $redirect ? $this->getDuplicateId() : $this->id;
		return Config::need('www_path') . '/author/' . $id;
	}

	function getName($first_name = true, $last_name = true, $middle_name = true) {
		$this->load();

		$string = '';
		if ($first_name)
			$string.=' ' . $this->data['first_name'];
		if ($last_name)
			$string.=' ' . $this->data['last_name'];
		if ($middle_name)
			$string.=' ' . $this->data['middle_name'];
		return trim($string);
	}

	function getAvgMark() {
		$this->load();
		return $this->data['avg_mark'];
	}

	function getPicture($mode = 'default') {
		if (!$mode)
			$mode = 'default';
		$mode = $mode ? $mode . '_' : '';
		if (!$this->loaded) {
			$this->load();
		}
		if ($this->data['has_cover']) {
			return Config::need('www_path') . '/static/upload/authors/' . (ceil($this->id / 5000)) . '/' . $mode . '' . $this->id . '_' . $this->data['has_cover'] . '.jpg';
		}
		return Config::need('www_path') . '/static/upload/authors/default.jpg';
	}

	function processBio($fullHtml) {
		$body = '';
		$short = '';
		$body = strip_tags($fullHtml, '<p><strong><i><em><b>');
		$body = str_replace('<strong>', '<b>', $body);
		$body = str_replace('</strong>', '</b>', $body);
		$body = str_replace('<i>', '<em>', $body);
		$body = str_replace('</i>', '</em>', $body);
		$short = close_dangling_tags(trim(_substr($body, 900)));
		return array($body, $short);
	}

	function getListData($coverSize = '') {
		if (!$this->loaded)
			$this->load();
		return array(
		    'id' => $this->id,
		    'first_name' => $this->data['first_name'],
		    'last_name' => $this->data['last_name'],
		    'middle_name' => $this->data['middle_name'],
		    'picture' => $this->getPicture($coverSize),
		    'lastSave' => $this->data['authorlastSave'],
		    'path' => $this->getUrl(),
		);
	}

}