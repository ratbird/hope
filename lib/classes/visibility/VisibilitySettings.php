<?php

/**
 * VisibilitySettings.php - Group of all possible states of a visibility
 *
 * To be able to edit visibilitysettings as easy as possible we define a state
 * in a class found in the visibilitySettings. To group all the visibilitySetting
 * we use this class. On a verify command this class decides to what class the
 * order should be passed to.
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
require_once 'VisibilityAbstract.php';

/**
 * Groups all visibilitySettings
 */
class VisibilitySettings
{

    /**
     * @var array all visibilitystates 
     */
    public $states = array();
    
     /**
     * @var array all names of all states
     */   
    private $names = array();
    
     /**
     * @var array all paths of all states
     */   
    private $require_path = array();
    
     /**
     * @var VisibilitySettings Singleton pattern
     */   
    static private $instance = null;

    /**
     * I/O is expensive. Therefore we make the whole class sessionwide singleton
     * to save some I/O.
     * 
     * @return VisibilitySettings The sessionwide visibilitySettings
     */
    static public function getInstance() 
    {
        if (!$_SESSION['VisibilitySettings']) {
            self::$instance = new self;
            $_SESSION['VisibilitySettings'] = serialize(new VisibilitySettings);
        } else {

            /*
             * This part is really tricky. We serialize the class to be able to
             * save it in the session BUT it will definetly need all the
             * contained visibilitySetting. Maybe later we use runkit_method_add
             * but at the moment we will not use an experimental method
             */
            $tmp = unserialize($_SESSION['VisibilitySettings']);
            foreach ($tmp->require_path as $path) {
                require_once $path;
            }
        }
        return unserialize($_SESSION['VisibilitySettings']);
    }

    /**
     * On first construct we scan the visibilitySettings folder and load all
     * applied visibilitySettings
     */
    function __construct()
    {

        $pathinfo = pathinfo(realpath(__FILE__));
        $includepath = $pathinfo['dirname'];

        // scan folder
        if ($handle = opendir("$includepath/visibilitySettings")) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && substr($file, -4) != ".svn") {

                    // load file and save everything needed
                    $require_path = "$includepath/visibilitySettings/$file";
                    require_once $require_path;
                    $classname = "Visibility_" . substr($file, 0, -4);
                    $tmp = new $classname;
                    if ($tmp->isActivated()) {
                        $this->states[$tmp->getIntRepresentation()] = $tmp;
                        $this->names[$tmp->getIntRepresentation()] = $tmp->getDisplayName();
                        $this->require_path[$tmp->getIntRepresentation()] = $require_path;
                    }
                }
            }
            closedir($handle);
            ksort($this->names);
        }
    }

    /**
     * Passthrou to the specified verify method
     * @param string $user_id The userid
     * @param string $owner_id The owner of the visibility
     * @param int $visibility the visibilityID
     * @return boolean true if the user may see it, false if the user is not
     * allowed to see 
     */
    function verify($user_id, $owner_id, $visibility)
    {
        return $this->states[$visibility] && $this->states[$visibility]->verify($user_id, $owner_id);
    }

    /**
     * Get Description of a specific state
     * @param int $stateid the int representation
     * @return string State description
     */
    function getDescription($stateid)
    {
        return $this->states[$stateid]->getDescription();
    }

    /**
     * Returns all keys of states
     * @return array all keys of states 
     */
    function getAllKeys()
    {
        return array_keys($this->names);
    }

    /**
     * Returns all names of states
     * @return array all names of states 
     */
    function getAllNames()
    {
        return $this->names;
    }

    /**
     * Returns the number of possible states
     * @return type 
     */
    function count()
    {
        return count($this->states);
    }

}

?>
