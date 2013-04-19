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
class Visibility_Nobody extends VisibilityAbstract{
    

    protected $activated = false;
    
    
    protected $int_representation = 8;
    
    
    protected $display_name = "Niemand";
    
    
    protected $description = "Sichtbar für niemanden";

    
    function verify($user_id, $other_id) {
        return false;
    }   
}

?>
