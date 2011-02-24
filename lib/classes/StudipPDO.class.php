<?php
/**
 * StudipPDO.class.php - Stud.IP PDO class
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Elmar Ludwig
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

/**
 * This is a special variant of the standard PDO class that does
 * not allow multiple statement execution.
 */
class StudipPDO extends PDO
{
    /**
     * Verifies that the given SQL query only contains a single statement.
     *
     * @param string    SQL statement to check
     * @throws PDOException when the query contains multiple statements
     */
    private function verify($statement)
    {
        if (strpos($statement, ';') !== false) {
            // replace all strings with placeholders
            $statement = preg_replace('/(["\'])(\1\1|\\\\.|.)*?\1/', '?', $statement);

            if (preg_match('/;\s*\S/', $statement)) {
                throw new PDOException('multiple statement execution not allowed');
            }
        }
    }

    /**
     * Executes an SQL statement and returns the number of affected rows.
     *
     * @param string    SQL statement
     * @return int      number of affected rows
     */
    public function exec($statement)
    {
        $this->verify($statement);
        return parent::exec($statement);
    }

    /**
     * Executes an SQL statement, returning a result set as a statement object.
     *
     * @param string    SQL statement
     * @param int       fetch mode (optional)
     * @param mixed     fetch mode parameter (see PDOStatement::setFetchMode)
     * @param mixed     fetch mode parameter (see PDOStatement::setFetchMode)
     * @return object   PDOStatement object
     */
    public function query($statement, $mode = NULL, $arg1 = NULL, $arg2 = NULL)
    {
        $this->verify($statement);

        if (isset($mode)) {
            return parent::query($statement, $mode, $arg1, $arg2);
        }

        return parent::query($statement);
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param string    SQL statement
     * @return object   PDOStatement object
     */
    public function prepare($statement, $driver_options = array())
    {
        $this->verify($statement);
        return parent::prepare($statement, $driver_options);
    }
}
