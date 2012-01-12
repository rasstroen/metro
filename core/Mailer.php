<?php

/*
 * 
 */

class Mailer {

	public static function send($email_from, $email_to, $name_to, $subject, $data, $template) {
		$body = self::createBody($data, $template);
		$to = self::mime_header_encode($name_to) . ' <' . $email_to . '>';
		$subject = self::mime_header_encode($subject);

		$headers = "From: $email_from\r\n";
		$type = 'html';
		$headers .= "Content-type: text/$type; charset=UTF-8\r\n";
		$headers .= "Mime-Version: 1.0\r\n";
		return mail($to, $subject, $body, $headers);
	}

	/**
	 * Создаем тело письма из массива переменных и XSL шаблона
	 *
	 * @param array $data any data
	 * @param string $template filename from xslt/theme/mail/ folder
	 * @return text тело письма
	 */
	private static function createBody($data, $template) {
		global $current_user;
		$xml = new DOMDocument();
		$xml->loadXML("<xml version=\"1.0\" encoding=\"utf-8\" >" . "<root></root></xml>");
		$rootNode = $xml->getElementsByTagName("root")->item(0);
		$dataNode = $xml->createElement('data');
		foreach ($data as $f => $v)
			$dataNode->setAttribute($f, $v);
		$rootNode->appendChild($dataNode);


		$template = file_get_contents(Config::need('xslt_files_path') . '/' . $current_user->getTheme() . '/mail/' . $template);

		$doc = new DOMDocument();
		$xsl = new XSLTProcessor();
		$doc->loadXML($template);
		$xsl->importStyleSheet($doc);
		// кладем в кеш xslt
		return $xsl->transformToXML($xml);
	}

	private static function mime_header_encode($str, $data_charset = 'UTF-8', $send_charset = 'UTF-8') {
		if ($data_charset != $send_charset) {
			$str = iconv($data_charset, $send_charset, $str);
		}
		return '=?' . $send_charset . '?B?' . base64_encode($str) . '?=';
	}

}