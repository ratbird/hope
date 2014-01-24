<?php

class RuleAdministrationModel {

    public static function getAdmissionRuleTypes() {
        return AdmissionRule::getAvailableAdmissionRules(false);
    }

}

?>