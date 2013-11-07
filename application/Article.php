<?php

require_once("java/Java.inc");
require_once("search_engine.php");

define("WIKI_COEFFICIENT",0.6);

class Article{
	public $id;
	public $title;
	public $description;
	public $url;
	public $tags;
	public $relevance;
	public $related_tag;
	public $potential_interests;
	
	private $db;
	
// 	private $wikiAPI;
	
	public function __construct($id,$title,$description,$url,$tags){
		$this->id = $id;
		$this->title = $title;
		$this->description = $description;
		$this->url = $url;
		$this->tags = $tags;
		
		$this->db = new Zend_Db_Adapter_Pdo_Mysql(array(
				'host'     => 'localhost',
				'username' => 'root',
				'password' => 'yshen800',
				'dbname'   => 'crs'
		));
		
		//$this->potential_interests = array();
	}
	
	public function match_pke($user_interests,$wikiAPI){
		$max_relevance = 0;
		$related_tag = "";
		
		foreach($user_interests as $interest => $weight){
// 			if($this->relate($interest))
// 				return true;
			
			$potential_interests = array();
			//$this->tags = $this->get_article_tags($this->id,$interest);
			//print $interest." ---tags:\n";
			//print_r($this->tags);
			//print "\n";
			$relevance = $this->cal_relevance_pke($interest,$wikiAPI,$potential_interests);
			//print $relevance."\n";
			if($relevance > $max_relevance){
				$max_relevance = $relevance;
				$related_tag = $interest;
				if($relevance == 1.0) {$this->potential_interests = array_values($potential_interests);break;}
				else{
					$this->potential_interests = array_values($potential_interests);
				}
			}
		}
		
		$this->relevance = $max_relevance;
		$this->related_tag = $related_tag;
		
		return $max_relevance;
	}
	
	public function match($user_interests,$wikiAPI){
		$max_relevance = 0;
		$related_tag = "";
		
		foreach($user_interests as $interest){
// 			if($this->relate($interest))
// 				return true;
			$potential_interests = array();
			$relevance = $this->cal_relevance($interest,$wikiAPI,$potential_interests);
			if($relevance > $max_relevance){
				$max_relevance = $relevance;
				$related_tag = $interest;
				if($relevance == 1.0) {$this->potential_interests = array_values($potential_interests);break;}
				else{
					$this->potential_interests = array_values($potential_interests);
				}
			}
		}
		
		$this->relevance = $max_relevance;
		$this->related_tag = $related_tag;
		
		return $max_relevance;
	}
	
	private function get_article_tags($id,$interest)
	{
		$result = $this->db->fetchAll("select tag,weight from article_tag_pke where article_id=$id and interest_tag='$interest'");
		$tags = array();
		foreach($result as $row){
			$tags[$row['tag']] = $row['weight'];
		}
		 
		return $tags;
	}
	
	private function cal_relevance($user_tag,$wikiAPI,&$potential_interests){
		$relevance = 0.0;
		$related_tag_num = 0;
		try{
			foreach($this->tags as $tag){
				if(java_values($wikiAPI->isSynonym($tag,$user_tag)))
					$tag_relevance = 1;
				elseif(java_values($wikiAPI->belongsTo($tag,$user_tag)))
					$tag_relevance = 0.99;
				else
					$tag_relevance = WIKI_COEFFICIENT * java_values($wikiAPI->getRelevance($tag,$user_tag)) + 
												(1-WIKI_COEFFICIENT) * get_relevance($tag,$user_tag);
				
				if($tag_relevance > 0.85){// && $tag_relevance != 1
					$potential_interests[] = $tag;					
				}
				
				if($tag_relevance == 1){
					return 1.0;
				}
 				elseif($tag_relevance > 0.85){
 					$related_tag_num++;
 					if($related_tag_num / count($this->tags) >= 0.5)
 						return 0.85;
 					else 
 						$relevance += $tag_relevance;
 				}
				else{
					$relevance += $tag_relevance;
				}
			}
		}catch(Exception $e){}
		
		
		if(count($this->tags) > 0)
			return $relevance / count($this->tags);
		else
			return 0;
	}
	
	private function cal_relevance_pke($user_tag,$wikiAPI,&$potential_interests){
		$relevance = 0.0;
		$related_tag_num = 0;
		$related_tag_relevance = 0;
		try{
			foreach($this->tags as $tag => $weight){
// 				if(java_values($wikiAPI->isSynonym($tag,$user_tag)))
// 					$tag_relevance = 1;
// 				elseif(java_values($wikiAPI->belongsTo($tag,$user_tag)))
// 					$tag_relevance = 0.99;
// 				else
// 					$tag_relevance = java_values($wikiAPI->getRelevance($tag,$user_tag));//WIKI_COEFFICIENT * java_values($wikiAPI->getRelevance($tag,$user_tag)) + (1-WIKI_COEFFICIENT) * get_relevance($tag,$user_tag);
// 				$tag1 = strtolower($tag);
// 				$tag2 = strtolower($user_tag);
// 				$result = $this->db->fetchAll("select * from tag_cache where tag1='$tag1' and tag2='$tag2'");
// 				if(count($result) > 0){
// 					$tag_relevance = $result[0]['relevance'];
// 				}
				$tag_relevance = java_values($wikiAPI->getWikiScore($tag,$user_tag,true));
				//print "$tag_relevance\n";
// 				print $tag." ".$user_tag." ".$tag_relevance."\n";
				//$this->db->query("insert into tag_cache values('$keyword','$interest',$relevance)");
				
				if($tag_relevance > 0.85){// && $tag_relevance != 1
					$potential_interests[] = $tag;	
					$related_tag_relevance += $tag_relevance;		 			
				}
				
				if($tag_relevance == 1){
					return 1.0;
				}
 				elseif($tag_relevance > 0.85){
 					$related_tag_num++;
 					if($related_tag_num / count($this->tags) >= 0.5)
 						return $related_tag_relevance / $related_tag_num;
 					else 
 						$relevance += $tag_relevance * $weight;
 				}
				else{
					$relevance += $tag_relevance * $weight;
				}
			}
		}catch(Exception $e){}
		
		
		if(count($this->tags) > 0)
			return $relevance;
		else
			return 0;
	}
}