<?php

/**
 * export_table - table export element
 *
 * The table element consists of the header (array) and the content (array of
 * array). You can add a whitelist to restrict the cols 
 * 
 * XML:
 * 
 * 1)
 * @code
 * <table>
 *  <sql>Put sql statement here</sql>
 * </table>
 * @endcode
 * 
 * The export table will fill itself by the sql result
 * 
 * 2)
 * @code
 * <table>
 *  <database>
 *      <name>seminar_user</name>
 *      <col rename="Benutzer">user_id</col>
 *      <col rename="Status">status</col>
 *      <where>status = 'dozent'</where>
 *  </database>
 * </table>
 * @endcode
 * 
 * 3)
 * NOT YET IMPLEMENTED!
 * @code
 * <table>
 *  <head>
 *   <entry>Header 1</entry>
 *   <entry>Header 2</entry>
 * </content>
 *  <content>
 *   <entry>Row 1 Entry 1</entry>
 *   <entry>Row 1 Entry 2</entry>
 * </content>
 *  <content>
 *   <entry>Row 2 Entry 1</entry>
 *   <entry>Row 2 Entry 2</entry>
 * </content>
 * </table>
 * @endcode
 * 
 * This statement will form a query for the db table seminar_user. It will use
 * the user_id, status and rename it with Benutzer and Status. Also a where
 * statement can be given
 * 
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
class exportTable extends ExportElement {

    // TableContent
    public $content = array();
    // Headlines
    public $header = array();
    // May the user edit what cells he wants
    public $userSelection = false;
    public $whitelist = array();

    function __construct($array = null) {
        if ($array != null) {
            $this->content = $array;
        }
    }

    /**
     * Reads out data of the database and parses it into the content
     * @param type $sql 
     */
    public function getFromSQL($sql) {
        $db = DBManager::get();
        $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $this->header = array_keys($result[0]);
        $this->content = $result;
    }

    /**
     * Renames a Header
     * @param type $oldname The old name of the Header
     * @param type $newname The new name of the Header
     */
    public function renameHeader($oldname, $newname) {
        foreach ($this->header as &$entry) {
            if ($entry == $oldname) {
                $entry = $newname;
            }
        }
    }

    /**
     * Deletes all columns where the header name is not in the array
     * @param type $array the whitelist
     */
    public function whiteList($array) {
        foreach ($this->header as &$entry) {
//todo
        }
    }

    /**
     * Easy function to get data from the database and parse it
     * @param type $table the wished table
     * @param type $cols the wished columns (optional)
     * @param type $where where clause (optional)
     */
    public function getFromDatabaseTable($table, $cols = null, $where = null) {

        if ($cols == null) {
            $head = "*";
        } else {
            foreach ($cols as $col) {
                $head .= $col . ",";
            }
            $head = substr($head, 0, -1);
        }
        if ($where != null) {
            $where = "WHERE $where";
        } else {
            $where = "";
        }
        $sql = "SELECT $head FROM $table $where";
        $this->getFromSQL($sql);
    }

    /**
     * Returns an Array of Headerentries
     * 
     * @return array Headerentries
     */
    public function getHeader() {
        return $this->whitelist ? : $this->header;
    }

    /**
     * Returns the content of the table
     * 
     * @return array array of content
     */
    public function getContent() {
        if (!$this->whitelist) {
            return $this->content;
        }
        $result = array();
        $flipped = array_flip($this->whitelist);
        foreach ($this->content as $row) {
            array_push($result, array_intersect_key($row, $flipped));
        }
        return $result;
    }

    /**
     * {@inheritdoc }
     */
    public function load($xml) {
        parent::load($xml);
        foreach ($xml->children() as $element) {
            switch ($element->getName()) {
                case "sql":
                    $this->getFromSQL((string) $element);
                    break;
                case "database":
                    $this->loadFromDatabaseXML($element);
                    break;
                case "head":
                    $this->setHead($element);
                case "content":
                    $this->setContent($element);
                default:
                    break;
            }
        }
    }

    /**
     * {@inheritdoc }
     */
    public function preview($elementNo) {
        $preview = "<table class='default'>"
                . "<caption>"
                . _('Tabelle')
                . "</caption>";

        //define head
        $preview .= "<thead><tr>";
        foreach ($this->header as $header) {
            $preview .= "<th>";
            if ($this->isEditable()) {
                $preview .= "<input type='checkbox' name='edit[$elementNo][]' checked='checked' value='$header'>";
            }
            $preview .= "$header</th>";
        }
        $preview .= "</tr></thead>";

        // define body
        $preview .= "<tbody>";
        for ($i = 0; $i < 5; $i++) {
            if ($this->content[$i]) {
                $preview .= "<tr>";
                foreach ($this->content[$i] as $entry) {
                    $preview .= "<td>$entry</td>";
                }
                $preview .= "</tr>";
            }
        }
        $preview .= "</tbody>";

        // define foot
        $preview .= "<tfoot><tr><td colspan=0>" . _("Insgesamt:") . " " . count($this->content) . " " . _("Einträge") . "</td></tr></tfoot>";

        // goodbye
        $preview .= "</table>";
        return $preview;
    }

    /**
     * {@inheritdoc }
     */
    public function edit($edit) {
        $this->whitelist = $edit;
    }

    /**
     * Sets the head
     */
    private function setHead($element) {
        foreach ($element as $entry) {
            $this->header[] = (string) $entry;
        }
    }

    /**
     * Sets the content
     */
    private function setContent($element) {
        foreach ($element as $entry) {
            $new[] = (string) $entry;
        }
        $this->content[] = $new;
    }

    /**
     * Loads table entries from database
     */
    private function loadFromDatabaseXML($element) {
        foreach ($element->children() as $databaseProperty) {
            switch ($databaseProperty->getName()) {

                case "name":
                    $name = (string) $databaseProperty;
                    break;
                case "col":
                    if (!$cols) {
                        $cols = array();
                    }
                    $newcol = (string) $databaseProperty;
                    $attributes = $databaseProperty->attributes();
                    if ($attributes['rename']) {
                        $newcol.= " as " . $attributes['rename'];
                    }
                    array_push($cols, $newcol);
                    break;
                case "where":
                    if ($where) {
                        $where .= " AND " . (string) $databaseProperty;
                    } else {
                        $where = (string) $databaseProperty;
                    }
                    break;
                default:
                    break;
            }
        }
        $this->getFromDatabaseTable($name, $cols, $where);
    }

}

?>
