<table width="100%" border="0" cellpadding="2" cellspacing="0">
  <tr>
        <td class="blank" valign="top">
            <table width="99%" cellspacing="0" cellpadding="0" border="0">
                <? if (is_array($termine) && sizeof($termine) > 0) : ?>
                <tr>
                    <td class="steelgraulight" colspan="10" height="24" align="center">
                        <a href="<?= URLHelper::getLink('?cmd='.(($openAll) ? 'close' : 'open') .'All') ?>">
                            <? if ($openAll) : ?>
                            <?= Assets::img('close_all.png', array('title' => _("Alle Termine zuklappen"))) ?>
                            <? else : ?>
                            <?= Assets::img('open_all.png', array('title' => _("Alle Termine aufklappen"))) ?>
                            <? endif ?>
                        </a>
                    </td>
                </tr>
                <? endif; ?>
                <tr>
                    <td colspan="10" height="3">
                    </td>
                </tr>
                <?

                $semester = new SemesterData();
                $all_semester = $semester->getAllSemesterData();

                if (sizeof($dates) > 0) foreach ($dates as $tpl) {

                    if ( ($grenze == 0) || ($grenze < $tpl['start_time']) ) {
                        foreach ($all_semester as $zwsem) {
                            if ( ($zwsem['beginn'] < $tpl['start_time']) && ($zwsem['ende'] > $tpl['start_time']) ) {
                                $grenze = $zwsem['ende'];
                                ?>
                                <tr>
                                    <td class="steelgraulight" align="center" colspan="9">
                                        <font size="-1"><b><?=$zwsem['name']?></b></font>
                                    </td>
                                </tr>
                                <?
                            }
                        }
                    }

                    // Template fuer einzelnes Datum


                    echo $this->render_partial('raumzeit/singledate_student', compact('tpl', 'issue_open'));

                } else {
                ?>
                    <tr>
                        <td align="center">
                            <br>
                            <?= _("Im ausgewählten Zeitraum sind keine Termine vorhanden."); ?>
                        </td>
                    </tr>
                <?
                }
                ?>
            </table>
        </td>
        <td class="blank" align="right" valign="top" width="270">
        <!-- Infobox -->
        <?
            // get a list of semesters (as display options)
            $semester_selectionlist = raumzeit_get_semesters($sem, $semester, $raumzeitFilter);

            // fill attributes
            $picture = 'infobox/schedules.jpg';
            $selectionlist_title = _("Semesterauswahl");
            $selectionlist = $semester_selectionlist;

            // render template
            echo $this->render_partial('infobox/infobox_dates', compact('picture', 'selectionlist_title', 'selectionlist', 'rechte', 'raumzeitFilter'));
        ?>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan="2">
            <br>
        </td>
    </tr>
</table>