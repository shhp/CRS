<?php

define('RELEVANCE_THRESHOLD',0.3);

require_once "Article.php";
require_once 'Zend/Loader.php';

Zend_Loader::loadClass('Zend_Db_Adapter_Pdo_Mysql');

class RecommendArticles
{
	private $users;
// 	private $article_recommended_max_id;
// 	private $user_interests;
// 	private $articles;
	private $db;
	private $wikiAPI;
	private $break_times;

	public function __construct()
	{
		$this->db = new Zend_Db_Adapter_Pdo_Mysql(array(
				'host'     => 'localhost',
				'username' => 'root',
				'password' => 'yshen800',
				'dbname'   => 'crs'
		));
		$this->wikiAPI = new Java('WikipediaAPI');
		
		$this->get_users();
	}

	public function analyze_articles()
	{
		
		foreach($this->users as $user){
			print "-------------User:$user---------------\n";
			$max_article_id = $this->get_article_analyzed_max_id($user);
			$articles = $this->get_articles($max_article_id, $user);
			$interests = $this->get_user_interests($user);
// 			print_r($interests);
			
			/*analyze articles*/
			try{
				foreach($articles as $article){
					print "article id: ".$article->id."\n";
					if(count($article->tags) == 0){
						if($this->break_times[$user] <= 3){
							$this->break_times[$user]++;
							break;
						}
						else{
							$this->break_times[$user] = 0;
						}
					}
						
					$max_article_id = $article->id;
					$relevance = $article->match($interests,$this->wikiAPI);
					print "related tag: ".$article->related_tag."\n";
					print "relevance: ".$article->relevance."\n";
				
					if($relevance > RELEVANCE_THRESHOLD)
						$this->recommend_article($article,$user);
				}
			}catch(Exception $e){
				
			}
			$this->set_article_analyzed_max_id($max_article_id,$user);
		}

	}
	
	private function recommend_article($article,$username){
		$data = array(
					'user_name' => "$username",
					'article_id' => $article->id,
					'related_tag' => "$article->related_tag",
					'relevance' => $article->relevance
				);
		$this->db->insert("recommend_articles", $data);
		
		if($article->relevance != 1){
			$recommend_num = array();
			foreach($article->potential_interests as $p_interest){
				print "  potential tag: ".$p_interest."\n";
				
				/*record each recommended article and related potential interests*/
				$data = array(
						"user_name" => "$username",
						"potential_interest" => "$p_interest",
						"article_id" => $article->id
				);
				$this->db->insert("article_potential_interest",$data);
				
				/*record  recommended_num for each potential interest*/
				$result = $this->db->fetchAll("select * from potential_recommended_read where user_name='$username' and potential_interest='$p_interest'");
				if(count($result) > 0){
					$update_data = array(
							"recommended_num" => $result[0]['recommended_num'] + 1
					);
					$this->db->update("potential_recommended_read",$update_data,"user_name='$username' and potential_interest='$p_interest'");
				}
				else{
					$insert_data = array(
							"user_name" => "$username",
							"potential_interest" => "$p_interest",
							"recommended_num" => 1,
							"read_num" => 0
					);
					$this->db->insert("potential_recommended_read",$insert_data);
				}
			}
		}
	}


	private function get_users(){
		$result = $this->db->fetchAll("select username from user_auth");
		$this->users = array();
		$this->break_times = array();
		
		foreach($result as $row){
			$username = $row['username'];
			$this->users[] = $username;
			$this->break_times[$username] = 0;
		}
	}
	
	private function get_user_interests($username)
	{
		$result = $this->db->fetchAll("select interest_tag from user_interest where username='$username'");
		$interests = array();
		
		foreach($result as $row){
			$interests[] = $row['interest_tag'];
		}
		
		return $interests;
	}

	private function get_article_analyzed_max_id($username)
	{
		$result = $this->db->fetchAll("select article_id from articles_analyzed where user_name='$username'");
		if(count($result) > 0){
			return $result[0]['article_id'];
		}
		else{
			$this->db->insert("articles_analyzed", array("user_name" => "$username","article_id" => 0));
			return 0;
		}
	}

	private function set_article_analyzed_max_id($id,$username)
	{
		$bind = array("article_id" => $id);
		$this->db->update("articles_analyzed",$bind,"user_name='$username'");
	}

	private function get_articles($id,$username)
	{
// 		$result = $this->db->fetchAll("select * from article where id > ($id) order by id desc");
		 
// 		if(count($result) > 0)
// 			$this->set_article_analyzed_max_id($result[0]['id'],$username);
		
		$result = $this->db->fetchAll("select * from article where id > ($id) order by id asc");
		$articles = array();
		 
		foreach($result as $row){
			$id = $row['id'];
			$article_tags = $this->get_article_tags($id);
			$articles[] = new Article($id,
									  $row['title'],
					                  $row['description'],
					                  $row['url'],
					                  $article_tags);
		}
		
		return $articles;
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

/*The process will recommend articles for all users repeatedly*/
$recommend_engine = new RecommendArticles();
while(true){
	$recommend_engine->analyze_articles();
	sleep(90);
}

