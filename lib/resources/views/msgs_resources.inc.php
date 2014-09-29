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
        "msg"=> _("Sie versuchen, eineN NutzerIn einzutragen, der/die nicht selbst in der Lage ist, die Belegung zu bearbeiten oder zu l�schen. Sie k�nnen dieseN NutzerIn leider nicht eintragen"));
$this->msg[3] = array (
        "mode" => 'success',
        "titel" => _("Belegung eingetragen"),
        "msg"=> _("Die Belegung wurde eingetragen"));
$this->msg[4] = array (
        "mode" => 'success',
        "titel" => _("Belegung ver�ndert"),
        "msg"=> _("Die Belegung wurde ver�ndert"));
$this->msg[5] = array (
        "mode" => 'success',
        "titel" => _("Belegung gel�scht"),
        "msg"=> _("Die Belegung wurde gel�scht"));
$this->msg[6] = array (
        "mode" => 'success',
        "titel" => _("Eigenschaften ver�ndert"),
        "msg"=> _("Die Eigenschaften der Ressource wurden ver�ndert"));
$this->msg[7] = array (
        "mode" => 'success',
        "titel" => _("Ressource gel�scht"),
        "msg"=> _("Die Ressource wurde gel�scht"));
$this->msg[8] = array (
        "mode" => 'success',
        "titel" => _("Berechtigungen ver�ndert"),
        "msg"=> _("Die Berechtigungseinstellungen der Ressource wurden ver�ndert"));
$this->msg[9] = array (
        "mode" => 'success',
        "titel" => _("Ressource verschoben"),
        "msg"=> _("Die Ressource wurde verschoben"));
$this->msg[10] = array (
        "mode" => "error",
        "msg"=> _("Bitte geben Sie eineN NutzerIn f�r die Belegung an, um diese Belegung zu speichern!"));
$this->msg[11] = array (
        "mode" => "error",
        "msg"=> _("Die Belegung konnte nicht gespeichert werden, da Sie sich mit einer anderen Belegung �berschneidet!"));
$this->msg[12] = array (
        "mode" => "info",
        "msg"=> _("Es existieren keine Ressourcen oder Ebenen, auf die Sie Zugriff haben.")."<br>"._("Um Ressourcen anzulegen, erzeugen Sie zun�chst eine neue Ebene, indem Sie &raquo;Neue Hierarchie erzeugen&laquo; anw�hlen.")." <br>"._("Anschlie�end k�nnen auf dieser Ebene Ressourcen anlegen"));
$this->msg[13] = array (
        "mode" => "info",
        "msg"=> _("Es existieren keine Ressourcen, die Sie im Rahmen dieser Veranstaltung belegen d�rfen."));
$this->msg[14] = array (
        "mode" => "info",
        "msg"=> _("Sie haben keine Ebene ausgew�hlt. Daher kann keine Liste erzeugt werden.")." <br>"._("Benutzen Sie die Suchfunktion oder w�hlen Sie unter &raquo;�bersicht&laquo; eine Ebene bzw. Ressource in der Hierarchie aus."));
$this->msg[15] = array (
        "mode" => "info",
        "msg"=> _("Sie haben kein Objekt zum Bearbeiten ausgew�hlt.")." <br>"._("Bitte w�hlen Sie zun�chst ein Objekt aus."));
$this->msg[16] = array (
        "mode" => "info",
        "msg"=> _("Sie haben kein Objekt zum Anzeigen ausgew�hlt.")." <br>"._("Bitte w�hlen Sie zun�chst ein Objekt aus."));
$this->msg[17] = array (
        "mode" => "error",
        "msg"=> _("Bitte geben Sie g�ltige Werte f�r Datum, Beginn und Ende der Belegung an!"));
$this->msg[18] = array (
        "mode" => "error",
        "msg"=> _("Bitte geben Sie einen g�ltigen Wert f�r das Ende der Wiederholung an!"));
$this->msg[19] = array (
        "mode" => "error",
        "msg"=> _("Das Ende der Wiederholung darf nicht vor dem ersten Termin der Wiederholung liegen!"));
$this->msg[20] = array (
        "mode" => "error",
        "msg"=> _("Die Endzeit darf nicht vor der Startzeit liegen!"));
$this->msg[21] = array (
        "mode" => "error",
        "msg"=> _("Die j�hrliche Wiederholung darf maximal 10 Jahre dauern!"));
$this->msg[22] = array (
        "mode" => "error",
        "msg"=> _("Die monatliche Wiederholung darf maximal 10 Jahre dauern!"));
$this->msg[23] = array (
        "mode" => "error",
        "msg"=> _("Die w�chentliche Wiederholung darf maximal 50 Mal wiederholt stattfinden!"));
$this->msg[24] = array (
        "mode" => "error",
        "msg"=> _("Die t�gliche Wiederholung darf maximal 100 Mal wiederholt stattfinden!"));
$this->msg[25] = array (
        "mode" => "error",
        "titel" => _("Fehlende Berechtigung"),
        "msg"=> _("Sie haben leider keine Berechtigung, diese Funktion zu benutzen!"));
$this->msg[26] = array (
        "mode" => "error",
        "msg"=> _("Fehler in den Sperrzeiten, bitte korrigieren Sie die Zeiten!"));
$this->msg[27] = array (
        "mode" => 'success',
        "msg"=> _("Die Sperrzeiten wurden ge�ndert."));
$this->msg[28] = array (
        "mode" => 'success',
        "msg"=> _("Die Sperrzeit wurde gel�scht. Falls die Blockierung aktiviert war, und der gel�schte Zeitraum bereits l�uft, k�nnen berechtigte Nutzer wieder Belegungen erstellen."));
