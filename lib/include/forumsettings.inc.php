<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
folder.php - Anzeige und Verwaltung des Ordnersystems
Copyright (C) 2002 Ralf Stockmann <rstockm@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

//Standard herstellen

use Studip\Button, Studip\LinkButton;

$cssSw=new cssClassSwitcher;
$forumsend = Request::option('forumsend');
$presetview = Request::option('presetview');
if ($forumsend=="bla"){
    if ($presetview == "theme")
        $presetview = Request::option('themeview');
    $forum["neuauf"] = Request::option('neuauf');
    $forum["rateallopen"] = Request::option('rateallopen');
    $forum["showimages"] = Request::option('showimages');
    $forum["sortthemes"] = Request::option('sortthemes');
    $forum["themeview"] = Request::option('themeview');
    $forum["presetview"] = $presetview;

    $forum["shrink"] = Request::option('shrink')*604800; // Anzahl der Sekunden pro Woche
    $forum["changed"] = "TRUE";
}

?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
        <td class="blank" colspan=2>&nbsp;
        </td>
    </tr>
    <tr>
        <td class="blank" width="100%" colspan="2" align="center">

        <?
        echo "<form action=\"".URLHelper::getLink('?view=$view')."\" method=\"POST\">";
        echo CSRFProtection::tokenTag();
        ?>
        <table width="70%" align="center" cellpadding="8" cellspacing="0" border="0" id="main_content">
            <tr>
                <th width="50%" align=center><?=_("Option")?></th>
                <th align=center><?=_("Auswahl")?></th>
            </tr>
            <tr  <? $cssSw->switchClass() ?>>
                <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                    <label for="neuauf">
                    <?print _("Neue Beiträge immer aufgeklappt");?></label>
                </td>
                <td <?=$cssSw->getFullClass()?>>
                    <input type="CHECKBOX" name="neuauf" id="neuauf" value="1"<?IF($forum["neuauf"]==1) echo " checked";?>>
                </td>
            </tr>
            <tr  <? $cssSw->switchClass() ?>>
                <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                    <label for="rateallopen">
                    <?print _("Bewertungsbereich bei geöffneten Postings immer anzeigen");?></label>
                    <div class="setting_info">
                        <?= _("Die Aktivierung dieser Einstellung blendet ein Kästchen neben den Forenbeiträgen ein, mit dem Sie Beiträge bewerten können.") ?>
                    </div>
                </td>
                <td <?=$cssSw->getFullClass()?>>
                    <input type="CHECKBOX" name="rateallopen" id="rateallopen" value=TRUE<?if($forum["rateallopen"]==TRUE) echo " checked";?>>
            </td>
            </tr>
            <tr  <? $cssSw->switchClass() ?>>
                <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                    <label for="showimages">
                    <?print _("Bilder im Bewertungsbereich anzeigen");?></label>
                </td>
                <td <?=$cssSw->getFullClass()?>>
                    <input type="CHECKBOX" name="showimages" id="showimages" value=TRUE<?if($forum["showimages"]==TRUE) echo " checked";?>>
            </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td align="right" class="blank" style="border-bottom:1px dotted black;">
                    <?echo _("Sortierung der Themenanzeige");?>
                </td>
                <td <?=$cssSw->getFullClass()?> align="left">
                    <label><input type=radio value="asc" name="sortthemes" <?if ($forum["sortthemes"]=="asc") echo "checked"; echo '> '._("Erstelldatum des Ordners - neue unten");?></label><br>
                    <label><input type=radio value="desc" name="sortthemes" <?if ($forum["sortthemes"]=="desc") echo "checked";echo '> '._("Erstelldatum des Ordners - neue oben");?></label><br>
                    <label><input type=radio value="last" name="sortthemes" <?if ($forum["sortthemes"]=="last") echo "checked";echo '> '._("Datum des neuesten Beitrags - neue oben");?></label><br>
                </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td align="right" class="blank" style="border-bottom:1px dotted black;">
                    <?echo _("Anzeigemodus der Themenanzeige");?>
                </td>
                <td align="left" <?=$cssSw->getFullClass()?>>
                    <label><input type=radio value="tree" name="themeview" <?if ($forum["themeview"]=="tree") echo "checked"; echo '> '._("Treeview");?></label><br>
                    <label><input type=radio value="mixed" name="themeview" <?if ($forum["themeview"]=="mixed") echo "checked";echo '> '._("Flatview");?></label><br>
                </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td align="right" class="blank" style="border-bottom:1px dotted black;">
                    <?echo _("Standardansicht");?>
                </td>
                <td <?=$cssSw->getFullClass()?> align="left">
                    <label><input type=radio value="theme" name="presetview" <?if ($forum["presetview"]=="tree" || $forum["presetview"]=="mixed") echo "checked"; echo '> '._("Themenansicht");?></label><br>
                    <label><input type=radio value="neue" name="presetview" <?if ($forum["presetview"]=="neue") echo "checked";echo '> '._("Neue Beiträge");?></label><br>
                    <label><input type=radio value="flat" name="presetview" <?if ($forum["presetview"]=="flat") echo "checked";echo '> '._("Letzte Beiträge");?></label><br>
                </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td align="right" class=blank style="border-bottom:1px dotted black;">
                    <label for="shrink"><?echo _("Alte Beiträge standardmäßig zuklappen nach");?></label>
                </td>
                <td align="left" <?=$cssSw->getFullClass()?>>
                    &nbsp;<select name="shrink" id="shrink">
                    <?
                    echo "<option value=0";
                    if ($forum["shrink"]==0) echo " selected";
                    echo ">"._("ausgeschaltet");
                    for ($i=1;$i<20;$i+=1) {
                        echo "<option value=\"$i\"";
                        if ($i*604800 == $forum["shrink"]) echo " selected";
                        echo ">$i "._("Wochen");
                    }
                    ?>
                    </select>
                </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <input type="hidden" name="forumsend" value="bla">
                <td  <?=$cssSw->getFullClass()?> colspan=2 align="middle">
                    <?= Button::create(_('Übernehmen'), array('title' => _('Änderungen übernehmen')))?>&nbsp;
                </td>
            </tr>
            </form>
        </table>
    </form>
    <br><br>
</td>
</tr>
</table>
<br>
