<?php
if (!function_exists('apache_request_headers')) {
    eval('
        function apache_request_headers() {
            foreach($_SERVER as $key=>$value) {
                if (substr($key,0,5)=="HTTP_") {
                    $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
                    $out[$key]=$value;
                }
            }
            return $out;
        }
    ');
} 
function is_running_process($process) {
	exec('ps axw | grep -v grep | grep ' . escapeshellarg($process), $output);
	$enabled = false;
	foreach ($output as $process_line) {
		if (preg_match('/' . $process . '/isU', $process_line)) {
			if ($enabled) {
				return true;
			}
			$enabled = true;
		}
	}
	return false;
}

function texttourl($s) {
	return preg_replace('#(?<!\])\bhttp://[^\s\[<]+#i', "<a rel='nofollow' href=\"$0\" target=_blank><u>$0</u></a>", $s);
}

function to_utf8($string) {
// From http://w3.org/International/questions/qa-forms-utf-8.html
	if (preg_match('%^(?:
      [\x09\x0A\x0D\x20-\x7E]            # ASCII
    | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
    | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
    | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
    | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
    | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
    | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
)*$%xs', $string)) {
		return $string;
	} else {
		return iconv('CP1251', 'UTF-8', $string);
	}
}

function convertXmlObjToArr($obj, &$arr) {
	$children = $obj->children();
	foreach ($children as $elementName => $node) {
		$nextIdx = count($arr);
		$arr[$nextIdx] = array();
		$arr[$nextIdx]['@name'] = strtolower((string) $elementName);
		$arr[$nextIdx]['@attributes'] = array();
		$attributes = $node->attributes();
		foreach ($attributes as $attributeName => $attributeValue) {
			$attribName = strtolower(trim((string) $attributeName));
			$attribVal = trim((string) $attributeValue);
			$arr[$nextIdx]['@attributes'][$attribName] = $attribVal;
		}
		$text = (string) $node;
		$text = trim($text);
		if (strlen($text) > 0) {
			$arr[$nextIdx]['@text'] = $text;
		}
		$arr[$nextIdx]['@children'] = array();
		convertXmlObjToArr($node, $arr[$nextIdx]['@children']);
	}
	return;
}

function simpleXMLToArray($xml, $flattenValues=true, $flattenAttributes = true, $flattenChildren=true, $valueKey='@value', $attributesKey='@attributes', $childrenKey='@children') {
	$return = array();
	if (!($xml instanceof SimpleXMLElement)) {
		return $return;
	}
	$name = $xml->getName();
	$_value = trim((string) $xml);

	if (strlen($_value) == 0) {
		$_value = null;
	}

	if ($_value != null) {
		if (!$flattenValues) {
			$return[$valueKey] = $_value;
		} else {
			$return = $_value;
		}
	}

	$children = array();
	$first = true;
	foreach ($xml->children() as $elementName => $child) {
		$value = simpleXMLToArray($child, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);
		if (isset($children[$elementName])) {
			if ($first) {
				$temp = $children[$elementName];
				unset($children[$elementName]);
				$children[$elementName][] = $temp;
				$first = false;
			}
			$children[$elementName][] = $value;
		} else {
			$children[$elementName] = $value;
		}
	}
	if (count($children) > 0) {
		if (!$flattenChildren) {
			$return[$childrenKey] = $children;
		} else {
			$return = array_merge($return, $children);
		}
	}

	$attributes = array();
	foreach ($xml->attributes() as $name => $value) {
		$attributes[$name] = trim($value);
	}
	if (count($attributes) > 0) {
		if (!$flattenAttributes) {
			$return[$attributesKey] = $attributes;
		} else {
			$return = array_merge($return, $attributes);
		}
	}

	return $return;
}

//Clean the inside of the tags
function clean_inside_tags($txt) {
	return preg_replace("/(<[A-Z]+?)\s(.*?)+(>{1}?)/is", "$1$3", $txt);
}

function valid_email_address($mail) {
	$user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
	$domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
	$ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
	$ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';
	return preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $mail);
}

