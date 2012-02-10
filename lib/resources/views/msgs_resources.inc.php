<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* msgs_resources.inc.php
*
* library for the messages (error, info and other)
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  resources
* @module       msgs_resources.inc.php
* @package      resources
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// msgs_resources.inc.php
// Alle Meldungen, die in der Ressourcenverwaltung ausgegeben werden
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

use Studip\Button,
    Studip\LinkButton;

$this->msg[1] = array (
        "mode" => "error",
        "titel" => _("Fehlende Berechtigung"),
        "msg"=> _("Sie haben leider keine Berechtigung, das Objekt zu bearbeiten!"));
$this->msg[2] = array (
        "mode" => "error",
        "titel" => _("NutzerIn hat keine Berechtigung") ,
        "msg"=> _("Sie versuchen, eineN NutzerIn einzutragen, der/die nicht selbst in der Lage ist, die Belegung zu bearbeiten oder zu l&ouml;schen. Sie k&ouml;nnen dieseN NutzerIn leider nicht eintragen"));
$this->msg[3] = array (
        "mode" => 'success',
        "titel" => _("Belegung eingetragen"),
        "msg"=> _("Die Belegung wurde eingetragen"));
$this->msg[4] = array (
        "mode" => 'success',
        "titel" => _("Belegung ver&auml;ndert"),
        "msg"=> _("Die Belegung wurde ver&auml;ndert"));
$this->msg[5] = array (
        "mode" => 'success',
        "titel" => _("Belegung gel&ouml;scht"),
        "msg"=> _("Die Belegung wurde gel&ouml;scht"));
$this->msg[6] = array (
        "mode" => 'success',
        "titel" => _("Eigenschaften ver&auml;ndert"),
        "msg"=> _("Die Eigenschaften der Ressource wurden ver&auml;ndert"));
$this->msg[7] = array (
        "mode" => 'success',
        "titel" => _("Ressource gel&ouml;scht"),
        "msg"=> _("Die Ressource wurde gel&ouml;scht"));
$this->msg[8] = array (
        "mode" => 'success',
        "titel" => _("Berechtigungen ver&auml;ndert"),
        "msg"=> _("Die Berechtigungseinstellungen der Ressource wurden ver&auml;ndert"));
$this->msg[9] = array (
        "mode" => 'success',
        "titel" => _("Ressource verschoben"),
        "msg"=> _("Die Ressource wurde verschoben"));
$this->msg[10] = array (
        "mode" => "error",
        "msg"=> _("Bitte geben Sie eineN NutzerIn f&uuml;r die Belegung an, um diese Belegung zu speichern!"));
$this->msg[11] = array (
        "mode" => "error",
        "msg"=> _("Die Belegung konnte nicht gespeichert werden, da Sie sich mit einer anderen Belegung &uuml;berschneidet!"));
$this->msg[12] = array (
        "mode" => "info",
        "msg"=> _("Es existieren keine Ressourcen oder Ebenen, auf die Sie Zugriff haben.")."<br>"._("Um Ressourcen anzulegen, erzeugen Sie zun&auml;chst eine neue Ebene, indem Sie &raquo;Neue Hierarchie erzeugen&laquo; anw&auml;hlen.")." <br>"._("Anschließend k&ouml;nnen auf dieser Ebene Ressourcen anlegen"));
$this->msg[13] = array (
        "mode" => "info",
        "msg"=> _("Es existieren keine Ressourcen, die Sie im Rahmen dieser Veranstaltung belegen d&uuml;rfen."));
$this->msg[14] = array (
        "mode" => "info",
        "msg"=> _("Sie haben keine Ebene ausgew&auml;hlt. Daher kann keine Liste erzeugt werden.")." <br>"._("Benutzen Sie die Suchfunktion oder w&auml;hlen Sie unter &raquo;&Uuml;bersicht&laquo; einen Ebene bzw. Ressource in der Hierarchie aus."));
$this->msg[15] = array (
        "mode" => "info",
        "msg"=> _("Sie haben kein Objekt zum Bearbeiten ausgew&auml;hlt.")." <br>"._("Bitte w&auml;hlen Sie zun&auml;chst ein Objekt aus."));
$this->msg[16] = array (
        "mode" => "info",
        "msg"=> _("Sie haben kein Objekt zum Anzeigen ausgew&auml;hlt.")." <br>"._("Bitte w&auml;hlen Sie zun&auml;chst ein Objekt aus."));
