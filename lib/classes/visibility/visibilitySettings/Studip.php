<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Studip
 *
 * @author flo
 */
class Visibility_Studip extends VisibilityAbstract {

    protected $activated = true;
    protected $int_representation = 4;
    protected $display_name = "Stud.IP-intern";
    protected $description = "für alle Stud.IP-Nutzer sichtbar";

    public function verify($user_id, $other_id) {
        return $user_id != "nobody";
    }

}

?>
