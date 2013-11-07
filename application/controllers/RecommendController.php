<?php

define('RELEVANCE_THRESHOLD',0.5);

require_once dirname(__FILE__)."../../models/Article.php";

class RecommendController extends Zend_Controller_Action
{
	private $username;
	private $article_recommended_max_id;
	private $user_interests;
	private $articles;
	private $db;

    public function init()
    {
        /* Initialize action controller here */
    	$session = Zend_Registry::get('_session');
    	if(!isset($session->user)){
    		$this->_redirect('/');
    	}
    	
    	$this->username = $session->user;
    	$this->db = Zend_Registry::get('_db');
    	$this->user_interests = array();
    	$this->articles = array();
    	
//     	$this->get_user_interests();
    	$this->get_article_recommended_max_id();
//     	$this->get_articles();
    }

    public function indexAction()
    {
        // action body
        $recommend_articles = $this->get_recommend_articles();
//         foreach($this->articles as $article){
//         	if($article->match($this->user_interests) > RELEVANCE_THRESHOLD)
//         		$recommend_articles[] = $article;
//         }
        
    	/*
    	 * handle ajax
    	 */
    	if($this->getRequest()->isXmlHttpRequest()){
//     		$test_articles = array(new Article(1, "test", "test article", "", array()),new Article(1, "test2", "test article2", "", array()));
    		$response_articles = array();
    		foreach($recommend_articles as $article){
    			$r_article = array();
    			$r_article['id'] = $article->id;
    			$r_article['title'] = $article->title;
    			$r_article['link'] = $article->url;
    			$r_article['description'] = $article->description;
    			$r_article['related_tag'] = $article->related_tag;
    			$r_article['relevance'] = $article->relevance;
    			$response_articles[] = $r_article;
    		}
    		$response = array(
    						"article_num" => count($recommend_articles),
    						"articles" => $response_articles
    				);
    		$this->record_recommend($recommend_articles);
    		
    		$this->_helper->json($response);
    	}
    	else{
    		$this->view->articles = $recommend_articles;
    		$this->view->username = $this->username;
    		$this->record_recommend($recommend_articles);
    	}
    	
    	
    }
    
    public function historyAction(){
    	$result = array();
    	
    	if(isset($_REQUEST['tag'])){
    		$tag = $_REQUEST['tag'];
    		$all_articles = $this->db->fetchAll("select * from recommend_articles where user_name='$this->username' and article_id <= ($this->article_recommended_max_id) and related_tag = '$tag' order by article_id desc");
    	}
    	else
    		$all_articles = $this->db->fetchAll("select * from recommend_articles where user_name='$this->username' and article_id <= ($this->article_recommended_max_id) order by article_id desc");
    	 
    	foreach($all_articles as $row){
    		$article_id = $row['article_id'];
    		$related_tag = $row['related_tag'];
    		$relevance = $row['relevance'];
    	
    		$the_article = $this->db->fetchAll("select * from article where id=$article_id");
    		if(count($the_article) > 0){
    			$article = new Article($article_id,
    					$the_article[0]['title'],
    					$the_article[0]['description'],
    					$the_article[0]['url'],
    					array());
    			$article->related_tag = $related_tag;
    			$article->relevance = $relevance;
    			$result[] = $article;
    		}
    	}
    	
    	$this->view->username = $this->username;
    	$this->view->articles = $result;
    }
    
    public function clickarticleAction()
    {
    	if(isset($_POST['id'])){
    		$id = $_POST['id'];
    		$data = array(
    		    				"user_name" => "$this->username",
    		    				"article_id" => $id
    		    		);
    	    $this->db->insert('click_feedback',$data);
//     		$result = $this->db->fetchAll("select * from article_interest where user_name='$this->username' and article_id=$id");
//     		if(count($result) > 0){
//     			$related_tag = $result[0]['interest_tag'];
//     			$result = $this->db->fetchAll("select * from recommended_read where user_name='$this->username' and interest_tag='$related_tag'");
//     			if(count($result) > 0){
//     				$update_data = array(
//     						"read_num" => $result[0]['read_num'] + 1
//     				);
//     				$this->db->update("recommended_read",$update_data,"user_name='$this->username' and interest_tag='$related_tag'");
//     			}
//     		}
    		
//     		$result = $this->db->fetchAll("select * from article_potential_interest where user_name='$this->username' and article_id=$id");
//     		if(count($result) > 0){
//     			$potential_interest = $result[0]['potential_interest'];
//     			$result = $this->db->fetchAll("select * from potential_recommended_read where user_name='$this->username' and potential_interest='$potential_interest'");
//     			if(count($result) > 0){
//     				$update_data = array(
//     						"read_num" => $result[0]['read_num'] + 1
//     				);
//     				$this->db->update("potential_recommended_read",$update_data,"user_name='$this->username' and potential_interest='$potential_interest'");
//     			}
//     		}
//     		$data = array(
//     				"username" => "$this->username",
//     				"interest_tag" => "$tag"
//     		);
//     		$this->db->insert('user_interest',$data);
    		$this->_helper->json(array("result" => $id));
    	}
    }
    
