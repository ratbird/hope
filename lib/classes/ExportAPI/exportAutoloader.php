<?php
/**
 * this file registers the autoloader for all formats and elements
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
spl_autoload_register(function ($class) {
            if (file_exists(__DIR__ . '/elements/' . $class . '.php')) {
                include 'elements/' . $class . '.php';
            }
            if (file_exists(__DIR__ . '/formats/' . $class . '.php')) {
                include 'formats/' . $class . '.php';
            }
        });
?>
