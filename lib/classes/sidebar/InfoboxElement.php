<?php
// TODO: Get rid of this ASAP!
class InfoboxElement extends WidgetElement
{
    public $icon;
    
    public function __construct($content = '', $icon = false)
    {
        parent::__construct($content);
        $this->setIcon(Assets::image_path($icon));
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