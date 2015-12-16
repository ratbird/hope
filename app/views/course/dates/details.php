<table style="width: 100%" class="default nohover" data-termin_id="<?= htmlReady($date->getId()) ?>">
    <tbody>
        <tr>
            <td><strong><?= _("Thema") ?></strong></td>
            <td>
                <ul class="themen_list">
                    <? foreach ($date->topics as $topic) : ?>
                        <?= $this->render_partial("course/dates/_topic_li", compact("topic")) ?>
                    <? endforeach ?>
                </ul>
                <? if ($GLOBALS['perm']->have_studip_perm("tutor", $date['range_id'])) : ?>
                <div>
                    <form onSubmit="STUDIP.Dates.addTopic(); return false;">
                        <input type="text" name="new_topic" id="new_topic" placeholder="<?= _("Thema hinzuf�gen") ?>">
                        <a href="#" onClick="STUDIP.Dates.addTopic(); return false;"><?= Assets::img("icons/16/blue/add", array('class' => "text-bottom")) ?></a>
                    </form>
                    <script>
                        jQuery(function () {
                            jQuery("#new_topic").autocomplete({
                                'source': <?= json_encode(studip_utf8encode(Course::findCurrent()->topics->pluck('title'))) ?>
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
            <td><strong><?= _("Durchf�hrende Dozenten") ?></strong></td>
            <td>
                <? $dozenten = $date->dozenten ?>
                <? count($dozenten) > 0 || $dozenten = array_map(function ($m) { return $m->user; }, (Course::findCurrent()->getMembersWithStatus("dozent"))) ?>
                <ul class="dozenten_list clean">
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

<div style="text-align: center;" data-dialog-button>
    <div class="button-group">
        <? if (!$dates_locked && $GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) : ?>
            <?= \Studip\LinkButton::create(_("Termin bearbeiten"), 
                    URLHelper::getUrl('dispatch.php/course/timesrooms', 
                            array('contentbox_open' => $date['metadate_id'], 'singleDateID' => $date->getId()))) ?>
        <? endif ?>
        <? if (!$cancelled_dates_locked && $GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) : ?>
            <?= \Studip\LinkButton::create(_("Ausfallen lassen"), 
                    URLHelper::getURL("dispatch.php/course/cancel_dates", 
                            array('termin_id' => $date->getId())), array('data-dialog' => '')) ?>
        <? endif ?>
    </div>
</div>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/date-sidebar.png');