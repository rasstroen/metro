<?php

$root = Config::need('base_path');
chdir($root . DIRECTORY_SEPARATOR);
require_once $root . '/functions/functions.php';

$includePathes = array(
    $root,
    $root . 'core',
    $root . 'modules',
    $root . 'modules/write',
    $root . 'jmodules',
    $root . 'classes/User',
    $root . 'classes/Book',
    $root . 'classes/Solr',
    $root . 'classes/Person',
    $root . 'classes/Event',
    $root . 'classes/Magazine',
    $root . 'classes/BiberLog',
    $root . 'classes/Relations',
    $root . 'classes',
    $root . 'functions',
    $root . 'phplib',
);

set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, $includePathes));

function __autoload($className) {
	require_once($className . '.php');
}