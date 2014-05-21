<?php
class OptionsWidget extends ListWidget
{
    const INDEX = 'options';
    
    public function __construct()
    {
        parent::__construct();
        
        $this->addCSSClass('widget-options');
        $this->title = _('Einstellungen');
    }
    
    public function addCheckbox($label, $state, $toggle_url, $toggle_url_off = null)
    {
        $content = sprintf('<a href="%s" class="options-checkbox options-%s">%s</a>',
                           ($state && $toggle_url_off !== null) ? $toggle_url_off : $toggle_url,
                           $state ? 'checked' : 'unchecked',
                           htmlReady($label));
        $this->addElement(new WidgetElement($content));
    }
}