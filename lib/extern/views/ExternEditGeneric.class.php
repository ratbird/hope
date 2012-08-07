<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* ExternEditGeneric.class.php
*
*
*
*
* @author       Peter Thienel <pthienel@web.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternEditGeneric
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternEditGeneric.class.php
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


require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/ExternEdit.class.php");

class ExternEditGeneric extends ExternEdit {

    function ExternEditGeneric (&$config, $form_values = "", $faulty_values = "",
             $edit_element = "") {
        parent::ExternEdit($config, $form_values, $faulty_values, $edit_element);
    }

    /**
    * Prints out a form with a pull-down field for different font-faces.
    *
    * @param string attribute The name of the attribute (Syntax: [tag-name]_[attribute_name])
    * @param string title The title of this form.
    * @param string info The info text.
    */
    function editFaceGeneric ($attribute, $title, $info) {
        $faces = array(
            "" => _("keine Auswahl"),
            "Arial,Helvetica,sans-serif" => _("serifenlose Schrift"),
          "Times,Times New Roman,serif" => _("Serifenschrift"),
            "Courier,Courier New,monospace" => _("diktengleiche Schrift")
        );
        $form_name = $this->element_name . "_" . $attribute;
        $value = $this->getValue($attribute);

        if ($this->faulty_values[$form_name][0])
            $error_sign = $this->error_sign;
        else
            $error_sign = "";

        $out = "<tr><td><table width=\"100%\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\">\n";
        $out .= "<tr" . $this->css->getFullClass() . "><td{$this->width_1} nowrap=\"nowrap\"><font size=\"2\">";
        $out .= "$title</font></td>\n";
        $out .= "<td{$this->width_2} nowrap=\"nowrap\"><select name=\"$form_name\" size=\"1\">\n";
        foreach ($faces as $face_type => $face_name) {
            if ($value == $face_type)
                $out .= "<option selected=\"selected\" ";
            else
                $out .= "<option ";
            $out .= "value=\"$face_type\">";
            $out .= $face_name . "</option>";
        }
        $out .= "</select>\n";
        $out .= tooltipIcon($info);
        $out .= "$error_sign</td></tr>\n</table>\n</td></tr>\n";
        $this->css->switchClass();

        return $out;
    }

    /**
    * Prints out a form with a text field.
    *
    * @param string attribute The name of the attribute (Syntax: [tag-name]_[attribute_name])
    * @param mixed title The title(s) of the textfield(s).
    * @param string info The info text.
    * @param int size The size (length) of this textfield.
    * @param int maxlength The maximal length of the text.
    */
    function editTextfieldGeneric ($attribute, $title, $info, $size, $maxlength) {
        $form_name = $this->element_name . "_" . $attribute;
        $value = $this->getValue($attribute);

        if ($title == "") {
            $title = "&nbsp;";
            $width_1 = " width=\"1%\"";
            $width_2 = " width=\"99%\"";
        }
        else {
            $width_1 = $this->width_1;
            $width_2 = $this->width_2;
        }

        if (is_array($title)) {
            $out = "";
            for($i = 0; $i < sizeof($title); $i++) {

                if ($this->faulty_values[$form_name][$i])
                    $error_sign = $this->error_sign;
                else
                    $error_sign = "";

                $out .= "<tr><td><table width=\"100%\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\">\n";
                $out .= "<tr" . $this->css->getFullClass() . "><td$width_1 nowrap=\"nowrap\"><font size=\"2\">";
                $out .= "{$title[$i]}</font></td>\n";
                $out .= "<td$width_2 nowrap=\"nowrap\"><input type=\"text\" name=\"{$form_name}[]\" size=\"$size\"";
                $out .= " maxlength=\"$maxlength\" value=\"{$value[$i]}\">&nbsp; \n";
                $out .= tooltipIcon(is_array($info) ? $info[$i] : $info);
                $out .= "$error_sign</td></tr>\n</table>\n</td></tr>\n";
                $this->css->switchClass();
            }
            return $out;
        }

        if ($this->faulty_values[$form_name][0])
            $error_sign = $this->error_sign;
        else
            $error_sign = "";

        $out .= "<tr><td><table width=\"100%\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\">\n";
        $out .= "<tr" . $this->css->getFullClass() . "><td$width_1 nowrap=\"nowrap\"><font size=\"2\">";
        $out .= "$title</font></td>\n";
        $out .= "<td$width_2 nowrap=\"nowrap\"><input type=\"text\" name=\"$form_name\" size=\"$size\"";
        $out .= " maxlength=\"$maxlength\" value=\"$value\">&nbsp; \n";
        $out .= tooltipIcon($info);
        $out .= "$error_sign</td></tr>\n</table>\n</td></tr>\n";
        $this->css->switchClass();

        return $out;
    }

