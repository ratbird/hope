<?php
/**
 * A special sidebar widget for lists of selectable items.
 *
 * @author  Rasmus Fuhse <fuhse@data-quest.de>
 * @license GPL 2 or later
 * @since   3.1
 */
class SelectorWidget extends SidebarWidget
{
    
    /**
     * Constructs the widget by defining a special template.
     */
    public function __construct()
    {
        $this->setTitle(_("Veranstaltungen"));
        $this->template = 'sidebar/selector-widget';
    }
    
    public function setUrl($url) 
    {
        $this->template_variables['url'] = $url;
    }
    
    public function setSelectParameterName($name) 
    {
        $this->template_variables['name'] = $name;
    }
    
    public function setSelection($value) 
    {
        $this->template_variables['value'] = $value;
    }

}