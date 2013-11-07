<?php

require_once("java/Java.inc");

class Article{
	public $id;
	public $title;
	public $description;
	public $url;
	public $tags;
	public $relevance;
	public $related_tag;
	public $potential_interests;
	
// 	private $wikiAPI;
	
	public function __construct($id,$title,$description,$url,$tags){
		$this->id = $id;
		$this->title = $title;
		$this->description = $description;
		$this->url = $url;
		$this->tags = $tags;
		
		//$this->potential_interests = array();
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
				if($relevance == 1.0) break;
				else{
					$this->potential_interests = array_values($potential_interests);
				}
			}
		}
		
		$this->relevance = $max_relevance;
		$this->related_tag = $related_tag;
		
		return $max_relevance;
	}
	
	private function cal_relevance($user_tag,$wikiAPI,&$potential_interests){
		$relevance = 0.0;
		$bylink_num = 0;
		try{
			foreach($this->tags as $tag){
				$tag_relevance = java_values($wikiAPI->getRelevance($tag,$user_tag));
				
				if($tag_relevance > 0.9 && $tag_relevance != 1){
					$potential_interests[] = $tag;
				}
				
				if($tag_relevance == 1){
					return 1.0;
				}
				elseif($tag_relevance == 0.95){
					$bylink_num++;
					if($bylink_num >= 2)
						return 1.0;
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
}