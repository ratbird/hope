<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* personal settings
*
* helper functions for handling personal settings
*
*
* @author       Stefan Suchi <suchi@data-quest.de>
* @access       public
* @modulegroup  library
* @module       mystudip.inc
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// mystudip.inc.php
// helper functions for handling personal settings
// Copyright (c) 2003 Stefan Suchi <suchi@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

/**
* generates drop-down box for language selection
*
* This function generates a drop-down box for language selection.
* Language could be given as selected default.
*
* @access   public
* @param        string  pre-selected language (in "de_DE" style)
*/

use Studip\Button, Studip\LinkButton;

function select_language($selected_language = "") {
    global $INSTALLED_LANGUAGES, $DEFAULT_LANGUAGE;

    if (!isset($selected_language)) {
        $selected_language = $DEFAULT_LANGUAGE;
    }

    echo "<select name=\"forced_language\" id=\"forced_language\" width=30>";
    foreach ($INSTALLED_LANGUAGES as $temp_language => $temp_language_settings) {
        if ($temp_language == $selected_language) {
            echo "<option selected value=\"$temp_language\">" . $temp_language_settings["name"] . "</option>";
        } else {
            echo "<option value=\"$temp_language\">" . $temp_language_settings["name"] . "</option>";
        }
    }

    echo "</select>";

    return;
}


/**
* generates first page of personal settings
*
* This function generates the first page of personal settings.
*
* @access   public
*/
function change_general_view() {
    global $PHP_SELF, $auth, $perm, $forum, $user, $my_studip_settings;

    $cssSw = new cssClassSwitcher;
    ?>
    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
        <tr>
            <td class="blank">&nbsp;
            </td>
        </tr>
        <tr>

            <td class="blank" width="100%" align="center">
            
                <form method="POST" action="<?= URLHelper::getLink('?cmd=change_general&studipticket='.get_ticket())?>">
            <?= CSRFProtection::tokenTag() ?>
            <table width="70%" align="center" cellpadding=8 cellspacing=0 border=0 id="main_content">
                <tr>
                    <th width="50%" align=center><?=_("Option")?></th>
                    <th align=center><?=_("Auswahl")?></th>
                </tr>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="forced_language"><?print _("Sprache");?></label>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <? select_language($_SESSION['_language']); ?>
                    </td>
                </tr>

                <?
                if (!$perm->have_perm("root")) {
                ?>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="personal_startpage"><?print _("Pers&ouml;nliche Startseite");?></label><br>
                        <br><div id="personal_startpage_description" class="setting_info">
                        <?print _("Sie k&ouml;nnen hier einstellen, welche Seite standardm&auml;ßig nach dem Einloggen angezeigt wird. Wenn Sie zum Beispiel regelm&auml;&szlig;ig die Seite &raquo;Meine Veranstaltungen&laquo;. nach dem Login aufrufen, so k&ouml;nnen Sie dies hier direkt einstellen.");?></font><br><br>
                        </div>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <select name="personal_startpage" id="personal_startpage" aria-describedby="personal_startpage_description">
                            <?
                            printf ("<option %s value=\"\">"._("keine")."</option>", (!$my_studip_settings["startpage_redirect"]) ? "selected" : "");
                            printf ("<option %s value=\"1\">"._("Meine Veranstaltungen")."</option>", ($my_studip_settings["startpage_redirect"] ==  1) ? "selected" : "");
                            printf ("<option %s value=\"3\">"._("Mein Stundenplan")."</option>", ($my_studip_settings["startpage_redirect"] == 3) ? "selected" : "");
                            printf ("<option %s value=\"4\">"._("Mein Adressbuch")."</option>", ($my_studip_settings["startpage_redirect"] == 4) ? "selected" : "");
                            printf ("<option %s value=\"5\">"._("Mein Planer")."</option>", ($my_studip_settings["startpage_redirect"] == 5) ? "selected" : "");
                            ?>
                        </select>
                    </td>
                </tr>
                <?
                }
                ?>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="skiplinks_enable"><?print _("Skiplinks einblenden");?></label><br>
                        <br><div id="skiplinks_enable_description" class="setting_info">
                        <? print _("Mit dieser Einstellung wird nach dem ersten Drücken der Tab-Taste eine Liste mit Skiplinks eingeblendet, mit deren Hilfe Sie mit der Tastatur schneller zu den Hauptinhaltsbereichen der Seite navigieren können. Zusätzlich wird der aktive Bereich einer Seite hervorgehoben.");?>
                        </div>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <?
                        echo "<input type=\"checkbox\" name=\"skiplinks_enable\" id=\"skiplinks_enable\" aria-describedby=\"skiplinks_enable_description\" value=\"1\"";
                        if ($user->cfg->getValue("SKIPLINKS_ENABLE")) {
                            echo " checked";
                        }
                        echo ">";
                        ?>
                        </font><br><br>
                    </td>
                </tr>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="accesskey_enable"><?print _("Tastenkombinationen f&uuml;r Hauptfunktionen");?></label><br>
                        <br><div id="accesskey_enable_description" class="setting_info">
                        <? print _("Mit dieser Einstellung k&ouml;nnen Sie f&uuml;r die meisten in der Kopfzeile erreichbaren Hauptfunktionen eine Bedienung "
                        . "&uuml;ber Tastenkombinationen aktivieren. <br>Die Tastenkombination wird im Tooltip des jeweiligen Icons angezeigt. "
                        . "Diese kann für jeden Browser und jedes Betriebssystem unterschiedlich sein (siehe <a href='"
                        . URLHelper::getLink('http://en.wikipedia.org/wiki/Accesskey', NULL)."'>Wikipedia</a>)"); ?>
                        </div>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <?
                        echo "<input type=\"checkbox\" name=\"accesskey_enable\" id=\"accesskey_enable\" aria-describedby=\"accesskey_enable_description\" value=\"1\"";
                        IF ($user->cfg->getValue("ACCESSKEY_ENABLE")) {
                            echo " checked";
                        }
                        echo ">";
                        ?>
                        </font><br><br>
                    </td>
                </tr>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="showsem_enable"><?print _("Semesteranzeige auf &raquo;Meine Veranstaltungen&laquo;");?></label><br>
                        <br><div id="showsem_enable_description" class="setting_info">
                        <?print _("Mit dieser Einstellung k&ouml;nnen Sie auf der Seite &raquo;Meine Veranstaltungen&laquo; die Einblendung des Start- und Endsemesters hinter jeder Veranstaltung aktivieren.");?>
                        </div>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <?
                        echo "<input type=\"CHECKBOX\" name=\"showsem_enable\" id=\"showsem_enable\" aria-describedby=\"showsem_enable_description\" value=\"1\"";
                        IF ($user->cfg->getValue("SHOWSEM_ENABLE")) {
                            echo " checked";
                        }
                        echo ">";
                        ?>
                        </font><br><br>
                    </td>
                </tr>
                <tr <? $cssSw->switchClass() ?>>
                    <td <?=$cssSw->getFullClass()?> colspan=2 align="center">
                        <?=Button::create(_("Übernehmen"), array('title' => _("Änderungen übernehmen"))) ?>&nbsp;
                        <input type="hidden" name="view" value="allgemein">
                    </td>
                </tr>
                </form>
            </table>
            <br>
            <br>
            </td>
        </tr>
    </table>
<?
}
?>
