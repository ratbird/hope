
<table valign="top" class="blank" border="0" cellspacing="1" cellpadding="0">
    <tr>
        <td class="steelgroup0" align="center">
            <table border="0" cellspacing="1" cellpadding="1">
                <tr>
                    <td align="center" class="steelgroup0" valign="top" style="white-space:nowrap;">
                    <? if ($mod == 'NONAV' || $mod == 'NONAVARROWS') : ?>
                        &nbsp;
                    <? else : ?>
                        <a href="<?= $href . $ptime . '&imt=' . mktime(0, 0, -1, $amonth->mon, 15, $amonth->year - 1) ?>"><img border="0" src="<?= Assets::image_path('icons/16/blue/arr_eol-left.png') ?>" <?= tooltip(_("ein Jahr zurück")) ?>></a>
                        <a href="<?= $href . $ptime . '&imt=' . ($amonth->getStart() - 1) ?>"><img border="0" src="<?= Assets::image_path('icons/16/blue/arr_2left.png') ?>" <?= tooltip(_("einen Monat zurück")) ?>></a>
                    </td>
                    <td class="precol1w" colspan="<?= (($mod == 'NOKW')? 5 : 6) ?>" align="center">
                        <?= htmlentities(strftime("%B", $amonth->getStart()), ENT_QUOTES) . ' ' . $amonth->getYear() ?>
                    </td>
                </tr>






   <?