    private function record_recommend($recommend_articles){
    	$recommend_num = array();
    	foreach($this->user_interests as $interest){
    		$recommend_num["$interest"] = 0;
    	}
    	 
    	foreach($recommend_articles as $article){
    		$data = array(
    				"user_name" => "$this->username",
    				"interest_tag" => "$article->related_tag",
    				"article_id" => $article->id
    		);
    		$this->db->insert("article_interest",$data);
    		$recommend_num["$article->related_tag"]++;
    	}
    	 
    	foreach($recommend_num as $key => $num){
    		if($recommend_num[$key] > 0){
    			$result = $this->db->fetchAll("select * from recommended_read where user_name='$this->username' and interest_tag='$key'");
    			if(count($result) > 0){
    				$update_data = array(
    						"recommended_num" => $result[0]['recommended_num'] + $num
    				);
    				$this->db->update("recommended_read",$update_data,"user_name='$this->username' and interest_tag='$key'");
    			}
    			else{
    				$insert_data = array(
    						"user_name" => "$this->username",
    						"interest_tag" => "$key",
    						"recommended_num" => $num,
    						"read_num" => 0
    				);
    				$this->db->insert("recommended_read",$insert_data);
    			}
    		}
    	}
    }
    
    private function get_recommend_articles(){
    	$result = array();
    	
    	$all_articles = $this->db->fetchAll("select * from recommend_articles where user_name='$this->username' and article_id > ($this->article_recommended_max_id) order by article_id desc");
    	if(count($all_articles) > 0)
    		//$this->set_article_recommended_max_id($all_articles[0]['article_id']);
    	
		$all_articles = $this->db->fetchAll("select * from recommend_articles where user_name='$this->username' and article_id > ($this->article_recommended_max_id) order by rank_score desc");
    	foreach($all_articles as $row){
    		$article_id = $row['article_id'];
    		$related_tag = $row['related_tag'];
    		$relevance = $row['relevance'];
    		
    		$the_article = $this->db->fetchAll("select * from article where id=$article_id");
    		if(count($the_article) > 0){
    			$article = new Article($article_id,
    					               $the_article[0]['title'],
    					               $the_article[0]['description'],
    					               $the_article[0]['url'], 
    					               array());
    			$article->related_tag = $related_tag;
    			$article->relevance = $relevance;
    			$result[] = $article; 
    		}
    	}
    	
    	return $result;
    }
    
    private function get_user_interests()
    {
    	$result = $this->db->fetchAll("select interest_tag from user_interest where username='$this->username'");
    	foreach($result as $row){
    		$this->user_interests[] = $row['interest_tag'];
    	}
    }
    
    private function get_article_recommended_max_id()
    {
    	$result = $this->db->fetchAll("select article_id from articles_recommended where user_name='$this->username'");
    	if(count($result) > 0){
    		$this->article_recommended_max_id = $result[0]['article_id'];
    	}
    	else{
    		$this->article_recommended_max_id = 0;
    	}
    }
    
    private function set_article_recommended_max_id($id)
    {
    	$bind = array("article_id" => $id);
    	$this->db->update("articles_recommended",$bind,"user_name='$this->username'");
    }
    
    private function get_articles()
    {
    	$result = $this->db->fetchAll("select * from article where id > ($this->article_recommended_max_id) order by id desc");
    	
    	if(count($result) > 0)
    		$this->set_article_recommended_max_id($result[0]['id']);
    	
    	foreach($result as $row){
    		$id = $row['id'];
    		$article_tags = $this->get_article_tags($id);
    		$this->articles[] = new Article($id,
    										$row['title'], 
    										$row['description'], 
    										$row['url'], 
    										$article_tags);
    	}
    }
    
    private function get_article_tags($id)
    {
    	$result = $this->db->fetchAll("select tag from article_tag where article_id=$id");
    	$tags = array();
    	foreach($result as $row)
    		$tags[] = $row['tag'];
    	
    	return $tags;
    }
    


}