    /**
    * Prints out a Form with a textarea.
    *
    * @param string attribute The name of the attribute (Syntax: [tag-name]_[attribute_name])
    * @param string title The title of this textarea.
    * @param string info The info text.
    * @param int rows The number of rows of this textarea.
    * @param int cols The number of columns of this textarea.
    */
    function editTextareaGeneric ($attribute, $title, $info, $rows, $cols) {
        $form_name = $this->element_name . "_" . $attribute;
        $value = $this->getValue($attribute);

        if ($title == "") {
            $title = "&nbsp;";
            $width_1 = " width=\"1%\"";
            $width_2 = " width=\"99%\"";
        }
        else {
            $width_1 = $this->width_1;
            $width_2 = $this->width_2;
        }

        if ($this->faulty_values[$form_name][0])
            $error_sign = $this->error_sign;
        else
            $error_sign = "";

        $out = "<tr><td><table width=\"100%\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\">\n";
        $out .= "<tr" . $this->css->getFullClass() . "><td$width_1 nowrap=\"nowrap\"><font size=\"2\">";
        $out .= "$title</font></td>\n";
        $out .= "<td$width_2 nowrap=\"nowrap\">";
        $out .= "<textarea name=\"$form_name\" cols=\"$cols\" rows=\"$rows\" wrap=\"virtual\">";
        $out .= $value;
        $out .= "</textarea>&nbsp; \n";
        $out .= tooltipIcon($info);
        $out .= "$error_sign</td></tr>\n</table>\n</td></tr>\n";
        $this->css->switchClass();

        return $out;
    }

    /**
    * Prints out a Form with checkboxes.
    *
    * @param string attribute The name of the attribute (Syntax: [tag-name]_[attribute_name])
    * @param string title The title of this form with checkboxes.
    * @param string info The info text.
    * @param array check_values The values of the checkboxes.
    * @param array check_names The names of the checkboxes.
    */
    function editCheckboxGeneric ($attribute, $title, $info, $check_values, $check_names) {
        $form_name = $this->element_name . "_" . $attribute;
        $value = $this->getValue($attribute);

        if ($title == "") {
            $title = "&nbsp;";
            $width_1 = " width=\"1%\"";
            $width_2 = " width=\"99%\"";
        }
        else {
            $width_1 = $this->width_1;
            $width_2 = $this->width_2;
        }

        $size = sizeof($check_values);
        $out .= "<tr><td><table width=\"100%\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\">\n";

        if ($size > 1) {
        //  $form_name .= "[]";
            if (is_array($title)) {
                for ($i = 0; $i < $size; $i++) {
                    if ($this->faulty_values[$form_name][$i])
                        $error_sign = $this->error_sign;
                    else
                        $error_sign = "";

                    $out .= "<tr" . $this->css->getFullClass() . "><td$width_1 nowrap=\"nowrap\"><font size=\"2\">";
                    $out .= "$title[$i]</font></td>\n";
                    $out .= "<td$width_2 nowrap=\"nowrap\">";
                    $out .= "<input type=\"checkbox\" name=\"{$form_name}[]\" value=\"{$check_values[$i]}\"";
                    if (is_array($value) && in_array($check_values[$i], $value))
                        $out .= " checked";
                    if ($size == 1)
                        $out .= "></td></tr>\n";
                    else
                        $out .= "><font size=\"2\">{$check_names[$i]}&nbsp; &nbsp;</font>";
                    $out .= tooltipIcon($info);
                    $out .= "$error_sign</td></tr>\n";
                    $this->css->switchClass();
                }
            }
            else {
                if ($this->faulty_values[$form_name][0])
                    $error_sign = $this->error_sign;
                else
                    $error_sign = "";

                $out .= "<tr" . $this->css->getFullClass() . "><td$width_1 nowrap=\"nowrap\"><font size=\"2\">";
                $out .= "$title</font></td>\n";
                $out .= "<td$width_2 nowrap=\"nowrap\">";
                for ($i = 0; $i < $size; $i++) {
                    $out .= "<input type=\"checkbox\" name=\"{$form_name}[]\" value=\"{$check_values[$i]}\"";
                    if (is_array($value) && in_array($check_values[$i], $value))
                        $out .= " checked";
                    if ($size == 1)
                        $out .= "> &nbsp;\n";
                    else
                        $out .= "><font size=\"2\">{$check_names[$i]}&nbsp; &nbsp;</font>\n";
                }
                $out .= tooltipIcon($info);
                $out .= "$error_sign</td></tr>\n";
            }
        }
        else {
            if ($this->faulty_values[$form_name][0])
                $error_sign = $this->error_sign;
            else
                $error_sign = "";

            $out .= "<tr" . $this->css->getFullClass() . "><td$width_1 nowrap=\"nowrap\"><font size=\"2\">";
            $out .= "$title</font></td>\n";
            $out .= "<td$width_2 nowrap=\"nowrap\">";
            $out .= "<input type=\"checkbox\" name=\"{$form_name}\" value=\"$check_values\"";
            if ($value == $check_values)
                $out .= " checked";
            $out .= "> &nbsp;\n";
            $out .= tooltipIcon($info);
            $out .= "$error_sign</td></tr>\n";
        }

    //  $out .= "<img src=\" ".$GLOBALS['ASSETS_URL']."images/icons/16/grey/info-circle.png\"";
    //  $out .= tooltip($info, TRUE, TRUE) . ">$error_sign</td></tr>\n</table>\n</td></tr>\n";
        $out .= "</table>\n</td></tr>\n";
        $this->css->switchClass();

        return $out;
    }

