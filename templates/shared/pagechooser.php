<?
$pages = ceil($num_postings / $perPage);
if ($pages <= 1) return;

if ($page) $cur_page = $page; else $cur_page = 1;

$run = true;
$add_dots = false;

// show additional text over thread-postings
// $ret .= "$num_postings "._("Einträge") .", ".  _("Seite") . " $cur_page von $pages &bull; ";

// previous page
if ($cur_page > 1) {
	$ret .= '<a href="'. URLHelper::getLink(sprintf($pagelink, $cur_page-1) ) .'">'. _("zurück") .'</a>&nbsp;|&nbsp;';
}

// show pages
for ($i = 1; $i <= $pages; $i++) {

    if ($pages >= 6) {
        $add_dots = false;
        // show the two first and the two last pages
        if ($cur_page == -1) {
            if (($pages - 2) >= $i && (2 < $i)) {
                $run = false;
            } else {
                $run = true;
            }

            if ($i == 3) {
                $add_dots = true;
            }
        }

        // show the first and the last page, as well as the two pages before and after
        else {
            $run= false;

            if ($cur_page < 3) {
                $start = 1;
                $end = 5;
            } else if ($cur_page > ($pages - 3)) {
                $start = $pages - 4;
                $end = $pages;
            } else {
                $start = $cur_page -2;
                $end = $cur_page + 2;
            }

            if ($start != 1 && $i == 1) {
                $run = true;
            }

            if ($start > 2 && $i == 2) $add_dots = true;

            if ($end != $pages && $i == $pages) {
                $run = true;
                if ($end < $pages - 1) $add_dots = true;
            }

            if ($i >= $start && $i <= $end) {
                $run = true;
            }
        }
    }

    if ($add_dots) {
        $ret .= '&nbsp;|&nbsp;&hellip;';
    }

    // only show pages to choose if they are meant to be shown
    if ($run) {

        if ($i > 1) $ret .= '&nbsp;|&nbsp;';
        if ($cur_page == $i) {
            $ret .= '<b>'. $i.'</b>';
        } else {
            $ret .= '<a href="'. URLHelper::getLink(sprintf($pagelink, $i) ) .'">'. $i .'</a>';
        }
    }
}

// next page
if ($cur_page < ($i-1)) {
	$ret .= '&nbsp;|&nbsp;<a href="'. URLHelper::getLink(sprintf($pagelink, $cur_page+1) ) .'">'. _("weiter") .'</a>';
}

echo $ret;
