<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/UpdateInformation.class.php';

class JsupdaterController extends AuthenticatedController {

    public function get_action() {
        $data = UpdateInformation::getInformation();
        $data = array_merge($data, $this->coreInformation());
        $data = $this->recursive_studip_utf8encode($data);
        $this->render_text(json_encode($data));
    }

    protected function coreInformation() {
        $data = array();
        return $data;
    }

    protected function recursive_studip_utf8encode(array $data) {
        foreach ($data as $key => $component) {
            if (is_array($component)) {
                $data[$key] = $this->recursive_studip_utf8encode($component);
            } elseif(is_string($component)) {
                $data[$key] = studip_utf8encode($component);
            }
        }
        return $data;
    }
}