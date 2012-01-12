<?php

// храним разрешения в поле юзера notify_rules в виде битовой маски.
// каждые 3 бита - 1 правило - email,notify и запасной бит
// всего используем 24 бита (8 правил максимум)
class UserNotify {

	private $user;
	private $defaultRules = 81; //00 00 00 00 00 00 00 00 01 01 00 01;

	const UN_EVENT_COMMENT = 1; // новый коммент к своему эвенту
	const UN_COMMENT_ANSWER = 2; // ответ на свой коммент
	const UN_NEW_MESSAGE = 3; // новое сообщение
	const UN_NEW_FRIEND = 4; // новый друг
	const UN_WHATS_NEW = 5; // рассылка "что нового"?
	//
	const UN_G_OBJECTS_COMMENTS = 6; // следим за комментариями к чему-либо
	const UN_G_NEW_REVIEWS = 7; // следим за отзывами к книге
	const UN_G_NEW_GENRES = 8; // следим за новинками жанра
	const UN_G_NEW_AUTHORS = 9; // следим за новинками автора
	//
	//
	const UNT_EMAIL = 1; // отправлять на мыло
	const UNT_NOTIFY = 2; // отправлять нотифай на сайте
	//
	// список правил. true = нельзя отключить

	private $rules = array(
	    self::UN_EVENT_COMMENT => array(
		self::UNT_EMAIL => false,
		self::UNT_NOTIFY => true,
	    ),
	    self::UN_COMMENT_ANSWER => array(
		self::UNT_EMAIL => false,
		self::UNT_NOTIFY => false,
	    ),
	    self::UN_NEW_MESSAGE => array(
		self::UNT_EMAIL => false,
		self::UNT_NOTIFY => true,
	    ),
	    self::UN_NEW_FRIEND => array(
		self::UNT_EMAIL => false,
		self::UNT_NOTIFY => true,
	    ),
	    self::UN_WHATS_NEW => array(
		self::UNT_EMAIL => false,
		self::UNT_NOTIFY => false,
	    ),
	    // global
	    self::UN_G_OBJECTS_COMMENTS => array(
		self::UNT_EMAIL => false,
		self::UNT_NOTIFY => false,
	    ),
	    self::UN_G_NEW_REVIEWS => array(
		self::UNT_EMAIL => false,
		self::UNT_NOTIFY => false,
	    ),
	    self::UN_G_NEW_GENRES => array(
		self::UNT_EMAIL => false,
		self::UNT_NOTIFY => false,
	    ),
	    self::UN_G_NEW_AUTHORS => array(
		self::UNT_EMAIL => false,
		self::UNT_NOTIFY => false,
	    ),
	);
	private $ruleNames = array(
	    self::UN_EVENT_COMMENT => 'event_comment',
	    self::UN_COMMENT_ANSWER => 'comment_answer',
	    self::UN_NEW_MESSAGE => 'new_message',
	    self::UN_NEW_FRIEND => 'new_friend',
	    self::UN_WHATS_NEW => 'whats_new',
	    self::UN_G_NEW_AUTHORS => 'global_new_authors',
	    self::UN_G_NEW_GENRES => 'global_new_genres',
	    self::UN_G_NEW_REVIEWS => 'global_new_reviews',
	    self::UN_G_OBJECTS_COMMENTS => 'global_objects_comments',
	);
	private $typeNames = array(
	    self::UNT_EMAIL => 'email',
	    self::UNT_NOTIFY => 'notify',
	);

	function __construct(User $user) {
		$this->user = $user;
	}

	function can($rule, $type) {
		if (isset($this->rules[$rule]) && isset($this->rules[$rule][$type])) {
			
		}else
			throw new Exception('Illegal rule #' . $rule . ' type #' . $type . ' for notify');
		$ruleAlwaysEnabled = $this->rules[$rule][$type];
		if ($ruleAlwaysEnabled) // нельзя отключить уведомление
			return true;
		// проверяем, не отключил ли юзер правило
		$value = $this->getUserRules();
		$shift = ($rule * 2 - $type);
		$mask = 1 << $shift;
		$bit = ($value & $mask) >> ($shift);

		return $bit;
	}

	function getUserRules() {
		$this->user->load();
		$rules = $this->user->getProperty('notify_rules', $this->defaultRules);
		return $rules | $this->defaultRules;
	}

	function setPermission($rule, $type, $on = true) {
		if (isset($this->rules[$rule]) && isset($this->rules[$rule][$type])) {
			
		}else
			throw new Exception('Illegal rule #' . $rule . ' type #' . $type . ' for setPermission');
		$value = $this->getUserRules();
		$shift = ($rule * 2 - $type);
		$mask = 1 << $shift;
		if ($on)
			$value = $value | $mask;
		else
			$value = $value & (~$mask);
		$this->user->setProperty('notify_rules', $value);
		return true;
	}

	function getAllUserRules() {
		foreach ($this->rules as $rule => $data) {
			foreach ($data as $type => $value) {
				$out[$this->ruleNames[$rule]][$this->typeNames[$type]]['enabled'] = $value ? 1 : ((int) ($this->can($rule, $type)));
				$out[$this->ruleNames[$rule]][$this->typeNames[$type]]['cant_be_changed'] = (int) $this->rules[$rule][$type];
			}
		}
		return $out;
	}

}