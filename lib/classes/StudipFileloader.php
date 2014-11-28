<?php
/**
 * @author     <sebastian@phpunit.de>
 * @author     <mlunzena@uos.de>
 * @copyright  2001-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 */
class StudipFileloader
{
    /**
     * Loads a PHP sourcefile and transfers all therein defined
     * variables into a specified container.
     * Optionally you may inject more bindings into the scope, if the
     * sourcefile requires them.
     *
     * @param  string $_filename   which file to load
     * @param  array  $_container  where to put the new variables into
     * @param  array  $_injected   optional bindings, to inject into
     *                             the scope before loading
     */
    public static function load($_filename, &$_container, $_injected = array())
    {
        extract($_injected);

        $_oldVariableNames = array_keys(get_defined_vars());

        include $_filename;

        $newVariables     = get_defined_vars();
        $newVariableNames = array_diff(
            array_keys($newVariables), $_oldVariableNames
        );

        foreach ($newVariableNames as $variableName) {
            if ($variableName !== '_oldVariableNames') {
                $_container[$variableName] = $newVariables[$variableName];
            }
        }
    }
}
