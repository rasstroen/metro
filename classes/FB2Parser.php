<?php

/**
 *
 */
$html1 = array('EPIGRAPH' => '<blockquote class="epigraph">', 'TEXT-AUTHOR' => '<blockquote class="book"><i>', 'ANNOTATION' => 'h4 class="book"',
    'POEM' => '<div class="poem">', 'V' => 'div', 'EMPTY-LINE' => 'br', 'STANZA' => '<div class="stanza">', 'P' => 'p class="book"', 'STYLE' => '-',
    'SUBTITLE' => 'h5 class="book"', 'CITE' => 'blockquote class="book"', 'STRONG' => 'b', 'EMPHASIS' => 'i', 'STRIKETHROUGH' => 'strike');

//Трансляция закрывающих тэгов fb2 => html. Отсутствующие тут транслируются по предыдущей схеме.
$html2 = array('EPIGRAPH' => '</blockquote>', 'TEXT-AUTHOR' => '</i></blockquote>', 'EMPTY-LINE' => '-', 'TITLE' => '</h3>', 'SECTION' => '-',
    'POEM' => '</div>', 'STANZA' => '</div>', 'V' => '</div>', 'IMAGE' => '-', 'P' => '</p>', 'ANNOTATION' => '</h4>', 'SUBTITLE' => '</h5>', 'CITE' => '</blockquote>');


$tags_fb2 = array();
$tags_html = array();

class FB2Parser {

	private $parsedDescription = false;
	private $filename;
	private $SimpleXML;
	private $book_info;
	private $TOC;
	private $html;
	public $book_id;

	function __construct($filename, $id = 0) {
		$this->filename = $filename;
		$this->book_id = $id;
		$this->encodings['utf-8'] = 'UTF-8';
		$this->encodings['koi8-r'] = 'KOI8-R';
		$this->encodings['windows-1251'] = 'CP1251';
		$this->encodings['windows-1252'] = 'CP1252';
	}

	function parseDescription() {
// загружаем из файла только первый блок, без текста
		$fp = fopen($this->filename, 'r');
		$body_started = false;
		$description_started = false;
		$data = '';


		$encoding = false;
		while (!$body_started && (($s = fgets($fp)) !== false)) {
			if (!$encoding && $s) {
				$epos = strpos($s, 'encoding=');
				if (!$epos)
					$encoding = 'UTF-8';
				else {
					$encoding = substr($s, $epos + 10);
					if (strpos($encoding, '"'))
						$encoding = substr($encoding, 0, strpos($encoding, '"'));
					if (strpos($encoding, "'"))
						$encoding = substr($encoding, 0, strpos($encoding, "'"));
					if (!isset($this->encodings[strtolower($encoding)]))
						throw new Exception($encoding . " - file illegal encoding\n" . $s . ' ' . $this->filename);
					else
						$encoding = $this->encodings[strtolower($encoding)];
				}
			}
			if (strpos($s, '<body') !== false) {
				$body_started = true;
				$body_start = mb_strpos($s, '<body', null, $encoding);
				$s = mb_substr($s, 0, $body_start, $encoding);
				$tx = $s;
				$data .= $s;
			} else
				$data .= $s;
		}
		$data .= '</FictionBook>';
		if ($this->SimpleXML = simplexml_load_string($data)) {
			$this->parsedDescription = true;
			$title_info = 'title-info';
			foreach ($this->SimpleXML->description->$title_info->children() as $key => $children) {
				switch ($key) {
					case 'genre':
						$this->book_info['genre'][] = (string) $children;
						break;
					case 'book-title':
						$this->book_info['book-title'] = (string) $children;
						break;
					case 'author': // автор(ы) произведения
						$last_name = 'last-name';
						$first_name = 'first-name';
						$middle_name = 'middle-name';
						$home_page = 'home-page';
						$this->book_info['author'][] = array('first-name' => $children->$first_name,
						    'middle-name' => $children->$middle_name,
						    'last-name' => $children->$last_name,
						    'home-page' => $children->$home_page,
						    'email' => $children->email);
						break;
					case 'annotation':
						$this->book_info['annotation'] = trim(str_replace(array('<annotation>', '</annotation>'), array('', ''), $children->asXML()));
						break;
					case 'coverpage':
						$this->book_info['coverpage'] = $children->image;
						break;
					case 'date': // хранит дату создания документа.
						$this->book_info['date'] = $children;
						break;
					case 'translator': // переводчик(и)
						$last_name = 'last-name';
						$first_name = 'first-name';
						$middle_name = 'middle-name';
						$home_page = 'home-page';
						$this->book_info['translator'][] = array('first-name' => $children->$first_name,
						    'middle-name' => $children->$middle_name,
						    'last-name' => $children->$last_name,
						    'home-page' => $children->$home_page,
						    'email' => $children->email);
						break;
					case 'lang': // язык книги
						$this->book_info['lang'] = $children;
						break;
					case 'year': // год издания книги.
						$this->book_info['year'] = $children;
						break;
				}
			}
		} else
			throw new Exception('FB2 file is invalid');
	}

