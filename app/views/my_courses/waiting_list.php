<? SkipLinks::addIndex(_("Wartelisten"), 'my_waitlists') ?>
<table class="default collapsable" id="my_waitlists">
    <caption>
        <?= _("Anmelde- und Wartelisteneinträge") ?>
    </caption>
    <colgroup>
        <col width="1px">
        <col width="65%">
        <col width="7%">
        <col width="10%">
        <col width="10%">
        <col width="15%">
        <col width="3%">
    </colgroup>

    <thead>
    <tr>
        <th></th>
        <th style="text-align: left"><?= _("Name") ?></th>
        <th><?= _('Inhalt') ?></th>
        <th style="text-align: center"><?= _("Datum") ?></th>
        <th style="text-wrap: none; white-space: nowrap"><b><?= _("Position/Chance") ?></th>
        <th><?= _("Art") ?></th>
        <th></th>
    </tr>
    </thead>

    <? foreach ($waiting_list as $wait) {

        // wir sind in einer Anmeldeliste und brauchen Prozentangaben
        if ($wait["status"] == "claiming") {
            // Grün der Farbe nimmt mit Wahrscheinlichkeit ab
            $chance_color = dechex(55 + $wait['admission_chance'] * 2);
        } // wir sind in einer Warteliste
        else {
            $chance_color = $wait["position"] < 30
                ? dechex(255 - $wait["position"] * 6)
                : 44;
        }

        $seminar_name = $wait["Name"];
        if (SeminarCategories::GetByTypeId($wait['sem_status'])->studygroup_mode) {
            $seminar_name .= ' (' . _("Studiengruppe") . ', ' . _("geschlossen") . ')';
        }
        ?>
        <tbody>
        <tr>
            <td title="<?=_("Position oder Wahrscheinlichkeit")?>" style="background:#44<?= $chance_color ?>44">
            </td>

            <td>
                <a href="<?= URLHelper::getLink('dispatch.php/course/details/', array('sem_id' => $wait['seminar_id'], 'send_from_search_page' => 'dispatch.php/my_courses/index', 'send_from_search' => 'TRUE')) ?>">
                    <?= htmlReady($seminar_name) ?>
                </a>
            </td>
            <td>
                <a data-dialog="size=auto" href="<?= $controller->url_for(sprintf('course/details/index/%s', $wait['seminar_id'])) ?>">
                    <? $params = tooltip2(_("Veranstaltungsdetails anzeigen")); ?>
                    <? $params['style'] = 'cursor: pointer'; ?>
                    <?= Assets::img('icons/20/grey/info-circle.png', $params) ?>
                </a>
            </td>
            <td style="text-align: center">
                <?= $wait["status"] == "claiming" ? date("d.m.", $wait["admission_endtime"]) : "-" ?>
            </td>

            <td style="text-align: center">
                <?= $wait["status"] == "claiming" ? ($wait['admission_chance'] . "%") : $wait["position"] ?>
            </td>

            <td style="wtext-align: center">
                <? if ($wait["status"] == "claiming") : ?>
                    <?= _("Los") ?>
                <? elseif ($wait["status"] == "accepted") : ?>
                    <?= _("Vorl.") ?>
                <?
                else : ?>
                    <?= _("Wartel.") ?>
                <? endif ?>
            </td>

            <td style="text-align: right">
                <a href="<?= $controller->url_for(sprintf('my_courses/decline/%s/%s', $wait['seminar_id'], 'suppose_to_kill_admission')) ?>">
                    <?= Assets::img('icons/20/grey/door-leave.png', tooltip2(_("aus der Veranstaltung abmelden"))) ?>
                </a>
            </td>
        </tr>
        </tbody>
    <? } ?>

</table>
<br>
<br>
