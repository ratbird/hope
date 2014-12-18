<?php

/**
 * Admission_RuleAdministrationController - Global administration
 * of available admission rules
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

require_once('app/controllers/authenticated_controller.php');
require_once('app/models/rule_administration.php');
require_once('lib/classes/admission/AdmissionRule.class.php');

class Admission_RuleAdministrationController extends AuthenticatedController {

    /**
     * @see AuthenticatedController::before_filter
     */
    public function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        if (Request::isXhr()) {
            $this->via_ajax = true;
            $this->set_layout(null);
            $request = Request::getInstance();
            foreach ($request as $key => $value) {
                $request[$key] = studip_utf8decode($value);
            }
        } else {
            $this->via_ajax = false;
            $layout = $GLOBALS['template_factory']->open('layouts/base');
            $this->set_layout($layout);
            PageLayout::setTitle(_('Verwaltung von Anmelderegeln'));
            Navigation::activateItem('/admin/config/admissionrules');
        }
        PageLayout::addSqueezePackage('admission');
        $this->set_content_type('text/html;charset=windows-1252');
    }

    /**
     * Show overview of available admission rules.
     */
    public function index_action() {
        $this->ruleTypes = RuleAdministrationModel::getAdmissionRuleTypes();
        // Available rule classes.
        $ruleClasses = array_map(function($s) { return strtolower($s); }, array_keys($this->ruleTypes));
        // Found directories with rule definitions.
        $ruleDirs = array_map(function($s) { return basename($s); }, glob($GLOBALS['STUDIP_BASE_PATH'].'/lib/admissionrules/*', GLOB_ONLYDIR));
        // Compare the two.
        $this->newRules = array_diff($ruleDirs, $ruleClasses);
    }

    /**
     * Shows where the given admission rule is activated (system wide or
     * only at specific institutes).
     * 
     * @param String $ruleType Class name of the rule type to check.
     */
    public function check_activation_action($ruleType) {
        if (Request::isXhr()) {
            $this->response->add_header('X-Title', _('Verfügbarkeit der Anmelderegel'));
            $this->response->add_header('X-No-Buttons', 1);
        }
        $this->ruleTypes = RuleAdministrationModel::getAdmissionRuleTypes();
        $this->type = $ruleType;
        $stmt = DBManager::get()->prepare("SELECT ai.`institute_id`
            FROM `admissionrule_inst` ai
            JOIN `admissionrules` r ON (ai.`rule_id`=r.`id`)
            WHERE r.`ruletype`=?");
        $stmt->execute(array($ruleType));
        $this->activated = array();
        $this->globally = true;
        $this->atInst = false;
        while ($current = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($globally) $globally = false;
            if (!$atInst) $atInst = true;
            $institute = new Institute($current['institute_id']);
            $this->activated[$current['institute_id']] = $institute->name;
        }
    }

    /**
     * (De-)Activates the given rule type for system wide usage.
     * 
     * @param  String $ruleType the class name of the rule type to activate.
     */
    public function activate_action($ruleType) {
        CSRFProtection::verifyUnsafeRequest();
        if (Request::submitted('submit')) {
            $success = false;
            $stmt = DBManager::get()->prepare("UPDATE `admissionrules` SET `active`=? WHERE `ruletype`=?");
            $success = $stmt->execute(array((bool) Request::get('enabled'), $ruleType));
            // Get corresponding rule id.
            $stmt = DBManager::get()->prepare("SELECT `id` FROM `admissionrules` WHERE `ruletype`=? LIMIT 1");
            $success = $stmt->execute(array($ruleType));
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (Request::get('enabled')) {
                    $stmt = DBManager::get()->prepare("DELETE FROM `admissionrule_inst`
                        WHERE `rule_id` IN (SELECT `id` FROM `admissionrules` WHERE `ruletype`=?);");
                    $success = $stmt->execute(array($ruleType));
                    if (Request::get(activated) == 'inst') {
                        $institutes = Request::getArray('institutes');
                        $query = "INSERT INTO `admissionrule_inst`
                            (`rule_id`, `institute_id`, `mkdate`)
                            VALUES ";
                        $params = array();
                        $first = true;
                        foreach ($institutes as $institute) {
                            if ($first) {
                                $first = false;
                            } else {
                                $query .= ", ";
                            }
                            $query .= "(?, ?, UNIX_TIMESTAMP())";
                            $params[] = $data['id'];
                            $params[] = $institute;
                        }
                        $stmt = DBManager::get()->prepare($query);
                        $success = $stmt->execute($params);
                    }
                }
            }
            if ($success) {
                $this->successmsg = _('Ihre Einstellungen wurden gespeichert.');
            } else {
                $this->errormsg = _('Ihre Einstellungen konnten nicht gespeichert werden.');
            }
        }
    }

    /**
     * Installs a new admission rule.
     */
    public function install_action() {
        CSRFProtection::verifyUnsafeRequest();
        try {
            if ($this->flash['upload_file']) {
                $uploadFile = $this->flash['upload_file'];
            } else {
                $uploadFile = $_FILES['upload_file']['tmp_name'];
            }
            $ruleAdmin = new RuleAdministrationModel();
            $ruleAdmin->install($uploadFile);
            $this->flash['success'] = _('Die Anmelderegel wurde erfolgreich installiert.');
            if (isset($uploadFile)) {
                unlink($uploadFile);
            }
            $this->redirect('admission/ruleadministration');
        } catch (Exception $e) {
            $this->flash['error'] = $e->getMessage();
            $this->redirect('admission/ruleadministration');
        }
    }

    /**
     * Deletes the given admission rule type from the system, including all
     * data belonging to it (especially saved values in DB!).
     */
    public function uninstall_action($ruleType) {
        if (Request::int('really')) {
            try {
                $ruleAdmin = new RuleAdministrationModel();
                $ruleAdmin->uninstall($ruleType);
                $this->flash['success'] = _('Die Anmelderegel wurde erfolgreich gelöscht.');
            } catch (AdmissionRuleInstallationException $e) {
                $this->flash['error'] = $e->getMessage();
            }
            $this->redirect($this->url_for('admission/ruleadministration'));
        }
        if (Request::int('cancel')) {
           $this->redirect($this->url_for('admission/ruleadministration'));
        }
    }

    /**
     * Downloads an admission rule as ZIP file.
     * @param String $ruleName Class name of the admission rule, is used for file name. 
     *   
     */
    public function download_action($ruleName) {
        $dirname = $GLOBALS['ABSOLUTE_PATH_STUDIP'].'admissionrules/'.
            strtolower($ruleName);
        $filename = $ruleName.'.zip';
        $filepath = get_config('TMP_PATH').'/'.$filename;

        create_zip_from_directory($dirname, $filepath);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.filesize($filepath));
        header('Pragma: public');

        $this->render_nothing();

        readfile($filepath);
        unlink($filepath);
    }

    /**
     * Validate ticket (passed via request environment).
     * This method always checks Request::quoted('ticket').
     *
     * @throws InvalidArgumentException  if ticket is not valid
     */
    private function check_ticket() {
        if (!check_ticket(Request::option('ticket'))) {
            throw new InvalidArgumentException(_('Das Ticket für diese Aktion ist ungültig.'));
        }

    }

}

?>
