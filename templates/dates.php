<?
# Lifter010: TODO
?>
<table id="layout_container" width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
      <td class="blank" valign="top">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" class="dates_items">
                <? if (is_array($termine) && sizeof($termine) > 0) : ?>
                <tr>
                    <th colspan="10">
                        <a href="<?= URLHelper::getLink('?cmd='.(($openAll) ? 'close' : 'open') .'All') ?>">
                            <? if ($openAll) : ?>
                            <?= Assets::img('close_all.png', array('title' => _("Alle Termine zuklappen"))) ?>
                            <? else : ?>
                            <?= Assets::img('open_all.png', array('title' => _("Alle Termine aufklappen"))) ?>
                            <? endif ?>
                        </a>
                    </th>
                </tr>
                <? endif; ?>
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
                                    <th colspan="9">
                                        <b><?=$zwsem['name']?></b>
                                    </th>
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
</table>