$this->msg[17] = array (
        "mode" => "error",
        "msg"=> _("Bitte geben Sie g&uuml;ltige Werte f&uuml;r Datum, Beginn und Ende der Belegung an!"));
$this->msg[18] = array (
        "mode" => "error",
        "msg"=> _("Bitte geben Sie einen g&uuml;ltigen Wert f&uuml;r das Ende der Wiederholung an!"));
$this->msg[19] = array (
        "mode" => "error",
        "msg"=> _("Das Ende der Wiederholung darf nicht vor dem ersten Termin der Wiederholung liegen!"));
$this->msg[20] = array (
        "mode" => "error",
        "msg"=> _("Die Endzeit darf nicht vor der Startzeit liegen!"));
$this->msg[21] = array (
        "mode" => "error",
        "msg"=> _("Die j&auml;hrliche Wiederholung darf maximal 10 Jahre dauern!"));
$this->msg[22] = array (
        "mode" => "error",
        "msg"=> _("Die monatliche Wiederholung darf maximal 10 Jahre dauern!"));
$this->msg[23] = array (
        "mode" => "error",
        "msg"=> _("Die w&ouml;chentliche Wiederholung darf maximal 50 Mal wiederholt stattfinden!"));
$this->msg[24] = array (
        "mode" => "error",
        "msg"=> _("Die t&auml;gliche Wiederholung darf maximal 100 Mal wiederholt stattfinden!"));
$this->msg[25] = array (
        "mode" => "error",
        "titel" => _("Fehlende Berechtigung"),
        "msg"=> _("Sie haben leider keine Berechtigung, diese Funktion zu benutzen!"));
$this->msg[26] = array (
        "mode" => "error",
        "msg"=> _("Fehler in den Sperrzeiten, bitte korrigieren Sie die Zeiten!"));
$this->msg[27] = array (
        "mode" => 'success',
        "msg"=> _("Die Sperrzeiten wurden ge&auml;ndert."));
$this->msg[28] = array (
        "mode" => 'success',
        "msg"=> _("Die Sperrzeit wurde gel&ouml;scht. Falls die Blockierung aktiviert war, und der gel&ouml;schte Zeitraum bereits l&auml;uft, k&ouml;nnen berechtigte Nutzer wieder Belegungen erstellen."));
$this->msg[29] = array (
        "mode" => "info",
        "msg"=> _("Sie haben den Raum als <u>blockierbar</u> markiert. Wollen Sie auch alle untergeordneten R&auml;ume ebenfalls als <u>blockierbar</u> markieren?")
            ."<br><a href=\"%s?set_lockable_recursiv=1&lock_resource_id=%s\">".makeButton("ja2")."</a>"
            ."&nbsp;<a href=\"%s\">".makeButton("nein")."</a>");
$this->msg[30] = array (
        "mode" => "info",
        "msg"=> _("Sie haben den Raum als <u>nicht</u> mehr blockierbar markiert. Wollen Sie auch alle untergeordneten R&auml;ume ebenfalls als <u>nicht</u> blockierbar markieren?")
            ."<br><a href=\"%s?unset_lockable_recursiv=1&lock_resource_id=%s\">".makeButton("ja2")."</a>"
            ."&nbsp;<a href=\"%s\">".makeButton("nein")."</a>");
$this->msg[31] = array (
        "mode" => "info",
        "msg"=> "<font size=\"-1\">"._("<b>Ressourcenblockierung vom %s bis zum %s.</b>")."</font><br>".
            _("Sie versuchen, ein Objekt zu bearbeiten, das zur Zeit f&uuml;r eine Bearbeitung gesperrt ist. Nur der globale Ressourcenadministrator hat Zugriff auf dieses Objekt! <br>(Wenn Sie normalerweise Zugriff auf dieses Objekt haben, wird Ihnen der Zugriff nach Aufhebung der Blockierung wieder gew&auml;hrt.)"));
$this->msg[32] = array (
        "mode" => 'success',
        "msg"=> _("Die ausgew&auml;hlten Eintr&auml;ge wurden in die aktuelle Anfrage &uuml;bernommen."));
$this->msg[33] = array (
        "mode" => 'success',
        "msg"=> _("Folgende R&auml;ume wurden gebucht und der Veranstaltung zugewiesen: <font size=\"-1\" color=\"black\">%s</font>"));
$this->msg[34] = array (
        "mode" => "error",
        "msg"=> _("Folgende R&auml;ume konnten wegen &Uuml;berschneidungen nicht gebucht werden: <font size=\"-1\" color=\"black\">%s</font>"));
