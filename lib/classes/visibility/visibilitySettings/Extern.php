<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Extern
 *
 * @author flo
 */
class Visibility_Extern extends VisibilityAbstract{
    
    protected $activated = true;
    protected $int_representation = 5;
    protected $display_name = "externe Seiten";
    protected $description = "auf externen Seiten sichtbar";
    
    function verify($user_id, $other_id) {
        return true;
    }   
}

?>
