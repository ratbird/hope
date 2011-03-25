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
            if (preg_match('/;\s*\S/', self::replaceStrings($statement))) {
                throw new PDOException('multiple statement execution not allowed');
            }
        }
    }

    /**
     * Replaces all string literals in the statement with placeholders.
     *
     * @param string    SQL statement
     * @return string   modified SQL statement
     */
    protected static function replaceStrings($statement)
    {
        $count = substr_count($statement, '"') + substr_count($statement, "'") + substr_count($statement, '\\');

        // use fast preg_replace() variant if possible
        if ($count < 1000) {
            $result = preg_replace('/"(""|\\\\.|[^\\\\"]+)*"|\'(\'\'|\\\\.|[^\\\\\']+)*\'/s', '?', $statement);
        } else {
            // split string into parts at quotes and backslash
            $parts = preg_split('/([\\\\"\'])/', $statement, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $result = '';

            for ($part = current($parts); $part !== false; $part = next($parts)) {
                // inside quotes, "" is ", '' is ' and \x is x
                if ($quote_chr !== NULL) {
                    if ($part === $quote_chr) {
                        $part = next($parts);

                        if ($part !== $quote_chr) {
                            // backtrack and terminate string
                            prev($parts);
                            $result .= '?';
                            $quote_chr = NULL;
                        }
                    } else if ($part === '\\') {
                        // skip next part
                        next($parts);
                    }
                } else if ($part === "'" || $part === '"') {
                    $quote_chr = $part;
                } else {
                    $result .= $part;
                }
            }
        }

        return $result;
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
