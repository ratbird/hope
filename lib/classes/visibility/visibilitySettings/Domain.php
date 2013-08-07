<?php
/**
 * Visibility_Domain.php - Verifies if two users belong to the same domain
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
class Visibility_Domain extends VisibilityAbstract {

    // Should this state be used?
    protected $activated = true;

    // What number does this state get in the database?
    protected $int_representation = 3;

    // How is the state displayed in the settings?
    protected $display_name = "Domain";

    // Description for the state
    protected $description = "nur f�r meine Nutzerdom�ne sichtbar";

    // When do two users have this state
    function verify($user_id, $other_id)
    {
        $user_domains = UserDomain::getUserDomainsForUser($user_id);
        $owner_domains = UserDomain::getUserDomainsForUser($other_id);
        if (count($user_domains) || count($owner_domains)) {
            return count(array_intersect($user_domains, $owner_domains)) > 0;
        } else {
            return true;
        }
    }
}
?>