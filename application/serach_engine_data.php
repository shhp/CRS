$termA = array("Google","earth","moon","music","bank","bank","tennis","antarctic","election","blood","theorem","financial crisis","computer","volcano","dinosaur","sea","mountaineering","movie","detective fiction","gene");
$termB = array("Facebook","moon","Facebook","guitar","money","tennis","badminton","glacier","debate","transfusion","tourism","stock market","calculator","orange juice","Toy Story","salt","camping","concert","puzzle","wheat");
$search_engine = new YahooSearchCounter(); 
$quote = false;

$jaccard_r = "jaccard\r\n";
$dice_r = "dice\r\n";
$overlap_r = "overlap\r\n";

for($i = 0;$i < 20;$i++){
	$term1 = $termA[$i];
	$term2 = $termB[$i];
	$page_counter1 = $search_engine->counter($term1,$quote);
	$page_counter2 = $search_engine->counter($term2,$quote);
	if($quote){
		$term1 = '"'.$term1.'"';
		$term2 = '"'.$term2.'"';
	}	
	$page_counter_and = $search_engine->counter($term1." and ".$term2);
	
	$jaccard = jaccard_score($page_counter1,$page_counter2,$page_counter_and);
	$dice = dice_score($page_counter1,$page_counter2,$page_counter_and);
	$overlap = overlap_score($page_counter1,$page_counter2,$page_counter_and);
	
	$jaccard_r .= $jaccard.",";
	$dice_r .= $dice.",";
	$overlap_r .= $overlap.",";
	
	print $term1."  ".$term2."  ".$jaccard."  ".$dice."  ".$overlap."\n";
	
}

print $jaccard_r."\r\n".$dice_r."\r\n".$overlap_r."\n";