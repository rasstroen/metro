<?php

/* этот класс пинает всех:
 * собирает xslt шаблоны
 * собирает xml дерево из деревьев модулей
 * собирает xslt шаблон из кусков
 * выполняет трансормацию
 * возвращает готовый HTML
 */

class PageConstructor {

	private $pageSettings;
	private $xsltFileName = '';
	private $xsltFiles = array();
	private $modules;

	public static function parseParams($type, $n) {
		switch ($type) {
			case 'get':
				return Request::get($n - 1);
				break;
			case 'current_user':
				return $current_user->id;
				break;
			case 'val':case 'var':
				return $n;
				break;
			case 'raw_get':
				return isset(Request::$get_normal[$n]) ? Request::$get_normal[$n] : '';
				break;
			default:
				die('*pts' . $type);
		}
	}

	function getPageStructure($path_to_structure) {
		global $current_user;
		$path_to_structure = Config::need('base_path') . 'structure' . DIRECTORY_SEPARATOR . $current_user->getTheme() . DIRECTORY_SEPARATOR . $path_to_structure;
		$path_to_default = Config::need('base_path') . 'structure' . DIRECTORY_SEPARATOR . $current_user->getTheme() . DIRECTORY_SEPARATOR . 'application.xml';
		StructureParser::clear();
		StructureParser::XMLToArray($path_to_structure, $path_to_default);
		$this->xsltFileName = StructureParser::getLayoutPath();
		$modules = StructureParser::getModules();
		return $modules;
	}

	function __construct($path_to_structure) {
		$this->modules = $this->getPageStructure($path_to_structure);
	}

	private function processModule($moduleName, $additionalSettings = array()) {
		// запускаем модуль
		$action = isset($additionalSettings['action']) ? $additionalSettings['action'] : false;
		$mode = isset($additionalSettings['mode']) ? $additionalSettings['mode'] : false;
		eval('$module = new ' . $moduleName . '_module($moduleName, $additionalSettings , $action , $mode);');
		/* @var $module BaseModule */
		// получаем xml от модуля
		Log::timing($moduleName . ' : processModule');
		$module->process();
		Log::timing($moduleName . ' : processModule');
		$xmlNode = $module->getResultXML();


		// добавляем xsl файл в список
		$xsltFileName = $module->getXSLTFileName();

		if ($xsltFileName)
			$this->addXsltFile($moduleName, $xsltFileName, $action, $mode);
		else if ($xsltFileName == null)
			$this->addXsltNullFile($moduleName, $action, $mode);

		if ($xmlNode !== false) {
			XMLClass::setNodeProps(XMLClass::appendNode($xmlNode, $moduleName), $module->getActionMode());
		}
	}

	function getMessageNode() {
		$messageA = array();
		$node = false;
		if ($r = Request::get('redirect')) {
			list($type, $id) = explode('_', $r);
			switch ($type) {
				case 's':
					$query = 'SELECT * FROM `series` WHERE `id`=' . (int) $id;
					$res = Database::sql2row($query);
					if ($res && isset($res['is_s_duplicate']) && $res['is_s_duplicate']) {
						$messageA = array('html' => 'Cерия «' . $res['title'] . '» была склеена с данной серией');
						$node = XMLClass::createNodeFromObject($messageA, false, 'message', true);
					}
					break;
				case 'b':
					$query = 'SELECT * FROM `book` WHERE `id`=' . (int) $id;
					$book = new Book((int) $id);
					if ($book->getDuplicateId()) {
						$messageA = array('html' => 'Книга «' . $book->getTitle(true) . '» была склеена с данной книгой');
						$node = XMLClass::createNodeFromObject($messageA, false, 'message', true);
					}
					break;
				case 'a':
					$person = new Person((int) $id);
					if ($person->getDuplicateId()) {
						$messageA = array('html' => 'Автор «' . $person->getName() . '» был склеен с данным автором');
						$node = XMLClass::createNodeFromObject($messageA, false, 'message', true);
					}
					break;
			}
		}

		return $node;
	}

