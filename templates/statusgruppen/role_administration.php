<?
# Lifter010: TODO
    use Studip\Button, Studip\LinkButton;
?>
<?
    $cssSw = new CSSClassSwitcher();
    $pos = 0;
    $style = "style=\"background-image: url('". Assets::image_path('forumstrich') ."');"
        ." background-position: right;"
        ." background-repeat: repeat-y;"
        ."\" ";

    if ($seminar_persons) :
        $width = '33%';
        $indirect = true;
    else :
        $width = '50%';
        $indirect = false;
    endif;
?>
<tr>
    <? for($i = 0; $i < $indent; $i++) : ?>
    <td class="blank" width="10" align="right" <?= ($followers[$i+1]) ? $style : '' ?>></td>
    <? endfor; ?>

    <? if ($has_child) : ?>
    <td class="blank" width="10" align="right" <?= $style ?> nowrap></td>
    <? else :
        $indent--;
    endif; ?>

    <td class="printcontent" colspan="<?= 19 - $indent ?>" width="100%">
            <? if ($editRole) : // edit the metadata of the role ?>
                <br>
                <table cellspacing="0" cellpadding="0" border="0" width="90%" style="margin: auto;">
                    <tr>
                        <td class="content_seperator" colspan="6">
                            &nbsp;<b><?= $range_type == 'sem' ? _("Gruppe bearbeiten") : _("Rollendaten bearbeiten") ?></b>
                        </td>
                    </tr>
                    <?= $this->render_partial('statusgruppen/role_administration_edit.php'); ?>
                </table>
            <? else :   ?>
            <!-- Buttonbar -->
            <br>
            <div style="text-align: center;">
                <?= LinkButton::create(_('Bearbeiten'), URLHelper::getURL('', array('view' => 'editRole', 'role_id' => $role_id)) . '#' . $role_id) ?>
                <?= LinkButton::create(_('Loeschen'), URLHelper::getURL('', array('cmd' => 'deleteRole', 'role_id' => $role_id))) ?>
            </div>
            <br>

            <form action="<?= URLHelper::getLink('') ?>" method="post" style="display: inline;">
                <?= CSRFProtection::tokenTag() ?>
                <input type="hidden" name="cmd" value="sort_person">
                <input type="hidden" name="role_id" value="<?= $role_id ?>">
                <table cellspacing="0" cellpadding="0" border="0" width="95%" style="margin: auto;">
                    <!-- Person assigned to this role - Heading -->
                    <tr>
                        <td class="content_seperator" colspan="6">
                            &nbsp;<b><?= $range_type == 'sem' ? _("Personen in dieser Gruppe") : _("Personen in dieser Rolle") ?></b>
                        </td>
                        <td class="content_seperator" width="5%" nowrap>
                            <?= ($role->getSize()) ? sizeof($persons) .' '._("von").' '. $role->getSize() : '' ?>
                            &nbsp;
                        </td>
                        <td class="content_seperator" width="1%" nowrap>
                            <?= ($role->getSelfassign()) ? Assets::img('icons/16/grey/info-circle.png', array('title' => _("Personen können sich dieser Gruppe selbst zuordnen"))) : '' ?>
                            <a href="<?= URLHelper::getLink('?cmd=sortByName&role_id='. $role_id ) ?>"><?= Assets::img('icons/16/blue/arr_eol-down.png') ?></a>
                        </td>
                    </tr>
                    <!-- Persons assigned to this role -->
                    <? if (is_array($persons)) foreach ($persons as $person) :
                                $cssSw->switchClass();
                                $pos ++;
                    ?>
                    <tr>
                        <td class="<?= $cssSw->getClass() ?>" width="1%" nowrap>
                            <input name="sort_person[]" value="<?= $person['username'] ?>" type="radio">
                        </td>

                        <td class="<?= $cssSw->getClass() ?>" width="1%" nowrap>
                            <input
                                src="<?= Assets::image_path('icons/16/yellow/arr_eol-right.png') ?>"
                                name="do_person_sort[<?= $person['username'] ?>]" type="image">
                        </td>

                        <td class="<?= $cssSw->getClass() ?>" width="1%" nowrap style="padding-left: 6px;">
                            <? if ($pos < sizeof($persons)) : ?>
                            <a href="<?= URLHelper::getLink('?cmd=move_down&role_id='. $role_id .'&username='. $person['username']) ?>">
                                <?= Assets::img('icons/16/yellow/arr_2down.png') ?>
                            </a>
                            <? endif; ?>
                        </td>

                        <td class="<?= $cssSw->getClass() ?>" width="1%" nowrap style="padding-left: 4px;">
                            <? if ($pos > 1) : ?>
                            <a href="<?= URLHelper::getLink('?cmd=move_up&role_id='. $role_id .'&username='. $person['username']) ?>">
                                <?= Assets::img('icons/16/yellow/arr_2up.png') ?>
                            </a>
                            <? endif; ?>
                        </td>

                        <td class="<?= $cssSw->getClass() ?>" width="1%" nowrap>
                            &nbsp;&nbsp;<?= $pos ?>&nbsp;
                        </td>

                        <td class="<?= $cssSw->getClass() ?>">
                            <? if ($range_type == 'sem') : ?>
                            <a href="about.php?username=<?= $person['username'] ?>">
                            <? else: ?>
                            <a href="edit_about.php?view=Karriere&open=<?= $role_id ?>&username=<?= $person['username'] ?>#<?= $role_id ?>">
                            <? endif; ?>
                                <?= htmlReady($person['fullname']) ?>
                            </a>
                        </td>

                        <td class="<?= $cssSw->getClass() ?>" width="1%" colspan="2" align="right">
                            <a href="<?= URLHelper::getLink('?role_id='. $role_id .'&cmd=removePerson&username='. $person['username']) ?>">
                            <?= Assets::img('icons/16/blue/trash.png') ?>
                            </a>
                        </td>
                    </tr>
                    <? endforeach; ?>
                </table>
            </form>
            <br>
            <table cellspacing="0" cellpadding="0" border="0" width="95%" style="margin: auto;">
                <tr>
                    <? if ($seminar_persons) : ?>
                    <td class="content_seperator">&nbsp;<?= _("VeranstaltungsteilnehmerInnen") ?></td>
                    <td>&nbsp;&nbsp;</td>
                    <? endif; ?>

                    <td class="content_seperator">&nbsp;<?= _("Mitarbeiterliste") ?></td>
                    <td>&nbsp;&nbsp;</td>
                    <td class="content_seperator" nowrap>&nbsp;<?= _("freie Personensuche") ?></td>
                </tr>
                <tr>
                    <? if ($seminar_persons) : ?>
                    <td width="<?= $width ?>" style="padding-left: 10px; padding-right: 10px;" valign="top" align="center">
                        <?= $this->render_partial('statusgruppen/role_administration_members', array('indirect' => false, 'inst_persons' => $seminar_persons)) ?>
                    </td>
                    <td>&nbsp;&nbsp;</td>
                    <? endif; ?>

                    <td width="<?= $width ?>" style="padding-left: 10px; padding-right: 10px;" valign="top" align="center">
                        <?= $this->render_partial('statusgruppen/role_administration_members', array('indirect' => $indirect)) ?>
                    </td>
                    <td>&nbsp;&nbsp;</td>
                    <td width="<?= $width ?>" style="padding-left: 10px; padding-right: 10px;" valign="top" align="center">
                    <?= $this->render_partial('statusgruppen/role_administration_search', array('anker'=>$role_id)) ?>
                </td>
            </tr>
        </table>
        <br>
            <? endif; // display person-administration ?>
    </td>
</tr>
