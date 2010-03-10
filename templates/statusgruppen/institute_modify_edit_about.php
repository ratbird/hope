<?
$cssSw = new CSSClassSwitcher();
$style = "style=\"background-image: url('". Assets::image_path('forumstrich') ."');"
    ." background-position: right;"
    ." background-repeat: repeat-y;"
    ."\" ";
?>
<tr>
    <td <?= ($followers) ? $style: ''?> width="1%">&nbsp;</td>
    <td width="99%" class="printcontent">
        <center>
        <br>
        <? if ($GLOBALS['perm']->have_studip_perm('admin', $inst_id)) : ?>
            <a href="inst_admin.php?admin_inst_id=<?= $inst_id ?>&list=true">
                <?= makebutton('zureinrichtung'); ?>
            </a>
            <br><br>
        <? else: ?>
            <a href="institut_main.php?auswahl=<?= $inst_id ?>">
                <?= makebutton('zureinrichtung'); ?>
            </a>
            <br><br>
        <? endif; ?>
        <form action="<?= URLHelper::getLink('#'. $inst_id) ?>" method="POST">
            <input type="hidden" name="cmd" value="special_edit">
            <input type="hidden" name="inst_id" value="<?= $inst_id ?>">
            <input type="hidden" name="studipticket" value="<?=get_ticket()?>">
            <input type="hidden" name="username" value="<?=$username?>">
            <input type="hidden" name="view" value=<?=$view?>>
            <table cellspacing="0" cellpadding="0" border="0" class="blank" width="90%">
            <tr>
                <td width="100%" colspan="4" class="topic">&nbsp;<?= _("Einrichtungsdaten") ?></td>
            </tr>
            <?
            $status = $data['inst_perms'];

            $cssSw->switchClass();
            echo '<tr><td class="'. $cssSw->getClass() .'">';
            echo _("Status").':';
            echo '</td><td class="'. $cssSw->getClass() .'" colspan="3">';

            if ($GLOBALS['perm']->have_studip_perm('admin', $inst_id) && $status != 'admin') :
                echo '&nbsp;&nbsp;<select name="status">';
                foreach ($allowed_status as $cur_status) :
                    echo '<option value="'. $cur_status .'"' . (($cur_status == $status)?' selected="selected"':'') .'>'. $cur_status .'</option>';
                endforeach;
                echo '</select>';
            else :
                $status[0] = strtoupper($status[0]);
                echo '&nbsp;'.$status;
            endif;

            echo '</td></tr>';

            echo "<input type=\"HIDDEN\" name=\"name[$inst_id]\" value=\"";
            echo htmlReady($data["Name"]) . "\">";
            $cssSw->switchClass();
            echo '<tr><td class="' . $cssSw->getClass() . '" align="left">';
            echo _("Raum:") . " </td><td class=\"" . $cssSw->getClass() . "\" colspan=\"3\" ";
            echo "align=\"left\">&nbsp; <input type=\"text\" style=\"width: 30%\" ";
            echo "size=\"" . round($max_col * 0.25 * 0.6) . "\" name=\"raum[$inst_id]\" ";
            echo "value=\"" . htmlReady($data["raum"]) . "\"></td></tr>";
            $cssSw->switchClass();
            echo "<td class=\"" . $cssSw->getClass() . "\" align=\"left\">";
            echo _("Sprechzeit:") . " </td><td class=\"" . $cssSw->getClass() . "\" colspan=\"3\" ";
            echo " align=\"left\">&nbsp; <input type=\"text\" style=\"width: 30%\" ";
            echo "size=\"" . round($max_col * 0.25 * 0.6) . "\" name=\"sprech[$inst_id]\" ";
            echo "value=\"" . htmlReady($data["sprechzeiten"]) . "\"></td></tr>";
            $cssSw->switchClass();
            echo "<td class=\"" . $cssSw->getClass() . "\" align=\"left\">";
            echo _("Telefon:") . " </td><td class=\"" . $cssSw->getClass() . "\" colspan=\"3\" ";
            echo " align=\"left\">&nbsp; <input type=\"text\" style=\"width: 30%\" ";
            echo "size=\"" . round($max_col * 0.25 * 0.6) . "\" name=\"tel[$inst_id]\" ";
            echo "value=\"" . htmlReady($data["Telefon"]) . "\"></td></tr>";
            $cssSw->switchClass();
            echo "<td class=\"" . $cssSw->getClass() . "\" align=\"left\">";
            echo _("Fax:") . " </td><td class=\"" . $cssSw->getClass() . "\" colspan=\"3\" ";
            echo "align=\"left\">&nbsp; <input type=\"text\" style=\"width: 30%\" ";
            echo "size=\"" . round($max_col * 0.25 * 0.6) . "\"   name=\"fax[$inst_id]\" ";
            echo "value=\"" . htmlReady($data["Fax"]) . "\"></td></tr>";

            // Datenfelder für Rollen in Einrichtungen ausgeben
            // Default-Daten der Einrichtung
            $entries = DataFieldEntry::getDataFieldEntries(array($user_id, $inst_id),'userinstrole');   // Default-Daten der Einrichtung
            if (is_array($entries))
            foreach ($entries as $id=>$entry) {
                $cssSw->switchClass();
                echo '<tr><td class="' . $cssSw->getClass() . '" align="left">' . $entry->getName() . ':</td>';
                echo '<td colspan="3" class="' . $cssSw->getClass() . '">&nbsp; ' . $entry->getHTML('datafields');
                echo '</td></tr>';
            }

            $cssSw->switchClass();
            ?>

                <tr>
                    <? $info = _("Angaben, die im Adressbuch und auf den externen Seiten als Standard benutzt werden."); ?>
                    <td class="<?=$cssSw->getClass()?>" align="left" nowrap="nowrap" colspan="2">
                        &nbsp;<?=_("Standard-Adresse:")?>&nbsp;
                        <? if ($data['externdefault']) : ?>
                        <img src="<?=$GLOBALS['ASSETS_URL']?>/images/haken_transparent.gif">
                        <input type="hidden" name="default_inst" value="<?=$inst_id?>">
                        <? else : ?>
                        <input type="checkbox" name="default_inst" value="<?=$inst_id?>" <?=($data['externdefault'] ? ' checked="checked"' : '')?>>
                        <? endif; ?>
                        &nbsp;<img src="<?=$GLOBALS['ASSETS_URL']?>/images/info.gif" <?=tooltip($info, TRUE, TRUE)?>>
                    </td>
                    <? $info = _("Die Angaben zu dieser Einrichtung werden nicht auf Ihrer Homepage und in Adressbüchern ausgegeben."); ?>
                    <td class="<?=$cssSw->getClass()?>">
                        &nbsp;<?= _("Einrichtung nicht auf Stud.IP Homepage:"); ?>
                        <input type="checkbox" name="visible[<?=$inst_id?>]" value="1" <?=($data['visible'] == '1' ? '' : ' checked="checked"')?>>&nbsp;
                        <img src="<?=$GLOBALS['ASSETS_URL']?>images/info.gif" <?=tooltip($info, TRUE, TRUE)?>>
                    </td>
                </tr>

            </table>

            <br>
            <input type="image" <?=makeButton('speichern', 'src')?> value="<?=_("Änderungen speichern")?>" align="absbottom">
            <br>
        </form>
        <br>
        </center>
    </td>
    <td class="printcontent"></td>
</tr>
