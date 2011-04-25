<?php

require ('common.conf.php');

define('VERSION', '0.1.0');

define('PAGESIZEDEF', 15);
define('PAGESIZEMAX', 20);
define('APPIDMINCHARS', 5);
define('APPIDMAXCHARS', 25);
define('VALID_APPID', '/^[a-z][a-z0-9]{'.APPIDMINCHARS.','.APPIDMAXCHARS.'}$/i');
define('MAX_CONCAT_LENGTH', 2048 * 3); # maximum length of group_concant - increasee if media list for tags gets truncated (no utf8 chars will be 1/3 of this)

define('URLBASE', 'http://'.$_SERVER['SERVER_NAME'].'/');
define('SYMBOLSENURLBASE', URLBASE.'media/symbols/EN/');
define('APIURLBASE', $config['APP_URL']);

define('APIMEDIAURLBASE', APIURLBASE.'symbol/EN/');
define('APITAGSURLBASE', APIURLBASE.'tag/EN/');


// hack to allow const reference in strings and heredoc
// use as {$constHack(SYMBOLSENURLBASE)}
// $const = function($constant) why does this not work? it's in docs
function constHack($constant)
{
    return $constant;
}

?>