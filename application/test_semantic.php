<?php
require_once("java/Java.inc");
//require_once("search_engine.php");

define("WIKI_COEFFICIENT",0.5);

function get_relevance_test($str1,$str2,$wikiAPI){
	if(java_values($wikiAPI->isSynonym($str1,$str2)))
		$tag_relevance = 1;
	elseif(java_values($wikiAPI->belongsTo($str1,$str2)))
	$tag_relevance = 0.99;
	else
		$tag_relevance = java_values($wikiAPI->getRelevance($str1,$str2));//WIKI_COEFFICIENT * java_values($wikiAPI->getRelevance($str1,$str2)) + (1-WIKI_COEFFICIENT) * get_relevance($str1,$str2);
	
	return $tag_relevance;
}

$wikiAPI = new Java('wikipedia.api.WikipediaAPI');
$tags = array("sports","entertainment","science","finance","information technology","health","travel",);


$tag1 = $argv[1];
$tag2 = $argv[2];

print $wikiAPI->getWikiScore($tag1,$tag2,false)."\n";

