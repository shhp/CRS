<?php
require_once "configure.php";
//require dirname(dirname(__FILE__))."/TextRank.php";

define('RECOMMEND_START_DATE',"'2010-12-30 0:0:0'");
define('RECOMMEND_END_DATE',"'2011-1-6 23:59:59'");
define('TWEET_START_DATE',"'2011-01-03 23:00:00'");

function extract_keywords(){
	global $db;
	global $argv;
	
	$begin = $argv[2];
	$limit = $argv[3];
	
	$tweets = $db->fetchAll("select * from tweets where creationTime < '2010-12-30 0:0:0' and userId in (select username from user_auth) limit $begin,$limit");	// 
	print "tweets number: ".count($tweets_ids)."\n";
	$count = 0;
	foreach($tweets as $tweet){
		$count++;
		$id = $tweet['id'];
		//$title = $article['title'];
		//$tweet = $db->fetchAll("select content from tweets where id=$id");	
		$description = $tweet['content'];
		//print "news id: ".$id."	title:".$title."	content".$description."\n";
		print "\n####### $count ---id $id ########\n";
		$keywords = new Keywords();
		$result = $keywords->addText("", $description,"English");
		foreach($result as $keyword => $weight){
			$data = array(
					"article_id" => $id,
					"tag" => "$keyword",
					"weight" => $weight
					
			);
			$db->insert('article_tag',$data);
		}		
		print_r($result);
		
	}
	
}

function extract_news_keywords(){
	global $db;
	global $argv;
	
	$begin = $argv[1];
	$limit = $argv[2];
	
	//$news = $db->fetchAll("select * from news where publish_date>'2010-12-30 0:0:0' and publish_date < '2011-1-6 23:59:59' limit $begin,$limit");	
	$news = $db->fetchAll("select * from news where id in (select * from news_for_recommendation)");	
	//print "tweets number: ".count($tweets_ids)."\n";
	$count = 0;
	foreach($news as $n){
		$count++;
		$id = $n['id'];
		$title = $n['title'];
		//$tweet = $db->fetchAll("select content from tweets where id=$id");	
		$description = $n['description'].$n['newscontent'];
		//print "news id: ".$id."	title:".$title."	content".$description."\n";
		print "\n####### $count ---id $id ########\n";
		$keywords = new Keywords();
		$result = $keywords->addText($title.".", $description,"English");
		foreach($result as $keyword => $weight){
			$data = array(
					"article_id" => $id,
					"tag" => "$keyword",
					"weight" => $weight
					
			);
			$db->insert('article_tag',$data);
		}		
		print_r($result);
		
	}
	
}

function user_interests(){
	global $db;
	$wikiAPI = new Java('wikipedia.api.WikipediaAPI');
	/************** Select 50 random users ************
	$users = $db->fetchAll("select username from user_auth");
	$random_users = array();
	for($i = 0; $i < 50; $i++){
		$random_index = $i*30 + rand(0,29);
		$random_users[] = $users[$random_index]['username'];
	}
	
	$db->delete("user_auth");
	foreach($random_users as $user){
		$db->insert('user_auth',array('username' => "$user", 'password' => "$user"));
	}
	*************************************************/
	
	/************ Enrich tweets if it is retweet ********	
	$tweets = $db->fetchAll("select * from tweets where retweetedFromPostId is not NULL limit 1000");
	foreach($tweets as $tweet){
		$id = $tweet['id'];
		$content = $tweet['content'];
		//$content = str_replace("RT", "", $content);
		$retweetId = $tweet['retweetedFromPostId'];
		
		$retweet = $db->fetchAll("select content from tweets_sample where id=$retweetId");
		if(count($retweet) > 0){
			print "origin content: ".$content."\n";
			print "retweet: ".$retweet['content'];
			print "\n--------------------------\n";
		}
	}
	****************************************************************/
	
	$users = $db->fetchAll("select username from user_auth");
	foreach($users as $user){
		$userId = $user['username'];
		$hashtags = array();
		
		print "\n----------user ".$userId."---------------\n";
		$tweets = $db->fetchAll("select content from tweets where userId=$userId and creationTime < '2010-12-30 0:0:0' and content like '%#%'");
		foreach($tweets as $tweet){
			//print $tweet['content']."\n";
			$content = $tweet['content'];
			preg_match_all('/#[a-zA-Z0-9]+? /',$content,$matches);
			foreach($matches[0] as $hashtag){
				$tag = str_replace("#","",$hashtag);
				$tag = str_replace(" ","",$tag);
				$tag = strtolower($tag);
				if(!is_numeric($tag) && java_values($wikiAPI->isPage($tag)) && !java_values($wikiAPI->isAmbiguous($tag))){					
					if(isset($hashtags[$tag]))
						$hashtags[$tag]++;
					else
						$hashtags[$tag] = 1;
				}
				
			}
		}
		arsort($hashtags);
		$hashtags = array_slice($hashtags,0,10);
		print_r($hashtags);
		foreach($hashtags as $interest_tag=>$count){
			$db->insert("user_interest",array("username"=>$userId,"interest_tag"=>$interest_tag,"weight"=>1));
		}
	}
	
}