$this->msg[35] = array (
        "mode" => "error",
        "msg"=> _("Die folgende R&auml;ume konnten wegen &Uuml;berschneidungen nicht gebucht werden:")."<br>"._("Eine neue Anfrage, die einzeln bearbeitet werden mu&szlig;, wurde f&uuml;r jede Belegungszeit erstellt.  <font size=\"-1\" color=\"black\">%s</font>"));
$this->msg[36] = array (
        "mode" => "info",
        "msg"=> _("Sie haben alle Anfragen, die Sie ausgew&auml;hlt haben, bearbeitet. Wollen Sie jetzt Nachrichten zu allen aufgel&ouml;sten Anfragen versenden?")
            ."<br><a href=\"%s?snd_closed_request_sms=1&reset_set=1\">".makeButton("ja2")."</a>"
            ."&nbsp;<a href=\"%s?reset_set=1\">".makeButton("nein")."</a>");
$this->msg[37] = array (
        "mode" => 'success',
        "msg"=> _("Die regelm&auml;&szlig;ige Belegung wurde in Einzeltermine umgewandelt."));
$this->msg[38] = array (
        "mode" => 'success',
        "msg"=> _("Belegung wurde in die Ressource &raquo;%s&laquo; verschoben."));
$this->msg[39] = array (
        "mode" => "error",
        "msg"=> _("Die Belegung konnte nicht verschoben werden, da Sie sich in der gew&uuml;nschten Ressource mit einer anderen Belegung &uuml;berschneidet!"));
$this->msg[40] = array (
        "mode" => "info",
        "msg"=> _("Sie haben bereits Anfragen, die Sie ausgew&auml;hlt haben, bearbeitet. Klicken Sie auf &raquo;absenden&laquo;, um Nachrichten zu aufgel&ouml;sten Anfragen versenden.")
            ."&nbsp;<a href=\"%s?snd_closed_request_sms=1\">".makeButton("absenden")."</a>");
$this->msg[41] = array (
        "mode" => "info",
        "msg"=> _("Mit den von ihnen ausgewählten Einstellungen sind keine Anfragen, die Sie auflösen könnten, vorhanden."));
$this->msg[42] = array (
        "mode" => "info",
        "msg"=> _("Folgenden Einzelterminen wurde kein Raum zugewiesen, da gesonderte Anfragen zu diesem Termine vorliegen, die einzeln bearbeitet werden m&uuml;ssen:")."<font size=\"-1\" color=\"black\">%s</font>");
$this->msg[43] = array (
        "mode" => "info",
        "msg"=> "<form action=\"%s\" method=\"post\">"
            . CSRFProtection::tokenTag()
            . "<table class=\"default\"><tr><td style=\"vertical-align:top\">"
            . _("Wollen Sie die Anfrage wirklich ablehnen?")
            ."<br><br>" . makeButton('ja2', 'input')
            ."&nbsp;<a href=\"%s?decline_request=0\">".makeButton("nein")."</a><br>"
            .'<input type="hidden" name="decline_request" value="1"></td><td style="vertical-align:top">'
            ._("Grund der Ablehnung:").'<br>'
            .'<textarea cols="30" rows="3" name="decline_message" style="width:90%%"></textarea></td></tr></table>'
            .'</form>');
$this->msg[44] = array (
        "mode" => "error",
        "msg"=> _("Die Belegungen konnte nicht gespeichert werden, da sie mit folgenden Sperrzeiten kollidiert:")
            ."<br><font size=\"-1\" color=\"black\">%s</font>");
$this->msg[45] = array (
        "mode" => 'success',
        "msg"=> _("Es wurden %s Raumanfragen gel&ouml;scht."));
$this->msg[46] = array (
    'mode' => 'error',
    'msg'  => _("Sie müssen eineN NutzerIn eintragen oder eine freie Eingabe zur Belegung eintragen, um diese Belegung speichern zu können!")
);
$this->msg[47] = array (
        "mode" => "success",
        "msg"=> _("Belegung wurde in die Ressource &raquo;%s&laquo; kopiert."));
$this->msg[48] = array (
        "mode" => "error",
        "msg"=> _("Die Belegung konnte nicht in die Ressource &raquo;%s&laquo; kopiert werden, da sie sich mit einer anderen Belegung &uuml;berschneidet:") . "<br><font size=\"-1\" color=\"black\">%s</font>");
$this->msg[49] = array(
    'mode' => 'info',
    'msg'  => _('Sie können den Anfragenplan nur anzeigen, wenn Sie Anfragen für einen bestimmten Raum auflösen.')
);
?>