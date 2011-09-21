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
    const PARAM_ARRAY  = 100;
    const PARAM_COLUMN = 101;

    /**
     * Verifies that the given SQL query only contains a single statement.
     *
     * @param string    SQL statement to check
     * @throws PDOException when the query contains multiple statements
     */
    protected function verify($statement)
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
        }

        if (!isset($result)) {
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
                    $saved_pos = key($parts);
                } else {
                    $result .= $part;
                }
            }

            if ($quote_chr !== NULL) {
                // unterminated quote: copy to end of string
                $result .= implode(array_slice($parts, $saved_pos));
            }
        }

        return $result;
    }

    /**
     * Quotes the given value in a form appropriate for the type.
     * If no explicit type is given, the value's PHP type is used.
     *
     * @param string    PHP value to quote
     * @param int       parameter type (e.g. PDO::PARAM_STR)
     * @return string   quoted SQL string
     */
    public function quote($value, $type = NULL)
    {
        if (!isset($type)) {
            if (is_null($value)) {
                $type = PDO::PARAM_NULL;
            } else if (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } else if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } else if (is_array($value)) {
                $type = StudipPDO::PARAM_ARRAY;
            } else {
                $type = PDO::PARAM_STR;
            }
        }

        switch ($type) {
            case PDO::PARAM_NULL:
                return 'NULL';
            case PDO::PARAM_BOOL:
                return $value ? '1' : '0';
            case PDO::PARAM_INT:
                return (int) $value;
            case StudipPDO::PARAM_ARRAY:
                return join(',', array_map(array($this, 'quote'), $value));
            case StudipPDO::PARAM_COLUMN:
                return preg_replace('/\\W/', '', $value);
            default:
                return parent::quote($value);
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
        return new StudipPDOStatement($this, $statement, $driver_options);
    }

    /**
     * This method is intended only for use by the StudipPDOStatement class.
     *
     * @param string    SQL statement
     * @return object   PDOStatement object
     */
    public function prepareStatement($statement, $driver_options = array())
    {
        return parent::prepare($statement, $driver_options);
    }
}

/**
 * This is a "fake" PDOStatement implementation that behaves mostly like
 * a real statement object, but has some additional features:
 *
 * - Parameters passed to execute() are quoted according to their PHP type.
 * - A PHP NULL value will result in an actual SQL NULL value in the query.
 * - Array types are supported for all placeholders ("WHERE value IN (?)").
 * - Positional and named parameters can be mixed in the same query.
 */
class StudipPDOStatement implements IteratorAggregate
{
    protected $db;
    protected $query;
    protected $options;
    protected $columns;
    protected $params;
    protected $count;
    protected $stmt;

    /**
     * Initializes a new StudipPDOStatement instance.
     */
    public function __construct($db, $query, $options)
    {
        $this->db = $db;
        $this->query = $query;
        $this->options = $options;
        $this->params = array();
    }

    /**
     * Arranges to have a particular variable bound to a given column in
     * the result-set from a query. Each call to fetch() or fetchAll()
     * will update all the variables that are bound to columns.
     */
    public function bindColumn($column, &$param/*, ...*/)
    {
        $args = func_get_args();
        $args[1] = &$param;
        $this->columns[] = $args;
        return true;
    }

    /**
     * Binds a PHP variable to a corresponding named or question mark place-
     * holder in the SQL statement that was used to prepare the statement.
     * Unlike bindValue(), the variable is bound as a reference and will
     * only be evaluated at the time that execute() is called.
     */
    public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR)
    {
        if (is_string($parameter) && $parameter[0] !== ':') {
            $parameter = ':' . $parameter;
        }

        $this->params[$parameter] = array('value' => &$variable, 'type' => $data_type);
        return true;
    }

    /**
     * Binds a value to a corresponding named or question mark placeholder
     * in the SQL statement that was used to prepare the statement.
     */
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR)
    {
        if (is_string($parameter) && $parameter[0] !== ':') {
            $parameter = ':' . $parameter;
        }

        $this->params[$parameter] = array('value' => $value, 'type' => $data_type);
        return true;
    }

    /**
     * Forwards all unknown methods to the actual statement object.
     */
    public function __call($name, array $arguments)
    {
        return call_user_func_array(array($this->stmt, $name), $arguments);
    }

    /**
     * Forwards all Iterator methods to the actual statement object.
     */
    public function getIterator()
    {
        return $this->stmt;
    }

    /**
     * Executes the prepared statement and returns a PDOStatement object.
     */
    public function execute($input_parameters = NULL)
    {
        // bind additional parameters from execute()
        if (isset($input_parameters)) {
            foreach ($input_parameters as $key => $value) {
                $this->bindValue(is_int($key) ? $key + 1 : $key, $value, NULL);
            }
        }

        // emulate prepared statement if necessary
        foreach ($this->params as $key => $param) {
            if ($param['type'] === StudipPDO::PARAM_ARRAY ||
                $param['type'] === StudipPDO::PARAM_COLUMN ||
                $param['type'] === NULL && !is_string($param['value'])) {
                $emulate_prepare = true;
                break;
            }
        }

        // build the actual query string and prepared statement
        if ($emulate_prepare) {
            $this->count = 1;
            $query = preg_replace_callback('/\?|:\w+/', array($this, 'replaceParam'), $this->query);
        } else {
            $query = $this->query;
        }

        $this->stmt = $this->db->prepareStatement($query, $this->options);

        // bind query parameters on the actual statement
        if (!$emulate_prepare) {
            foreach ($this->params as $key => $param) {
                $this->stmt->bindValue($key, $param['value'], $param['type']);
            }
        }

        // set up column bindings on the actual statement
        if (isset($this->columns)) {
            foreach ($this->columns as $args) {
                call_user_func_array(array($this->stmt, 'bindColumn'), $args);
            }
        }

        return $this->stmt->execute();
    }

    /**
     * Replaces a placeholder with the corresponding parameter value.
     * Throws an exception if there is no corresponding value.
     */
    protected function replaceParam($matches)
    {
        $name = $matches[0];

        if ($name == '?') {
            $key = $this->count++;
        } else {
            $key = $name;
        }

        if (!isset($this->params[$key])) {
            throw new PDOException('missing parameter in query: ' . $key);
        }

        return $this->db->quote($this->params[$key]['value'], $this->params[$key]['type']);
    }
}
