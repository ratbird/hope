<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO


function banner_show() {
	$db = new DB_Seminar;

	$q = 'SELECT ad_id, priority, startdate, enddate FROM banner_ads WHERE priority > 0';
	$db->query($q);
	$sum = 0;

	// array that contains banner ids and an offset
	// offsets start with 0 and increase by pow(2, priority)
	// a random number between 0 and sum(pow(2,priorities)) is
	// drawn and the banner with the highest offset smaller than
	// this number is chosen

	$banners = array();
	$now = time();
	// collect banners to consider
	// build banners array
	while ($db->next_record()) {
		if ($db->f("startdate") && $now < $db->f("startdate")) continue;
		if ($db->f("enddate") && $now > $db->f("enddate")) continue;
		$sum += pow(2, $db->f("priority"));
		$banners[]=array("ad_id"=>$db->f("ad_id"), "offset"=>$sum);
	}

	// draw random number and select banner
	$x=mt_rand(0,$sum);
	$ad_id=0;
	foreach ($banners as $i) {
		if ($i["offset"] >= $x) {
			$ad_id=$i["ad_id"];
			break;
		}
	}

	// display banner if found one
	if ($ad_id) {
		$q="SELECT * FROM banner_ads WHERE ad_id='".$ad_id."'";
		$db->query($q);
		$db->next_record();
		print '<table width="100%" cellpadding="5"><tr><td align="center">';
		$pic='<img src="'.$GLOBALS['DYNAMIC_CONTENT_URL'].'/banner/'.$db->f('banner_path').'" border="0" ' . tooltip($db->f('alttext')) .'>';
		switch ($db->f('target_type')) {
			case 'url':
				$link='<a href="'.$db->f('target').'" target="_blank">'.$pic.'</a>';
				break;
			case 'seminar':
				$link='<a href="details.php?sem_id='.$db->f('target').'">'.$pic.'</a>';
				break;
			case 'user':
				$link='<a href="about.php?username='.$db->f('target').'">'.$pic.'</a>';
				break;
			case 'inst':
				$link='<a href="institut_main.php?auswahl='.$db->f('target').'">'.$pic.'</a>';
				break;
			case 'none':
				$link = $pic;
				break;

		}
		print $link;
		print '</td></tr></table>';

		// update view counter
		$q = "UPDATE banner_ads SET views=views+1 WHERE ad_id='" . $ad_id . "'";
		$db->query($q);
	}

}
