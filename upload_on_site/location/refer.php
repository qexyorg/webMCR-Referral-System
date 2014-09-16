<?php
/*
----------------------------------------
---- Mcr Referrer System by Qexy.org ---
---- Version: 1.1 ----------------------
---- Site: http://qexy.org -------------
---- Support: support@qexy.org ---------
----------------------------------------
*/

if(!defined('MCR') || empty($user)) {header("Location: ".BASE_URL.""); exit;}

define('RS_VERSION', '1.1');
define('RS_STYLE', MCR_STYLE.'Default/refer/');
define('RS_CORE', base64_decode('UmVmZXJyZXIgU3lzdGVt'));
define('RS_CORE_X', base64_decode('PGEgaHJlZj0iaHR0cDovL3FleHkub3JnIj5RZXh5Lm9yZzwvYT4='));
define('RS_COPYRIGHT', 'Qexy.Org'); // Копирайт

// Options
define('RS_ADD', 100); // Добавит пригласившему +100 в iconomy

include_once(MCR_ROOT.'instruments/refer.class.php');

$refer = new refer;

$page = 'Реферальная система';
$menu->SetItemActive('qx_refer');

if(file_exists(MCR_ROOT.'rs_install/install.php')){
	$content_main = $refer->install();
}else{
	$content_main = $refer->myRef();
}


?>