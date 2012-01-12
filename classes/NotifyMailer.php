<?php

/*
 * 
 */

class NotifyMailer {

	private static $templates;

	public static function prepareTemplates() {
		$arr = array(
		    'body',
		    'body_plain',
		    'footer',
		    'footer_plain',
		    'header',
		    'header_plain'
		);
		$templates = array();
		foreach ($arr as $template) {
			$template_body = file_get_contents(Config::need('base_path') . 'email_templates' . DIRECTORY_SEPARATOR . $template . '.php');
			$templates[$template] = $template_body;
		}
		self::$templates = $templates;
	}

	public static function send($email_from, $email_to, $name_to, $subject, $message) {
		$to = self::mime_header_encode($name_to) . ' <' . $email_to . '>';
		$body = $message;
		$body_plain = strip_tags(preg_replace("/\<a.*href=\"(.*)\".*?\>(.*?)\<\/a\>+/", "$1", $body));
		// using a template
		eval('$body="' . (self::$templates['header'] . ' ' . self::$templates['body'] . ' ' . self::$templates['footer']) . '";');
		eval('$body_plain="' . (self::$templates['header_plain'] . ' ' . self::$templates['body_plain'] . ' ' . self::$templates['footer_plain']) . '";');
		$crlf = "\r\n";
		$subject = self::mime_header_encode($subject);
		$hdrs = array(
		    'From' => $email_from,
		    'Subject' => $subject
		);
		$mime = new Mail_mime($crlf);

		$mime->setTXTBody($body_plain);
		$mime->setHTMLBody($body);

		$body = $mime->get();
		$hdrs = $mime->headers($hdrs);

		$mail = & Mail::factory('mail');
		return $mail->send($to, $hdrs, $body);
	}

	private static function mime_header_encode($str, $data_charset = 'UTF-8', $send_charset = 'UTF-8') {
		if ($data_charset != $send_charset) {
			$str = iconv($data_charset, $send_charset, $str);
		}
		return '=?' . $send_charset . '?B?' . base64_encode($str) . '?=';
	}

}