function digit2($n) {
	return str_pad($n, 2, '0', STR_PAD_LEFT);
}

function prepare_review($s, $allowTags = false, $additionalTags = '') {
	if (!$s)
		return '';
	$allowTags = ($allowTags !== false) ? $allowTags : '<p><u><strike><s><em><i><strong><b><br><ul><ol><li><span>';
	$allowTags.=$additionalTags;
	$s = str_replace('&nbsp;', ' ', $s);
	$s = (clean_inside_tags(strip_tags($s, $allowTags)));
	$s = texttourl($s);
	return trim($s);
}

function getDateFromString(&$s) {
	$parts = explode('-', $s);
	$rs = '0000-00-00';
	if (count($parts) < 2) {
		$parts = explode('.', $s);
		if (count($parts) > 2 && (checkdate($parts[1], $parts[0], $parts[2]))) {
			$rs = digit2((int) $parts[2]) . '-' . digit2((int) $parts[1]) . '-' . digit2((int) $parts[0]);
		}
	} else
	if (count($parts) > 2) {
		if (checkdate($parts[2], $parts[1], $parts[0])) {
			$rs = (int) $parts[0] . '-' . (int) $parts[2] . '-' . (int) $parts[1];
		}
	}else
		$rs = '0000-00-00';
	return $rs;
}

function _substr($text, $_length) {
	if (mb_strlen($text) < $_length)
		return $text;
	$tlength = mb_strripos(mb_substr($text, 0, $_length, 'UTF-8'), '.', null, 'UTF-8') + 1;
	$length = mb_strripos(mb_substr($text, 0, $_length, 'UTF-8'), ' ', null, 'UTF-8');
	$tlength = $tlength > 1 ? $tlength : $_length;
	$length = $length > 1 ? $length : $_length;
	return mb_substr($text, 0, min($tlength, $length, $_length), 'UTF-8');
}

function close_dangling_tags($html) {
	//сначала берем все открытые теги
	preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU", $html, $result);
	$openedtags = $result[1];

	// после все закрытые
	preg_match_all("#</([a-z]+)>#iU", $html, $result);
	$closedtags = $result[1];
	$len_opened = count($openedtags);

	# все теги закрыты
	if (count($closedtags) == $len_opened) {
		return $html;
	}

	$openedtags = array_reverse($openedtags);
	# close tags
	for ($i = 0; $i < $len_opened; $i++) {
		if (!in_array($openedtags[$i], $closedtags)) {
			$html .= '</' . $openedtags[$i] . '>';
		} else {
			unset($closedtags[array_search($openedtags[$i], $closedtags)]);
		}
	}
	return $html;
}

function timestamp_to_ymd($ts) {
	$ts = explode(' ', $ts);
	return explode('-', $ts[0]);
}

function getBookFilePath($id_file, $id_book, $filetype, $prefix = '') {
	$ft = Config::need('filetypes');
	return $prefix . DIRECTORY_SEPARATOR . (ceil($id_book / 5000)) . '__' . $filetype . DIRECTORY_SEPARATOR . $id_file . '_' . $id_book . '.' . $ft[$filetype];
}

function getBookFilePathFB2Html($id_file, $id_book, $filetype, $prefix = '') {
	$ft = Config::need('filetypes');
	return $prefix . DIRECTORY_SEPARATOR . (ceil($id_book / 5000)) . '__' . $filetype . DIRECTORY_SEPARATOR . $id_file . '_' . $id_book . '.' . $ft[$filetype] . '.body.htm';
}

function getBookFilePathFB2HtmlDownload($id_file, $id_book, $filetype, $prefix = '') {
	$ft = Config::need('filetypes');
	return $prefix . DIRECTORY_SEPARATOR . (ceil($id_book / 5000)) . '__' . $filetype . DIRECTORY_SEPARATOR . $id_file . '_' . $id_book . '.' . $ft[$filetype] . '.html';
}



