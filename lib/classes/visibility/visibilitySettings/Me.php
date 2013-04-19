<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Me
 *
 * @author flo
 */
class Visibility_Me extends VisibilityAbstract {

    protected $activated = true;
    protected $int_representation = 1;
    protected $display_name = "nur mich selbst";
    protected $description = "nur für mich sichtbar";

    function verify($user_id, $other_id) {
        return $user_id == $other_id;
    }

}

?>
