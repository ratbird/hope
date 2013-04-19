<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Buddies
 *
 * @author flo
 */
class Visibility_Buddies extends VisibilityAbstract{
    
    // Soll dieser Status benutzt werden können
    protected $activated = true;
    
    // Welche int Repräsentation in der Datenbank
    protected $int_representation = 2;
    
    // Was wird in den Einstellungen angezeigt
    protected $display_name = "Buddies";
    
    // Was wird bei Visibility::getStateDescription() angezeigt
    protected $description = "nur für meine Buddies sichtbar";
    
    // Wann haben zwei Nutzer diesen Status
    function verify($user_id, $other_id) {
        return CheckBuddy(get_username($other_id), $user_id) || $user_id == $other_id;
    }   
}

?>
