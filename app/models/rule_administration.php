<?php

/**
 * RuleAdministrationModel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.0
 */

class RuleAdministrationModel {

    /**
     * Fetches the available types of admission rules, including disabled ones..
     */
    public static function getAdmissionRuleTypes() {
        return AdmissionRule::getAvailableAdmissionRules(false);
    }

}

?>