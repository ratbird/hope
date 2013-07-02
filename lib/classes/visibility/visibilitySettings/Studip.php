<?php
/**
 * Visibility_Studip.php - Verifies if the visiting user is logged into studip
 * an therefore can see the visibility
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
class Visibility_Studip extends VisibilityAbstract {

    // Should this state be used?
    protected $activated = true;
    
    // What number does this state get in the database?
    protected $int_representation = 4;
    
    // How is the state displayed in the settings?
    protected $display_name = "Stud.IP-intern";
    
    // Description for the state
    protected $description = "f�r alle Stud.IP-Nutzer sichtbar";

    // When do two users have this state
    public function verify($user_id, $other_id)
    {
        return $user_id != "nobody";
    }
}
?>