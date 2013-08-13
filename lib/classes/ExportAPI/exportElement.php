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
abstract class exportElement {

    private $editable;

    function load($xml) {
        $attributes = $xml->attributes();
        if ($attributes['edit']) {
            $this->editable = true;
        }
    }

    function preview($elementNo) {
        return "";
    }

    public function edit($edit) {
        
    }

    public function setEditable($state) {
        $this->editable = $state;
    }

    public function isEditable() {
        return $this->editable;
    }

}

?>
