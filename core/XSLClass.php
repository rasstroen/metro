<?php

// класс генерирует xslt шаблон для страницы
class XSLClass {

        private $xsltFileName = '';

        function __construct($xsltFileName) {
                $this->xsltFileName = $xsltFileName;
        }

        public function getHTML($xml) {
                global $current_user;
                Log::timingplus('XSLTProcessor');
                $xslTemplate = new DOMDocument();
                $filename = Config::need('xslt_files_path') . DIRECTORY_SEPARATOR . $current_user->getTheme() . DIRECTORY_SEPARATOR . $this->xsltFileName;
                if (Config::need('xslcache')) {
                        $xslProcessor = new xsltCache();
                        $xslProcessor->importStyleSheet($filename);
                } else {
                        $xslProcessor = new XSLTProcessor();
                        $xslTemplate->load($filename, LIBXML_NOENT | LIBXML_DTDLOAD);
                        $xslProcessor->importStyleSheet($xslTemplate);
                }

                $html = $xslProcessor->transformToXML($xml);
                Log::timingplus('XSLTProcessor');
                return $html;
        }

}