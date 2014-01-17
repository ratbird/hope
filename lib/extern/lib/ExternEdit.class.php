<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ExternEdit.class.php
*
*
*
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternEdit
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternEdit.class.php
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

use Studip\Button, Studip\LinkButton;

require_once('lib/visual.inc.php');
require_once('lib/classes/cssClassSwitcher.inc.php');
require_once('lib/language.inc.php');

class ExternEdit {

    var $css;
    var $config;
    var $form_values = array();
    var $faulty_values = array();
    var $element_name = "main";
    var $is_post_vars = FALSE;
    var $edit_element;
    var $width_1 = " width=\"20%\"";
    var $width_2 = " width=\"80%\"";
    var $error_sign = "<font size=\"4\" color=\"ff0000\">&nbsp; &nbsp;<b>*</b></font>";

    function ExternEdit (&$config, $form_values = "", $faulty_values = "",
             $edit_element = "") {

        $this->css = new CssClassSwitcher("", "table_header_bold");
        $this->config =& $config;
        $this->form_values = $form_values;
        $this->edit_element = $edit_element;

        if (is_array($form_values))
            $this->is_post_vars = TRUE;

        if ($faulty_values != "")
            $this->faulty_values = $faulty_values;
    }

    function setElementName ($element_name) {
        $this->element_name = $element_name;
    }

    function getValue ($attribute) {
        if ($this->is_post_vars && ($this->edit_element == $this->element_name)) {
            $form_name = $this->element_name . "_" . $attribute;
            $value = $this->form_values[$form_name];

            if ($value != "" || $this->faulty_values[$form_name]) {
                if (is_array($value)) {
                    // fit the values for output in a form
                    //for ($i = 0; $i < sizeof($value); $i++)
                    foreach ($value as $key => $val) {
                        $val_tmp[$key] = htmlReady(stripslashes($val));
                    }

                    return $val_tmp;
                }

                return htmlReady(stripslashes($value));
            }
        }

        $value = $this->config->getValue($this->element_name, $attribute);
        if (is_array($value)) {
            // fit the values for output in a form
            foreach ($value as $key => $val) {
            //for ($i = 0; $i < sizeof($value); $i++)
                $val_tmp[$key] = htmlReady(stripslashes($val));
            }

            return $val_tmp;
        }

        return htmlReady($this->config->getValue($this->element_name, $attribute));
    }

    function getEditFormContent ($attributes, $tag_headlines = NULL) {
        $previous_tag = '';

        foreach ($attributes as $attribute) {
            $attribute_part = explode('_', $attribute);
            
            if (!$attribute_part[2] && $attribute_part[1]) {
                $edit_function = 'edit' . $attribute_part[1];

                if ($attribute_part[0] != $previous_tag) {
                    if ($previous_tag != '') {
                        $out .= $this->editContentTable($headline, $table);
                        $out .= $this->editBlankContent();
                        if (!is_array($tag_headlines)) {
                            $headline = sprintf(_("Angaben zum HTML-Tag &lt;%s&gt;"), $attribute_part[0]);
                        } else {
                            $headline = $tag_headlines[$attribute_part[0]];
                        }
                        $headline = $this->editHeadline($headline);
                        $table = '';
                    } else {
                        if (!is_array($tag_headlines)) {
                            $headline = sprintf(_("Angaben zum HTML-Tag &lt;%s&gt;"), $attribute_part[0]);
                        } else {
                            $headline = $tag_headlines[$attribute_part[0]];
                        }
                        $headline = $this->editHeadline($headline);
                    }
                    $previous_tag = $attribute_part[0];
                }
                $table .= $this->$edit_function($attribute);
                
            } elseif ($attribute_part[2] && $tag_headlines["{$attribute_part[0]}_{$attribute_part[2]}"]) {
            
                $attribute_name = $attribute_part[0] . '_' . $attribute_part[2];
                $edit_function = 'edit' . $attribute_part[1];
                if ($attribute_name != $previous_tag) {
                    if ($previous_tag != '') {
                        $out .= $this->editContentTable($headline, $table);
                        $out .= $this->editBlankContent();
                        $headline = $this->editHeadline($tag_headlines[$attribute_name]);
                        $table = '';
                    } else {
                        $headline = $this->editHeadline($tag_headlines[$attribute_name]);
                    }
                    $previous_tag = $attribute_name;
                }
                $table .= $this->$edit_function($attribute);
            }
            
        }
        
        $out .= $this->editContentTable($headline, $table);

        return $out;
    }

    function editHeader () {
        $out = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" ";
        $out .= "width=\"95%\" align=\"left\">\n";

        return $out;
    }

    function editFooter () {
        $out = "</table>\n";

        return $out;
    }

