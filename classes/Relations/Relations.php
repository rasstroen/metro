<?php

class Relations {

        public static $relation_types = array(
            self::RELATION_TYPE_TRANSLATE => 'перевод',
            self::RELATION_TYPE_DUPLICATE => 'дубликат',
            self::RELATION_TYPE_EDITION => 'редакция',
        );
        const RELATION_TYPE_TRANSLATE = 1;
        const RELATION_TYPE_DUPLICATE = 2;
        const RELATION_TYPE_EDITION = 3;

        protected static $error = '';
        protected static $cached_relations = array();
        protected static $cached_prelations = array();


        public static function getLastError() {
                return self::$error;
        }

        public static function setError($s) {
                self::$error = $s;
        }
}