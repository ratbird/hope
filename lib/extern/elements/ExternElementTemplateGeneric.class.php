<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* ExternElementTemplateGeneric.class.php
* 
* 
* 
*
* @author       Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  extern
* @module       ExternElementTemplateGeneric
* @package  studip_extern
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// ExternElementTemplateGeneric.class.php
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


require_once($GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/ExternElement.class.php');
require_once($GLOBALS['RELATIVE_PATH_EXTERN'].'/lib/HTML_Template_IT/ITX.php');

class ExternElementTemplateGeneric extends ExternElement {

    var $attributes = array('template', 'rendermarkerassubpart');
    var $markers;
    var $new_datafields = FALSE;
    var $render_marker_inside_subparts = FALSE;
    

    /**
    * Constructor
    *
    * @param array config
    */
    function ExternElementTemplateGeneric ($config = '') {
        if ($config)
            $this->config = $config;
        
        $this->name = "TemplateGeneric";
        $this->real_name = _("Standard Template");
        $this->description = _("Hier kann ein Template hinterlegt werden.");
    }
    
    /**
    * 
    */
    function getDefaultConfig () {
        $config = array(
            'template' => _("Geben Sie hier das Template ein."),
            'rendermarkerassubpart' => ''
        );
        
        return $config;
    }
    
    function toStringEdit ($post_vars = '', $faulty_values = '',
            $edit_form = '', $anker = '') {
        global $EXTERN_MODULE_TYPES;
            
        if ($faulty_values == '')
            $faulty_values = array();   
        $out = '';
        $tag_headline = '';
        $table = '';
        if ($edit_form == '')
            $edit_form = new ExternEditHtml($this->config, $post_vars, $faulty_values, $anker);
        
        $edit_form->setElementName($this->getName());
        $element_headline = $this->getEditFormHeadline($edit_form);
        
        $headline = $edit_form->editHeadline(_("Beschreibung der Marker"));
        $table = $edit_form->editMarkerDescription($this->markers, $this->new_datafields);
        
        $content_table = $edit_form->editContentTable($headline, $table);
        $content_table .= $edit_form->editBlankContent();
        
        $headline = $edit_form->editHeadline(_("Template"));
        $info = _("Geben Sie hier das Template ein.");
        $table = $edit_form->editTextareaGeneric('template', '', $info, 40, 80);
        
        $info = _("Wählen Sie diese Option, wenn alle Marker mit Subparts umgeben werden sollen:") . "\n\n<!-- BEGIN MARKER-NAME -->\n\t###MARKER-NAME###\n<!-- END MARKER-NAME -->";
        $table .= $edit_form->editCheckboxGeneric('rendermarkerassubpart', _("Alle Marker mit Subparts umschließen:"), $info, '1', '0');
        
        $content_table .= $edit_form->editContentTable($headline, $table);
        $content_table .= $edit_form->editBlankContent();
        
        $hidden = array($this->getName . '_chdate' => time());
        $submit = $edit_form->editSubmit($this->config->getName(),
                $this->config->getId(), $this->getName(), $hidden);
        $out = $edit_form->editContent($content_table, $submit);
        $out .= $edit_form->editBlank();
        
        return  $element_headline . $out;
    }
    
    function toString ($args) {
        $template = $this->config->getValue($this->getName(), 'template');
        
        if ($args['hide_markers'] !== FALSE) {
            $args['hide_markers'] = TRUE;
        }
        $out = $this->renderTmpl($template, $args['content'], $args['subpart'], $args['hide_markers']);
        
        return $out;
    }
    
    function renderTmpl (&$tmpl, $content, $first_subpart = '', $hide_markers = TRUE) {
        $tmpl_obj = new HTML_Template_IT();
        $tmpl_obj->clearCacheOnParse = FALSE;
        $tmpl_obj->setTemplate($tmpl, $hide_markers, TRUE);
        
        if ($this->config->getValue($this->getName(), 'rendermarkerassubpart')) {
            $this->renderMarkerInsideSubparts();
        }
        
        // set global markers (not nested in a subpart)
        if (is_array($content['__GLOBAL__'])) {
            $tmpl_obj->setVariable($content['__GLOBAL__']);
            unset($content['__GLOBAL__']);
        }
        
        $this->renderSubpart($tmpl_obj, $content, $first_subpart);
        return $tmpl_obj->get();
    }
    
    function renderSubpart (&$tmpl_obj, &$content, $current_subpart = '') {
        if ($current_subpart != '') {
            $tmpl_obj->setCurrentBlock($current_subpart);
        }
        if (!is_array($content)) {
            $content = array();
        }
        foreach ($content as $marker => $item) {
            if (is_int($marker)) {
                $tmpl_obj->parse($this->renderSubpart($tmpl_obj, $item, $current_subpart));
            } else if (is_array($item)) {
                $tmpl_obj->parse($this->renderSubpart($tmpl_obj, $item, $marker));
            } else {
                if ($this->render_marker_inside_subparts) {
                    if ($item !== '') {
                        $tmpl_obj->setVariable($marker, $item);
                        $tmpl_obj->setCurrentBlock($marker);
                        $tmpl_obj->setVariable($marker, $item);
                    }
                } else {
                    $tmpl_obj->setVariable($marker, $item);
                }
            }
        }
        return $current_subpart;
    }
    
    function checkValue ($attribute, $value) {
        if ($attribute == 'rendermarkerassubpart') {
        
            // This is especially for checkbox-values. If there is no checkbox
            // checked, the variable is not declared and it is necessary to set the
            // variable to "0".
            if (!isset($_POST[$this->getName() . '_' . $attribute])) {
                $_POST[$this->getName() . '_' . $attribute] = '';
                return FALSE;
            }
            return !($value == '1' || $value == '');
        }
        if ($attribute == 'template') {
        //  return preg_match("|^https?://.*$|i", $value);
            return FALSE;
        }
        return FALSE;
    }
    
    function renderMarkerInsideSubparts () {
        $this->render_marker_inside_subparts = TRUE;
    }
    
}

?>
