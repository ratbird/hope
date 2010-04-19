<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
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
function select_language($selected_language = "") {
    global $INSTALLED_LANGUAGES, $DEFAULT_LANGUAGE;

    if (!isset($selected_language)) {
        $selected_language = $DEFAULT_LANGUAGE;
    }

    echo "<select name=\"forced_language\" width=30>";
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
    global $PHP_SELF, $_language, $auth, $perm, $forum, $user, $my_studip_settings;

    $db = new DB_Seminar;

    $cssSw = new cssClassSwitcher;
    ?>
    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
        <tr>
            <td class="blank" colspan=2>&nbsp;
            </td>
        </tr>
        <tr>

            <td class="blank" width="100%" colspan="2" align="center">
            <blockquote>
                <font size="-1"><b><?print _("Hier k&ouml;nnen Sie die Ansicht von Stud.IP nach Ihren Vorstellungen anpassen.");?>
            </blockquote>
            <form method="POST" action="<? echo $PHP_SELF ?>?cmd=change_general&studipticket=<?=get_ticket()?>">
            <table width="70%" align="center"cellpadding=8 cellspacing=0 border=0>
                <tr>
                    <th width="50%" align=center><?=_("Option")?></th>
                    <th align=center><?=_("Auswahl")?></th>
                </tr>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <font size="-1"><?print _("Sprache");?></font>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <? select_language($_language); ?>
                    </td>
                </tr>

                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <font size="-1"><?print _("Java-Script Hovereffekte");?></font><br>
                        <br><div align="left"><font size="-1">
                        <?print _("Mit dieser Funktion k&ouml;nnen Sie durch reines &Uuml;berfahren bestimmter Icons mit dem Mauszeiger (z.B. in den Foren oder im Adressbuch) die entsprechenden Eintr&auml;ge anzeigen lassen. Sie k&ouml;nnen sich so sehr schnell und effizient auch durch gr&ouml;&szlig;ere Informationsmengen arbeiten. Da jedoch die Ladezeiten der Seiten erheblich ansteigen, empfehlen wir diese Einstellung nur für NutzerInnen die mindestens &uuml;ber eine ISDN Verbindung verf&uuml;gen.");?>
                        </font></div>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <?
                        IF ($auth->auth["jscript"]) {
                            echo "<input type=CHECKBOX name='jshover' value=1";
                        IF($forum["jshover"]==1)
                            echo " checked";
                        echo ">";
                        } else
                        echo "<font size=\"-1\">"._("Sie müssen in Ihrem Browser Javascript aktivieren um dieses Feature nutzen zu können.")."</font>";
                        ?>
                        </font><br><br>
                    </td>
                </tr>
                <?
                if (!$perm->have_perm("root")) {
                ?>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <font size="-1"><?print _("pers&ouml;nliche Startseite");?></font><br>
                        <br><div align="left"><font size="-1">
                        <?print _("Sie k&ouml;nnen hier einstellen, welcher Systembereich automatisch nach dem Login oder Autologin aufgerufen wird. Wenn Sie zum Beispiel regelm&auml;&szlig;ig die Seite &raquo;Meine Veranstaltungen&laquo;. nach dem Login aufrufen, so k&ouml;nnen Sie dies hier direkt einstellen.");?></font><br><br>
                        </font></div>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <select name="personal_startpage">
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
                        <font size="-1"><?print _("Tastenkombinationen f&uuml;r Hauptfunktionen");?></font><br>
                        <br><div align="left"><font size="-1">
                        <?print _("Mit dieser Einstellung k&ouml;nnen Sie f&uuml;r die meisten in der Kopfzeile erreichbaren Hauptfunktionen eine Bedienung &uuml;ber Tastenkombinationen aktivieren. <br>Die Tastenkombination wird im Tooltip des jeweiligen Icons angezeigt.");?>
                        </font></div>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <?
                        echo "<input type=\"CHECKBOX\" name=\"accesskey_enable\" value=\"1\"";
                        IF ($user->cfg->getValue($user->id, "ACCESSKEY_ENABLE")) {
                            echo " checked";
                        }
                        echo ">";
                        ?>
                        </font><br><br>
                    </td>
                </tr>
                <tr  <? $cssSw->switchClass() ?>>
                    <td  align="right" class="blank" style="border-bottom:1px dotted black;">
                        <font size="-1"><?print _("Semesteranzeige auf &raquo;Meine Veranstaltungen&laquo;");?></font><br>
                        <br><div align="left"><font size="-1">
                        <?print _("Mit dieser Einstellung k&ouml;nnen Sie auf der Seite &raquo;Meine Veranstaltungen&laquo; die Einblendung des Start- und Endsemesters hinter jeder Veranstaltung aktivieren.");?>
                        </font></div>
                    </td>
                    <td <?=$cssSw->getFullClass()?>>
                        <?
                        echo "<input type=\"CHECKBOX\" name=\"showsem_enable\" value=\"1\"";
                        IF ($user->cfg->getValue($user->id, "SHOWSEM_ENABLE")) {
                            echo " checked";
                        }
                        echo ">";
                        ?>
                        </font><br><br>
                    </td>
                </tr>
                <tr <? $cssSw->switchClass() ?>>
                    <td  <?=$cssSw->getFullClass()?> colspan=2 align="middle">
                        <font size=-1><input type="IMAGE" <?=makeButton("uebernehmen", "src") ?> border=0 value="<?_("&Auml;nderungen &uuml;bernehmen")?>"></font>&nbsp;
                        <input type="HIDDEN" name="view" value="allgemein">
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
