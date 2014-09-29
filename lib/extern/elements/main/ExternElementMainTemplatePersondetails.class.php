<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ExternElementMainTemplatePersondetails.class.php
*
*
*
*
* @author       Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternElementMainTemplatePersondetails
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternElementMainTemplatePersondetails.class.php
//
// Copyright (C) 2007 Peter Thienel <thienel@data-quest.de>,
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


require_once($GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/ExternElementMain.class.php');

class ExternElementMainTemplatePersondetails extends ExternElementMain {

    /**
    * Constructor
    *
    */
    function ExternElementMainTemplatePersondetails ($module_name, &$data_fields, &$field_names, &$config) {
        $this->attributes = array(
                'name', 'nameformat', 'dateformat', 'language', 'studiplink', 'defaultaddr', 'onlylecturers'
        );
        $this->real_name = _("Grundeinstellungen");
        $this->description = _("In den Grundeinstellungen k�nnen Sie allgemeine Daten des Moduls �ndern.");
        parent::ExternElementMain($module_name, $data_fields, $field_names, $config);
    }

    /**
    *
    */
    function getDefaultConfig () {

        $config = array(
            "name" => '',
            "nameformat" => '',
            "dateformat" => '%d. %b. %Y',
            "language" => '',
            'defaultaddr' => '',
            'onlylecturers' => '1'
        );

        get_default_generic_datafields($config, "user");

        return $config;
    }

    /**
    *
    */
    function toStringEdit ($post_vars = "", $faulty_values = "",
            $edit_form = "", $anker = "") {

        update_generic_datafields($this->config, $this->data_fields["content"],
                $this->field_names["content"], "user");
        $out = "";
        $table = "";
        if ($edit_form == "")
            $edit_form = new ExternEditModule($this->config, $post_vars, $faulty_values, $anker);

        $edit_form->setElementName($this->getName());
        $element_headline = $edit_form->editElementHeadline($this->real_name,
                $this->config->getName(), $this->config->getId(), TRUE, $anker);

        $headline = $edit_form->editHeadline(_("Name der Konfiguration"));
        $table = $edit_form->editName("name");
        $content_table = $edit_form->editContentTable($headline, $table);
        $content_table .= $edit_form->editBlankContent();

        $content_table .= $this->getSRIFormContent($edit_form);

        $headline = $edit_form->editHeadline(_("Weitere Angaben"));

        $title = _("Namensformat:");
        $info = _("W�hlen Sie, wie Personennamen formatiert werden sollen.");
        $values = array("", "no_title_short", "no_title", "no_title_rev", "full", "full_rev");
        $names = array(_("keine Auswahl"), _("Meyer, P."), _("Peter Meyer"), _("Meyer Peter"),
                _("Dr. Peter Meyer"), _("Meyer, Peter, Dr."));
        $table = $edit_form->editOptionGeneric("nameformat", $title, $info, $values, $names);

        $title = _("Datumsformat:");
        $info = _("W�hlen Sie, wie Datumsangaben formatiert werden sollen.");
        $values = array("%d. %b. %Y", "%d.%m.%Y", "%d.%m.%y", "%d. %B %Y", "%m/%d/%y");
        $names = array(_("25. Nov. 2003"), _("25.11.2003"), _("25.11.03"),
                _("25. November 2003"), _("11/25/03"));
        $table .= $edit_form->editOptionGeneric("dateformat", $title, $info, $values, $names);

        $title = _("Sprache:");
        $info = _("W�hlen Sie eine Sprache f�r die Datumsangaben aus.");
        $values = array("", "de_DE", "en_GB");
        $names = array(_("keine Auswahl"), _("Deutsch"), _("Englisch"));
        $table .= $edit_form->editOptionGeneric("language", $title, $info, $values, $names);

        if (in_array(get_object_type($this->config->range_id), array('fak', 'global'))) {
            $title = _("Nur Lehrende:");
            $info = _("Es werden nur Personen angezeigt, die in einer sichtbaren Veranstaltung des aktuellen Semesters Dozent sind.");
            $values = '1';
            $table .= $edit_form->editCheckboxGeneric('onlylecturers', $title, $info, $values, '');

            $table .= $edit_form->editTextblock('<span style="font-weight: bold">'
                . _("Das Modul zeigt nur Personen an, die eine Standardadresse angegeben haben.")
                . '</span>');
        } else {
            $title = _("Standard-Adresse:");
            $info = _("Wenn Sie diese Option w�hlen, wird die Standard-Adresse ausgegeben, die jede(r) Mitarbeiter(in) bei seinen universit�ren Daten ausw�hlen kann. W�hlen Sie diese Option nicht, wenn immer die Adresse der Einrichtung ausgegeben werden soll.");
            $table .= $edit_form->editCheckboxGeneric('defaultaddr', $title, $info, '1', '0');
        }

        $content_table .= $edit_form->editContentTable($headline, $table);
        $content_table .= $edit_form->editBlankContent();

        $submit = $edit_form->editSubmit($this->config->getName(),
                $this->config->getId(), $this->getName());
        $out = $edit_form->editContent($content_table, $submit);
        $out .= $edit_form->editBlank();

        return $element_headline . $out;
    }

    function checkValue ($attribute, $value) {
        if ($attribute == 'defaultaddr' || $attribute == 'onlylecturers') {
            if (!isset($_POST["Main_$attribute"])) {
                $_POST["Main_$attribute"] = 0;
                return FALSE;
            }
            return !($value == "1" || $value == "");
        }

        return FALSE;
    }

}

?>
