<?php

/**
 * exportElement - basetype for an exportElement
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
abstract class ExportElement {

    private $editable;

    /**
     * Loads the xml description
     * 
     * @param xml $xml The inner xml of an element 
     */
    function load($xml) {
        $attributes = $xml->attributes();
        if ($attributes['edit']) {
            $this->editable = true;
        }
    }

    /**
     * HTML output for the preview of an element
     * 
     * @param int $elementNo Elementnumber to reidentify element on edit
     * @return string
     */
    function preview($elementNo) {
        return "";
    }

    /**
     * Edits the element
     * 
     * @param array $edit 
     */
    public function edit($edit) {
        
    }

    /**
     * Defines if the element is editable
     * 
     * @param boolean $state Editable state of the object
     */
    public function setEditable($state) {
        $this->editable = $state;
    }

    /**
     * Checks if an element is actually editable
     * 
     * @return boolean true if editable, otherwise false
     */
    public function isEditable() {
        return $this->editable;
    }

}

?>
