<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
global $SEM_TYPE;
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <? if (isset($meldung)) parse_msg($meldung); ?>

    <? if (is_array($_my_inst)) { ?>
        <tr>
            <td class="blank">

                <form action="<?= URLHelper::getLink() ?>" method="post">

                    <?= CSRFProtection::tokenTag() ?>

                    <div style="font-weight:bold;font-size:10pt;margin-left:10px;">
                        <?= _("Bitte w&auml;hlen Sie eine Einrichtung aus:") ?>
                    </div>

                    <div style="margin-left:10px;">
                        <select name="institut_id" style="vertical-align:middle;">
                            <? while (list($key, $value) = each($_my_inst)) : ?>
                                <option <?= $key == $_my_admin_inst_id ? "selected" : "" ?>
                                        value="<?= $key ?>"
                                        style="<?= $value['is_fak'] ? 'font-weight: bold;' : '' ?>">
                                    <?= htmlReady($value["name"]) ?> (<?= $value["num_sem"] ?>)
                                </option>

                                <? if ($value["is_fak"]) { ?>
                                    <? for ($i = 0; $i < $value["num_inst"]; ++$i) { ?>
                                        <? list($inst_key, $inst_value) = each($_my_inst); ?>
                                        <option <?= $inst_key == $_my_admin_inst_id ? "selected" : "" ?>
                                                value="<?= $inst_key ?>">
                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                            <?= htmlReady($inst_value["name"]) ?> (<?= $inst_value["num_sem"] ?>)
                                        </option>
                                    <? } ?>
                                <? } ?>
                            <? endwhile; ?>
                        </select>

                        <?= SemesterData::GetSemesterSelector(array('name'=>'select_sem', 'style'=>'vertical-align:middle;'), $_default_sem) ?>
                        <?= Button::create(_('auswählen'), array('title' => _('Einrichtung auswählen')))?>
                    </div>
                </form>
                <br>
            </td>
        </tr>

        <? if ($num_my_sem) { ?>
            <tr>
                <td class="blank">
                    <table border="0" cellpadding="0" cellspacing="0" width="99%" align="center">
                        <tr>
                            <td class="topic" colspan="8">
                                <b>
                                    <?=_("Veranstaltungen an meinen Einrichtungen") ?>
                                    <?= $_my_admin_inst_id ? " - " . htmlReady($_my_inst[$_my_admin_inst_id]['name']) : "" ?>
                                </b>
                            </td>
                        </tr>

                        <tr>
                            <th width="2%">
                                &nbsp;
                            </th>

                            <th width="6%" align="left">
                                <a href="<?= URLHelper::getLink('', array('sortby' => 'VeranstaltungsNummer')) ?>"><?=_("Nr.")?></a>
                            </th>

                            <th width="50%" align="left">
                                <a href="<?= URLHelper::getLink('', array('sortby' => 'Name')) ?>"><?=_("Name")?></a>
                            </th>

                            <th width="10%" align="left">
                                <a href="<?= URLHelper::getLink('', array('sortby' => 'status')) ?>"><?=_("Veranstaltungstyp")?></a>
                            </th>

                            <th width="15%" align="left">
                                <b><?= _("DozentIn") ?></b>
                            </th>

                            <th width="10%">
                                <b><?= _("Inhalt") ?></b>
                            </th>

                            <th width="5%">
                                <a href="<?= URLHelper::getLink('', array('sortby' => 'teilnehmer')) ?>"><?=_("TeilnehmerInnen")?></a>
                            </th>

                            <th width="2%">
                                &nbsp;
                            </th>
                        </tr>

                        <? $cssSw->enableHover(); ?>
                        <? foreach ($my_sem as $semid => $values) { ?>
                            <?
                            $cssSw->switchClass();
                            $class = $cssSw->getClass();

                            $lastVisit = $values['visitdate'];
                            ?>

                            <tr <?= $cssSw->getHover()?>>
                                <td class="<?= $class ?>">
                                    <?= CourseAvatar::getAvatar($semid)->getImageTag(Avatar::SMALL, array('title' => htmlReady($values['name']))) ?>
                                </td>

                                <td class="<?= $class ?>">
                                    <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $semid)) ?>">
                                        <?= $values["VeranstaltungsNummer"] ?>
                                    </a>
                                </td>

                                <td class="<?= $class ?>">
                                    <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $semid)) ?>"
                                       style="<?= lastVisit <= $values['chdate'] ? 'color: red;' : '' ?>">
                                        <?= htmlReady($values["name"]) ?>
                                    </a>
                                    <? if (!$_default_sem || $values['startsem'] != $values['endsem']) { ?>
                                        <font size="-1">
                                            (<?= htmlReady($values['startsem']) ?><?= $values['startsem'] != $values['endsem'] ? " - " . $values['endsem'] : "" ?>)
                                        </font>
                                    <? } ?>
                                    <? if ($values["visible"] == 0) { ?>
                                        <font size="-1"><?= _("(versteckt)") ?></font>
                                    <? } ?>
                                </td>

                                <td class="<?= $class ?>">
                                    <?= $SEM_TYPE[$values["status"]]["name"] ?>
                                </td>

                                <td class="<?= $class ?>">
                                    <?= $this->render_partial_collection('meine_seminare/_dozent', $values['dozenten']) ?>
                                </td>

                                <td class="<?= $class ?>" nowrap>
                                    <? print_seminar_content($semid, $values); ?>
                                </td>

                                <td class="<?= $class ?>" align="right" nowrap>
                                    <?= $values["teilnehmer"] ?>
                                </td>

                                <td class="<?= $class ?>" align="right">
                                    <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $semid, 'redirect_to' => 'adminarea_start.php', 'new_sem' => 'TRUE')) ?>">
                                        <?= Assets::img('icons/16/grey/admin.png', tooltip2(_("Veranstaltungsdaten bearbeiten"))) ?>
                                    </a>
                                </td>
                            </tr>
                        <? } ?>
                    </table>
                    <br>
                </td>
            </tr>
        <? } ?>
    <? } ?>
</table>
