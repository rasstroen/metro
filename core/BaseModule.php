<?php

// проверяем настройки модуля,
// если кеш включен

class BaseModule {

	public $action = 'show';
	public $mode = false;
	public $xmlPart = false;
	protected $moduleName = '';
	protected $settings = array();
	protected $xml_cache_name = '';
	protected $data = array(); // выходные данные модуля
	protected $cached = false;
	protected $cache_enabled = false;
	protected $params;
	protected $props;
	protected $writeParameters = array();

	function parseParams($params) {
		global $current_user;
		if (is_array($params))
			foreach ($params as $param) {
				switch ($param['type']) {
					case 'get':
						$this->params[$param['name']] = Request::get($param['value'] - 1);
						break;
					case 'current_user':
						$this->params[$param['name']] = $current_user->id;
						break;
					case 'val':case 'var':
						$this->params[$param['name']] = $param['value'];
						break;
					default:
						die($param['type']);
				}
			}
	}

	public function getActionMode() {
		if ($this->mode)
			return array('action' => $this->action, 'mode' => $this->mode);
		return array('action' => $this->action);
	}

	function __construct($moduleName, array $additionalSettings, $action, $mode) {
		global $dev_mode;
		$this->action = $action;
		$this->mode = $mode;

		if (isset($additionalSettings['params']))
			$this->parseParams($additionalSettings['params']);
		$this->moduleName = $moduleName;
		foreach ($additionalSettings as $settingName => $value) {
			// именно на этой странице у модуля появились дополнительные настройки
			$this->settings[$settingName] = $value;
		}
		// цепляем данные из соответствующего модуля записи
		$this->writeParameters = PostWrite::getWriteParameters($moduleName);
		foreach ($this->writeParameters as $f => $v) {
			$this->data['write'][$f] = $v;
		}

		if (count($this->writeParameters)) {
			$this->disableCaching();
			Log::logHtml('caching for module # ' . $moduleName . ' disabled [post params]');
		} else
		if ($this->checkCacheSettings()) {
			// вынимаем из кеша
			$cachedXml = $this->getFromCache();
			// если получилось
			if ($cachedXml) {
				Log::logHtml('caching for module # ' . $moduleName . ' enabled [got xml from cache]');
				$this->beforeCachedRun();
				$this->xmlPart = $cachedXml;
			}
		} else {
			Log::logHtml('caching for module # ' . $moduleName . ' disabled [module settings]');
		}
	}

	/**
	 * перед тем как отдать данные из кеша выполняем эту ф-цию
	 */
	protected function beforeCachedRun() {
		
	}

	protected function disableCaching() {
		$this->cache_enabled = false;
	}

	/**
	 * нужен ли кеш для хранения xml модуля?
	 * 
	 * @return boolean да или нет 
	 */
	protected function checkCacheSettings() {
		if ((isset($this->params['cache']) && $this->params['cache'])) {
			$this->xml_cache_name = Request::getModuleUniqueHash($this->moduleName, $this->action, $this->mode, $this->params);
			if (Request::post('writemodule')) {
				// erasing cache if any write actions
				Cache::drop($this->xml_cache_name, Cache::DATA_TYPE_XML);
				$this->cache_enabled = false;
			}
			else
				$this->cache_enabled = true;
		}
		return $this->cache_enabled;
	}

	// генерируем xml дерево модуля
	public final function process() {
		if ($this->xmlPart !== false) {
			// xml уже взят
			$this->xmlPart->setAttribute('from_cache', true);
			return true;
		}
		$this->generateData();
	}

	protected function generateData() {
		throw new Exception($this->moduleName . '->generateData() must be implemented', Error::E_MUST_BE_IMPLEMENTED);
	}

	// пытаемся получить ноду из кеша
	protected function getFromCache() {
		if (!$this->cache_enabled)
			return false;
		$cache_sec = isset($this->params['cache']) ? max(0, (int) $this->params['cache']) : 0;
		if (!$cache_sec)
			return false;
		if ($data = Cache::get($this->xml_cache_name, Cache::DATA_TYPE_XML, $cache_sec)) {
			Log::timingplus($this->moduleName . ' : XML from cache');
			$doc = new DOMDocument;
			$doc->loadXML($data);
			// говорим нашему дереву что этот кусок из кеша будет вставлен
			$part = $doc->getElementsByTagName("module")->item(0);
			$this->xmlPart = XMLClass::$xml->importNode($part, true);
			Log::timingplus($this->moduleName . ' : XML from cache');
			return $this->xmlPart;
		}
		return false;
	}

	// отправляем ноду в кеш
	protected function putInCache() {
		if ($this->cache_enabled) {
			$cache_sec = isset($this->params['cache']) ? max(0, (int) $this->params['cache']) : 0;
			if (!$cache_sec)
				return false;
			Cache::set($this->xml_cache_name, XMLClass::$xml->saveXML($this->xmlPart), $cache_sec, Cache::DATA_TYPE_XML);
			Log::logHtml('caching for module # ' . $this->moduleName . ' enabled [put into cache]');
		}
	}

	public function getResultXML() {
		if ($this->xmlPart !== false)
			return $this->xmlPart;
		$this->xmlPart = XMLClass::createNodeFromObject($this->data, false, 'module');
		$this->putInCache();
		return $this->xmlPart;
	}

	// отдаем имя шаблона
	public function getXSLTFileName($ignoreXHTML = false) {
		return isset($this->settings['action']) ? $this->settings['action'] : 'show';
	}

}