<?php
/**
 * export_text - text export element
 *
 * Textelement has a content string for the text
 * 
 * XML:
 * 
 * <text>text to insert into document</text>
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
class exportText extends exportElement {

    public $content;

    public function setText($text) {
        $this->content = $text;
    }

    public function load($xml) {
        $this->content = (string) $xml;
        parent::load($xml);
    }

    public function preview($elementNo) {
        if ($this->isEditable()) {
            return "<input size='120' name=edit[$elementNo] value='$this->content' /><br />";
        }
        return "<p>$this->content</p>";
    }
    
    public function edit($edit) {
        $this->content = $edit;
    }

}

?>
