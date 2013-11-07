<?php

require_once dirname(__FILE__)."../../models/Tag.php";
require "java/Java.inc";

class ProfileController extends Zend_Controller_Action
{

    private $session = null;

    private $username = null;

    private $db = null;
    
    private $db_jwpl = null;
    
    private $wikiAPI = null;

    public function init()
    {
        /* Initialize action controller here */
    	$this->session = Zend_Registry::get('_session');
    	if(!isset($this->session->user)){
    		$this->_redirect('/');
    	}
    	else{
    		$this->username = $this->session->user;
    		$this->db = Zend_Registry::get('_db');
    		$this->db_jwpl = $db = new Zend_Db_Adapter_Pdo_Mysql(array(
				'host'     => 'localhost',
				'username' => 'root',
				'password' => 'yshen800',
				'dbname'   => 'jwpl'
		));
    		$this->wikiAPI = new Java("WikipediaAPI");
    	}
    	
    }

    public function indexAction()
    {
        // action body
        $this->view->username = $this->username;
        $this->view->interests = array();
        $this->view->potential_interests = array();
        
        $result = $this->db->fetchAll("select interest_tag from user_interest where username='$this->username'");
        foreach($result as $row){
        	$interest_tag = $row['interest_tag'];
        	$data = $this->db->fetchAll("select * from recommended_read where user_name='$this->username' and interest_tag='$interest_tag'");
        	$recommended_num = 0;
        	$read_num = 0;
        	if(count($data) > 0){
        		$recommended_num = $data[0]['recommended_num'];
        		$read_num = $data[0]['read_num'];
        	}
        	$this->view->interests[] = new Tag($interest_tag, $recommended_num, $read_num);
        }
        
        $result = $this->db->fetchAll("select * from potential_recommended_read where user_name='$this->username' order by read_num desc limit 10");
        foreach($result as $row){
        	$potential_interest = $row['potential_interest'];
//         	$data = $this->db->fetchAll("select * from recommended_read where user_name='$this->username' and interest_tag='$interest_tag'");
            $recommended_num = $row['recommended_num'];
            $read_num = $row['read_num'];
        	$this->view->potential_interests[] = new Tag($potential_interest, $recommended_num, $read_num);
        }
    }

    public function newtagAction()
    {
    	$this->view->username = $this->username;
    	if(isset($_POST['tag'])){
    			$tag = $_POST['tag'];
    			$tag = str_replace("_", " ", $tag);
    			
    			$result = $this->db->fetchAll("select * from user_interest where username = '$this->username' and interest_tag = '$tag'");
    			
    			if(count($result) > 0){
    				$this->_helper->json(array("result" => -1));
    			}
    			else{
    				$tag = str_replace(" ", "_", $tag);
    				$result = $this->db_jwpl->fetchAll("select name from Page where name='$tag'");
    				if(count($result) > 0){
    					$data = array(
    							"username" => "$this->username",
    							"interest_tag" => str_replace("_", " ", $tag)
    					);
    					$this->db->insert('user_interest',$data);
    					$this->_helper->json(array("result" => 1));
    				}
    				else{
    					$this->_helper->json(array("result" => -2));
    				}
    			}
    			
    	}
    }
    
    public function gethintAction()
    {
    	if(isset($_REQUEST['term'])){
    		$tag_prefix = $_REQUEST['term'];
    		$tag_prefix = str_replace(" ", "_", $tag_prefix);
    		$tags = array();
//     		$this->_helper->json(array("asg","sgd"));

    		if(java_values($this->wikiAPI->isAmbiguous($tag_prefix))){
    			$this->_helper->json(java_values($this->wikiAPI->getAllMeanings($tag_prefix)));
    		}
			elseif(java_values($this->wikiAPI->isPage($tag_prefix))){
				$title = java_values($this->wikiAPI->getPageTitle($tag_prefix));
				$this->_helper->json(array("$title"));
			}   
			else{
				$result = $this->db_jwpl->fetchAll("select name from Page where name like '$tag_prefix%' limit 10");
				if(count($result) > 0){
					foreach($result as $row)
						$tags[] = $row['name'];
					 
					$this->_helper->json($tags);
				}
				else
					$this->_helper->json($tags);
			} 		 
    		
//     		if(count($result) > 0){
//     			$this->_helper->json(array("result" => -1));
//     		}
//     		else{
//     			$data = array(
//     					"username" => "$this->username",
//     					"interest_tag" => "$tag"
//     			);
//     			$this->db->insert('user_interest',$data);
//     			$this->_helper->json(array("result" => 1));
//     		}
    		 
    	}
    }


}





