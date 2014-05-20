<?php
// TODO: Get rid of this ASAP!
class InfoboxWidget extends ListWidget
{
    public function __construct()
    {
        parent::__construct();
        
        $this->addCSSClass('infobox-widget');
        $this->layout = 'sidebar/infobox-layout.php';
    }
}