    /**
    * Prints out a Form with radio-buttons.
    *
    * @param string attribute The name of the attribute (Syntax: [tag-name]_[attribute_name])
    * @param string title The title of this form with radio-buttons.
    * @param string info The info text.
    * @param array radio_values The values of the radio-buttons.
    * @param array radio_names The names of the radio-buttons.
    */
    function editRadioGeneric ($attribute, $title, $info, $radio_values, $radio_names) {
        $form_name = $this->element_name . "_" . $attribute;
        $value = $this->getValue($attribute);

        if ($title == "") {
            $title = "&nbsp;";
            $width_1 = " width=\"1%\"";
            $width_2 = " width=\"99%\"";
        }
        else {
            $width_1 = $this->width_1;
            $width_2 = $this->width_2;
        }

        if ($this->faulty_values[$form_name][0])
            $error_sign = $this->error_sign;
        else
            $error_sign = "";

        $out = "<tr><td><table width=\"100%\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\">\n";
        $out .= "<tr" . $this->css->getFullClass() . "><td$width_1 nowrap=\"nowrap\"><font size=\"2\">";
        $out .= "$title</font></td>\n";
        $out .= "<td$width_2 nowrap=\"nowrap\">";

        for ($i = 0; $i < sizeof($radio_values); $i++) {
            $out .= "<input type=\"radio\" name=\"$form_name\" value=\"{$radio_values[$i]}\"";
            if ($value == $radio_values[$i])
                $out .= " checked";
            $out .= "><font size=\"2\">{$radio_names[$i]}&nbsp; &nbsp;</font>\n";
        }
        
        $out .= tooltipIcon($info);
        $out .= "$error_sign</td></tr>\n</table>\n</td></tr>\n";
        $this->css->switchClass();

        return $out;
    }

    /**
    * Prints out a Form with an option-list.
    *
    * @param string attribute The name of the attribute (Syntax: [tag-name]_[attribute_name])
    * @param string title The title of this option-list.
    * @param string info The info text.
    * @param array radio_values The values of the options.
    * @param array radio_names The names of the options.
    * @param int length The visible size of the option-list (default 1, pull-down).
    * @param boolean multiple Set this TRUE, if you want a multiple option-list (default FALSE)
    */
    function editOptionGeneric ($attribute, $title, $info, $option_values, $option_names,
            $size = 1, $multiple = FALSE) {

        $form_name = $this->element_name . "_" . $attribute;
        $value = $this->getValue($attribute);

        if ($title == "") {
            $title = "&nbsp;";
            $width_1 = " width=\"1%\"";
            $width_2 = " width=\"99%\"";
        }
        else {
            $width_1 = $this->width_1;
            $width_2 = $this->width_2;
        }

        if ($this->faulty_values[$form_name][0])
            $error_sign = $this->error_sign;
        else
            $error_sign = "";

        $out = "<tr><td><table width=\"100%\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\">\n";
        $out .= "<tr" . $this->css->getFullClass() . "><td$width_1 nowrap=\"nowrap\"><font size=\"2\">";
        $out .= "$title</font></td>\n";
        $out .= "<td$width_2 nowrap=\"nowrap\">";
        if ($multiple)
            $out .= "<select name=\"{$form_name}[]\" size=\"$size\" multiple>";
        else
            $out .= "<select name=\"$form_name\" size=\"$size\">";

        for ($i = 0; $i < sizeof($option_values); $i++) {
            $out .= "<option value=\"{$option_values[$i]}\"";
            if ($multiple) {
                if ($option_values[$i] && in_array($option_values[$i], (array) $value)) {
                    $out .= " selected";
                }
            } else {
                if ($value == $option_values[$i] && $option_values[$i]) {
                    $out .= " selected";
                }
            }
            $out .= ">{$option_names[$i]}</option>\n";
        }

        $out .= "</select>\n";
        $out .= tooltipIcon($info);
        $out .= "$error_sign</td></tr>\n</table>\n</td></tr>\n";
        $this->css->switchClass();

        return $out;
    }

}