function getBookFilePathFB2Toc($id_file, $id_book, $filetype, $prefix = '') {
	$ft = Config::need('filetypes');
	return $prefix . DIRECTORY_SEPARATOR . (ceil($id_book / 5000)) . '__' . $filetype . DIRECTORY_SEPARATOR . $id_file . '_' . $id_book . '.' . $ft[$filetype] . '.toc.htm';
}

function getBookDownloadUrl($id_file, $id_book, $filetype) {
	$ft = Config::need('filetypes');
	$www = Config::need('www_path');
	return $www . '/download/' . $filetype . '/' . $id_file . '_' . $id_book;
}

function getBookFileDirectory($id_book, $filetype) {
	$ft = Config::need('filetypes');
	return (ceil($id_book / 5000)) . '__' . $filetype . DIRECTORY_SEPARATOR;
}

function sort_by_newest_time($a, $b) {
	return ($a['timestamp'] > $b['timestamp']) ? -1 : 1;
}

function sort_by_count($a, $b) {
	return $b['count'] > $a['count'];
}

function sort_by_add_time($a, $b) {
	return $b['add_time'] > $a['add_time'];
}

function sort_by_time($a, $b) {
	return $b['time'] > $a['time'];
}

function sort_by_role($a, $b) {
	return $b['role'] < $a['role'];
}

function sort_by_mark($a, $b) {
	return 1;
}

function sort_by_genre($a, $b) {
	return 1;
}

function isISBN13Valid($n) {
	$check = 0;
	for ($i = 0; $i < 13; $i+=2)
		$check += substr($n, $i, 1);
	for ($i = 1; $i < 12; $i+=2)
		$check += 3 * substr($n, $i, 1);
	return $check % 10 == 0;
}

function isISBNValid($n) {
	$check = 0;
	for ($i = 0; $i < 9; $i++)
		$check += ( 10 - $i) * substr($n, $i, 1);
	$t = substr($n, 9, 1); // tenth digit (aka checksum or check digit)
	$check += ( $t == 'x' || $t == 'X') ? 10 : $t;
	return $check % 11 == 0;
}

function extractISBN($string) {
	$isbn = str_replace(array('o', 'O'), '0', $string);
	$x = '';
	if (!ctype_digit($isbn)) {
		$out = '';

		for ($i = 0; $i < strlen($isbn); $i++) {
			if ($isbn[$i] == 'x' || $isbn[$i] == 'X')
				$x = 'X';
			if (is_numeric($isbn[$i]))
				$out.=$isbn[$i];
		}
		$isbn = $out;
	}
	if (isISBN13Valid($isbn . $x) || isISBNValid($isbn . $x)) {
		return $isbn;
	} else
	if (is_numeric($isbn) && strlen($isbn) > 8)
		return $isbn;
	return false;
}

