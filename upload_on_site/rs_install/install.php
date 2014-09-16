<?php

$query = BD("CREATE TABLE IF NOT EXISTS `{$config['db_name']}`.`refer` (
`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`username` VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL ,
`from` VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
) ENGINE = MYISAM;");

if(!$query){exit('Error Mysql! Install str: #3');}

?>