function get_ground_truth(){
	global $db;
	global $argv;
	
	$begin = $argv[1];
	$limit = $argv[2];

	$tweets = $db->fetchAll("select * from tweets where content like '%http://%' and creationTime > '2010-12-30 0:0:0' limit $begin,$limit");
	$count = 0;
	foreach($tweets as $tweet){
		$count++;
		$tweet_id = $tweet['id'];
		$content = $tweet['content'];
		$user_id = $tweet['userId'];
		
		print "\n########### $count ##############\n";
		//print "$content \n";
		preg_match("/http:\/\/[^ ]+/",$content,$match);
		//print "------- $match[0]\n";
		$url = expand_url($match[0]);
		if($url != ''){
			print $url."\n";
			$db->insert("url_in_tweet", array("url" => $url));
			$news = $db->fetchAll("select id from news where url = '$url'");
			foreach($news as $news_entry){
				$news_id = $news_entry['id'];
				print "find $news_id\n";
				$db->insert("nas",array("userId"=>$user_id, "tweetId"=>$tweet_id, "newsid"=>$news_id));
			}
		}
		
	}
	
}

function expand_url($url){
    $knowurl = 'http://knowurl.com/y.php';
	$data = array('url' => $url);

	// use key 'http' even if you send the request to https://...
	$options = array('http' => array('method'  => 'POST','content' => http_build_query($data),'header'=>"Content-Type: application/x-www-form-urlencoded\r\n"));
	$context  = stream_context_create($options);
	$result = file_get_contents($knowurl, false, $context);

	//var_dump($result);
	preg_match("/http:(.+)<br\/>/",$result,$match);
	$originURL = str_replace("<br/>","",$match[0]);
	//$originURL = preg_replace("/\?(.+)/","",$originURL);
	return $originURL;
}

function evaluate(){
	global $db;
	global $argv;
	
	$user_id = $argv[2];
	
	$recommendations_rank = array();
	$rank = 0;
	$recommendations = $db->fetchAll("select * from recommend_articles where user_name=$user_id and article_id in (select * from news_for_recommendation) order by rank_score desc");
	foreach($recommendations as $recommendation){
		$rank++;
		$recommendations_rank[$recommendation['article_id']] = $rank;
	}
	
	$ground_truths = $db->fetchAll("select distinct newsid from nas where userId=$user_id");
	$highest_rank = 100;
	foreach($ground_truths as $ground_truth){
		$newsId = $ground_truth['newsid'];
		$rank = $recommendations_rank[$newsId];
		print "$newsId: $rank\n";
		if($rank < $highest_rank)
			$highest_rank = $rank;
	}
	print "highest rank: $highest_rank  RR: ".(1.0/$highest_rank)."\n";
}

//extract_news_keywords();
//user_interests();
//get_ground_truth();
//evaluate();
$function = $argv[1];
$function();
