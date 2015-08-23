<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ExternElementMainPersondetails.class.php
* 
*  
* 
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternElementMainPersondetails
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternElementMainPersondetails.class.php
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

class ExternElementMainPersondetails extends ExternElementMain {

    /**
    * Constructor
    *
    */
    function ExternElementMainPersondetails ($module_name, &$data_fields, &$field_names, &$config) {
        $this->attributes = array(
                'name', 'genericdatafields', 'order', 'visible',
                'aliases', 'width', 'showcontact', 'showimage', 'wholesite', 'nameformat',
                'dateformat', 'language', 'studiplink', 'urlcss', 'title', 'copyright',
                'author'
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
            /*
            "order" => '|0|1|2|3|4|5|6|7|8',
            "visible" => '|1|1|1|1|1|1|1|1|1',
            "aliases" => '||'._("Lebenslauf").'|'._("Schwerpunkte").'|'._("Lehrveranstaltungen").'|'
                    ._("Aktuell").'|'._("Termine").'|'._("Publikationen").'|'._("Literaturlisten").'|',
            */
            "order" => '|0|1|2|3|4|5|6|7',
            "visible" => '|1|1|1|1|1|1|1|1',
            "aliases" => '||'._("Lebenslauf").'|'._("Schwerpunkte").'|'._("Lehrveranstaltungen").'|'
                    ._("Aktuell").'|'._("Termine").'|'._("Publikationen").'|',
            "showcontact" => '1',
            "showimage" => 'right',
            "wholesite" => '0',
            "nameformat" => '',
            "dateformat" => '%d. %b. %Y',
            "language" => '',
            "studiplink" => 'top',
            "urlcss" => '',
            "title" => _("MitarbeiterInnen"),
            "copyright" => htmlReady($GLOBALS['UNI_NAME_CLEAN']
                    . " ({$GLOBALS['UNI_CONTACT']})"),
            "author" => ''
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
        
        $headline = $edit_form->editHeadline(_("Allgemeine Angaben zum Tabellenaufbau"));
        
        $edit_function = $this->edit_function;
        $table = $edit_form->editMainSettings($this->field_names["content"],
                array("aliases" => array(0, 7)), array("sort", "width", "widthpp"));
        
        $content_table .= $edit_form->editContentTable($headline, $table);
        $content_table .= $edit_form->editBlankContent();
        
        $headline = $edit_form->editHeadline(_("Weitere Angaben"));
        
        $title = _("Kontaktdaten anzeigen:");
        $info = _("Anw�hlen, wenn die Kontaktdaten (Anschrift, Email, Telefon usw.) angezeigt werden sollen.");
        $values = "1";
        $names = "";
        $table = $edit_form->editCheckboxGeneric("showcontact", $title, $info, $values, $names);
        
        $title = _("Bild anzeigen:");
        $info = _("W�hlen Sie, ob ein vom Nutzer in Stud.IP eingestelltes Bild links oder rechts neben den Kontaktdaten angezeigt werden soll.");
        $value = array("left", "right", "0");
        $names = array(_("links"), _("rechts"), _("nicht anzeigen"));
        $table .= $edit_form->editRadioGeneric("showimage", $title, $info, $value, $names);
        
        $title = _("Namensformat:");
        $info = _("W�hlen Sie, wie Personennamen formatiert werden sollen.");
        $values = array("", "no_title_short", "no_title", "no_title_rev", "full", "full_rev");
        $names = array(_("keine Auswahl"), _("Meyer, P."), _("Peter Meyer"), _("Meyer Peter"),
                _("Dr. Peter Meyer"), _("Meyer, Peter, Dr."));
        $table .= $edit_form->editOptionGeneric("nameformat", $title, $info, $values, $names);
        
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
        
        $title = _("Stud.IP-Link:");
        $info = _("Ausgabe eines Links, der direkt zum Stud.IP-Administrationsbereich verweist.");
        $value = array("top", "bottom", "0");
        $names = array(_("oberhalb"), _("unterhalb der Tabelle"), _("ausblenden"));
        $table .= $edit_form->editRadioGeneric("studiplink", $title, $info, $value, $names);
        
        $title = _("HTML-Header/Footer:");
        $info = _("Anw�hlen, wenn die Seite als komplette HTML-Seite ausgegeben werden soll, z.B. bei direkter Verlinkung oder in einem Frameset.");
        $values = "1";
        $names = "";
        $table .= $edit_form->editCheckboxGeneric("wholesite", $title, $info, $values, $names);
        
        $title = _("Stylesheet-Datei:");
        $info = _("Geben Sie hier die URL Ihrer Stylesheet-Datei an.");
        $table .= $edit_form->editTextfieldGeneric("urlcss", $title, $info, 50, 200);
        
        $title = _("Seitentitel:");
        $info = _("Geben Sie hier den Titel der Seite ein. Der Titel wird bei der Anzeige im Web-Browser in der Titelzeile des Anzeigefensters angezeigt.");
        $table .= $edit_form->editTextfieldGeneric("title", $title, $info, 50, 200);
        
        $title = _("Copyright:");
        $info = _("Geben Sie hier einen Copyright-Vermerk an. Dieser wird im Meta-Tag \"copyright\" ausgegeben, wenn Sie die Option \"HTML-Header/Footer\" angew�hlt haben.");
        $table .= $edit_form->editTextfieldGeneric("copyright", $title, $info, 50, 200);
        
        $title = _("Autor:");
        $info = _("Geben Sie hier den Namen des Seitenautors an. Dieser wird im Meta-Tag \"author\" ausgegeben, wenn Sie die Option \"HTML-Header/Footer\" angew�hlt haben.");
        $table .= $edit_form->editTextfieldGeneric("author", $title, $info, 50, 200);
        
        $content_table .= $edit_form->editContentTable($headline, $table);
        $content_table .= $edit_form->editBlankContent();
        
        $submit = $edit_form->editSubmit($this->config->getName(),
                $this->config->getId(), $this->getName());
        $out = $edit_form->editContent($content_table, $submit);
        $out .= $edit_form->editBlank();
        
        return $element_headline . $out;
    }
    
    function checkValue ($attribute, $value) {
        if ($attribute == "showcontact") {
            if (!isset($_POST["Main_$attribute"])) {
                $_POST["Main_$attribute"] = 0;
                return FALSE;
            }
                
            return !($value == "1" || $value == "");
        }
        
        if ($attribute == "showimage") {
            if (!isset($_POST["Main_$attribute"])) {
                $_POST["Main_$attribute"] = 0;
                return FALSE;
            }
            
            return !($value == "left" || $value == "right" || $value == "0");
        }
        
        return FALSE;
    }
    
}

?>
