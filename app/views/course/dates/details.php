<table style="width: 100%">
    <tbody>
        <tr>
            <td><strong><?= _("Thema") ?></strong></td>
            <td>
                <ul class="themen_list">
                    <? foreach ($date->topics as $topic) : ?>
                        <li>
                            <?= Assets::img("icons/16/blue/star", array('class' => "text-bottom")) ?>
                            <?= htmlReady($topic['title']) ?>
                            <? if ($GLOBALS['perm']->have_studip_perm("tutor", $topic['seminar_id'])) : ?>
                            <a href="#" onClick=""><?= Assets::img("icons/16/blue/trash", array('class' => "text-bottom")) ?></a>
                            <? endif ?>
                        </li>
                    <? endforeach ?>
                </ul>
                <? if ($GLOBALS['perm']->have_studip_perm("tutor", $topic['seminar_id'])) : ?>
                <div>
                    <input type="text" name="new_topic" id="new_topic" placeholder="<?= _("Thema hinzufügen") ?>">
                    <a href="#" onClick=""><?= Assets::img("icons/16/blue/add", array('class' => "text-bottom")) ?></a>
                    <script>
                        jQuery(function () {
                            jQuery("#new_topic").autocomplete({
                                'source': <?= json_encode(studip_utf8encode(array_map(function ($t) { return $t['title']; }, CourseTopic::findBySQL("seminar_id = ?", array($topic['seminar_id']))))) ?>
                            });
                        });
                    </script>
                </div>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <td><strong><?= _("Art des Termins") ?></strong></td>
            <td><?= htmlReady($GLOBALS['TERMIN_TYP'][$date['date_typ']]['name']) ?></td>
        </tr>
        <tr>
            <td><strong><?= _("Durchführende Dozenten") ?></strong></td>
            <td>
                <? $dozenten = $date->dozenten ?>
                <? count($dozenten) > 0 || $dozenten = array_map(function ($m) { return $m->user; }, (Course::findCurrent()->getMembersWithStatus("dozent"))) ?>
                <ul class="dozenten_list">
                <? foreach ($dozenten as $dozent) : ?>
                    <li>
                        <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => $dozent['username'])) ?>"><?= Avatar::getAvatar($dozent['user_id'])->getImageTag(Avatar::SMALL)." ".htmlReady($dozent->getFullName()) ?></a>
                    </li>
                <? endforeach ?>
                </ul>
            </td>
        </tr>
        <tr>
            <td><strong><?= _("Beteiligte Gruppen") ?></strong></td>
            <td>
                <? $groups = $date->statusgruppen ?>
                <? if (count($groups)) : ?>
                <ul>
                    <? foreach ($groups as $group) : ?>
                    <li><?= htmlReady($group['name']) ?></li>
                    <? endforeach ?>
                </ul>
                <? else : ?>
                    <?= _("alle Teilnehmer") ?>
                <? endif ?>
            </td>
        </tr>
    </tbody>
</table>

<div style="text-align: center;">
    <?= \Studip\LinkButton::create(_("Bearbeiten"), URLHelper::getURL("raumzeit.php#".$date->getId(), array('raumzeitFilter' => "all", 'cycle_id' => $date['metadate_id'], 'singleDateID' => $date->getId()))) ?>
    <?= \Studip\LinkButton::create(_("Ausfallen lassen"), URLHelper::getURL("raumzeit.php#".$date->getId(), array('raumzeitFilter' => "all", 'cmd' => "delete_singledate", 'subcommand' => "cancel", 'cycle_id' => $date['metadate_id'], 'sd_id' => $date->getId()))) ?>
</div>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/date-sidebar.png"));