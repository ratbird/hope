<tr id="date_<?= $date->getId() ?>">
    <td>
        <?
        //show arrow if this is the next date
        if ((!$dates[$key - 1] || ($dates[$key - 1]['end_time'] < time())) && ($date['end_time'] >= time())) {
            echo Assets::img("icons/20/black/arr_1right", array('class' => "text-bottom", 'title' => _("Der nächste Termin")));
        }
        ?>
    </td>
    <td><a href="<?= URLHelper::getLink("dispatch.php/course/dates/details/".$date->getId()) ?>" data-dialog="buttons=false"><?= htmlReady($GLOBALS['TERMIN_TYP'][$date['date_typ']]['name']) ?></a></td>
    <td>
        <a href="<?= URLHelper::getLink("dispatch.php/course/dates/details/".$date->getId()) ?>" data-dialog="buttons=false">
        <?= (floor($date['date'] / 86400) !== floor($date['end_time'] / 86400)) ? date("d.m.Y, H:i", $date['date'])." - ".date("d.m.Y, H:i", $date['end_time']) : date("d.m.Y, H:i", $date['date'])." - ".date("H:i", $date['end_time']) ?></td>
    </a>
    <td>
        <ul class="themen_list">
            <? foreach ($date->topics as $topic) : ?>
                <?= $this->render_partial("course/dates/_topic_li", compact("topic")) ?>
            <? endforeach ?>
        </ul>
    </td>
    <td><?= htmlReady($date['raum']) ?></td>
</tr>