	public function process() {
		global $current_user;
		/* @var $current_user CurrentUser */
		if ($pid = Request::get('pid'))
			Statistics::setPartnerCookie($pid);
		XMLClass::$pageNode = XMLClass::createNodeFromObject(array(), false, 'page', false);
		XMLClass::appendNode(XMLClass::$pageNode, '');

		XMLClass::$accessNode = XMLClass::createNodeFromObject(AccessRules::getRules(), false, 'access', true);
		XMLClass::appendNode(XMLClass::$accessNode, '');

		XMLClass::$pageNode->setAttribute('current_url', Request::$url);


		if ($mn = $this->getMessageNode())
			XMLClass::$pageNode->appendChild($mn);

		XMLClass::$pageNode->setAttribute('prefix', Config::need('www_path') . '/');
		XMLClass::$varNode = XMLClass::$xml->createElement('variables');
		foreach (Request::$get_normal as $f => $v) {
			if (is_numeric($f)) { // cachebreaker?
				XMLClass::$varNode->setAttribute('n' . $f, $f);
			} else {
				XMLClass::$varNode->setAttribute($f, $v);
			}
		}
		XMLClass::$pageNode->appendChild(XMLClass::$varNode);

		XMLClass::$rootNode->appendChild(XMLClass::$xml->importNode(StructureParser::toXML(), 1));

		if ($current_user->authorized) {
			XMLClass::$CurrentUserNode = XMLClass::createNodeFromObject($current_user->getXMLInfo(), false, 'current_user', true);
		}
		else
			XMLClass::$CurrentUserNode = XMLClass::createNodeFromObject(array(), false, 'current_user', false);
		XMLClass::$pageNode->appendChild(XMLClass::$CurrentUserNode);
		// втыкаем модули страницы
		$role = $current_user->getRole();


		foreach ($this->modules as $module) {
			$this->processModule($module['name'], $module);
		}

		if ($pageTitle = StructureParser::getTitle()) {
			$this->buildPageTitle($pageTitle);
		}

		switch (Request::$responseType) {
			case 'xml':case 'xmlc':
				return XMLClass::dumpToBrowser();
				break;
			case 'xsl':case 'xslc':
				$xslTemplateClass = new XSLClass($this->xsltFileName);
				return $xslTemplateClass->dumpToBrowser();
				break;
			case 'html':
				$xslTemplateClass = new XSLClass($this->xsltFileName);
				$html = $xslTemplateClass->getHTML(XMLClass::$xml);
				return $html;
				break;
			default:
				return XMLClass::dumpToBrowser();
				break;
		}
	}

	public static function buildPageTitlePart($var) {
		$x = explode(':', $var[1]);
		$name = false;
		if (count($x) == 3)
			list($name, $paramtype, $paramvalue) = $x;

		if (count($x) == 2) {
			list($name, $paramvalue) = $x;
			$paramtype = 'raw_get';
		}
		if ($name) {
			$val = self::parseParams($paramtype, $paramvalue);
			switch ($name) {
				case 'profile-nickname':
					$user = Users::getByIdsLoaded(array((int) $val));
					$user = isset($user[$val]) ? $user[$val] : false;
					/* @var $user User */
					if ($user)
						return $user->getNickName();
					break;
				case 'book-title':
					$book = Books::getInstance()->getByIdLoaded((int) $val);
					/* @var $book Book */
					return $book->getTitle(1);
					break;
				case 'person-title':
					$person = Persons::getInstance()->getById((int) $val);
					/* @var $person Person */
					return $person->getName();
					break;
				case 'genre-title':
					return Request::pass('genre-title');
					break;
				case 'forum-title':
					$t = Request::pass('forum-title');
					if (!$t)
						$t = Database::sql2single('SELECT name FROM `term_data` WHERE `tid`=' . (int) $val);
					return $t;
					break;
				case 'post-subject':
					return Request::pass('post-subject');
					break;
				case 'theme-title':
					return Request::pass('theme-title');
					break;
				case 'serie-title':
					$t = Request::pass('serie-title');
					if (!$t) {
						$t = Database::sql2single('SELECT `title` FROM `series` WHERE `id`=' . (int) $val);
					}
					return $t;
					break;
				case 'shelf-name':
					if ($val == 'loved')
						return 'Любимые книги';
					if (isset(Config::$shelfIdByNames[$val]))
						return isset(Config::$shelves[Config::$shelfIdByNames[$val]]) ? Config::$shelves[Config::$shelfIdByNames[$val]] : $val;
					break;
				case 'magazine-title':
					$query = 'SELECT `title` FROM `magazines` WHERE `id`=' . (int) $val;
					return Database::sql2single($query);
					break;
				case 'thread-subject':
					$query = 'SELECT `subject` FROM `users_messages` WHERE `id`=' . (int) $val;
					return Database::sql2single($query);
					break;
				case 'get':
					return $val;
					break;
				default:
					throw new Exception('Cant process title part "' . $var[1] . '"');
					break;
			}
		}
	}

	private function buildPageTitle($title) {
		$title = preg_replace_callback('/\{(.+)\}/isU', 'PageConstructor::buildPageTitlePart', $title);
		XMLClass::$pageNode->setAttribute('title', $title);
	}

	//-----------
	// добавляем шаблон модуля в список шаблонов страницы
	private function addXsltFile($moduleName, $xsltFileName, $action, $mode) {
		$this->xsltFiles[$moduleName][] = array(
		    'view' => $xsltFileName,
		    'action' => $action,
		    'mode' => $mode
		);
	}

}