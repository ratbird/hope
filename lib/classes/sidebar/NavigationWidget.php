<?php
class NavigationWidget extends LinksWidget
{
    public function __construct()
    {
        parent::__construct();
        
        $this->title = _('Navigation');
        $this->addCSSClass('sidebar-navigation');
    }
}
