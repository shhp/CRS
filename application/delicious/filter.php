<?php
require_once("java/Java.inc");

//$wikiAPI = new Java('wikipedia.api.WikipediaAPI');

function filter_tags(){	
	global $wikiAPI;
	global $argv;
	
	$tags_dat_file = fopen("./tags.dat","r");
	
	/* ignore the first line */
	fgets($tags_dat_file);
	
	$line_start = $argv[1];
	$line_to_read = $argv[2];
	$line_read = 0;
	$filtered_tags = "";
	
	for($i = 1; $i < $line_start; $i++){
		fgets($tags_dat_file);			
	}
		
	while($line = fgets($tags_dat_file)){			
		if($line_read < $line_to_read){
			$line_read++;
			$line_split = explode("\t",$line);
			$id = $line_split[0];
			$tag = substr($line_split[1],0,-2);//substr($line_split[1],0,-1)
			//print "substring: ".$line_split[1]."\n";
			//print "isPage: ".java_values($wikiAPI->isPage("google"))."\n";
			if(java_values($wikiAPI->isPage($tag))){
				print $id;
				print " ".$tag;
				print " "."yes\n";
				//print $id." ".$tag."  yes"."\n";		
				$filtered_tags .= $id."\t".$tag."\n";
			}
			else{
				print $id;
				print " ".$tag;
				print " "."no\n";
				//print $id." ".$tag."  no"."\n";
			}
		}
		else
			break;
	}

	$tags_output_file = fopen("./delicious_tags.txt","a+");
	fwrite($tags_output_file,$filtered_tags);
}

function filter_bookmarks(){
	$bookmarks_dat_file = fopen("./bookmarks.dat","r");
	
	/* ignore the first line */
	fgets($bookmarks_dat_file);
	
	$bookmarks = "";
	while($line = fgets($bookmarks_dat_file)){
		$line_split = explode("\t",$line);
		$id = $line_split[0];
		$md5 = $line_split[1];
		$principalUrl = substr($line_split[5],0,-2);
		print $id." ".$md5." ".$principalUrl."\n";
		$bookmarks .= $id."\t".$md5."\t".$principalUrl."\n";
	}
	
	$bookmarks_output_file = fopen("./bookmarks.txt","a+");
	fwrite($bookmarks_output_file,$bookmarks);
}

filter_bookmarks();
	
?>