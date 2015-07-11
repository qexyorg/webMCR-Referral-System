<?php
/**
 * Referral-System module for WebMCR
 *
 * Main class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.2.0
 *
 */

// Check Qexy constant
if (!defined('QEXY')){ exit("Hacking Attempt!"); }

class module{

	// Set default variables
	private $user			= false;
	private $db				= false;
	private $api			= false;
	public	$title			= '';
	public	$bc				= '';
	private	$cfg			= array();
	private $csrf_name		= 'ref_main';
	private $mcfg			= array();

	// Set constructor vars
	public function __construct($api){
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->cfg			= $api->cfg;
		$this->api			= $api;
		$this->mcfg			= $this->api->getMcrConfig();

	}

	private function refer_array(){

		$start		= $this->api->pagination($this->cfg['rop_list'], 0, 0); // Set start pagination

		$end		= $this->cfg['rop_list']; // Set end pagination
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];
		$site_ways		= $this->mcfg['site_ways'];
		$uid			= $this->user->id;

		$query = $this->db->query("SELECT `i`.uid, `i`.`date`, `u`.`{$bd_users['login']}`, `u`.`{$bd_users['female']}`, `u`.`default_skin`

									FROM `qx_ref_invites` AS `i`

									LEFT JOIN `{$bd_names['users']}` AS `u`
										ON `u`.`{$bd_users['id']}` = `i`.`uid`

									WHERE `i`.uid_from='$uid'

									ORDER BY `i`.`id` DESC

									LIMIT $start,$end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("list/refer-none.html"); } // Check returned result

		ob_start();

		while($ar = $this->db->get_row($query)){

			$login = $this->db->HSC($ar[$bd_users['login']]);

			$charname = (intval($ar[$bd_users['female']])==0) ? 'Char_Mini_female.png' : 'Char_Mini.png';

			$avatar = (intval($ar['default_skin'])===1) ? 'default/'.$charname.'?refresh='.mt_rand(1000, 9999) : $login.'_Mini.png';

			$data = array(
				"ID"			=> intval($ar['uid']),
				"LOGIN"			=> $login,
				"LINK"			=> ($this->cfg['use_us']) ? BASE_URL.'?mode=users&uid='.$login : 'javascript://',
				"AVATAR"		=> BASE_URL.$site_ways['mcraft'].'/tmp/skin_buffer/'.$avatar,
				"DATE"			=> date("d.m.Y в H:i", $ar['date']),
			);

			$data["USE_US"] = ($this->cfg['use_us']) ? $this->api->sp("list/refer-us.html", $data) : '';
			$data["USE_MAILBOX"] = ($this->cfg['use_mailbox']) ? $this->api->sp("list/refer-mailbox.html", $data) : '';

			echo $this->api->sp("list/refer-id.html", $data);
		}

		return ob_get_clean();
	}

	private function invited_form(){

		$data = array(
			"INVITE"		=> (isset($_GET['invite']) && !empty($_GET['invite'])) ? $this->db->HSC(@$_GET['invite']) : '',
			"API_SET"		=> $this->api->csrf_set($this->csrf_name),
			"API_SECURITY"	=> $this->csrf_name,
		);

		return $this->api->sp("list/refer-form.html", $data);
	}

	private function invited_by($query){

		$bd_users		= $this->mcfg['bd_users'];

		$ar = $this->db->get_row($query);

		$login = $this->db->HSC($ar[$bd_users['login']]);

		$data = array(
			"INVITED_BY"		=> ($this->cfg['use_us']) ? '<a href="'.BASE_URL.'?mode=users&uid='.$login.'">'.$login.'</a>' : $login,
		);

		return $this->api->sp("list/refer-by.html", $data);
	}

	private function refer_list(){

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= 'Главная'; // Set title page

		/*
		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($this->csrf_name)){ $this->api->notify("Hacking Attempt!", "&do=403", "403", 3); }
			
			$this->api->notify("Настройки успешно сохранены", "&do=admin", "Поздравляем!", 1);

		}
		*/

		// Постраничная навигация +
		$sql			= "SELECT COUNT(*) FROM `qx_ref_invites` WHERE uid_from='{$this->user->id}'"; // Set SQL query for pagination function

		$page			= "&pid="; // Set url for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_list'], $page, $sql); // Set pagination
		// Постраничная навигация -
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];

		$query = $this->db->query("SELECT `u`.`{$bd_users['login']}`
									FROM `qx_ref_invites` AS `i`
									INNER JOIN `{$bd_names['users']}` AS `u`
										ON `u`.`{$bd_users['id']}`=`i`.uid_from
									WHERE `i`.uid='{$this->user->id}'");

		if(!$query){ $this->api->notify("Произошла ошибка базы данных main#".__LINE__, '&do=403', 'Внимание!', 3); }

		$data = array(
			"PAGINATION"	=> $pagination,
			"CONTENT"		=> $this->refer_array(),
			"INVITE_FORM"	=> ($this->db->num_rows($query)<=0) ? $this->invited_form() : $this->invited_by($query),
		);

		return $this->api->sp('list/refer-list.html', $data);
	}

	public function _list(){

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($this->csrf_name)){ $this->api->notify("Hacking Attempt!", "", "403", 3); }
			
			$login = $this->db->safesql(@$_POST['login']);
		
			$bd_names		= $this->mcfg['bd_names'];
			$bd_users		= $this->mcfg['bd_users'];
			$bd_money		= $this->mcfg['bd_money'];

			$query = $this->db->query("SELECT `u`.`{$bd_users['id']}`, `i`.`uid`
										FROM `{$bd_names['users']}` AS `u`
										LEFT JOIN `qx_ref_invites` AS `i`
											ON `i`.`uid`='{$this->user->id}'
										WHERE `u`.`{$bd_users['login']}`='$login'");

			if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Пользователь не найден", "", "Ошибка", 3); }

			$ar = $this->db->get_row($query);

			$uid_from = intval($ar[$bd_users['id']]);

			if(!is_null($ar['uid'])){ $this->api->notify("Вы уже являетесь рефералом", "", "Ошибка", 3); }

			if($uid_from == $this->user->id){ $this->api->notify("Нельзя сделать себя рефералом", "", "Ошибка", 3); }

			$ip = $this->api->getIP();
			$time = time();
			$money = $this->cfg['money'];

			$insert = $this->db->query("INSERT INTO `qx_ref_invites`
											(uid, uid_from, ip, `date`)
										VALUES
											('{$this->user->id}', '$uid_from', '$ip', '$time')");

			if(!$insert){ $this->api->notify("Произошла ошибка базы данных main#".__LINE__, "", "Внимание!", 3); }

			$update = $this->db->query("UPDATE `{$bd_names['iconomy']}` SET `{$bd_money['money']}`=`{$bd_money['money']}`+$money WHERE `{$bd_money['login']}`='$login'");

			if(!$update){ $this->api->notify("Произошла ошибка базы данных main#".__LINE__.$this->db->error.". Ошибка таблицы экономики.", "", "Внимание!", 3); }

			$this->api->notify("Вы успешно подтвердили приглашение", "Поздравляем!", 1);

		}

		return $this->refer_list();
	}
}

/**
 * Referral-System module for WebMCR
 *
 * Main class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.2.0
 *
 */
?>