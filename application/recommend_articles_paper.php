<?php

define('RELEVANCE_THRESHOLD',0.0);

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
	
	private $wikiRelevance = array();
	private $SERelevance = array();
	
	private $website;
	private $begin_id;
	private $limit_num;

	public function __construct()
	{
		global $argv;
		//var_dump($argv);
		//$this->website = $argv[1];
		//$this->begin_id = intval($argv[1]);
		//$this->limit_num = intval($argv[2]);
		
		$this->db = new Zend_Db_Adapter_Pdo_Mysql(array(
				'host'     => 'localhost',
				'username' => 'root',
				'password' => 'yshen800',
				'dbname'   => 'crs'
		));
		$this->wikiAPI = new Java('wikipedia.api.WikipediaAPI');
		
		$this->get_users();
	}

	public function analyse_articles()
	{
	    global $argv;
		$begin_id = $argv[1];
		$limit_num = $argv[2];
		//$username = $argv[3];
		
		//print $limit_num."\n";
		$user_count = 0;
		foreach($this->users as $user){
			if( $user != null){//|| $user == "yanghuan"  $user == "wangchong" ||  $user == "yanghuan"
				$user_count++;
				$articles = $this->get_articles($begin_id,$limit_num,$user);
				$interests = $this->get_user_interests($user);
				
				/*analyze articles*/
				try{
					$count = 0;
					foreach($articles as $article){
						$count++;
						print "\n########## user $user_count--$user ---tweet $count ############\n";
						
						$relevance = $article->match_pke($interests,$this->wikiAPI);
						print "related tag: ".$article->related_tag."\n";
						print "relevance: ".$article->relevance."\n";
						
						//if(count($article->potential_interests) == 0)
							//$article->potential_interests[] = $article->related_tag;
					
						if($relevance > RELEVANCE_THRESHOLD){
							$rank_score = $article->relevance*$interests[$article->related_tag];
							$this->recommend_article($article,$user,$rank_score);
// 							$this->recommend_article($article,'yanghuan',$rank_score);
						}
							
						//print "----------------------------\n";
					}
				}catch(Exception $e){
					
				}
				/*$articles = $this->db->fetchAll("select * from recommend_articles where user_name='$user'");
				$interests = $this->get_user_interests($user);
				
				foreach($articles as $article){
					$article_id = $article['article_id'];
					$relevance = $article['relevance'];
					$related_tag = $article['related_tag'];
					$new_rank_score = $relevance * $interests[$related_tag];
					
					$data = array('rank_score' => $new_rank_score);
					$this->db->update("recommend_articles",$data,"user_name='$user' and article_id=$article_id");
				}*/
			}
			
		}

	}
	
	public function click_articles(){
		global $argv;
		$username = $argv[2];
		
		$articles = $this->db->fetchAll("select article_id from recommend_articles where user_name='$username'");
		foreach($articles as $article){
			$article_id = $article['article_id'];
			if($this->should_click($article_id, $username)){
				$this->db->delete("click_feedback", "user_name='$username' and article_id=$article_id");
				$this->db->insert("click_feedback", array("user_name"=>"$username","article_id"=>$article_id));
				print $article_id."\n";
			}
			
		}
		
	}
	
	private function should_click($article_id,$username){
		if($username == 'wangchong'){
			if($article_id > 1495 && $article_id < 1596)
				return true;
			if($article_id > 1195 && $article_id < 1296)
				return true;
			if($article_id > 1595 && $article_id < 1646)
				return true;
			if($article_id > 1675 && $article_id < 1706)
				return true;
			if($article_id > 1705 && $article_id < 1756)
				return true;
			if($article_id > 1845 && $article_id < 1876)
				return true;
// 			if($article_id == 1938 )
// 				return true;
			if($article_id > 1935 && $article_id < 1996)
				return true;
			
			return false;
		}
		else if($username == 'yanghuan'){
			if($article_id > 1395 && $article_id < 1496)
				return true;
			if($article_id > 1295 && $article_id < 1396)
				return true;
			if($article_id > 1645 && $article_id < 1676)
				return true;
			if($article_id > 1755 && $article_id < 1846)
				return true;
			if($article_id > 1875 && $article_id < 1936)
				return true;
				
			return false;
		}
	}
	
	public function analyse_click_ratio(){
		global $argv;
		$username = $argv[2];
		$topN = $argv[3];
		
		$topN_article_ids = $this->db->fetchAll("select article_id from recommend_articles where user_name='$username' order by rank_score desc limit $topN");
		$click_count = 0;
		foreach($topN_article_ids as $article_id){
			$id = $article_id['article_id'];
			$clicked = $this->db->fetchAll("select article_id from click_feedback where user_name='$username' and article_id=$id");
			if(count($clicked) > 0)
				$click_count++;
		}
		
		print "clicked: ".$click_count."  ratio:".$click_count/($topN*1.0)."\n";
	}
	
	public function recommend_pkd(){
		print "-------------User:$user---------------\n";
			$articles = $this->get_articles($this->begin_id,$this->limit_num);
			$interests = $this->get_user_interests($user);
			
			/*analyze articles*/
			try{
				foreach($articles as $article){
					print "article id: ".$article->id."\n";
					
					$relevance = $article->match_pke($interests,$this->wikiAPI);
					print "related tag: ".$article->related_tag."\n";
					print "relevance: ".$article->relevance."\n";
					
					if(count($article->potential_interests) == 0)
						$article->potential_interests[] = $article->related_tag;
				
					if($relevance > RELEVANCE_THRESHOLD){
						$rank_score = $article->relevance*$interests[$article->related_tag];
						$this->recommend_article($article,$user,$rank_score);
						$this->recommend_article($article,'yanghuan',$rank_score);
					}
						
					print "----------------------------\n";
				}
			}catch(Exception $e){
				
			}
	}
	
	public function simple_keywords_extraction(){
		global $argv;
		$begin_id = $argv[2];
		$limit_num = $argv[3];
		
		$keywords = $this->db->fetchAll("select * from article_tag_pke where article_id>$begin_id and interest_tag='travel' limit $limit_num");
		foreach($keywords as $keyword){
			$data = array(
					"article_id" => $keyword['article_id'],
					"tag" => $keyword['tag'],
					"weight" => $keyword['weight'],
					"interest_tag" => 'Lockheed Martin'
					);
			$this->db->insert("article_tag_pke", $data);
		}
		
		$keywords = $this->db->fetchAll("select * from article_tag_pke where article_id>$begin_id and interest_tag='travel' limit $limit_num");
		foreach($keywords as $keyword){
			$data = array(
					"article_id" => $keyword['article_id'],
					"tag" => $keyword['tag'],
					"weight" => $keyword['weight'],
					"interest_tag" => 'United Kingdom'
					);
			$this->db->insert("article_tag_pke", $data);
		}
		
		$keywords = $this->db->fetchAll("select * from article_tag_pke where article_id>$begin_id and interest_tag='travel' limit $limit_num");
		foreach($keywords as $keyword){
			$data = array(
					"article_id" => $keyword['article_id'],
					"tag" => $keyword['tag'],
					"weight" => $keyword['weight'],
					"interest_tag" => 'Miami'
					);
			$this->db->insert("article_tag_pke", $data);
		}
		
		$keywords = $this->db->fetchAll("select * from article_tag_pke where article_id>$begin_id and interest_tag='science' limit $limit_num");
		foreach($keywords as $keyword){
			$data = array(
					"article_id" => $keyword['article_id'],
					"tag" => $keyword['tag'],
					"weight" => $keyword['weight'],
					"interest_tag" => 'Water'
					);
			$this->db->insert("article_tag_pke", $data);
		}
		
		$keywords = $this->db->fetchAll("select * from article_tag_pke where article_id>$begin_id and interest_tag='science' limit $limit_num");
		foreach($keywords as $keyword){
			$data = array(
					"article_id" => $keyword['article_id'],
					"tag" => $keyword['tag'],
					"weight" => $keyword['weight'],
					"interest_tag" => 'Education'
					);
			$this->db->insert("article_tag_pke", $data);
		}
		
		$keywords = $this->db->fetchAll("select * from article_tag_pke where article_id>$begin_id and interest_tag='health' limit $limit_num");
		foreach($keywords as $keyword){
			$data = array(
					"article_id" => $keyword['article_id'],
					"tag" => $keyword['tag'],
					"weight" => $keyword['weight'],
					"interest_tag" => 'Human'
					);
			$this->db->insert("article_tag_pke", $data);
		}
		
		$keywords = $this->db->fetchAll("select * from article_tag_pke where article_id>$begin_id and interest_tag='health' limit $limit_num");
		foreach($keywords as $keyword){
			$data = array(
					"article_id" => $keyword['article_id'],
					"tag" => $keyword['tag'],
					"weight" => $keyword['weight'],
					"interest_tag" => 'Eating'
					);
			$this->db->insert("article_tag_pke", $data);
		}
		
		$keywords = $this->db->fetchAll("select * from article_tag_pke where article_id>$begin_id and interest_tag='health' limit $limit_num");
		foreach($keywords as $keyword){
			$data = array(
					"article_id" => $keyword['article_id'],
					"tag" => $keyword['tag'],
					"weight" => $keyword['weight'],
					"interest_tag" => 'Virus'
					);
			$this->db->insert("article_tag_pke", $data);
		}
		
		$keywords = $this->db->fetchAll("select * from article_tag_pke where article_id>$begin_id and interest_tag='health' limit $limit_num");
		foreach($keywords as $keyword){
			$data = array(
					"article_id" => $keyword['article_id'],
					"tag" => $keyword['tag'],
					"weight" => $keyword['weight'],
					"interest_tag" => 'Squash (plant)'
					);
			$this->db->insert("article_tag_pke", $data);
		}
		
	}
	
	public function recommend_no_pke(){
		if($user == "paper"){
				print "-------------User:$user---------------\n";
				$articles = $this->get_articles_website($this->website,$this->begin_id);
//www.smashingmagazine.com  www.webmd.com  www.rottentomatoes.com www.gadling.com  www.treehugger.com  seekingalpha.com  www.astromart.com
				$interests = $this->get_user_interests($user);
	// 			print_r($interests);
				
				/*analyze articles*/
				try{
					foreach($articles as $article){
						print "article id: ".$article->id."\n";
						
						$relevance = $article->match($interests,$this->wikiAPI);
						print "related tag: ".$article->related_tag."\n";
						print "relevance: ".$article->relevance."\n";
						
						if(count($article->potential_interests) == 0)
							$article->potential_interests[] = $article->related_tag;
					
						if($relevance > RELEVANCE_THRESHOLD)
							$this->recommend_article($article,$user);
							
					    print "----------------------------\n";
					}
				}catch(Exception $e){
					
				}
			}
	}
	
	public function analyse_threshold(){
		global $argv;
		$min_relevance = $argv[2];
		$max_relevance = $argv[3];
		$user_name = $argv[4];
		
// 		$articles = $this->db->fetchAll(" select * from recommend_articles where user_name='$user_name' and relevance >= $min_relevance and relevance < $max_relevance");
// 		$recommend_num = count($articles)*1.0;
// 		$good_recommendation_num = 0;
		
// 		foreach($articles as $article){
// 			$article_id = $article['article_id'];
// 			$related_tag = $article['related_tag'];
// 			if($this->is_good_recommendation($article_id,$related_tag))
// 				$good_recommendation_num++;
// 		}
		$articles = $this->db->fetchAll(" select * from recommend_articles where user_name='$user_name' and relevance >= $min_relevance and relevance < $max_relevance");
		$recommend_num = count($articles)*1.0;
		$good_recommendation_num = 0;
		
		foreach($articles as $article){
			$article_id = $article['article_id'];
			$related_tag = $article['related_tag'];
			if($this->get_origin_tag($article_id) == $related_tag )
				$good_recommendation_num++;
		}
		
		print "good: ".$good_recommendation_num ." recommend: ".$recommend_num."\n";
		print "precision: ". $good_recommendation_num/$recommend_num ." recall: ".$good_recommendation_num/350 ."\n";
	}
	
	private function get_origin_tag($article_id){
		if($article_id < 1296)
			return "finance";
		if($article_id < 1396)
			return "information technology";
		if($article_id < 1496)
			return "health";
		if($article_id < 1596)
			return "travel";
		if($article_id < 1706)
			return "entertainment";
		if($article_id < 1846)
			return "science";
		if($article_id < 1996)
			return "sports";
	}
	
	private function is_good_recommendation($article_id,$related_tag){
		$article_data = $this->db->fetchAll(" select source from article where id = $article_id");
		$source = $article_data[0]['source'];
		//print $article_id." ".$related_tag." ".$source."\n";
		$website_tags = $this->db->fetchAll(" select tag from website_tag where website = '$source'");
		foreach($website_tags as $tag){			
			if($tag['tag'] == $related_tag){
				return true;
			}
		}
		
		return false;
	}
	
	private function categorize(){
		$articles = $this->get_articles_website("www.astromart.com",960);
			$interests = $this->get_user_interests($user);
// 			print_r($interests);
			
			/*analyze articles*/
			try{
				foreach($articles as $article){
					print "article id: ".$article->id."\n";
					$wikiScore = array();
					$SEScore = array();
					foreach($interests as $interest){
						$wikiScore["$interest"] = $this->get_wiki_average($article,$interest);
						$SEScore["$interest"] = $this->get_SE_average($article,$interest);
					}
					$this->wikiRelevance[] = $wikiScore;
					$this->SERelevance[] = $SEScore;
					//$max_article_id = $article->id;
					//$relevance = $article->match($interests,$this->wikiAPI);
					//print "related tag: ".$article->related_tag."\n";
					//print "relevance: ".$article->relevance."\n";
				
					//if($relevance > RELEVANCE_THRESHOLD)
						//$this->recommend_article($article,$user);
				}
			}catch(Exception $e){
				
			}
			//$this->set_article_analyzed_max_id($max_article_id,$user);
			print_r($this->wikiRelevance);
			print "\n";
			print_r($this->SERelevance);
			print "\n";
			
			$result = array();
			$result_str = "";
			for($j = 0; $j <= 10;$j++){
				$e = $j/10.0;
				$result["$e"] = $this->get_categorization_precision("astronomy",$e);
				$result_str .= $result["$e"]."\n";
			}
			print_r($result);
			print "\n".$result_str;
	}
	
	private function get_categorization_precision($category,$e){
		$correct_num = 0;
		for($i = 0; $i < count($this->wikiRelevance); $i++){
			$max_relevance = 0;
			$related_tag = "";
			$wiki = $this->wikiRelevance[$i];
			$se = $this->SERelevance[$i];
			foreach($wiki as $key => $wikiScore){
				$seScore = $se["$key"];
				$relevance = $e*$wikiScore + (1-$e)*$seScore;
				if($relevance > $max_relevance && $relevance >= 0.1){
					$max_relevance = $relevance;
					$related_tag = $key;
				}
			}
			if($related_tag == $category)
				$correct_num++;
		}
		
		return $correct_num;
	}
	
	private function get_wiki_average($article,$interest){
		if(count($article->tags) == 0)
			return 0;
			
		$relevance = 0.0;
		foreach($article->tags as $tag){
			$relevance += java_values($this->wikiAPI->getRelevance($tag,$interest));
		}
		
		return $relevance / count($article->tags);
	}
	
	private function get_SE_average($article,$interest){
		if(count($article->tags) == 0)
			return 0;
			
		$relevance = 0.0;
		foreach($article->tags as $tag){
			$relevance += get_relevance($tag,$interest);
		}
		
		return $relevance / count($article->tags);
	}
	
	private function recommend_article($article,$username,$rank_score){
		$id = $article->id;
		$count = $this->db->delete("recommend_articles", "user_name='$username' and article_id=$id");
		print "delete count: ".$count."\n";
		
		$data = array(
					'user_name' => "$username",
					'article_id' => $article->id,
					'related_tag' => "$article->related_tag",
					'relevance' => $article->relevance,
					'rank_score' => $rank_score
				);
		$this->db->insert("recommend_articles", $data);
		
		/*if($article->relevance > 0){
			$count = $this->db->delete("article_potential_interest", "user_name='$username' and article_id=$id");
			print "delete count potential: ".$count."\n";
		
			$recommend_num = array();
			foreach($article->potential_interests as $p_interest){
				//print "  potential tag: ".$p_interest."\n";
				
				//record each recommended article and related potential interests
				$data = array(
						"user_name" => "$username",
						"potential_interest" => "$p_interest",
						"article_id" => $article->id
				);
				$this->db->insert("article_potential_interest",$data);
				
				//record  recommended_num for each potential interest
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
		}*/
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
		$result = $this->db->fetchAll("select * from user_interest where username='$username'");
		$interests = array();
		
		foreach($result as $row){
			$interests[$row['interest_tag']] = $row['weight'];
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

	private function get_articles($begin_id,$limit,$username)
	{
// 		$result = $this->db->fetchAll("select * from article where id > ($id) order by id desc");
		 
// 		if(count($result) > 0)
// 			$this->set_article_analyzed_max_id($result[0]['id'],$username);
		
		//$result = $this->db->fetchAll("select id from tweets where userId = $username and creationTime < '2010-12-30 0:0:0' limit $begin_id,$limit");
		$result = $this->db->fetchAll("select * from news where id in (select * from news_for_recommendation)");
		$articles = array();
		 
		foreach($result as $row){
			$id = $row['id'];
			$article_tags = $this->get_article_tags($id);
			$articles[] = new Article($id,
									  "",
					                  "",
					                  '',
					                  $article_tags);
		}
		
		return $articles;
	}
	
	private function get_articles_website($website,$min_id)
	{		
		$result = $this->db->fetchAll("select * from article where source='$website' and id > $min_id order by id asc limit $this->limit_num");
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
		$result = $this->db->fetchAll("select * from article_tag where article_id=$id");
		$tags = array();
		foreach($result as $row)
			$tags[$row['tag']] = $row['weight'];
		 
		return $tags;
	}



}

/*The process will recommend articles for all users repeatedly*/
$recommend_engine = new RecommendArticles();

$recommend_engine->analyse_articles();	
//$recommend_engine->analyse_threshold();	
//$function = $argv[1];
//$recommend_engine->{$function}();
//$recommend_engine->analyse_click_ratio();	


