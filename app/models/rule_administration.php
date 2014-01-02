<?php

/**
 * Exception class used to report admission rule install errors.
 */
class AdmissionRuleInstallationException extends Exception
{
}

class RuleAdministrationModel {

    public static function getAdmissionRuleTypes() {
        $ruleTypes = AdmissionRule::getAvailableAdmissionRules(false);
        foreach ($ruleTypes as $className => $details) {
            $stmt = DBManager::get()->prepare("SELECT * FROM `admissionrules` WHERE `ruletype`=?");
            $stmt->execute(array($className));
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $ruleTypes[$className]['active'] = $data['active'];
            $ruleTypes[$className]['deleteable'] = (bool) $data['deleteable'];
        }
        return $ruleTypes;
    }

    public function install($filename) {
        $dirname = 'tmp_'.md5($filename);
        $extractTo = $GLOBALS['ABSOLUTE_PATH_STUDIP'].'admissionrules/'.$dirname;

        // extract uploaded files
        mkdir($extractTo);

        if (unzip_file($filename, $extractTo)) {
            rmdirr($extractTo);
            throw new AdmissionRuleInstallationException(_('Fehler beim Entpacken der Regeldefinition.'));
        }

        // load the manifest
        $manifest = $this->getManifest($extractTo);

        if ($manifest['classname']) {
            $installTo = $GLOBALS['ABSOLUTE_PATH_STUDIP'].'admissionrules/'.
                strtolower($manifest['classname']);
            // Check if directory already exists.
            if (file_exists($installTo)) {
                throw new Exception(_('Eine Anmelderegel mit diesem Namen existiert bereits!'));
            }
            // Rename temp directory.
            rename($GLOBALS['ABSOLUTE_PATH_STUDIP'].'admissionrules/'.$dirname,
                $installTo);
            $this->createDBSchema($GLOBALS['ABSOLUTE_PATH_STUDIP'].
                'admissionrules/'.strtolower($manifest['classname']), $manifest);
            // Insert values into database.
            $stmt = DBManager::get()->prepare("INSERT INTO `admissionrules` (`ruletype`, `active`, `mkdate`) VALUES (?, ?, ?)");
            $stmt->execute(array($manifest['classname'], 1, time()));
        } else {
            rmdirr($extractTo);
            throw new AdmissionRuleInstallationException(_('Fehler beim Entpacken der Regeldefinition.'));
        }
    }

    public function uninstall($ruleName) {
        $dirname = $GLOBALS['ABSOLUTE_PATH_STUDIP'].'admissionrules/'.
            strtolower($ruleName);
        $manifest = $this->getManifest($dirname);
        $stmt = DBManager::get()->prepare("DELETE FROM `admissionrules` WHERE `ruletype`=?");
        if ($stmt->execute(array($ruleName))) {
            $this->uninstallDBSchema($GLOBALS['ABSOLUTE_PATH_STUDIP'].
                'admissionrules/'.strtolower($ruleName), $manifest);
            rmdirr($GLOBALS['ABSOLUTE_PATH_STUDIP'].'admissionrules/'.strtolower($ruleName));
        }
    }

    /**
     * Read the manifest in the given directory.
     * Returns NULL if the manifest cannot be found.
     *
     * @return array containing the manifest information
     */
    private function getManifest($directory) {
        $manifest = @file($directory.'/rule.manifest');
        $result = array();

        if ($manifest === false) {
            return NULL;
        }

        foreach ($manifest as $line) {
            list($key, $value) = explode('=', $line);
            $key = trim($key);
            $value = trim($value);

            // skip empty lines and comments
            if ($key === '' || $key[0] === '#') {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Create the needed database entries for the admission rule.
     *
     * @param string  $directory  absolute path to the admission rule
     * @param array   $manifest   rule manifest information
     */
    private function createDBSchema($directory, $manifest)
    {
        if (isset($manifest['installsql'])) {
            $schemafile = $directory.'/'.$manifest['installsql'];
            $contents   = file_get_contents($schemafile);
            $statements = preg_split("/;[[:space:]]*\n/", $contents, -1, PREG_SPLIT_NO_EMPTY);
            $db = DBManager::get();
            foreach ($statements as $statement) {
                $db->exec($statement);
            }
        }
    }

    /**
     * Remove the database entries for the admission rule.
     *
     * @param string  $directory  absolute path to the admission rule
     * @param array   $manifest   rule manifest information
     */
    private function uninstallDBSchema($directory, $manifest)
    {
        if (isset($manifest['uninstallsql'])) {
            $schemafile = $directory.'/'.$manifest['uninstallsql'];
            $contents   = file_get_contents($schemafile);
            $statements = preg_split("/;[[:space:]]*\n/", $contents, -1, PREG_SPLIT_NO_EMPTY);
            $db = DBManager::get();
            foreach ($statements as $statement) {
                echo $statement.'<br/>';
                $db->exec($statement);
            }
        }
    }

}

?>