function includeMonth ($imt, $href, $mod = '', $js_include = '', $ptime = '') {
	global $ABSOLUTE_PATH_STUDIP, $RELATIVE_PATH_CALENDAR, $CANONICAL_RELATIVE_PATH_STUDIP;
	require_once $RELATIVE_PATH_CALENDAR . '/lib/CalendarMonth.class.php';

	$amonth = new CalendarMonth($imt);
	$now = mktime(12, 0, 0, date('n', time()), date('j', time()), date('Y', time()), 0);
	$width = '25';
	$height = '25';

	$ret = "<table valign=\"top\" class=\"blank\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\">\n";
	$ret .= "<tr><td class=\"steelgroup0\" align=\"center\">\n";
	$ret .= "<table border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";
	$ret .= "<tr>\n";

	// navigation arrows left
	$ret .= "<td align=\"center\" class=\"steelgroup0\" valign=\"top\" style=\"white-space:nowrap;\">\n";
	if ($mod == 'NONAV' || $mod == 'NONAVARROWS') {
		$ret .= '&nbsp;';
	} else {
		$ret .= "<a href=\"$href$ptime&imt=";
		$ret .= mktime(0, 0, -1, $amonth->mon, 15, $amonth->year - 1) . "\">";
		$ret .= '<img border="0" src="' . Assets::image_path('icons/16/blue/arr_eol-left.png') . '" ';
		$ret .= tooltip(_("ein Jahr zurück")) . "></a>";
		$ret .= "<a href=\"$href$ptime&imt=" . ($amonth->getStart() - 1) . "\">";
		$ret .= '<img border="0" src="' . Assets::image_path('icons/16/blue/arr_2left.png') . '" ';
		$ret .= tooltip(_("einen Monat zurück")) . "></a>\n";
	}
	$ret .= "</td>\n";

	// month and year
	$ret .= '<td class="precol1w" colspan="'. (($mod == 'NOKW')? 5:6). '" align="center">';
	$ret .= sprintf("%s %s</td>\n",
			htmlentities(strftime("%B", $amonth->getStart()), ENT_QUOTES), $amonth->getYear());

	// navigation arrows right
	$ret .= '<td class="steelgroup0" align="center" valign="top" style="white-space:nowrap;">';
	if ($mod == 'NONAV' || $mod == 'NONAVARROWS') {
		$ret .= '&nbsp;';
	} else {
		$ret .=	"<a href=\"$href$ptime&imt=" . ($amonth->getEnd() + 1) . '">';
		$ret .= '<img border="0" src="' . Assets::image_path('icons/16/blue/arr_2right.png') . '" ';
		$ret .= tooltip(_("einen Monat vor")) . "></a>";
		$ret .= "<a href=\"$href$ptime&imt=";
		$ret .= (mktime(0, 0, 1, $amonth->mon, 1, $amonth->year + 1)) . '">';
		$ret .= '<img border="0" src="' . Assets::image_path('icons/16/blue/arr_eol-right.png') . '" ';
		$ret .= tooltip(_("ein Jahr vor")) . "></a>\n";
	}
	$ret .= "</td></tr>\n";

	// weekdays
	$ret .= "<tr>\n";
	$day_names_german = array('MO', 'DI', 'MI', 'DO', 'FR', 'SA', 'SO');
	foreach ($day_names_german as $day_name_german)
		$ret .= "<td align=\"center\" class=\"precol2w\" width=\"$width\">" . wday("", "SHORT", $day_name_german) . "</td>\n";
	if ($mod != 'NOKW')
		$ret .= "<td class=\"precol2w\" width=\"$width\">&nbsp;</td>";
	$ret .= "</tr>\n</table></td></tr>\n<tr><td class=\"blank\">";
	$ret .= "<table class=\"blank\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\">";

	// Im Kalenderblatt ist links oben immer Montag. Das muss natuerlich nicht der
	// Monatserste sein. Es muessen evtl. noch Tage des vorangegangenen Monats
	// am Anfang und des folgenden Monats am Ende angefuegt werden.
	$adow = date('w', $amonth->getStart());
	if ($adow == 0)
		$adow = 6;
	else
		$adow--;
	$first_day = $amonth->getStart() - $adow * 86400 + 43200;
	// Ist erforderlich, um den Maerz richtig darzustellen
	// Ursache ist die Sommer-/Winterzeit-Umstellung
	$cor = 0;
	if ($amonth->mon == 3)
		$cor = 1;

	$last_day = ((42 - ($adow + date("t", $amonth->getStart()))) % 7 + $cor) * 86400
	 	        + $amonth->getEnd() - 43199;

	for ($i = $first_day, $j = 0; $i <= $last_day; $i += 86400, $j++) {
		$aday = date("j", $i);
		// Tage des vorangehenden und des nachfolgenden Monats erhalten andere
		// style-sheets
		$style = '';
		if (($aday - $j - 1 > 0) || ($j - $aday  > 6))
			$style = 'light';

		// Feiertagsueberpruefung
		$hday = holiday($i);

		if ($j % 7 == 0)
			$ret .= '<tr>';

		if (abs($now - $i) < 43199 && !($mod == 'NONAV' && $style == 'light'))
			$ret .= "<td class=\"celltoday\" ";
		elseif (date('m', $i) != $amonth->mon)
			$ret .= "<td class=\"lightmonth\"";
		else
			$ret .= "<td class=\"month\"";

		$ret .= "align=\"center\" width=\"$width\" height=\"$height\">";

		$js_inc = '';
		if (is_array($js_include)) {
			$js_inc = " onClick=\"{$js_include['function']}(";
			if (sizeof($js_include['parameters']))
				$js_inc .= implode(", ", $js_include['parameters']) . ", ";
			$js_inc .= "'" . date('m', $i) . "', '$aday', '" . date('Y', $i) . "')\"";
		}
		if (abs($ptime - $i) < 43199 )
			$aday = "<span style=\"border-width: 2px; border-style: solid; "
					. "border-color: #DD0000; padding: 2px;\">$aday</span>";

		if (($j + 1) % 7 == 0) {
			if ($mod == 'NONAV' && $style == 'light') {
				$ret .= '&nbsp;'; // Tag gehört nicht zu diesem Monat
			} else {
				$ret .= "<a class=\"{$style}sdaymin\" href=\"$href$i\"";
				if ($hday['name'])
					$ret .= ' ' . tooltip($hday['name']);
				$ret .= "$js_inc>$aday</a>";
			}
			$ret .= "</td>\n";

			if ($mod != 'NOKW') {
				$ret .= " <td class=\"steel1\" align=\"center\" width=\"$width\" height=\"$height\">";
				if ($mod != 'NONAV') $ret .= "<a href=\"./calendar.php?cmd=showweek&atime=$i\">";
				$ret .= "<font class=\"kwmin\">" . strftime("%V", $i) . "</font>";
				if ($mod != 'NONAV') $ret .= '</a>';
				$ret .= '</td>';
			}
			$ret .= "</tr>\n";
		}
		else {
			if ($mod == 'NONAV' && $style == 'light') {
				$ret .= '&nbsp;'; // Tag gehört nicht zu diesem Monat
			} else {
				// unterschiedliche Darstellung je nach Art des Tages (Rang des Feiertages)
				switch ($hday['col']) {
					case 1:
						$ret .= "<a class=\"{$style}daymin\" href=\"$href$i\" ";
						$ret .= tooltip($hday['name']) . "$js_inc>$aday</a>";
						break;
					case 2:
					case 3;
						$ret .= "<a class=\"{$style}hdaymin\" href=\"$href$i\" ";
						$ret .= tooltip($hday['name']) . "$js_inc>$aday</a>";
						break;
					default:
						$ret .= "<a class=\"{$style}daymin\" href=\"$href$i\"$js_inc>$aday</a>";
				}
			}
			$ret .= "</td>\n";
		}
	}
	$ret .= "</table>\n</td></tr>\n";
	$ret .= "</table>\n";
	return $ret;
}
