<?
# Lifter010: TODO
global $auth, $perm, $SEM_CLASS, $SEM_TYPE, $INST_TYPE;
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
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
                <br>
                <table border="0" cellpadding="1" cellspacing="0" width="98%" valign="top" id="my_seminars">

                    <? if (isset($meldung)) { parse_msg($meldung, "§", "blank", 5); }?>

                    <tr align="center" valign="top">
                        <th width="2%" colspan="2" nowrap="nowrap" align="center">
                            <a href="gruppe.php">
                                <?= Assets::img('icons/16/blue/group.png', array('title' => _("Gruppe ändern"), 'class' => 'middle')) ?>
                            </a>
                        </th>
                        <th width="85%" align="left"><?= _("Name") ?></th>
                        <th width="10%" align="left"><b><?= _("Inhalt") ?></b></th>
                        <th width="3%"></th>
                    </tr>
                    <?= $this->render_partial("meine_seminare/_group") ?>
                </table>
                <br><br>
            <? } ?>


            <? if (sizeof($waitlists)) { ?>
                <? SkipLinks::addIndex(_("Wartelisten"), 'my_waitlists') ?>
                <table border="0" cellpadding="2" cellspacing="0" width="98%" align="center" class="blank" id="my_waitlists">
                    <tr>
                        <th width="67%" align="left" colspan="3"><?= _("Anmelde- und Wartelisteneintr&auml;ge") ?></th>
                        <th width="10%"><b><?= _("Datum") ?></b></th>
                        <th width="10%" nowrap><b><?= _("Position/Chance") ?></b></th>
                        <th width="10%"><b><?= _("Art") ?></b></th>
                        <th width="3%"></th>
                    </tr>

                    <? $cssSw->resetClass(); ?>

                    <?
                    foreach ($waitlists as $wait) {
                        // wir sind in einer Anmeldeliste und brauchen Prozentangaben
                        if ($wait["status"] == "claiming") {
                            $admission_chance = Seminar::GetInstance($wait["seminar_id"])->getAdmissionChance($wait["studiengang_id"]);
                            // Grün der Farbe nimmt mit Wahrscheinlichkeit ab
                            $chance_color = dechex(55 + $admission_chance * 2);
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

                        $cssSw->switchClass();
                        ?>

                        <tr<?= $cssSw->getHover()?>>
                            <td width="1%" bgcolor="#44<?= $chance_color ?>44">
                                <?= Assets::img("blank.gif", array("size" => "7@12") + tooltip2(_("Position oder Wahrscheinlichkeit"))) ?>
                            </td>

                            <td width="1%" class="<?= $cssSw->getClass() ?>">&nbsp;</td>

                            <td width="55%" class="<?= $cssSw->getClass() ?>" align="left">
                                <a href="<?= URLHelper::getLink('details.php', array('sem_id' => $wait['seminar_id'], 'send_from_search_page' => 'meine_seminare.php', 'send_from_search' => 'TRUE'))?>">
                                    <?= htmlReady($seminar_name) ?>
                                </a>
                            </td>

                            <td width="10%" align="center" class="<?= $cssSw->getClass() ?>">
                                <?= $wait["status"] == "claiming" ? date("d.m.", $wait["admission_endtime"]) : "-" ?>
                            </td>

                            <td width="10%" align="center" class="<?= $cssSw->getClass() ?>">
                                <?= $wait["status"] == "claiming" ? ($admission_chance . "%") : $wait["position"] ?>
                            </td>

                            <td width="10%" align="center" class="<?= $cssSw->getClass() ?>">
                                <? if ($wait["status"] == "claiming") : ?>
                                    <?= _("Los") ?>
                                <? elseif ($wait["status"] == "accepted") : ?>
                                    <?= _("Vorl.") ?>
                                <? else : ?>
                                    <?= _("Wartel.") ?>
                                <? endif ?>
                            </td>

                            <td width="3%" class="<?= $cssSw->getClass() ?>" align="center">
                                <a href="<?= URLHelper::getLink('', array('auswahl' => $wait['seminar_id'], 'cmd' => 'suppose_to_kill_admission')) ?>">
                                    <?= Assets::img('icons/16/grey/door-leave.png', tooltip2(_("aus der Veranstaltung abmelden"))) ?>
                                </a>
                            </td>
                        </tr>
                    <? } ?>
                </table>
                <br>
                <br>
            <? } ?>

            <? if (sizeof($my_bosses)) {
                echo $this->render_partial('meine_seminare/_deputy_bosses');
            }?>


            <? if (!$num_my_inst) { ?>

                <table border="0" cellpadding="0" cellspacing="0" width="100%" align="center" class="blank">
                    <?
                    if (!$GLOBALS['ALLOW_SELFASSIGN_INSTITUTE'] || $perm->have_perm("dozent")) {
                        $meldung = "info§" . sprintf(_("Sie wurden noch keinen Einrichtungen zugeordnet. Bitte wenden Sie sich an einen der zust&auml;ndigen %sAdministratoren%s."), "<a href=\"dispatch.php/siteinfo/show\">", "</a>") . "§";
                    } else {
                        $meldung = "info§" . sprintf(_("Sie haben sich noch keinen Einrichtungen zugeordnet. Um sich Einrichtungen zuzuordnen, nutzen Sie bitte die entsprechende %sOption%s unter \"Nutzerdaten - Studiendaten\" auf Ihrer pers&ouml;nlichen Einstellungsseite."), "<a href=\"edit_about.php?view=Studium#einrichtungen\">", "</a>") . "§";
                    }
                    parse_msg($meldung);
                    ?>
                </table>

            <? } else { ?>
                <? SkipLinks::addIndex(_("Meine Einrichtungen"), 'my_institutes')?>
                <table border="0" cellpadding="1" cellspacing="0" width="98%" align="center" class="blank" id="my_institutes">
                    <tr valign="top" align="center">
                        <th width="1%">&nbsp; </th>
                        <th width="86%" align="left"><?= _("Meine Einrichtungen") ?></th>
                        <th width="10%"><b><?= _("Inhalt") ?></b></th>
                        <th width="3%"></th>
                    </tr>

                    <? foreach ($my_obj as $instid => $values) {
                        if ($values['obj_type'] == "inst") {
                            $cssSw->switchClass();
                            $lastVisit = $values['visitdate'];
                            ?>
                            <tr <?= $cssSw->getHover()?>>
                                <td class="<?= $cssSw->getClass() ?>">
                                    <?= InstituteAvatar::getAvatar($instid)->getImageTag(Avatar::SMALL, array('title' => htmlReady($values['name']))) ?>
                                </td>

                                <td align="left" class="<?= $cssSw->getClass() ?>">
                                    <a href="institut_main.php?auswahl=<?= $instid ?>">
                                        <?= htmlReady($INST_TYPE[$values["type"]]["name"] . ": " . $values["name"]) ?>
                                    </a>
                                </td>

                                <td class="<?= $cssSw->getClass() ?>" align="left" nowrap="nowrap">
                                    <? print_seminar_content($instid, $values, "institut"); ?>
                                </td>

                                <td class="<?= $cssSw->getClass() ?>" align="right" nowrap="nowrap">
                                <?  if ($GLOBALS['ALLOW_SELFASSIGN_INSTITUTE'] && $values['status'] == 'user') { ?>
                                    <? if (get_config('CHAT_ENABLE') && $values["modules"]["chat"]) { ?>

                                        <a href="<?= !$auth->auth['jscript'] ? 'chat_online.php' : '#' ?>"
                                           onClick="return open_chat(<?= $chat_info[$instid]['is_active'] ? 'false' : "'$instid'" ?>);">
                                            <?= chat_get_chat_icon($chat_info[$instid]['chatter'], $chat_invs[$chat_info[$instid]['chatuniqid']], $chat_info[$instid]['is_active'], true, 'grey', 'red', '') ?>
                                        </a>
                                    <? } else { ?>
                                        <?= Assets::img("blank.gif", array('size' => '16')) ?>
                                    <? } ?>

                                        <a href="<?= URLHelper::getLink('', array('auswahl' => $instid, 'cmd' => 'inst_kill')) ?>">
                                            <?= Assets::img('icons/16/grey/door-leave.png', tooltip2(_("aus der Einrichtung austragen"))) ?>
                                        </a>
                                <? } else { ?>
                                        <?= Assets::img('blank.gif', array('size' => '16')) ?>
                                <? } ?>
                                    </td>
                            </tr>
                        <? } ?>
                    <? } ?>
                </table>
            <? } ?>
        </td>

        <td class="blank" width="270" align="right" valign="top">
            <? print_infobox ($infobox, "infobox/seminars.jpg"); ?>
        </td>
    </tr>
    <tr>
        <td class="blank" colspan=2>&nbsp;</td>
    </tr>
</table>

