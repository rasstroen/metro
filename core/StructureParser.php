<?php

class StructureParser {

	private static $modules;
	private static $data;
	private static $navigations;
	private static $navigations_string;
	
	public static $cache_seconds = 5;
	public static $partials_cache_time = 5;

	public static function clear() {
		self::$modules = array();
		self::$data = array();
	}

	public static function XMLToArray($path_to_structure, $path_to_default) {
		if (list(self::$data, self::$modules, self::$navigations) = Cache::get('structure_' . $path_to_structure)) {
			$dom = new DOMDocument();
			$dom->loadXML(self::$navigations);
			self::$navigations = $dom->getElementsByTagName('navigations')->item(0);
		} else {
			self::parse($path_to_default);
			self::parse($path_to_structure);
			self::$data['path'] = $path_to_structure;
			if (!self::getLayoutPath())
				throw new Exception('missed layout header in structure file ' . $path_to_structure);

			Cache::set('structure_' . $path_to_structure, array(self::$data, self::$modules, self::$navigations_string), self::$cache_seconds);
		}
	}

	private static function parse($path) {
		$path = trim($path);
		$libmodules = array();
		if (file_exists($path))
			convertXmlObjToArr($xml = simplexml_load_file($path), $structure);
		else
			throw new Exception('[' . $path . '] cant be loaded');

		$i = 0;

		foreach ($structure[0]['@children'] as $children) {
			if (in_array($children['@name'], array('stylesheet', 'javascript'))) {
				self::$data[$children['@name']][] = $children['@attributes'];
			} else if (in_array($children['@name'], array('layout'))) {
				self::$data[$children['@name']]['file'] = $children['@attributes']['file'];
			} else if (in_array($children['@name'], array('role'))) {
				self::$data[$children['@name']]['need'] = $children['@attributes']['need'];
			}
			else
				self::$data[$children['@name']] = $children['@text'];
		}

		if (isset(self::$data['role']['need']))
			Error::CheckThrowAuth(self::$data['role']['need']);

		// вынимаем модули
		$i = 0;
		$j = 0;

		foreach ($structure[1]['@children'] as $data) {
			$container = $data['@name'];
			foreach ($data['@children'] as $module) {
				$i++;
				$libmodules[$i] = $module['@attributes'];
				$libmodules[$i]['container'] = $container;
				if (isset($module['@children']))
					foreach ($module['@children'] as $param)
						if ($param['@name'] == 'param') {
							$libmodules[$i]['params'][$j++] = $param['@attributes'];
						}
			}
		}
		//navigations
		if (isset($structure[2])) {
			/* @var $xml SimpleXMLElement */
			$dom = new DOMDocument();
			self::$navigations_string = $xml->navigations->asXML();
			$dom->loadXML(self::$navigations_string);
			self::$navigations = $dom->getElementsByTagName('navigations')->item(0);
		}
		foreach ($libmodules as $module)
			self::$modules[] = $module;
	}

	private static function createFileFromPartials($files, $tmp_name, $ext = 'js') {
		$exists = Cache::get('partfile_' . $tmp_name . '.' . $ext);
		if ($exists) {
			return $tmp_name;
		}
		$concat_static_enabled = Config::need('concat_static', false);
		// склеиваем файлы в один
		$filename = Config::need('static_path') . '/default/' . $ext . '/transform/' . $tmp_name . '.' . $ext;
		$full_data = '';
		foreach ($files as $file) {
			if (!$concat_static_enabled || ($file['path'] == 'tiny_mce/tiny_mce'))
				continue;
			$fn = Config::need('static_path') . '/default/' . $ext . '/' . $file['path'] . '.' . $ext;
			if (file_exists($fn))
				$full_data.="\n\n" . file_get_contents($fn);
		}
		if ($full_data) {
			file_put_contents($filename, $full_data);
			Cache::set('partfile_' . $tmp_name . '.' . $ext, time(), self::$partials_cache_time);
			return $tmp_name;
		}
		return false;
	}

	public static function toXML() {
		$node = XMLClass::$xml->createElement('structure');
		$data = XMLClass::$xml->createElement('data');
		$blocks = XMLClass::$xml->createElement('blocks');

		$content_block = XMLClass::$xml->createElement('content');
		$blocks->appendChild($content_block);

		$sidebar_block = XMLClass::$xml->createElement('sidebar');
		$blocks->appendChild($sidebar_block);

		$header_block = XMLClass::$xml->createElement('header');
		$blocks->appendChild($header_block);

		$node->appendChild($data);
		$node->appendChild($blocks);
		$concat_static_enabled = Config::need('concat_static', false);
		foreach (self::$data as $field => $value) {
			switch ($field) {
				case 'title':
					$Node = XMLClass::$xml->createElement($field);
					$Text = XMLClass::$xml->createTextNode($value);
					$Node->appendChild($Text);
					$data->appendChild($Node);
					break;
				case 'stylesheet':case 'javascript':
					$tmpname = '';
					$additional_js = array();
					foreach ($value as $item) {
						$tmpname.=implode('|', $item);
						if (!$concat_static_enabled || ($item['path'] == 'tiny_mce/tiny_mce')) {
							$additional_js[] = $item;
						}
					}
					$r = self::createFileFromPartials($value, md5($tmpname), $field == 'stylesheet' ? 'css' : 'js');
					$value = $additional_js;
					if ($r)
						$value[] = array('path' => 'transform/' . md5($tmpname));


					foreach ($value as $item) {
						$Node = XMLClass::$xml->createElement($field);
						foreach ($item as $f => $v)
							$Node->setAttribute($f, $v);
						$data->appendChild($Node);
					}
					break;
				default:
					$Node = XMLClass::$xml->createElement($field);
					if (is_array($value)) {
						foreach ($value as $f => $v)
							$Node->setAttribute($f, $v);
					}else
						$Node->setAttribute('value', $value);
					$data->appendChild($Node);
					break;
			}
		}
		foreach (self::$modules as $module) {
			$Node = XMLClass::$xml->createElement('module');
			foreach ($module as $field => $value) {
				if (is_string($value))
					$Node->setAttribute($field, $value);
			}
			switch ($module['container']) {
				case 'content':
					$content_block->appendChild($Node);
					break;
				case 'header':
					$header_block->appendChild($Node);
					break;
				default:
					$sidebar_block->appendChild($Node);
					break;
			}
		}

		if (self::$navigations) {
			$node->appendChild(XMLClass::$xml->importNode(self::$navigations, 1));
		}

		return $node;
	}

	public static function getLayoutPath() {
		return self::$data['layout']['file'];
	}

	public static function getModules() {
		return self::$modules;
	}

	public static function getTitle() {
		return isset(self::$data['title']) ? self::$data['title'] : '';
	}

}
