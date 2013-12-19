<?php

class StudipDocumentFolder extends SimpleORMap {

    /**
     * constructor
     * @param string id: primary key of table dokumente
     * @return null
     */
    function __construct($id = null)
    {
        $this->db_table = 'folder';
        parent::__construct($id);
    }

    function getPermissions()
    {
        $result = array();
        foreach (array(1=>'visible', 'writable', 'readable', 'extendable') as $bit => $perm) {
            if ($this->permission & $bit)
                $result[] = $perm;
        }
        return $result;
    }
}