    function editSubmit ($module_name, $config_id, $element_name = "", $hidden = NULL) {
        $this->css->resetClass();
        $this->css->switchClass();

        $out = "<tr><td align=\"center\" colspan=\"2\" nowrap=\"nowrap\"";
        $out .= $this->css->getFullClass() . ">&nbsp;";
        $out .= Button::createAccept(_("�bernehmen"), "submit"); 
        $out .= "&nbsp; &nbsp; &nbsp;";
        $out .= LinkButton::createCancel(_("Abbrechen"), URLHelper::getURL('?list=TRUE'));
        $out .= "<input type=\"hidden\" name=\"config_id\" value=\"$config_id\">";
        $out .= "<input type=\"hidden\" name=\"mod\" value=\"$module_name\">";
        if ($element_name) {
            $out .= "<input type=\"hidden\" name=\"edit\" value=\"$element_name\">";
        }
        if (!is_null($hidden)) {
            foreach ($hidden as $name => $value) {
                $out .= "<input type=\"hidden\" name=\"$name\" value=\"$value\">";
            }
        }
        $out .= "</td></tr>";

        return $out;
    }
    
    function editHeadline ($headline) {
        $headline = "&nbsp; $headline";

        $out = "<table class=\"blank\" width=\"100%\" cellpadding=\"0\" ";
        $out .= "cellspacing=\"0\" border=\"0\">\n<tr><td class=\"" . $this->css->getHeaderClass();
        $out .= "\" width=\"100%\"><font size=\"2\"><b>$headline</b></font>";
        $out .= "</td></tr>\n</table>\n";

        $this->css->resetClass();
        $this->css->switchClass();

        return $out;
    }

    function editElementHeadline ($element_real_name, $module_name, $config_id,
            $open = TRUE) {

        $icon =  Assets::img('icons/16/blue/file-generic.png', array('class' => 'text-top'));

        if ($open) {
            $link = URLHelper::getLink('?com=close&mod=' . $module_name . '&edit=' . $this->element_name . '&config_id=' . $config_id . '#anker');
            $open = "open";
        }
        else {
            $link = URLHelper::getLink('?com=open&mod=' . $module_name . '&edit=' . $this->element_name . '&config_id=' . $config_id . '#anker');
            $open = "close";
        }

        $titel = $element_real_name;
        $titel = "<a class=\"tree\" href=\"$link\">$titel</a>";
        if ($this->element_name == $this->edit_element)
            $titel .= "<a name=\"anker\">&nbsp;</a>";

        $out = "<tr><td class=\"blank\" width=\"100%\">\n";
        $out .= "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
        $out .= "<tr>\n";
        $out .= printhead(0, "", $link, $open, TRUE, $icon, $titel, "", 0, FALSE);
        $out .= "</tr></table>\n</td></tr>\n";

        return $out;
    }

    function editContentTable ($header, $body) {
        $out = "\n<!-- BEGIN ContentTable -->\n";
        $out .= "<table width=\"90%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
        $out .= "<tr><td class=\"blank\" width=\"100%\">\n";
        $out .= "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
        $out .= "<tr><td class=\"blank\" width=\"100%\">\n" . $header;
        $out .= "</td></tr>\n</table>\n";
        $out .= "<table width=\"100%\" style=\"border-style:solid; border-width:1px; ";
        $out .= "border-color:#000000;\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
        $out .= $body . "</table>\n</td></tr>\n</table>\n";
        $out .= "<!-- END ContentTable -->\n";

        $this->css->resetClass();

        return $out;
    }

    function editContent ($content, $submit, $class = "") {
        if (!$class) {
            $this->css->resetClass();
            $this->css->switchClass();
            $class = $this->css->getClass();
        }

        $out = "\n<!-- BEGIN Content -->\n";
        $out .= "<tr><td class=\"$class\" width=\"100%\" align=\"left\">\n";
        $out .= '<form name="edit_form" action="' . URLHelper::getLink('?com=store#anker') .  '" method="post">';
        $out .= CSRFProtection::tokenTag();
        $out .= "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\">\n";
        $out .= "<tr>" . printcontent("100%", FALSE, $content, "", FALSE) . "</tr>";
        $out .= "$submit</table>\n</form>\n</td></tr>\n";
        $out .= "<!-- END Content -->\n";

        return $out;
    }

    function editBlankContent ($class = "") {
        if (!$class) {
            $this->css->resetClass();
            $this->css->switchClass();
            $class = $this->css->getClass();
        }

        $out = "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
        $out .= "<tr><td class=\"$class\">&nbsp;</td></tr>\n";
        $out .= "</table>\n";

        return $out;
    }

    function editBlankContentTable ($class = "") {
        if (!$class) {
            $this->css->resetClass();
            $this->css->switchClass();
            $class = $this->css->getClass();
        }

        $out = "<tr><td>\n<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
        $out .= "<tr><td class=\"$class\">&nbsp;</td></tr>\n";
        $out .= "</table>\n</td></tr>\n";

        return $out;
    }

    function editBlank ($class = "") {
        if (!$class) {
            $this->css->resetClass();
            $this->css->switchClass();
            $class = $this->css->getClass();
        }

        $out = "<tr><td class=\"$class\" colspan=\"2\">&nbsp;</td></tr>\n";
        $out .= "</td></tr>\n";

        return $out;
    }

    function editTextblock ($text, $class = "") {
        if (!$class) {
            $this->css->resetClass();
            $this->css->switchClass();
            $class = $this->css->getClass();
        }

        $out = "<tr><td>\n<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">\n";
        $out .= "<tr><td class=\"$class\">$text</td></tr>\n";
        $out .= "</table>\n</td></tr>\n";

        return $out;
    }

}

?>