	function getProperty($name, $default = false) {
		if (!$this->parsedDescription)
			$this->parseDescription();
		return isset($this->book_info[$name]) ? $this->book_info[$name] : $default;
	}

	/** берем файл с диска и читаем построчно до <body> - использовать по необходимости, всё необходимое - в базе
	 *
	 * @param type $olType
	 * @param type $olClass
	 * @param type $LiEmentIdPrefix
	 * @return string 
	 */
	function getTOCHTML($olType = 'ul', $olClass ='toc', $LiEmentIdPrefix = 't') {
		$TOC = $this->getTOC();

		$out = '';
		$oldLevel = 0;
		foreach ($TOC as $item) {
			if ($item['level'] < 0)
				continue;
			if ($item['level'] > $oldLevel) {
				$out .= '<' . $olType . ' class="' . $olClass . '">';
			}
			if ($item['level'] < $oldLevel) {
				for ($kk = 1; $kk < ($oldLevel - $item['level']); $kk++)
					$out.='</' . $olType . '>';
				$out .= '</' . $olType . '>';
			}
			$out .= '<li><a href="#' . $LiEmentIdPrefix . $item['id'] . '">' . $item['title'] . '</a></li>';
			$oldLevel = $item['level'];
		}
		for ($i = 0; $i < $oldLevel; $i++)
			$out.='</' . $olType . '>';
		return $out;
	}

	function getHTML() {
		$xml = new DOMDocument;
		$xml->load($this->filename);
		$rootNode = $xml->getElementsByTagName('FictionBook')->item(0);
		$parserNode = $xml->createElement('parser');
		$parserNode->setAttribute('image_path', Config::need('www_path') . '/static/upload/book_images/' . (ceil($this->book_id / 5000)) . '/');
		$this->saveFileImages($xml);
		$rootNode->appendChild($parserNode);
		$xslProcessor = new XSLTProcessor();
		$xslTemplate = new DOMDocument();
		//die($xml->saveXML());
		$xslTemplate->load(Config::need('xslt_files_path') . DIRECTORY_SEPARATOR . 'fb2_body.xsl', LIBXML_NOENT | LIBXML_DTDLOAD);
		$xslProcessor->importStyleSheet($xslTemplate);
		$html = $xslProcessor->transformToXML($xml);
		return $html;
	}

	function getHTMLDownload() {
		$xml = new DOMDocument;
		$xml->load($this->filename);
		$rootNode = $xml->getElementsByTagName('FictionBook')->item(0);
		$parserNode = $xml->createElement('parser');
		$parserNode->setAttribute('image_path', Config::need('www_path') . '/static/upload/book_images/' . (ceil($this->book_id / 5000)) . '/');
		$this->saveFileImages($xml);
		$rootNode->appendChild($parserNode);
		$xslProcessor = new XSLTProcessor();
		$xslTemplate = new DOMDocument();
		//die($xml->saveXML());
		$xslTemplate->load(Config::need('xslt_files_path') . DIRECTORY_SEPARATOR . 'fb2.xsl', LIBXML_NOENT | LIBXML_DTDLOAD);
		$xslProcessor->importStyleSheet($xslTemplate);
		$html = $xslProcessor->transformToXML($xml);
		return $html;
	}

