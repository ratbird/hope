<?php
/**
 * exportFormat - basetype for an exporttype
 *
 * The exportFormat makes sure that your export runs the way it should.
 * 
 * If you want to implement another Format implement the following methods:
 * - start() // to begin the writing of the export
 * - export_mission($type) // what action should be performed if a exporttype
 * is not found
 * - export_{type}($content) // what should be done with an export of the type
 * {type} and with the content $content
 * - finish() // do what ever is nessecary to send the user the document
 * 
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
class exportFormat {

    public $content;
    public $filename;

    public function setContent($content) {
        $this->content = $content;
    }

    public function export() {
        $this->start();
        foreach ($this->content as $element) {
            $type = get_class($element);
            if (method_exists($this, $type)) {
                call_user_func(array($this, $type), $element);
            } else {
                call_user_func(array($this, "exportMissing"), $type);
            }
        }
        $this->finish();
    }

}

?>
