<?php

// модуль отвечает за отображение баннеров
class download_module extends BaseModule {

	function generateData() {
		global $current_user;
		/* @var $current_user CurrentUser */
		if (!$current_user->authorized)
			throw new Exception('Auth required');
		$current_user->can_throw('books_download');



		$filetype = Request::get(0);
		list($id_file, $id_book) = explode('_', Request::get(1));

		$can_load = $current_user->canBookDownload($id_book, $id_file);
		if ($can_load !== true)
			if ($can_load[1])
				throw new Exception('You cant download this book - limit of a ' . $can_load[1] . ' books in day exceed');
			else
				throw new Exception('Please prolong your subscription, сучечка!');


		$book = Books::getInstance()->getByIdLoaded($id_book);
		/* @var $book Book */
		if (!$book->loaded)
			throw new Exception('Book doesn\'t exists');

		if (!$filetype || !$id_file || !$id_book) {
			throw new Exception('Wrong download url');
		}
		$realPath = getBookFilePath($id_file, $id_book, $filetype, Config::need('files_path'));
		global $dev_mode;
		if (!is_readable($realPath)) {
			if ($dev_mode)
				throw new Exception('Sorry, file ' . $realPath . ' doesn\'t exists');
			else
				throw new Exception('Sorry, file  doesn\'t exists');
		}

		$current_user->onBookDownload($book->id);
		$current_user->save();
		
		if(Request::get('html') !== false){
			// downloading generated html
			$book->getHTMLDownload();
			$realPath = getBookFilePathFB2HtmlDownload($id_file, $id_book, $filetype, Config::need('files_path'));
			$filetype = 4;
		}
			

		@ob_end_clean();
		$ft = Config::need('filetypes');
		$book->setReaded();
		if (Config::need('smart_download')) {
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			header("Content-Description: File Transfer");
			header('Content-Disposition: attachment; filename="' . $book->getTitle(1) . '.' . $ft[$filetype]);
			header("X-Accel-Redirect: " . str_replace('/w/ru.jnpe.ls2/core', '', $realPath));
			exit();
		}
		//

		header('Content-Disposition: attachment; filename="' . $book->getTitle(1) . '.' . $ft[$filetype]);
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Description: File Transfer");
		readfile($realPath);
		exit();
	}

}