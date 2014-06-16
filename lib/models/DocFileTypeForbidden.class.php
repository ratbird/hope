<?php

class DocFileTypeForbidden extends SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'doc_filetype_forbidden';
        
        $this->belongs_to['userConfig'] = array(
            'class_name'  => 'DocUsergroupConfig',
            'foreign_key' => 'usergroup',
        );
        
        $this->belongs_to['filetype'] = array(
            'class_name'  => 'DocFiletype',
            'foreign_key' => 'dateityp_id',
        );

        
        
        
        parent::__construct($id);

    }
}