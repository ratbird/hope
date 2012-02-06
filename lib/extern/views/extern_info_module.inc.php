<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* extern_info_module.inc.php
* 
* 
* 
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       extern_info_module
* @package  studip_extern
*/

use Studip\Button, Studip\LinkButton;

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// extern_info_module.inc.php
// 
// Copyright (C) 2003 Peter Thienel <pthienel@web.de>,
// Suchi & Berg GmbH <info@data-quest.de>
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

require_once($GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/ExternConfig.class.php');

$info = ExternConfig::GetInfo($range_id, $config_id);
$css_switcher = new CssClassSwitcher("", "");

echo "<tr><td class=\"blank\" width=\"100%\">";
echo "&nbsp;</td></tr>\n";
echo "<tr><td class=\"" . $css_switcher->getHeaderClass() . "\" height=\"20\" valign=\"bottom\">\n";
echo "<font size=\"2\"><b>&nbsp;" . _("Allgemeine Daten") . "</b></font></td></tr>\n";
$css_switcher->switchClass();
echo "<tr><td" . $css_switcher->getFullClass() . ">\n";
echo "<blockquote><font size=\"2\"><br><b>";
echo _("Modulname:");
echo "</b>&nbsp " . $info["module_name"];
echo "<br><br>\n<font size=\"2\"><b>";
echo _("Name der Konfiguration:");
echo "</b>&nbsp " . $info["name"];
echo "<br><br>\n<b>";
echo _("Erstellt am:");
echo "</b>&nbsp " . $info["make_date"];
echo "&nbsp; &nbsp; &nbsp; <b>";
echo _("Letzte &Auml;nderung:");
echo "</b>&nbsp " . $info["change_date"];
echo "<br><br>\n<b>";
echo _("Beschreibung:");
echo "</b>&nbsp " . $EXTERN_MODULE_TYPES[$info["module_type"]]["description"];
echo "<br></font></blockquote>\n</td></tr>\n";
echo "<tr><td" . $css_switcher->getFullClass() . ">&nbsp;</td></tr>\n";

if ($info["module_type"] != 0) {
    if ($info["level"] == 1) {
        echo "<tr><td class=\"" . $css_switcher->getHeaderClass() . "\" height=\"20\" valign=\"bottom\">\n";
        echo "<font size=\"2\"><b>&nbsp;" . _("Direkter Link") . "</b></font></td></tr>\n";
        echo "<tr><td" . $css_switcher->getFullClass() . ">\n";
        echo "<blockquote><font size=\"2\"><br>\n";
        echo _("Der folgende Link verweist auf die von Stud.IP generierte HTML-Seite.");
        echo "<blockquote>\n<a href=\"{$info['link']}\" target=\"_blank\"><b>";
        echo $info["link_br"] . "</b></a></blockquote>\n";
        echo _("Diese Adresse k&ouml;nnen Sie in einen Link auf Ihrer Website integrieren, um auf die Ausgabe des Moduls zu verweisen.");
        echo "<br></font></blockquote>\n</td></tr>\n";
        echo "<tr><td" . $css_switcher->getFullClass() . ">&nbsp;</td></tr>\n";
    }
    if ($EXTERN_SRI_ENABLE && sri_is_enabled($SessSemName[1])) {
        echo "<tr><td class=\"" . $css_switcher->getHeaderClass() . "\" height=\"20\" valign=\"bottom\">\n";
        echo "<font size=\"2\"><b>&nbsp;" . _("Stud.IP-Remote-Include (SRI)  Schnittstelle") . "</b></font></td></tr>\n";
        echo "<tr><td" . $css_switcher->getFullClass() . ">\n";
        echo "<blockquote>\n<font size=\"2\"><br>";
        echo _("Der unten aufgef&uuml;hrte Textblock erm&ouml;glicht Ihnen den Zugriff auf die Stud.IP-Remote-Include-Schnittstelle (SRI).");
        echo "</font><blockquote>\n<b><pre>" . $info["sri"] . "</pre></b></blockquote>\n<font size=\"2\">";
        echo _("Kopieren Sie dieses Code-Schnipsel in eine beliebige Stelle im HTML-Quelltext einer Seite Ihrer Website.");
        echo "\n<br>";
        echo _("Durch eine spezielle Art des Seitenaufrufs, wird an dieser Stelle die Ausgabe des Moduls eingef&uuml;gt.");
        echo "<br></font></blockquote>\n</td></tr>\n";
        echo "<tr><td" . $css_switcher->getFullClass() . ">&nbsp;</td></tr>\n";
        
        echo "<tr><td class=\"" . $css_switcher->getHeaderClass() . "\" height=\"20\" valign=\"bottom\">\n";
        echo "<font size=\"2\"><b>&nbsp;" . _("Link zur SRI-Schnittstelle") . "</b></font></td></tr>\n";
        echo "<tr><td" . $css_switcher->getFullClass() . ">\n";
        echo "<blockquote>\n<font size=\"2\"><br>";
        echo _("Über diesen Link erreichen Sie die SRI-Schnittstelle:");
        echo "<blockquote><b>" . $info["link_sri"] . "</b></blockquote>\n";
        printf (_("Ersetzen Sie %s durch die URL der Seite, in die Sie die Ausgabe des Moduls einf&uuml;gen wollen. Diese Seite muss obigen Code-Schnipsel enthalten."),
                _("URL_DER_INCLUDE_SEITE"));
        echo "<br></font></blockquote>\n</td></tr>\n";
    }
}

$css_switcher->resetClass();
$css_switcher->switchClass();

echo "<tr><td" . $css_switcher->getFullClass() . " align=\"center\">&nbsp;<br>\n";
echo LinkButton::create('<<  ' . _('Zurück'), URLHelper::getLink('?list=TRUE'));
echo "</a><br>&nbsp;</td></tr>\n";

?>
