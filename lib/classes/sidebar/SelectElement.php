<?php
class SelectElement extends WidgetElement
{
    protected $id;
    protected $label;
    protected $active;
    
    public function __construct($id, $label, $active = false) {
        $this->id     = $id;
        $this->label  = $label;
        $this->active = $active;
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

    public function setActive($active = true)
    {
        $this->active = $active;
    }
    
    public function isActive()
    {
        return $this->active;
    }

    public function render()
    {
        return $this->label;
    }
}