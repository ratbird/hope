<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Domain
 *
 * @author flo
 */
class Visibility_Domain extends VisibilityAbstract {

    protected $activated = true;
    protected $int_representation = 3;
    protected $display_name = "Domain";
    protected $description = "nur für meine Nutzerdomäne sichtbar";

    function verify($user_id, $other_id) {
        $user_domains = UserDomain::getUserDomainsForUser($user_id);
        $owner_domains = UserDomain::getUserDomainsForUser($other_id);
        return array_intersect($user_domains, $owner_domains);
    }

}

?>
