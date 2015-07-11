<?php
/**
 * User-System module for WebMCR
 *
 * Admin class
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

	// Set constructor vars
	public function __construct($api){
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->cfg			= $api->cfg;
		$this->api			= $api;
		$this->mcfg			= $this->api->getMcrConfig();

		if($this->user->lvl < $this->cfg['lvl_admin']){
			$this->api->notify("Доступ запрещен!", "", "403", 3);
		}
	}

	private function main_settings(){
		$api_security		= 'ref_settings';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($api_security)){ $this->api->notify("Hacking Attempt!", "&do=403", "403", 3); }

			$this->cfg['title']			= $this->db->HSC(strip_tags(@$_POST['title']));
			$this->cfg['lvl_access']	= intval(@$_POST['lvl_access']);
			$this->cfg['lvl_admin']		= intval(@$_POST['lvl_admin']);
			$this->cfg['rop_list']		= (intval(@$_POST['rop_list'])<=0) ? 1 : intval(@$_POST['rop_list']);
			$this->cfg['rop_multiple']	= (intval(@$_POST['rop_multiple'])<=0) ? 1 : intval(@$_POST['rop_multiple']);
			$this->cfg['money']			= (floatval(@$_POST['money'])<=0) ? 1 : floatval(@$_POST['money']);
			$this->cfg['use_mailbox']	= (intval(@$_POST['use_mailbox'])===1) ? true : false;
			$this->cfg['use_us']		= (intval(@$_POST['use_us'])===1) ? true : false;

			if(!$this->api->savecfg($this->cfg, 'configs/ref.cfg.php')){
				$this->api->notify("Произошла ошибка сохранения настроек", "&do=admin", "Ошибка!", 3);
			}
			
				$this->api->notify("Настройки успешно сохранены", "&do=admin", "Поздравляем!", 1);

		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_URL.'&do=admin',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Панель управления";

		$data = array(
			"USE_US"		=> ($this->cfg['use_us']===true) ? 'selected' : '',
			"USE_MAILBOX"	=> ($this->cfg['use_mailbox']===true) ? 'selected' : '',
			"API_SET"		=> $this->api->csrf_set($api_security),
			"API_SECURITY"	=> $api_security,
		);

		return $this->api->sp('admin/main.html', $data);
	}

	private function multiple_array(){
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];

		$start		= $this->api->pagination($this->cfg['rop_list'], 0, 0); // Set start pagination

		$end		= $this->cfg['rop_list']; // Set end pagination

		$query = $this->db->query("SELECT `i`.ip, `u`.`{$bd_users['id']}`, `u`.`{$bd_users['login']}`, `u`.`{$bd_users['email']}`, `u`.`{$bd_users['ip']}` AS `uip`, `u`.create_time
									FROM `qx_ref_invites` AS `i`
									LEFT JOIN `{$bd_names['users']}` AS `u`
										ON `u`.`{$bd_users['id']}`=`i`.`uid`
									WHERE `i`.`ip` IN (
										SELECT `ip`
										FROM `qx_ref_invites`
										GROUP BY `ip`
										HAVING COUNT(`ip`) > 1
									)
									ORDER BY `i`.`ip` ASC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("admin/multiple-none.html"); }

		ob_start();

		while($ar = $this->db->get_row($query)){

			$data = array(
				"UID"			=> intval($ar[$bd_users['id']]),
				"LOGIN"			=> $this->db->HSC($ar[$bd_users['login']]),
				"EMAIL"			=> $this->db->HSC($ar[$bd_users['email']]),
				"UIP"			=> $this->db->HSC($ar['uip']),
				"IP"			=> $this->db->HSC($ar['ip']),
				"DATE_REG"		=> $this->db->HSC($ar['create_time']),
			);

			echo $this->api->sp("admin/multiple-id.html", $data);
		}

		return ob_get_clean();
	}

	private function multiple(){
		
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];

		// Постраничная навигация +
		$sql			= "SELECT COUNT(*) FROM `{$bd_names['users']}`
							WHERE `{$bd_users['ip']}` IN (
								SELECT `{$bd_users['ip']}`
								FROM `{$bd_names['users']}`
								GROUP BY `{$bd_users['ip']}`
								HAVING COUNT(`{$bd_users['ip']}`) > 1
							)"; // Set SQL query for pagination function

		$page			= "&pid="; // Set url for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_multiple'], $page, $sql); // Set pagination
		// Постраничная навигация -

		$data = array(
			"PAGINATION"	=> $pagination,
			"CONTENT"		=> $this->multiple_array(),
		);

		return $this->api->sp('admin/multiple-list.html', $data);
	}

	public function _list(){

		$op = (isset($_GET['op'])) ? $_GET['op'] : '';

		switch($op){
			case 'multiple': return $this->multiple(); break;

			default: return $this->main_settings(); break;
		}
	}

}

/**
 * User-System module for WebMCR
 *
 * Admin class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.2.0
 *
 */
?>