<?php
/* 
 Authour DharmveerMotyar Lucky :). 
 dvmotyar@yahoo.co.in 
 Chat- dharmmotyar@gmail.com :o 
   14 Feb 2009 
    
 This is a class returns no of all result pages From googleSearch 
 You can set it for with Quates result by setting flage $withKts = 'withKts'  
 I think it may be very usefull for SEO realated work. 
  
*/ 

define("THRESHOLD",5);

//set_time_limit(0); 
class GoogleSearchCounter{ 

  function counter($search,$withQuote=false) { 
		
		
	   $search = urlencode($search); 
		
	   if($withQuote) $search='"'.$search.'"';  
	   
	   $curl = curl_init(); 
		
	   $url = "http://www.google.co.in/search?q=".$search."&btnG=Search&meta="; 
	  // print $url."\n";
 
	   curl_setopt($curl, CURLOPT_URL, $url); 
 
	   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		
	   $result=curl_exec ($curl); 
 
	   $error = curl_error($curl); 
 
	   $errorno = curl_errno($curl); 
 
	   curl_close ($curl);     				
//print $result;	   
		
	   //preg_match('/Results <b>.*[<][^a-z]b> - <b>.*[<][^a-z]b> of about <b>.*[<][^a-z]b> for/',$result,$matches); 
	   preg_match('/About .* results<\/div>/',$result,$matches);
	   //print_r($matches[0]);	
		
	  return str_replace(",","",substr($matches[0],6,-14)) * 1.0; 
		
	   } 
                              
} 

class YahooSearchCounter{ 

  function counter($search,$withQuote=false) { 
		
		
	   $search = urlencode($search); 
		
	   if($withQuote) $search='"'.$search.'"';  
	   
	   $curl = curl_init(); 
		
	   $url = "http://search.yahoo.com/search?p=".$search."&norw=1"; 	  
	  // print $url."\n";
 
	   curl_setopt($curl, CURLOPT_URL, $url); 
 
	   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		
	   $result=curl_exec ($curl); 
 
	   $error = curl_error($curl); 
 
	   $errorno = curl_errno($curl); 
 
	   curl_close ($curl);     				
//print $result;	   
		
	   //preg_match('/Results <b>.*[<][^a-z]b> - <b>.*[<][^a-z]b> of about <b>.*[<][^a-z]b> for/',$result,$matches); 
	   preg_match('/<span id="resultCount">.*?<\/span>/',$result,$matches);
	   //print_r($matches[0]);	
		
	  return str_replace(",","",substr($matches[0],strpos($matches[0],'">')+2,-7)) * 1.0; 
		
	   } 
                              
} 

function get_relevance($term1,$term2){
	$search_engine = new YahooSearchCounter(); 
	$quote = false;
	
	$page_counter1 = $search_engine->counter($term1,$quote);
	$page_counter2 = $search_engine->counter($term2,$quote);
	if($quote){
		$term1 = '"'.$term1.'"';
		$term2 = '"'.$term2.'"';
	}
	
	$page_counter_and = $search_engine->counter($term1." and ".$term2);
	//print $page_counter1.",".$page_counter2.",".$page_counter_and."\n";
	
	if($page_counter1 < THRESHOLD || $page_counter2 < THRESHOLD || $page_counter_and < THRESHOLD){
		return 0;
	}
	else{
		//print "jaccard:".jaccard_score($page_counter1,$page_counter2,$page_counter_and)."\n";
		//print "overlap:".overlap_score($page_counter1,$page_counter2,$page_counter_and)."\n";
		//print "dice:".dice_score($page_counter1,$page_counter2,$page_counter_and)."\n";
		$result = 2 * ( 0.6*dice_score($page_counter1,$page_counter2,$page_counter_and) +
						0.4*overlap_score($page_counter1,$page_counter2,$page_counter_and));
		return ($result > 1 ? 1.0 : $result);
	}
}

function jaccard_score($pc1,$pc2,$pca){
	return $pca / ($pc1 + $pc2 - $pca);
}

function overlap_score($pc1,$pc2,$pca){
	$min = ($pc1 < $pc2 ? $pc1 : $pc2);
	//$result = 2 * $pca / $min;
	//return ($result > 1 ? 1.0 : $result);
	return ($min < $pca ? $pca / ($pca+$min) : $pca / $min);
}

function dice_score($pc1,$pc2,$pca){
	//$result = 3 * (2*$pca / ($pc1 + $pc2));
	//return ($result > 1 ? 1.0 : $result);
	return 2*$pca / ($pc1 + $pc2);
}

print get_relevance('climate change','finance');
print get_relevance('climate change','sustainability');

?>