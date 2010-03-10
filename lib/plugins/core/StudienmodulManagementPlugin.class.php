<?php
# Lifter007: TODO
# Lifter003: TODO
/*
* StudienmodulManagementPlugin.class.php
*
* Copyright (C) 2008 - André Noack <noack@data-quest.de>
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License as
* published by the Free Software Foundation; either version 2 of
* the License, or (at your option) any later version.
*/


/**
* StudienmodulManagementPlugin Interface.
*
* @author    anoack
* @copyright (c) Authors
*/

interface StudienmodulManagementPlugin {
    
    /**
     * Gibt die Bezeichnung für ein Modul zurück
     *
     * @param string $module_id eine ID aus der Tabelle sem_tree
     * @param string $semester_id eine ID aus der Tabelle semester_data
     * 
     * @return string
     */
    function getModuleTitle($module_id, $semester_id = null);
    
    /**
     * Gibt die Kurzbeschreibung für ein Modul zurück
     *
     * @param string $module_id eine ID aus der Tabelle sem_tree
     * @param string $semester_id eine ID aus der Tabelle semester_data
     * 
     * @return string
     */
    function getModuleDescription($module_id, $semester_id = null);
    
    /**
     * Gibt ein Objekt vom Typ Navigation zurück, das Titel, Link und Icon für
     * ein Modul enthalten kann, z.B. zur Darstellung eines Info Icons
     *
     * @param string $module_id $module_id eine ID aus der Tabelle sem_tree
     * @param string $semester_id eine ID aus der Tabelle semester_data
     * 
     * @return Navigation
     */
    function getModuleInfoNavigation($module_id, $semester_id = null);
    
    
}

?>
