<?
# Lifter010: TODO
global $auth, $perm, $SEM_CLASS, $SEM_TYPE, $INST_TYPE;
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<? if (isset($meldung)) { parse_msg($meldung, "ï¿½", "blank", 5); }?>

    <? if (!$num_my_sem) { ?>
        <tr>
            <td class="blank" colspan="2"> </td>
        </tr>
        <tr>
            <td valign="top" class="blank">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center" class="blank">
                    <? if (isset($meldung)) { parse_msg($meldung); } ?>
                </table>
    <? } else { ?>
        <? SkipLinks::addIndex(_("Meine Veranstaltungen"), 'my_seminars') ?>
        <tr valign="top">
            <td valign="top" class="blank" align="center">
                <table class="default" id="my_seminars">
                    <caption>
                        <?=_("Veranstaltungen") ?>
                    </caption>
                    <colgroup>
                        <col width="10px">
                        <col width="25px">
                        <? if (Config::get()->IMPORTANT_SEMNUMBER): ?>
                            <col width="25px">
                        <? endif; ?>
                        <col >
                        <col width="20%">
                        <col width="3%">
                    </colgroup> 
                    <thead >
                        <tr>
                            <th colspan="2" nowrap="nowrap" align="center">
                                <a href="<?= URLHelper::getLink('dispatch.php/my_courses/groups') ?>">
                                    <?= Assets::img('icons/20/blue/group.png', array('title' => _("Gruppe ändern"), 'class' => 'middle')) ?>
                                </a>
                            </th>
                            <? if (Config::get()->IMPORTANT_SEMNUMBER): ?>
                                <th><?= _("Veranstaltungsnummer") ?></th>
                            <? endif; ?>
                            <th><?= _("Name") ?></th>
                            <th><?= _("Inhalt") ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <?= $this->render_partial("meine_seminare/_group") ?>
                </table>
                <br><br>
            <? } ?>


            <? if (sizeof($waitlists)) { ?>
                <? SkipLinks::addIndex(_("Wartelisten"), 'my_waitlists') ?>
                <table class="default" id="my_waitlists">
                    <caption>
                        <?=_("Anmelde- und Wartelisteneintr&auml;ge") ?>
                    </caption>
                    <colgroup>
                        <col width="10px">
                        <col width="25px">
                        <col >
                        <col width="20%">
                        <col width="3%">
                    </colgroup> 
  
                    <thead>
                        <tr>
                            <th align="left" colspan="3"><?= _("Name") ?></th>
                            <th><?= _("Datum") ?></th>
                            <th nowrap><b><?= _("Position/Chance") ?></th>
                            <th><?= _("Art") ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?
                    foreach ($waitlists as $wait) {
                        // wir sind in einer Anmeldeliste und brauchen Prozentangaben
                        if ($wait["status"] == "claiming") {
                            // Grï¿½n der Farbe nimmt mit Wahrscheinlichkeit ab
                            $chance_color = dechex(55 + $wait['admission_chance'] * 2);
                        }

                        // wir sind in einer Warteliste
                        else {
                            $chance_color = $wait["position"] < 30
                                            ? dechex(255 - $wait["position"] * 6)
                                            : 44;
                        }

                        $seminar_name = $wait["Name"];

                        if (SeminarCategories::GetByTypeId($wait['sem_status'])->studygroup_mode) {
                            $seminar_name .= ' ('. _("Studiengruppe") . ', ' . _("geschlossen") .')';
                        }
                        ?>
                        <tr>
                            <td width="1%" bgcolor="#44<?= $chance_color ?>44">
                                <?= Assets::img("blank.gif", array("size" => "7@12") + tooltip2(_("Position oder Wahrscheinlichkeit"))) ?>
                            </td>

                            <td width="1%">&nbsp;</td>

                            <td width="55%" align="left">
                                <a href="<?= URLHelper::getLink('dispatch.php/course/details/', array('sem_id' => $wait['seminar_id'], 'send_from_search_page' => 'my_courses.php', 'send_from_search' => 'TRUE'))?>">
                                    <?= htmlReady($seminar_name) ?>
                                </a>
                            </td>

                            <td width="10%" align="center">
                                <?= $wait["status"] == "claiming" ? date("d.m.", $wait["admission_endtime"]) : "-" ?>
                            </td>

                            <td width="10%" align="center">
                                <?= $wait["status"] == "claiming" ? ($wait['admission_chance'] . "%") : $wait["position"] ?>
                            </td>

                            <td width="10%" align="center">
                                <? if ($wait["status"] == "claiming") : ?>
                                    <?= _("Los") ?>
                                <? elseif ($wait["status"] == "accepted") : ?>
                                    <?= _("Vorl.") ?>
                                <? else : ?>
                                    <?= _("Wartel.") ?>
                                <? endif ?>
                            </td>

                            <td width="3%" align="center">
                                <a href="<?= URLHelper::getLink('', array('auswahl' => $wait['seminar_id'], 'cmd' => 'suppose_to_kill_admission')) ?>">
                                    <?= Assets::img('icons/16/grey/door-leave.png', tooltip2(_("aus der Veranstaltung abmelden"))) ?>
                                </a>
                            </td>
                        </tr>
                    <? } ?>
                    </tbody>
                </table>
                <br>
                <br>
            <? } ?>

            <? if (sizeof($my_bosses)) {
                echo $this->render_partial('meine_seminare/_deputy_bosses');
            }?>


            <? if (!$num_my_inst) { ?>

                <table class="">
                    <?
                    if (!$GLOBALS['ALLOW_SELFASSIGN_INSTITUTE'] || $perm->have_perm("dozent")) {
                        $meldung = "infoï¿½" . sprintf(_("Sie wurden noch keinen Einrichtungen zugeordnet. Bitte wenden Sie sich an einen der zust&auml;ndigen %sAdministratoren%s."), "<a href=\"dispatch.php/siteinfo/show\">", "</a>") . "ï¿½";
                    } else {
                        $meldung = "infoï¿½" . sprintf(_("Sie haben sich noch keinen Einrichtungen zugeordnet. Um sich Einrichtungen zuzuordnen, nutzen Sie bitte die entsprechende %sOption%s unter \"Nutzerdaten - Studiendaten\" auf Ihrer pers&ouml;nlichen Einstellungsseite."), "<a href=\"dispatch.php/settings/studies#einrichtungen\">", "</a>") . "ï¿½";
                    }
                    parse_msg($meldung);
                    ?>
                </table>

            <? } else { ?>
                <? SkipLinks::addIndex(_("Meine Einrichtungen"), 'my_institutes')?>
                <table class="default" id="my_institutes">
                    <caption>
                        <?=_("Meine Einrichtungen") ?>
                    </caption> 
                    <colgroup>
                        <col width="10px">
                        <col width="25px">
                        <col >
                        <col width="20%">
                        <col width="3%">
                    </colgroup>                  
                    <thead>
                        <tr>
                            <th></th>
                            <th></th>
                            <th><?= _("Name") ?></th>
                            <th><?= _("Inhalt") ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <? foreach ($my_obj as $instid => $values) {
                        if ($values['obj_type'] == "inst") {
                            $lastVisit = $values['visitdate'];
                            ?>
                            <tr>
                                <td style="width:1px"></td>
                                <td>
                                    <?= (InstituteAvatar::getAvatar($instid)->getImageTag(Avatar::SMALL, array('title' => htmlReady($values['name']))) != '' ? Assets::img('icons/20/blue/institute.png') : 
                                        InstituteAvatar::getAvatar($instid)->getImageTag(Avatar::SMALL, array('title' => htmlReady($values['name'])))) ?>   
                                </td>

                                <td align="left">
                                    <a href="dispatch.php/institute/overview?auswahl=<?= $instid ?>">
                                        <?= htmlReady($INST_TYPE[$values["type"]]["name"] . ": " . $values["name"]) ?>
                                    </a>
                                </td>

                                <td align="left" nowrap="nowrap">
                                    <? print_seminar_content($instid, $values, "institut"); ?>
                                </td>

                                <td align="right" nowrap="nowrap">
                                    <? var_dump($values)?>
                                <?  if ($GLOBALS['ALLOW_SELFASSIGN_INSTITUTE'] && $values['status'] == 'user') { ?>
                                    <a href="<?= URLHelper::getLink('', array('auswahl' => $instid, 'cmd' => 'inst_kill')) ?>">
                                        <?= Assets::img('icons/20/grey/door-leave.png', tooltip2(_("aus der Einrichtung austragen"))) ?>
                                    </a>
                                <? } else { ?>
                                        <?= Assets::img('blank.gif', array('size' => '20')) ?>
                                <? } ?>
                                </td>
                            </tr>
                        <? } ?>
                    <? } ?>
                    </tbody>
                </table>
            <? } ?>
        </td>

        <td class="blank" width="270" align="right" valign="top">
            <? print_infobox ($infobox, "sidebar/seminar-sidebar.png"); ?>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan=2>&nbsp;</td>
    </tr>
</table>

