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

class refer{
	private static function refers(){
		global $player;
		ob_start();
		$query = BD("SELECT `username`,`from` FROM `refer` WHERE `from`='$player'");
		if($query && mysql_num_rows($query)>0){
			while($result = mysql_fetch_array($query)){
				echo '<p>'.$result['username'].'</p>';
			}
		}else{
			echo '<p>-</p>';
		}
		return ob_get_clean();
	}

	private static function form(){
		ob_start();
		include_once(RS_STYLE.'form.html');
		return ob_get_clean();
	}

	private static function checkUser($name){
		global $player, $bd_names, $bd_users;
		$name = mysql_real_escape_string($name);
		$query = BD("SELECT `{$bd_users['login']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$name' AND `{$bd_users['login']}`!='$player'");
		if(!$query){
			exit('Error mysql! STR: #29');
		}elseif(mysql_num_rows($query)<=0){
			return false;
		}
		return true;
	}

	public function myRef(){
		global $player,$bd_names,$bd_money;
		ob_start();
		if(!self::is_Core()){exit;}
		$query = BD("SELECT `username`,`from` FROM `refer` WHERE `username`='$player'");
		if(!$query){exit('Error mysql! STR: #43');}

		if(mysql_num_rows($query)<=0){
			if(isset($_POST['refer'], $_POST['submit'])){
				if(self::checkUser($_POST['refer'])){
					$from	= mysql_real_escape_string($_POST['refer']);
					$insert = BD("INSERT INTO `refer` (`username`,`from`) VALUES ('$player','$from')");
					$update = BD("UPDATE `{$bd_names['iconomy']}` SET `{$bd_money['money']}`={$bd_money['money']}+".RS_ADD." WHERE `{$bd_money['login']}`='$from'");
					if(!$insert || !$update){exit('Error mysql! STR: #50 or #51');}
					header('Location: '.BASE_URL.'go/refer/'); exit;
				}else{
					header('Location: '.BASE_URL.'go/refer/'); exit($_SESSION['rs_error'] = 'Пользователя не существует или вы пытаетесь сделать себя рефералом!');
				}
			}
			$form = self::form();
		}else{
			$result = mysql_fetch_array($query);
			$form = 'Вы пришли по приглашению пользователя "<b>'.htmlspecialchars($result['from']).'</b>"';
		}

		if(isset($_SESSION['rs_error'])){$info = htmlspecialchars($_SESSION['rs_error']);}else{$info='';}

		$refer = self::refers();

		include_once(RS_STYLE.'refer.html');
		if(isset($_SESSION['rs_error'])){unset($_SESSION['rs_error']);}
		return ob_get_clean();
	}

	private static function is_Core(){
		$url = RS_STYLE.'refer.html';
		$core = file_get_contents($url);
		$patern1 = base64_decode('XDxcP1w9UlNfQ09SRVw/XD4=');
		$patern2 = base64_decode('XDxcP1w9UlNfQ09SRV9YXD9cPg==');

		if(!preg_match('/'.$patern1.'/', $core)){return false;}
		if(!preg_match('/'.$patern2.'/', $core)){return false;}
		return true;
	}

	public function install(){
		global $config; // $config['db_name']
		ob_start();
		include_once(MCR_ROOT.'rs_install/install.php');
		include_once(MCR_ROOT.'rs_install/install.html');
		return ob_get_clean();
	}

}

?>