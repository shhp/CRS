<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initDB(){
		$db = new Zend_Db_Adapter_Pdo_Mysql(array(
				'host'     => '127.0.0.1',
				'username' => 'root',
				'password' => '112233',
				'dbname'   => 'crs'
		));
		Zend_Registry::set("_db", $db);
		
		$session = new Zend_Session_Namespace('session');
		Zend_Registry::set("_session", $session);
	}

}

