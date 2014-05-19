<style>
    .themen_list, .dozenten_list {
        margin: 0px;
        padding: 0px;
        list-style-type: none;
    }
</style>

<table class="default">
    <thead>
        <tr>
            <th></th>
            <th><?= _("Typ") ?></th>
            <th><?= _("Zeit") ?></th>
            <th><?= _("Thema") ?></th>
            <th><?= _("Raum") ?></th>
        </tr>
    </thead>
    <tbody>
        <? if (count($dates)) : ?>
        <? foreach ($dates as $key => $date) : ?>
        <tr>
            <td>
                <?
                //show arrow if this is the next date
                if ((!$dates[$key - 1] || ($dates[$key - 1]['end_time'] < time())) && ($date['end_time'] >= time())) {
                    echo Assets::img("icons/20/black/arr_1right", array('class' => "text-bottom", 'title' => _("Der nächste Termin")));
                }
                ?>
            </td>
            <td><a href="<?= URLHelper::getLink("dispatch.php/course/dates/details/".$date->getId()) ?>" data-lightbox="buttons=false"><?= htmlReady($GLOBALS['TERMIN_TYP'][$date['date_typ']]['name']) ?></a></td>
            <td>
                <a href="<?= URLHelper::getLink("dispatch.php/course/dates/details/".$date->getId()) ?>" data-lightbox="buttons=false">
                <?= (floor($date['date'] / 86400) !== floor($date['end_time'] / 86400)) ? date("d.m.Y, H:i", $date['date'])." - ".date("d.m.Y, H:i", $date['end_time']) : date("d.m.Y, H:i", $date['date'])." - ".date("H:i", $date['end_time']) ?></td>
                </a>
            <td>
                <ul class="themen_list">
                    <? foreach ($date->topics as $topic) : ?>
                    <li>
                        <?= Assets::img("icons/16/blue/star", array('class' => "text-bottom")) ?>
                        <?= htmlReady($topic['title']) ?>
                    </li>
                    <? endforeach ?>
                </ul>
            </td>
            <td><?= htmlReady($date['raum']) ?></td>
        </tr>
        <? endforeach ?>
        <? else : ?>
        <tr>
            <td colspan="5" style="text-align: center;"><?= _("Keine Termine vorhanden") ?></td>
        </tr>
        <? endif ?>
    </tbody>
</table>


<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/date-sidebar.png"));