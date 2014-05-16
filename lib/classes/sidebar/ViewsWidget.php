<?php
class ViewsWidget extends LinksWidget
{
    public function __construct()
    {
        parent::__construct();
        
        $this->title = _('Ansichten');
    }
}