//BBCODE
function _bbcode_filter_process(&$body, $format = -1) {

	$quote_text = 'Цитата:';
	$quote_user = '\\1 wrote:';

	// Encode all script tags to prevent XSS html injection attacks
	$body = preg_replace(array('#<script([^>]*)>#i', '#</script([^>]*)>#i'), array('&lt;script\\1&gt;', '&lt;/script\\1&gt;'), $body);

	// Find all [code] tags and check if they contain a newline. If we find a newline,
	// that [code] should be rendered as a block, otherwise it will still be inline
	$mode = 2;
	$pre = array();
	$i = 0;
	if (preg_match_all('#\[code(?::\w+)?\](.*?)\[/code(?::\w+)?\]#si', $body, $code_tags, PREG_SET_ORDER)) {
		foreach ($code_tags as $code_tag) {
			$code_tag[1] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $code_tag[1]);
			if (strpos($code_tag[1], "\n") === FALSE)
				$body = str_replace($code_tag[0], '<code class="bb-code">' . $code_tag[1] . '</code>', $body);
			elseif ($mode) {
				// Strip preformatted code blocks from text during line break processing, replaced below
				$body = str_replace($code_tag[0], "***pRe_sTrInG$i***", $body);
				$pre[$i++] = '<pre class="bb-code-block">' . $code_tag[1] . '</pre>';
			}
			else
				$body = str_replace($code_tag[0], '<pre class="bb-code-block">' . $code_tag[1] . '</pre>', $body);
		}
	}

	// Apply line and paragraph breaks (skipping preformatted code)
	if ($mode) {

		if ($mode == 1)  // Line breaks only (starting with PHP 4.0.5, nl2br() is XHTML compliant)
			$body = nl2br($body);

		if ($mode == 2) { // Line and paragraph breaks (may not always be XHTML compliant)
			$body = preg_replace("/(\r\n|\n|\r)/", "\n", $body);
			$body = preg_replace("/\n\n+/", "\n\n", $body);
			$parts = explode("\n\n", $body);
			for ($i = 0; $i < sizeof($parts); $i++) {
				// No linebreaks if paragraph starts with an HTML tag
				if (!preg_match('/^<.*>/', $parts[$i]))
					$parts[$i] = nl2br($parts[$i]);

				// Some tags should not be in paragraph blocks
				if (!preg_match('/^(?:<|\[)(?:table|list|ol|ul|pre|select|form|blockquote|hr)/i', $parts[$i]))
					$parts[$i] = '<p>' . $parts[$i] . '</p>';
			}
			$body = implode("\n\n", $parts);
		}

		// Reinsert preformatted code blocks
		foreach ($pre as $i => $code_tag)
			$body = str_replace("***pRe_sTrInG$i***", $code_tag, $body);
	}

	// Add closing tags to prevent users from disruping your site's HTML
	// (required for nestable tags only: [list] and [quote])
	preg_match_all('/\[quote/i', $body, $matches);
	$opentags = count($matches['0']);
	preg_match_all('/\[\/quote\]/i', $body, $matches);
	$unclosed = $opentags - count($matches['0']);
	for ($i = 0; $i < $unclosed; $i++)
		$body .= '[/quote]';
	preg_match_all('/\[list/i', $body, $matches);
	$opentags = count($matches['0']);
	preg_match_all('/\[\/list\]/i', $body, $matches);
	$unclosed = $opentags - count($matches['0']);
	for ($i = 0; $i < $unclosed; $i++)
		$body .= '[/list]';

	// begin processing for [size]
	if (stristr($body, '[size=') !== FALSE) { // prevent useless processing
		$arr = array(
		    'tag' => 'size',
		    'pattern' => '#\[\x07=([\d]+)(?::\w+)?\]([^\x07]*)\[/\x07(?::\w+)?\]#esi',
		    'replacement' => '"<span style=\"font-size:". _bbcode_round_size_val(\'$1\') ."px\">". str_replace(\'\"\', \'"\', \'$2\') ."</span>"',
		    'text' => $body);
		$body = _bbcode_replace_nest_tag($arr);
	} // end processing for [size]
	// begin processing for [color]
	if (stristr($body, '[color=') !== FALSE) { // prevent useless processing
		$arr = array(
		    'tag' => 'color',
		    'pattern' => '#\[\x07=([\#\w]+)(?::\w+)?\]([^\x07]*)\[/\x07(?::\w+)?\]#si',
		    'replacement' => '<span style="color:$1">$2</span>',
		    'text' => $body);
		$body = _bbcode_replace_nest_tag($arr);
	} // end processing for [color]
	// begin processing for [font]
	if (stristr($body, '[font=') !== FALSE) { // prevent useless processing
		$arr = array(
		    'tag' => 'font',
		    'pattern' => '#\[\x07=([\w\s]+)(?::\w+)?\]([^\x07]*)\[/\x07(?::\w+)?\]#si',
		    'replacement' => '<span style="font-family:$1">$2</span>',
		    'text' => $body);
		$body = _bbcode_replace_nest_tag($arr);
	} // end processing for [font]
	// begin processing for [list] and [*]
	if (stristr($body, '[list') !== FALSE) { // prevent useless processing
		$l_type = array(
		    NULL => array('style' => 'circle', 'tag' => 'ul'),
		    'c' => array('style' => 'circle', 'tag' => 'ul'),
		    'd' => array('style' => 'disc', 'tag' => 'ul'),
		    's' => array('style' => 'square', 'tag' => 'ul'),
		    '1' => array('style' => 'decimal', 'tag' => 'ol'),
		    'a' => array('style' => 'lower-alpha', 'tag' => 'ol'),
		    'A' => array('style' => 'upper-alpha', 'tag' => 'ol'),
		    'i' => array('style' => 'lower-roman', 'tag' => 'ol'),
		    'I' => array('style' => 'upper-roman', 'tag' => 'ol')
		);
		$body = preg_replace('#(\[[/]*)list(.*?\])#si', "$1\x07$2", $body);

		// replace to <li> tags - [*]..[*]|[*]..[/list]
		$body = preg_replace('#\[\*(?::\w+)?\]([^\x07]*?)(?=\s*?(\[\*(?::\w+)?\]|\[/\x07(?::\w+)?\]))#si', '<li>$1</li>', $body);
		// add </li> tags to nested <li> - [/list]..[/list]
		$body = preg_replace('#(\[/\x07(?::\w+)?\])(?=[^\x07]*?\[/\x07(?::\w+)?\])#si', '$1</li>', $body);
		// add </li> tags to nested <li> - [/list]..[*]..[list]
		$body = preg_replace('#(\[/\x07(?::\w+)?\])(?=[^\x07]*?\[\*(?::\w+)?\][^\x07]*?\[\x07.*(?::\w+)?\])#si', '$1</li>', $body);
		// replace to <li> tags for nested <li> - [*]..[list]
		$body = preg_replace('#\[\*(?::\w+)?\]([^\x07]*)?(?=\[\x07.*(?::\w+)?\])#si', '<li>$1', $body);

		// replace to <ol>/<ul> and </ol>/</ul> tags
		// It will be better to use &count and do-while, if php 5 or higher.
		while (preg_match("#\[\x07[=]*((?-i)[cds1aAiI])*(?::\w+)?\]([^\x07]*)\[/\x07(?::\w+)?\]#si", $body)) {
			$body = preg_replace("#\[\x07[=]*((?-i)[cds1aAiI])*(?::\w+)?\]([^\x07]*)\[/\x07(?::\w+)?\]#esi", '"<". $l_type[\'$1\']["tag"] ." class=\"bb-list\" style=\"list-style-type:". $l_type[\'$1\']["style"] .";\">". str_replace(\'\"\', \'"\', \'$2\') ."</". $l_type[\'$1\']["tag"] .">"', $body);
		}

		// remove <br /> tags
		$body = preg_replace('#(<[/]*([uo]l|li).*>.*)<br />#i', '$1', $body);
	} // end processing for [list] and [*]
	// Define BBCode tags
	$preg = array(
	    // Implement [notag]
	    '#\[notag(?::\w+)?\](.*?)\[/notag(?::\w+)?\]#sie' => '_bbcode_notag_tag(\'\\1\')',
	    // Headings and indexes - articles will almost always need them
	    '#\[h([1-6])(?::\w+)?\](.*?)\[/h[1-6](?::\w+)?\]#sie' => '_bbcode_generate_heading(\\1, \'\\2\')',
	    '#\[index\s*/?\]#sie' => '_bbcode_generate_index($body)',
	    '#\[index style=(ol|ul)\]#sie' => '_bbcode_generate_index($body, \'\\1\')',
	    // Font, text and alignment
	    '#\[align=(\w+)(?::\w+)?\](.*?)\[/align(?::\w+)?\]#si' => '<span style="text-align:\\1">\\2</span>',
	    '#\[float=(left|right)(?::\w+)?\](.*?)\[/float(?::\w+)?\]#si' => '<span style="float:\\1">\\2</span>',
	    '#\[justify(?::\w+)?\](.*?)\[/justify(?::\w+)?\]#si' => '<div style="text-align:justify;">\\1</div>',
	    '#\[(b|strong)(?::\w+)?\](.*?)\[/(b|strong)(?::\w+)?\]#si' => '<span style="font-weight:bold">\\2</span>',
	    '#\[(i|em)(?::\w+)?\](.*?)\[/(i|em)(?::\w+)?\]#si' => '<span style="font-style:italic">\\2</span>',
	    '#\[u(?::\w+)?\](.*?)\[/u(?::\w+)?\]#si' => '<span style="text-decoration:underline">\\1</span>',
	    '#\[s(?::\w+)?\](.*?)\[/s(?::\w+)?\]#si' => '<s>\\1</s>',
	    '#\[sup(?::\w+)?\](.*?)\[/sup(?::\w+)?\]#si' => '<sup>\\1</sup>',
	    '#\[sub(?::\w+)?\](.*?)\[/sub(?::\w+)?\]#si' => '<sub>\\1</sub>',
	    '#\[center(?::\w+)?\](.*?)\[/center(?::\w+)?\]#si' => '<div style="text-align:center">\\1</div>',
	    '#\[left(?::\w+)?\](.*?)\[/left(?::\w+)?\]#si' => '<div style="text-align:left">\\1</div>',
	    '#\[right(?::\w+)?\](.*?)\[/right(?::\w+)?\]#si' => '<div style="text-align:right">\\1</div>',
	    // Links without a protocol, with a protocol, and with good looking text
	    '#\[url(?::\w+)?\]www\.([\w:;&,%+~!=@\/\.\-\#\?]+?)\[/url(?::\w+)?\]#si' => '<a href="http://www.\\1" class="bb-url">\\1</a>',
	    '#\[url(?::\w+)?\]([\w:;&,%+~!=@\/\.\-\#\?]+?)\[/url(?::\w+)?\]#si' => '<a href="\\1" class="bb-url">\\1</a>',
	    '#\[url=www\.([\w:;&,%+~!=@\/\.\-\#\?]+?)\](.*?)\[/url(?::\w+)?\]#si' => '<a href="http://www.\\1" class="bb-url">\\2</a>',
	    '#\[url=([\w:;&,%+~!=@\/\.\-\#\?]+?)\](.*?)\[/url(?::\w+)?\]#si' => '<a href="\\1" class="bb-url">\\2</a>',
	    // Anchor tags for linking within documents
	    '#\[anchor=(\w+)(?::\w+)?\](.*?)\[/anchor(?::\w+)?\]#si' => '<a name="\\1">\\2</a>',
	    // Images without or with client-side sizing
	    '#\[img(?::\w+)?\]([\w:;&,~%+!=@\/\.\-\#\?]+)\[/img(?::\w+)?\]#si' => '<img src="\\1" alt="" class="bb-image" />',
	    '#\[img=(\d+)x(\d+)(?::\w+)?\]([\w:;&,~%+!=@\/\.\-\#\?]+)\[/img(?::\w+)?\]#si' => '<img width="\\1" height="\\2" alt="" src="\\3" class="bb-image" />',
	    '#\[img=([\w\s:;,\.\-\'\(\)]+)(?::\w+)?\]([\w:;&,~%+!=@\/\.\-\#\?]+)\[/img(?::\w+)?\]#si' => '<img alt="\\1" src="\\2" class="bb-image" />',
	    '#\[img align=(left|right|center)(?::\w+)?\]([\w:;&,~%+!=@\/\.\-\#\?]+)\[/img(?::\w+)?\]#si' => '<img src="\\2" alt="" align="\\1" class="bb-image" />',
	    // Flash animations and other special effects
	    '#\[flash=(\d+)x(\d+)(?::\w+)?\]([\w:;&,~%+!=@\/\.\-\#\?]+)\[/flash(?::\w+)?\]#si' => '<object type="application/x-shockwave-flash" data="\\3" width="\\1" height="\\2"><param name="movie" value="\\3" /></object>',
	    // Acronyms & abbreviations - show description when mouse moves over tag
	    '#\[acronym=([\w\s-,\.]+)(?::\w+)?\](.*?)\[/acronym(?::\w+)?\]#si' => '<acronym title="\\1">\\2</acronym>',
	    '#\[abbr=([\w\s-,\.]+)(?::\w+)?\](.*?)\[/abbr(?::\w+)?\]#si' => '<abbr title="\\1">\\2</abbr>',
	    // Quoting with or without specifying the source
	    '#\[quote(?::\w+)?\]#i' => '<div class="bb-quote">' . $quote_text . '<blockquote class="bb-quote-body">',
	    '#\[quote=(?:&quot;|"|\')?(.*?)["\']?(?:&quot;|"|\')?\]#i' => '<div class="bb-quote"><b>' . $quote_user . '</b><blockquote class="bb-quote-body">',
	    '#\[/quote(?::\w+)?\]#si' => '</blockquote></div>',
	    // PHP code blocks (syntax highlighted)
	    '#\[php(?::\w+)?\](?:[\r\n])*(.*?)\[/php(?::\w+)?\]#sie' => '_bbcode_php_tag(\'\\1\')',
	    // Links to popular sites
	    '#\[google(?::\w+)?\]([\w\s-]+?)\[/google(?::\w+)?\]#si' => '<a href="http://www.google.com/search?q=\\1">\\1</a>',
	    '#\[wikipedia(?::\w+)?\]([\w\s-]+?)\[/wikipedia(?::\w+)?\]#si' => '<a href="http://www.wikipedia.org/wiki/\\1">\\1</a>',
	    '#\[youtube\]([0-9a-zA-Z_\-]+)\[/youtube\]#si' => '<object width="425" height="366"><param name="movie" value="http://www.youtube.com/v/\\1"></param><embed src="http://www.youtube.com/v/\\1" type="application/x-shockwave-flash" width="425" height="366"></embed></object>',
	    // Table tags
	    '#\[table\](.+?)\[/table\]#si' => '<table class="bb-table">\\1</table>',
	    '#\[(row|r|tr)\](.+?)\[/(row|r|tr)\]#si' => '<tr>\\2</tr>',
	    '#\[(row|r|tr) color=([\#\w]+)\](.+?)\[/(row|r|tr)\]#si' => '<tr bgcolor=\\2>\\3</tr>',
	    '#\[(header|head|h)\](.+?)\[/(header|head|h)\]#si' => '<th>\\2</th>',
	    '#\[(col|c|td)\](.+?)\[/(col|c|td)\]#si' => '<td valign="top">\\2</td>',
	    // Cleanup table output (td, th and tr tags)
	    '#<([\/]?)t([dhr])><br />#si' => '<\\1t\\2>',
	    '#<table(.+?)><br />#si' => '<table\\1>',
	);
	$body = preg_replace(array_keys($preg), array_values($preg), $body);

	// Simple replacements (str_replace is faster than preg_replace)
	$str = array(
	    // Horizontal delimiter
	    '[hr]' => '<hr class="bb-hr" />',
	    // Force line break
	    '[br]' => '<br class="bb-br" />',
	    // Force space
	    '[sp]' => '&nbsp;',
	);
	$body = str_replace(array_keys($str), array_values($str), $body);

	// We cannot evaluate the variable in callback function because
	// there is no way to pass the $format variable

	$body = preg_replace(
		array('#\[email(?::\w+)?\](.*?)\[/email(?::\w+)?\]#si', '#\[email=(.*?)(?::\w+)?\]([\w\s]+)\[/email(?::\w+)?\]#si'), array('<a href="mailto:\\1" class="bb-email">\\1</a>', '<a href="mailto:\\1" class="bb-email">\\2</a>'), $body);


	// Turns web and e-mail addresses into clickable links
	if (1) {

		// pad with a space so we can match things at the start of the 1st line
		$ret = ' ' . $body;
		// padding to already filtered links
		$ret = preg_replace('#(<a.+>)(.+</a>)#i', "$1\x07$2", $ret);

		// matches an "xxx://yyyy" URL at the start of a line, or after a space.
		// xxxx can only be alpha characters.
		// yyyy is anything up to the first space, newline, comma, double quote or <
		$ret = preg_replace('#(?<=^|[\t\r\n >\(\[\]\|])([a-z]+?://[\w\-]+\.([\w\-]+\.)*\w+(:[0-9]+)?(/[^ "\'\(\n\r\t<\)\[\]\|]*)?)((?<![,\.])|(?!\s))#i', '<a href="\1">\1</a>', $ret);

		// matches a "www|ftp.xxxx.yyyy[/zzzz]" kinda lazy URL thing
		// Must contain at least 2 dots. xxxx contains either alphanum, or "-"
		// zzzz is optional.. will contain everything up to the first space, newline,
		// comma, double quote or <.
		$ret = preg_replace('#([\t\r\n >\(\[\|])(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\'\(\n\r\t<\)\[\]\|]*)?)#i', '\1<a href="http://\2.\3">\2.\3</a>', $ret);

		// matches an email@domain type address at the start of a line, or after a space.
		// Note: Only the followed chars are valid; alphanums, "-", "_" and or ".".
		if (0)
			$ret = preg_replace_callback("#([\t\r\n ])([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", '_bbcode_encode_mailto', $ret);
		else
			$ret = preg_replace('#([\t\r\n ])([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i', '\\1<a href="mailto:\\2@\\3">\\2@\\3</a>', $ret);

		// Remove our padding
		$ret = str_replace("\x07", '', $ret);
		$body = substr($ret, 1);
	}

	if (1) {
		$body = preg_replace('#<a([^>]+)>#i', '<a\\1 rel="nofollow">', $body);
	}

	return $body;
}

