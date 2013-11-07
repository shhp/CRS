<?php

class IndexController extends Zend_Controller_Action
{

	private $session;
	
    public function init()
    {
        /* Initialize action controller here */
    	$this->session = Zend_Registry::get('_session');
    }

    public function indexAction()
    {
        // action body
        if(isset($this->session->user)){
			$this->_redirect('/recommend');
        	//$this->_forward("index","recommend");
        }

    	$loginForm = new Application_Form_Login();
    	
    	if ($loginForm->isValid($_POST) && $loginForm->login->isChecked()) {
    	
    		$adapter = new Zend_Auth_Adapter_DbTable(
    				Zend_Registry::get("_db"),
    				'user_auth',
    				'username',
    				'password'
    				//'MD5(CONCAT(?, password_salt))'
    		);
    	
    		$adapter->setIdentity($loginForm->getValue('username'));
    		$adapter->setCredential($loginForm->getValue('password'));
    	
    		$auth   = Zend_Auth::getInstance();
    		$result = $auth->authenticate($adapter);
    	
    		if ($result->isValid()) {
    			$this->_helper->FlashMessenger('Successful Login');
				$this->view->message = 'Successful Login';
				
				$this->session->user = $loginForm->getValue('username');
				
     			$this->_redirect('/recommend');
				//$this->_forward("index","recommend");
    			return;
    		}
    		else{
    			$this->_helper->FlashMessenger('Login Failed');
    			$this->view->message = 'Login Failed';
//     			$this->_redirect('/fail');
    		}
    	
    	}
    	elseif($loginForm->register->isChecked()){
     		$this->_redirect('/register');
    		//$this->_forward("index","register");
    	}
    	
    	$this->view->loginForm = $loginForm;
    }

    public function logoutAction()
    {
        // action body
        unset($this->session->user);
        $this->_redirect("/");
    }


}



