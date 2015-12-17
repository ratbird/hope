<?php
// TODO: Get rid of this ASAP!
class InfoboxElement extends WidgetElement
{
    public $icon;
    
    public function __construct($content = '', $icon = false)
    {
        parent::__construct($content);
        if ($icon) {
            $this->setIcon(is_string($icon) ? Icon::create2($icon) : $icon);
        }
    }

    public function setIcon($icon)
    {
        $this->icon = $icon;
    }
    
    public function getIcon()
    {
        return $this->icon;
    }
}