function _bbcode_replace_nest_tag($arr = NULL) {
	$text = preg_replace('#(\[[/]*)' . $arr['tag'] . '(.*?\])#si', "$1\x07$2", $arr['text']);
	// It will be better to use &count and do-while, if php 5 or higher.
	while (preg_match($arr['pattern'], $text)) {
		$text = preg_replace($arr['pattern'], $arr['replacement'], $text);
	}
	return $text;
}

function _bbcode_notag_tag($text = NULL) {
	return str_replace(array('[', ']', '@'), array('&#91;', '&#93;', '&#64;'), stripslashes($text));
}

function _bbcode_php_tag($text = NULL) {
	return '<pre>' . highlight_string(str_replace('<br />', '', stripslashes($text)), true) . '</pre>';
}

function _bbcode_generate_heading($level, $text) {
	$anchor = preg_replace('/([\s]+)/', '_', $text);
	$anchor = preg_replace('/([\W]+)/', '', $anchor);
	return '<h' . $level . '><a name="' . $anchor . '">' . $text . '</a></h' . $level . '>';
}

function _bbcode_generate_index($body, $tag = 'ol') {
	$level = 0;
	$index = '<' . $tag . ">\n";
	$close_tags = 0;

	if (preg_match_all('#\[h([1-6]).*?\](.*?)\[/h([1-6]).*?\]#si', $body, $head_tags, PREG_SET_ORDER)) {
		foreach ($head_tags as $head_tag) {
			if ($level == 0)
				$level = $head_tag[1];
			$anchor = preg_replace('/([\s]+)/', '_', $head_tag[2]);
			$anchor = preg_replace('/([\W]+)/', '', $anchor);

			if ($head_tag[1] > $level) {
				$index .= '<' . $tag . ">\n";
				$index .= '<li><a href="#' . $anchor . '">' . $head_tag[2] . "</a>\n";
				$close_tags++;
				$level = $head_tag[1];
			} else if ($head_tag[1] < $level) {
				while ($close_tags > 0) {
					$index .= '</' . $tag . ">\n";
					$close_tags--;
				}
				$index .= '<li><a href="#' . $anchor . '">' . $head_tag[2] . "</a>\n";
				$level = $head_tag[1];
			} else {
				$index .= '<li><a href="#' . $anchor . '">' . $head_tag[2] . "</a>\n";
				$level = $head_tag[1];
			}
		}
	}
	while ($close_tags >= 0) {
		$index .= '</' . $tag . ">\n";
		$close_tags--;
	}
	return $index;
}

function _bbcode_round_size_val($size) {
	if ($size < 6)
		return 6;
	elseif ($size > 48)
		return 48;
	else
		return $size;
}