$this->msg[29] = array (
        "mode" => "info",
        "msg"=> _("Sie haben den Raum als <u>blockierbar</u> markiert. Wollen Sie auch alle untergeordneten R�ume ebenfalls als <u>blockierbar</u> markieren?")
            . "<br>". LinkButton::createAccept(_('JA!'), '%s')
            . ' ' . LinkButton::createCancel(_('NEIN!'), '%s'));
$this->msg[30] = array (
        "mode" => "info",
        "msg"=> _("Sie haben den Raum als <u>nicht</u> mehr blockierbar markiert. Wollen Sie auch alle untergeordneten R�ume ebenfalls als <u>nicht</u> blockierbar markieren?")
            . "<br>". LinkButton::createAccept(_('JA!'), '%s')
            . ' ' . LinkButton::createCancel(_('NEIN!'), '%s'));
$this->msg[31] = array (
        "mode" => "info",
        "msg"=> "<font size=\"-1\">"._("<b>Ressourcenblockierung vom %s bis zum %s.</b>")."</font><br>".
            _("Sie versuchen, ein Objekt zu bearbeiten, das zur Zeit f�r eine Bearbeitung gesperrt ist. Nur der globale Ressourcenadministrator hat Zugriff auf dieses Objekt! <br>(Wenn Sie normalerweise Zugriff auf dieses Objekt haben, wird Ihnen der Zugriff nach Aufhebung der Blockierung wieder gew�hrt.)"));
$this->msg[32] = array (
        "mode" => 'success',
        "msg"=> _("Die ausgew�hlten Eintr�ge wurden in die aktuelle Anfrage �bernommen."));
$this->msg[33] = array (
        "mode" => 'success',
        "msg"=> _("Folgende R�ume wurden gebucht und der Veranstaltung zugewiesen: <font size=\"-1\" color=\"black\">%s</font>"));
$this->msg[34] = array (
        "mode" => "error",
        "msg"=> _("Folgende R�ume konnten wegen �berschneidungen nicht gebucht werden: <font size=\"-1\" color=\"black\">%s</font>"));
$this->msg[35] = array (
        "mode" => "error",
        "msg"=> _("Die folgende R�ume konnten wegen �berschneidungen nicht gebucht werden:")."<br>"._("Eine neue Anfrage, die einzeln bearbeitet werden mu�, wurde f�r jede Belegungszeit erstellt.  <font size=\"-1\" color=\"black\">%s</font>"));
// message 36 is not used anymore
$this->msg[37] = array (
        "mode" => 'success',
        "msg"=> _("Die regelm��ige Belegung wurde in Einzeltermine umgewandelt."));
$this->msg[38] = array (
        "mode" => 'success',
        "msg"=> _("Belegung wurde in die Ressource &raquo;%s&laquo; verschoben."));
$this->msg[39] = array (
        "mode" => "error",
        "msg"=> _("Die Belegung konnte nicht verschoben werden, da Sie sich in der gew�nschten Ressource mit einer anderen Belegung �berschneidet!"));
$this->msg[40] = array (
        "mode" => "info",
        "msg"=> _("Sie haben bereits Anfragen, die Sie ausgew�hlt haben, bearbeitet. Klicken Sie auf &raquo;absenden&laquo;, um Nachrichten zu aufgel�sten Anfragen versenden.")
            . " ". LinkButton::createAccept(_('Absenden'), '%s'));
$this->msg[41] = array (
        "mode" => "info",
        "msg"=> _("Mit den von ihnen ausgew�hlten Einstellungen sind keine Anfragen, die Sie aufl�sen k�nnten, vorhanden."));
$this->msg[42] = array (
        "mode" => "info",
        "msg"=> _("Folgenden Einzelterminen wurde kein Raum zugewiesen, da gesonderte Anfragen zu diesem Termine vorliegen, die einzeln bearbeitet werden m�ssen:")."<font size=\"-1\" color=\"black\">%s</font>");
$this->msg[43] = array (
        "mode" => "info",
        "msg"=> "<form action=\"%s\" method=\"post\">"
            . CSRFProtection::tokenTag()
            . "<table class=\"default\"><tr><td style=\"vertical-align:top\">"
            . _("Wollen Sie die Anfrage wirklich ablehnen?")
            ."<br><br>" . Button::createAccept('JA!')
            . " ". LinkButton::createCancel(_('NEIN!'), '%s') .'<br>'
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
        "msg"=> _("Es wurden %s Raumanfragen gel�scht."));
$this->msg[46] = array (
    'mode' => 'error',
    'msg'  => _("Sie m�ssen eineN NutzerIn eintragen oder eine freie Eingabe zur Belegung eintragen, um diese Belegung speichern zu k�nnen!")
);
$this->msg[47] = array (
        "mode" => "success",
        "msg"=> _("Belegung wurde in die Ressource &raquo;%s&laquo; kopiert."));
$this->msg[48] = array (
        "mode" => "error",
        "msg"=> _("Die Belegung konnte nicht in die Ressource &raquo;%s&laquo; kopiert werden, da sie sich mit einer anderen Belegung �berschneidet:") . "<br><font size=\"-1\" color=\"black\">%s</font>");
$this->msg[49] = array(
    'mode' => 'info',
    'msg'  => _('Sie k�nnen den Anfragenplan nur anzeigen, wenn Sie Anfragen f�r einen bestimmten Raum aufl�sen.')
);

$this->msg[50] = array(
    'mode' => 'success',
    'msg'  => _('Der Kommentar f�r diese Belegung wurde gespeichert')
);