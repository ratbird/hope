<?

class DocFiletype extends SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'doc_filetype';
        
        $this->has_many['forbiddenTypes'] = array(
            'class_name' => 'DocFileTypeForbidden',
            'on_delete'  => 'delete',
            'on_store'   => 'store',
        );
        
        parent::__construct($id);
        
        
    }
}