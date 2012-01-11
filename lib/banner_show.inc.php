<?
# Lifter002: TODO
# Lifter007: TEST
# Lifter003: TEST


function banner_show() {
    $query = "SELECT ad_id, priority, startdate, enddate "
           . "FROM banner_ads WHERE priority > 0";
    $statement = DBManager::get()->query($query);

    // array that contains banner ids and an offset
    // offsets start with 0 and increase by pow(2, priority)
    // a random number between 0 and sum(pow(2,priorities)) is
    // drawn and the banner with the highest offset smaller than
    // this number is chosen

    $banners = array();
    $now = time();
    $sum = 0;
    // collect banners to consider, build banners array
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        if ($row['startdate'] && $now < $row['startdate']) {
            continue;
        }
        if ($row['enddate'] && $now > $row['enddate']) {
            continue;
        }
        $sum += pow(2, $row['priority']);
        $banners[] = array(
            'ad_id'  => $row['ad_id'],
            'offset' => $sum
        );
    }

    // draw random number and select banner
    $x=mt_rand(0,$sum);
    $ad_id=0;
    foreach ($banners as $i) {
        if ($i['offset'] >= $x) {
            $ad_id = $i['ad_id'];
            break;
        }
    }

    // if no banner found, leave
    if (!$ad_id) {
        return;
    }
    
    // load banner
    $query = "SELECT * FROM banner_ads WHERE ad_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($ad_id));
    $banner = $statement->fetch(PDO::FETCH_ASSOC);
        
    switch ($banner['target_type']) {
        case 'url': // links to specific url
            $template = '<a href="%s" target="_blank">%s</a>';
            break;
        case 'seminar':  // links to seminar
            $template = '<a href="details.php?sem_id=%s">%s</a>';
            break;
        case 'user': // links to user
            $template = '<a href="about.php?username=%s">%s</a>';
            break;
        case 'inst': // links to institute
            $link='<a href="institut_main.php?auswahl=%s">%s</a>';
            break;
        case 'none': // just the image
            $banner['target'] = ''; // clear target
            $template = '%s%s'; // adjust template to display only the image
            break;
    }

    $pic = sprintf('<img src="%s/banner/%s" border="0" %s>', 
                   $GLOBALS['DYNAMIC_CONTENT_URL'], $banner['banner_path'],
                   tooltip($banner['alttext']));
    $link = sprintf($template, $banner['target'], $pic);

    print '<table width="100%" cellpadding="5"><tr><td align="center">';
    print $link;
    print '</td></tr></table>';

    // update view counter
    DBManager::get()
        ->prepare("UPDATE banner_ads SET views = views + 1 WHERE ad_id = ?")
        ->execute(array($ad_id));
}
