<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @author      <mlunzena@uos.de>
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @copyright   2015 Stud.IP Core-Group
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
 *
 * // Add namespace prefix that indicates that class with the given
 * // namespace will be found in the given directory
 * StudipAutoloader::addAutoloadPath("[...]lib/classes", "Studip");
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
     * You may also pass an optional namespace prefix that indicates
     * that class that start with this prefix are found in the given
     * path.
     *
     * @param string $path   the path to add
     * @param string $prefix the optional namespace prefix
     */
    public static function addAutoloadPath($path, $prefix = '')
    {
        $path = realpath($path);
        if ($prefix) {
            $prefix = rtrim($prefix, '\\') . '\\';
        }

        self::$autoload_paths[] = compact('path', 'prefix');
    }


    /**
     * Removes a path from the list of paths.
     *
     * @param string $path   the path to remove
     * @param string $prefix the optional namespace prefix
     */
    public static function removeAutoloadPath($path, $prefix = '')
    {
        $path = realpath($path);

        foreach (self::$autoload_paths as $index => $item) {
            if ($item['path'] === $path && $item['prefix'] === $prefix) {
                unset(self::$autoload_paths[$index]);
            }
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
     * @return string|null   the path, if found, otherwise null
     */
    private static function findFile($class)
    {
        foreach (self::$autoload_paths as $item) {
            $class_file = self::convertClassToFilename($class, $item['prefix']);
            if ($class_file === false) {
                continue;
            }

            $base =  $item['path'] . DIRECTORY_SEPARATOR . $class_file;
            if (file_exists($base . '.class.php')) {
                return $base . '.class.php';
            } elseif (file_exists($base . '.php')) {
                return $base . '.php';
            }
        }
    }

    /**
     * Convert the raw php class name to a potential file name. Namespaces are taken
     * into account.
     *
     * @param string $class  the name of the class
     * @param string $prefix the optional namespace prefix
     * @return string containing the resolved file name.
     */
    private static function convertClassToFilename($class, $prefix = '')
    {
        // Test whether the namespace prefix matches the class name, leave early if not
        if ($prefix && strpos($class, $prefix) !== 0) {
            return false;
        }

        // Remove namespace prefix
        $class = substr($class, strlen($prefix));

        // Convert namespace into directory structure
        $namespaced = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $filename   = strtolower(dirname($namespaced)) . DIRECTORY_SEPARATOR . basename($namespaced);

        return $filename;
    }
}
