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
    
    public function addRadioButton($label, $url, $checked = false)
    {
        $content = sprintf('<a href="%s" class="options-radio options-%s">%s</a>',
                           $url,
                           $checked ? 'checked' : 'unchecked',
                           htmlReady($label));
        $this->addElement(new WidgetElement($content));
    }
    
    public function addSelect($label, $url, $name, $options, $selected_option = false, $attributes = array())
    {
        $widget = new SelectWidget($label, $url, $name);
        $widget->layout = false;

        foreach ($options as $value => $option_label) {
            $widget->addElement(new SelectElement($value, $option_label, $value === $selected_option));
        }

        $widget->attributes = array_merge($widget->attributes, $attributes);

        $content = $widget->render();

        $this->addElement(new WidgetElement($content));
    }
}