<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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

$cssSw=new cssClassSwitcher;

if ($forumsend=="bla"){
    if ($presetview == "theme")
        $presetview = $themeview;
    $forum["neuauf"] = $neuauf;
    $forum["postingsperside"] = $postingsperside;
    $forum["flatallopen"] = $flatallopen;
    $forum["rateallopen"] = $rateallopen;
    $forum["showimages"] = $showimages;
    $forum["sortthemes"] = $sortthemes;
    $forum["themeview"] = $themeview;
    $forum["presetview"] = $presetview;

    $forum["shrink"] = $shrink*604800; // Anzahl der Sekunden pro Woche
    $forum["changed"] = "TRUE";
}

?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
        <td class="blank" colspan=2>&nbsp;
        </td>
    </tr>
    <tr>
        <td class="blank" width="100%" colspan="2" align="center">
        <blockquote>
            <font size="-1"><b><?print _("Auf dieser Seite k&ouml;nnen Sie die Bedienung des Stud.IP-Forensystems an Ihre Bed&uuml;rfnisse anpassen.");?>
        </blockquote>

        <?
        echo "<form action=\"$PHP_SELF?view=$view\" method=\"POST\">";
        ?>
        <table width="70%" align="center" cellpadding=8 cellspacing=0 border=0>
            <tr>
                <th width="50%" align=center><?=_("Option")?></th>
                <th align=center><?=_("Auswahl")?></th>
            </tr>
            <tr  <? $cssSw->switchClass() ?>>
                <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                    <font size="-1">
                    <?print _("Neue Beiträge immer aufgeklappt");?></font>
                </td>
                <td <?=$cssSw->getFullClass()?>>
                    <input type="CHECKBOX" name="neuauf" value="1"<?IF($forum["neuauf"]==1) echo " checked";?>>
                </td>
            </tr>

            <tr  <? $cssSw->switchClass() ?>>
                <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                    <font size="-1">
                    <?print _("Alle Beiträge im Flatview immer aufgeklappt");?></font>
                </td>
                <td <?=$cssSw->getFullClass()?>>
                    <input type="CHECKBOX" name="flatallopen" value=TRUE<?if($forum["flatallopen"]==TRUE) echo " checked";?>>
            </td>
            </tr>
            <tr  <? $cssSw->switchClass() ?>>
                <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                    <font size="-1">
                    <?print _("Bewertungsbereich bei geöffneten Postings immer anzeigen");?></font>
                </td>
                <td <?=$cssSw->getFullClass()?>>
                    <input type="CHECKBOX" name="rateallopen" value=TRUE<?if($forum["rateallopen"]==TRUE) echo " checked";?>>
            </td>
            </tr>
            <tr  <? $cssSw->switchClass() ?>>
                <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                    <font size="-1">
                    <?print _("Bilder im Bewertungsbereich anzeigen");?></font>
                </td>
                <td <?=$cssSw->getFullClass()?>>
                    <input type="CHECKBOX" name="showimages" value=TRUE<?if($forum["showimages"]==TRUE) echo " checked";?>>
            </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td align="right" class="blank" style="border-bottom:1px dotted black;">
                    <font size=-1><?echo _("Anzahl der Postings pro Seite im Flatview");?></font>
                </td>
                <td <?=$cssSw->getFullClass()?>>
                    <font size=-1>
                    &nbsp;<select name="postingsperside">
                    <?
                    for ($i=5;$i<55;$i+=5) {
                        echo "<option value=\"$i\"";
                        if ($i == $forum["postingsperside"]) echo " selected";
                        echo ">$i";
                    }
                    ?>
                    </select>
                </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td align="right" class="blank" style="border-bottom:1px dotted black;">
                    <font size=-1><?echo _("Sortierung der Themenanzeige");?></font>
                </td>
                <td <?=$cssSw->getFullClass()?> align="left">
                    <font size=-1>
                    <input type=radio value="asc" name=sortthemes <?if ($forum["sortthemes"]=="asc") echo "checked"; echo "> "._("Alter des Ordners - neue unten");?><br>
                    <input type=radio value="desc" name=sortthemes <?if ($forum["sortthemes"]=="desc") echo "checked";echo "> "._("Alter des Ordners - neue oben");?><br>
                    <input type=radio value="last" name=sortthemes <?if ($forum["sortthemes"]=="last") echo "checked";echo "> "._("Alter des neuesten Beitrags - neue oben");?><br>
                </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td align="right" class="blank" style="border-bottom:1px dotted black;">
                    <font size=-1><?echo _("Anzeigemodus der Themenanzeige");?></font>
                </td>
                <td align="left" <?=$cssSw->getFullClass()?>>
                    <font size=-1>
                    <input type=radio value="tree" name=themeview <?if ($forum["themeview"]=="tree") echo "checked"; echo "> "._("Treeview");?><br>
                    <input type=radio value="mixed" name=themeview <?if ($forum["themeview"]=="mixed") echo "checked";echo "> "._("Flatview");?><br>
                </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td align="right" class="blank" style="border-bottom:1px dotted black;">
                    <font size=-1><?echo _("Einsprungsseite des Forums");?></font>
                </td>
                <td <?=$cssSw->getFullClass()?> align="left">
                    <font size=-1>
                    <input type=radio value="theme" name=presetview <?if ($forum["presetview"]=="tree" || $forum["presetview"]=="mixed") echo "checked"; echo "> "._("Themenansicht");?><br>
                    <input type=radio value="neue" name=presetview <?if ($forum["presetview"]=="neue") echo "checked";echo "> "._("Neue Beiträge");?><br>
                    <input type=radio value="flat" name=presetview <?if ($forum["presetview"]=="flat") echo "checked";echo "> "._("Letzte Beiträge");?><br>
                </td>
            </tr>
            <tr <? $cssSw->switchClass() ?>>
                <td align="right" class=blank style="border-bottom:1px dotted black;">
                    <font size=-1><?echo _("ForumAutoShrink-Engine aktivieren");?></font>
                </td>
                <td align="left" <?=$cssSw->getFullClass()?>>
                    <font size=-1>
                    &nbsp;<select name="shrink">
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
                    <font size=-1><input type="image" <?=makeButton("uebernehmen", "src") ?> border=0 value="<?_("Änderungen übernehmen")?>"></font>&nbsp;
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


<? IF ($forumsend=="anpassen") {
    echo " </td></tr></table>";
    die;
    }
