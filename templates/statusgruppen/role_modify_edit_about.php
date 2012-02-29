<?
# Lifter010: TODO
    use Studip\Button, Studip\LinkButton;
?>
<?
$cssSw = new CSSClassSwitcher();
$style = "style=\"background-image: url('". Assets::image_path('forumstrich.gif') ."');"
    ." background-position: left;"
    ." background-repeat: repeat-y;"
    ."\" ";
?>
<tr>
    <td <?= ($followers) ? $style: ''?> width="1%">&nbsp;</td>
    <td width="99%" class="printcontent">
        <center>
        <br>
        <? if ($GLOBALS['perm']->have_studip_perm('admin', $inst_id) && !$locked) : ?>
            <?= LinkButton::create(_('Löschen'), URLHelper::getURL('?view=Karriere&username='. $username .'&cmd=removeFromGroup&role_id='. $role_id .'&studipticket='. get_ticket())) ?>
            &nbsp;&nbsp;&nbsp;
            <?= LinkButton::create(_('Zur Funktion'), URLHelper::getURL('admin_roles.php', array('admin_inst_id' => $inst_id, 'open' => $role_id)) . '#' . $role_id) ?>
            <br><br>
        <? endif; ?>
            <input type="hidden" name="cmd" value="special_edit">
            <input type="hidden" name="role_id" value="<?= $role_id ?>">
            <input type="hidden" name="studipticket" value="<?=get_ticket()?>">
            <input type="hidden" name="username" value="<?=$username?>">
            <input type="hidden" name="view" value=<?=$view?>>
    <?

        // Rollendaten anzeigen
        //if ($sgroup = GetSingleStatusgruppe($role_id, $userID)) {
            //$groupOptions = getOptionsOfStGroups($userID);
            ?>
        <table cellspacing="0" cellpadding="1" border="0" class="blank" width="90%">
            <tr>
                <td align="left" colspan="4" class="topic">
                    &nbsp;<b><?= _("Daten für diese Funktion") ?></b>
                </td>
                <td class="blank">&nbsp;</td>
                <td class="topic">
                    &nbsp;<b><?=_("Standarddaten")?></b>
                </td>
            <?
            echo "<input type=\"hidden\" name=\"group_id[]\" value=\"$role_id\">";
            echo "</td></tr>\n";
            $cssSw->resetClass();
            $default_entries = DataFieldEntry::getDataFieldEntries(array($user_id, $inst_id));
            $entries = DataFieldEntry::getDataFieldEntries(array($user_id, $role_id));

            if (is_array($entries))
            foreach ($entries as $id=>$entry) {
                $cssSw->switchClass();
                ?>
                <tr>
                    <td class="<?=$cssSw->getClass()?>" align="left"></td>
                    <td class="<?=$cssSw->getClass()?>" align="left">
                        <?=$entry->getName();?>
                    </td>
                    <td colspan="1" class="<?=$cssSw->getClass()?>">&nbsp;
                <?
                global $auth;
                if ($entry->structure->editAllowed($auth->auth['perm']) && ($entry->getValue() != 'default_value') && !$locked) {
                    echo $entry->getHTML('datafields');
                    echo '</td>';

                    // Set-Default Checkbox
                    echo '<td class="'.$cssSw->getClass().'" align="right">';
                    echo '<a href="'. URLHelper::getLink('?cmd=set_default&username='.$username.'&view='.$view.'&subview='.$subview.'&role_id='.$role_id.'&chgdef_entry_id='.$id.'&cor_inst_id='.$inst_id.'&sec_range_id='.$role_id.'&subview_id='.$subview_id.'&studipticket='.get_ticket()).'" >';
                    echo Assets::img('icons/16/blue/checkbox-unchecked.png', array('class' => 'text-top', 'title' =>_("Diese Daten von den Standarddaten übernehmen")));
                    echo '</a>';
                } else {
                    if ($entry->getValue() == 'default_value') {
                        echo $default_entries[$id]->getDisplayValue();
                        echo '</td>';

                        // UnSet-Default Checkbox
                        echo '<td class="'.$cssSw->getClass().'" align="right">';
                        if ($entry->structure->editAllowed($auth->auth['perm']) && !$locked) {
                            echo '<a href="'. URLHelper::getLink('?cmd=unset_default&username='.$username.'&view='.$view.'&subview='.$subview.'&role_id='.$role_id.'&chgdef_entry_id='.$id.'&cor_inst_id='.$inst_id.'&sec_range_id='.$role_id.'&subview_id='.$subview_id.'&studipticket='.get_ticket()) .'" >';
                            echo Assets::img('icons/16/blue/checkbox-checked.png', array('class' => 'text-top', 'title' =>_("Diese Daten NICHT von den Standarddaten übernehmen")));
                            echo '</a>';
                        }
                    } else {
                        echo $entry->getDisplayValue();
                        echo '<td class="'.$cssSw->getClass().'" align="right">';
                    }
                }
                echo '</td>';
                echo '<td class="blank">&nbsp;</td>';
                echo '<td width ="30%" class="'.$cssSw->getClass().'"><font size="-1">'.$default_entries[$id]->getDisplayValue().'</font></td>';
                echo '</tr>';
            }
        //}
            $cssSw->switchClass();
        ?>
            <tr>
                <td colspan="4" class="<?= $cssSw->getClass() ?>" align="right">
                    <? if (!$locked) :?>
                    <font size="-1">
                        <?= _("Standarddaten übernehmen:") ?>
                        <a href="<?= URLHelper::getLink('?view=Karriere&username='. $username .'&inst_id='. $inst_id .'&cmd=makeAllSpecial&role_id='. $role_id .'&studipticket='. get_ticket()) ?>">
                            <?= _("keine") ?>
                        </a>
                        &nbsp;/&nbsp;
                        <a href="<?= URLHelper::getLink('?view=Karriere&username='. $username .'&inst_id='. $inst_id .'&cmd=makeAllDefault&role_id='. $role_id .'&studipticket='. get_ticket())?>">
                            <?=_("alle") ?>
                        </a>
                        </font>
                    <? else :?>
                        &nbsp;
                    <? endif;?>
                    </td>
                    <td class="blank">&nbsp;</td>
                    <td class="<?= $cssSw->getClass() ?>" align="center">
                    <? if (!$locked) :?>
                        <a href="<?= URLHelper::getLink('?view=Karriere&open='. $inst_id .'&username='. $username .'#'. $inst_id) ?>">
                        <?=_("ändern")?>
                        </a>
                    <? else :?>
                        &nbsp;
                    <? endif;?>
                    </td>
                </tr>
            </table>
        <br>
        <? if (!$locked) :?>
            <?= Button::createAccept(_('Änderungen speichern'), 'speichern') ?>
        <? endif;?>
        <br>
        <br>
        </center>
    </td>
    <td class="printcontent"></td>
</tr>
