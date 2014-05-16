<?php
class SelectElement extends WidgetElement
{
    protected $id;
    protected $text;
    
    public function __construct($id, $label) {
        $this->id = $id;
        $this->label = $label;
    }

    public function setId($id) 
    {
        $this->id = $id;
    }
	
    public function setLabel($label) 
    {
        $this->label = $label;
    }
	
    public function getId() 
    {
        return $this->id;
    }
	
    public function getLabel() 
    {
        return $this->label;
    }
	
    public function render()
    {
        return $this->label;
    }
}