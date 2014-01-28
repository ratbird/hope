<?php

/**
 * export_newline - a linefeed element
 *
 * adds a linefeed to the export
 * 
 * XML:
 * 
 * <newline />
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
class exportNewline extends ExportElement {

    /**
     * {@inheritdoc }
     */
    public function load($content) {
        
    }

    /**
     * {@inheritdoc }
     */
    public function preview($elementNo) {
        return "<br />";
    }

    /**
     * {@inheritdoc }
     */
    public function edit($edit) {
        
    }

}

?>
