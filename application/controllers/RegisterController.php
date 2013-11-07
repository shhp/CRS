<?php

class RegisterController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
        $form = new Application_Form_Register();
        
        if ($form->isValid($_POST)) {
        	 $username = $form->getValue("username");
        	 $password = $form->getValue("password");
        	 
        	 $db = Zend_Registry::get("_db");
        	 $data = array(
        	 			"username" => "$username",
        	 			"password" => "$password"
        	 		);
        	 try{
        	 	$db->insert('user_auth',$data);
        	 	$article_recommended_data = array(
        	 								"user_name" => "$username",
        	 								"article_id" => 0
        	 			);
        	 	$db->insert('articles_recommended',$article_recommended_data);
        	 	$session = Zend_Registry::get('_session');
        	 	$session->user = $username;
        	 	$this->_redirect("/profile/newtag");
        	 }
        	 catch(Zend_Db_Adapter_Exception $e){
        	 	$this->view->message = "code:".$e->getCode();
        	 }
        	 catch(Zend_Exception $e){
        	 	if($e->getCode() == 23000)
        	 		$this->view->message = "username:".$username." already exists!";
        	 }
        	
        }
        
        $this->view->form = $form;
    }


}

