<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @author      <mlunzena@uos.de>
 * @copyright   2013 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

/**
 * The StudipAutoloader takes care for automatically loading
 * classes. You just have to provide it with a couple of paths where
 * it may find the classes.
 *
 * Example:
 * \code
 * StudipAutoloader::register();
 * StudipAutoloader::addAutoloadPath("/tmp");
 * StudipAutoloader::addAutoloadPath("[...]lib/classes");
 * \endcode
 */
class StudipAutoloader
{
    protected static $autoload_paths = array();

    /**
     * Registers the StudipAutoloader as an autoloader.
     */
    public static function register()
    {
        spl_autoload_register(array(get_called_class(), 'loadClass'));
    }


    /**
     * Un-registers the StudipAutoloader again.
     */
    public static function unregister()
    {
        spl_autoload_unregister(array(get_called_class(), 'loadClass'));
    }


    /**
     * Adds another path to the list of paths where to search for
     * classes.
     *
     * @param string $path  the path to add
     */
    public static function addAutoloadPath($path)
    {
        self::$autoload_paths[] = realpath($path);
    }


    /**
     * Removes a path from the list of paths.
     *
     * @param string $path  the path to remove
     */
    public static function removeAutoloadPath($path)
    {
        $i = array_search(realpath($path), self::$autoload_paths);
        if ($i !== false) {
            unset(self::$autoload_paths[$i]);
        }
    }


    /**
     * Loads the specified class or interface.
     *
     * @param  string    $class  the name of the class
     * @return bool|null true, if loaded, otherwise null
     */
    public static function loadClass($class)
    {
        if ($file = self::findFile($class)) {
            include $file;

            return true;
        }
    }

    /**
     * Locate the file where the class is defined.
     * Handles possible namespaces by mapping the path elements to the
     * directory structure.
     *
     * @param string $class  the name of the class
     * @param bool   $handle_namespace Should namespaces be handled by
     *                                 converting into directory structure?
     *
     * @return string|null   the path, if found, otherwise null
     */
    private static function findFile($class, $handle_namespace = true)
    {
        // Handle possible namespace
        if ($handle_namespace && strpos($class, '\\') !== false) {
            // Convert namespace into directory structure
            $namespaced = str_replace('\\', DIRECTORY_SEPARATOR, $class);
            $namespaced = strtolower(dirname($namespaced)) . DIRECTORY_SEPARATOR . basename($namespaced);
            $class = basename($namespaced);

            if ($filename = self::findFile($namespaced, false)) {
                return $filename;
            }
        }

        foreach (self::$autoload_paths as $path) {
            $base =  $path . DIRECTORY_SEPARATOR . $class;
            if (file_exists($base . '.class.php')) {
                return $base . '.class.php';
            } elseif (file_exists($base . '.php')) {
                return $base . '.php';
            }
        }
    }
}