	function saveFileImages($xml) {
		$attachments = $xml->getElementsByTagName('binary');
		$upload_dir = Config::need('static_path') . '/upload/book_images/' . (ceil($this->book_id / 5000) . '/');
		@mkdir($upload_dir);
		foreach ($attachments as $element) {
			$name = $element->attributes->getNamedItem('id')->nodeValue;
			$filename = $upload_dir . $name;
			file_put_contents($filename, base64_decode($element->nodeValue));
		}
	}

	function getTOCXsl() {
		$xml = new DOMDocument;
		$xml->load($this->filename);
		$xslProcessor = new XSLTProcessor();
		$xslTemplate = new DOMDocument();
		$xslTemplate->load(Config::need('xslt_files_path') . DIRECTORY_SEPARATOR . 'fb2_toc.xsl', LIBXML_NOENT | LIBXML_DTDLOAD);
		$xslProcessor->importStyleSheet($xslTemplate);
		$html = $xslProcessor->transformToXML($xml);
		return $html;
	}

	/** читаем каждую строку файла! использовать в крайней необходимости
	 *
	 * @return type 
	 */
	function getTOC() {
		if (!$this->TOC) {
			$TOC = array();
// читаем всю книгу построчно
			$fp = fopen($this->filename, 'r');
			$current_level = 1;
			$currenttitle = '';
			$k = 1;
			$encoding = false;
			$left = false;
			$id = 0;
			$search_section = true;
			$search_title = false;
			$search_section_closed = false;


			while ($left || ($s = fgets($fp)) !== false) {

				if (!$encoding && $s) {
					$epos = strpos($s, 'encoding=');
					if (!$epos)
						$encoding = 'UTF-8';
					else {
						$encoding = substr($s, $epos + 10);
						if (strpos($encoding, '"'))
							$encoding = substr($encoding, 0, strpos($encoding, '"'));
						if (strpos($encoding, "'"))
							$encoding = substr($encoding, 0, strpos($encoding, "'"));
						if (!isset($this->encodings[strtolower($encoding)]))
							throw new Exception($encoding . " - file illegal encoding:" . $encoding . ' ' . $this->filename);

						else
							$encoding = $this->encodings[strtolower($encoding)];
					}
				}
				if ($left)
					$s = $left;

				$left = false;
				if ($search_section_closed) {
					if ((($pos = mb_strpos($s, '</section>', null, $encoding)) !== false) || (($pos = mb_strpos($s, '</body>', null, $encoding)) !== false)) {
						$current_level--;
						$search_section = true;
						$search_title = true;
						$search_title_closed = false;
						$search_section_closed = true;
						$left = mb_substr($s, $pos + 1, null, $encoding);
						$currenttitle = $s;
						continue;
					}
				}
				if ($search_title) {
					$currenttitle.= $s;
					if (($pos = mb_strpos($s, '</title>', null, $encoding)) !== false) {
						$pattern = '/\<title\>(.*)\<\/title\>/isU';
						preg_match_all($pattern, $currenttitle, $res);
						if ($encoding != 'UTF-8')
							$res[1][0] = iconv($encoding, 'UTF-8', $res[1][0]);

						$res[1][0] = str_replace(array('<title>', '</title>'), '', $res[1][0]);
						$res[1][0] = strip_tags($res[1][0]);
						$TOC[] = array('level' => $current_level, 'title' => trim($res[1][0]), 'id' => $k++);
						$search_section = true;
						$search_title = false;
						$search_title_closed = false;
						$search_section_closed = true;
						$left = mb_substr($s, $pos + 1, null, $encoding);
						$currenttitle = '';
						$current_level++;
						continue;
					}
				}
				if ($search_section) {
					if ((($pos = mb_strpos($s, '<section', null, $encoding)) !== false) || (($pos = mb_strpos($s, '<body', null, $encoding)) !== false)) {
// открыли section
						$search_section = false;
						$search_title = true;
						$search_section_closed = true;
						$search_title_closed = false;
						$left = mb_substr($s, $pos + 1, null, $encoding);
						$currenttitle = $s;
						continue;
					}
				}
			}
			$this->TOC = $TOC;
		}
		return $this->TOC;
	}

}