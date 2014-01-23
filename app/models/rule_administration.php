<?php

class RuleAdministrationModel {

    public static function getAdmissionRuleTypes() {
        $ruleTypes = AdmissionRule::getAvailableAdmissionRules(false);
        foreach ($ruleTypes as $className => $details) {
            $stmt = DBManager::get()->prepare("SELECT * FROM `admissionrules` WHERE `ruletype`=?");
            $stmt->execute(array($className));
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $ruleTypes[$className]['active'] = $data['active'];
        }
        return $ruleTypes;
    }

}

?>