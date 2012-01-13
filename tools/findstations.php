<pre><?php

require '../config.php';
require '../include.php';


$cid = Config::need('cid');
$csecret = Config::need('csecret');
$rurl = 'http://metro.ljrate.ru/tools/findstations.php';

function fs_auth($cid, $rurl) {
	$loc = 'https://foursquare.com/oauth2/authenticate?client_id=' . $cid . '&response_type=code&redirect_uri=' . $rurl;
	header('Location: ' . $loc);
}

function fs_get_token($cid, $csecret, $code, $rurl) {
	$url = 'https://foursquare.com/oauth2/access_token?client_id=' . $cid . '&client_secret=' . $csecret . '&grant_type=authorization_code&redirect_uri=' . $rurl . '&code=' . $code;
	$response = file_get_contents($url);
	$response = json_decode($response, true);
	return $response['access_token'];
}

if (!$atoken = Cache::get('atoken')) {
	if (!count($_GET))
		fs_auth($cid, $rurl);

	if (isset($_GET['code']))
		$atoken = fs_get_token($cid, $csecret, $_GET['code'], $rurl);
	Cache::set('atoken', $atoken, 60);
	echo 'got token from fs' . "\n";
}else {
	echo 'got token from cache' . "\n";
}

function fs_query($method, $params) {
	global $atoken;

	$url = 'https://api.foursquare.com/v2/' . $method.'?';
	$params['oauth_token'] = $atoken;
	$params['locale'] = 'ru';
	$params['v'] = '2';
	$pp = array();
	foreach ($params as $f => $v)
		$pp[] = $f . '=' . $v;
	$url.= implode('&', $pp);
	$res = json_decode(file_get_contents($url),1);
	return $res;
	
}
$lastId = isset($_GET['lastId'])?$_GET['lastId']:0;
$query = 'SELECT CONCAT(lat,\',\',lon) FROM metro_stations WHERE lat>0 AND id> '.$lastId.' ORDER BY id LIMIT 1';
$latlon = Database::sql2single($query);

$params['ll'] = $latlon;
$params['limit'] = 300;
$params['intent'] = 'browse';
$params['radius'] = 9000;
$params['categoryId'] = '4bf58dd8d48988d1fd931735';
$places = fs_query('venues/search', $params);


$i=1;
foreach($places['response']['groups'][0]['items'] as $station){
	$realName = str_replace('Метро', '', $station['name']);
	$realName = str_replace('метро', '', $realName);
	$realName = str_replace('станция', '', $realName);
	$realName = str_replace('«', '', $realName);
	$realName = str_replace('»', '', $realName);
	$realName = explode('(',$realName);
	$realName = $realName[0];
	$realName = explode(',',$realName);
	$realName = $realName[0];
	$realName = trim($realName);
	$query = 'UPDATE `metro_stations` SET lat='.$station['location']['lat'].', lon='.$station['location']['lng'].' WHERE title='.Database::escape($realName);
	Database::query($query);
	echo $i++.' '.$realName.' '.$station['location']['lat'].' '.$station['location']['lng']."\n";
}
$lastId++;

header('Location: http://metro.ljrate.ru/tools/findstations.php?lastId='.$lastId);

