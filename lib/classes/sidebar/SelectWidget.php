<?php
/**
 * Sidebar widget for lists of selectable items.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @since   3.1
 */
class SelectWidget extends SidebarWidget
{
    /**
     * Constructs the widget by defining a special template.
     */
    public function __construct($title, $url, $name, $method = 'get')
    {
        $this->template = 'sidebar/select-widget';
        $this->setTitle($title);
        $this->setUrl($url);
        $this->setSelectParameterName($name);
        $this->setRequestMethod($method);
    }

    public function setUrl($url)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            $url = str_replace('?' . $query , '', $url);
            parse_str(html_entity_decode($query) ?: '', $query_params);
        } else {
            $query_params = array();
        }

        $this->template_variables['url']    = URLHelper::getLink($url);
        $this->template_variables['params'] = $query_params;
    }

    public function setSelectParameterName($name) 
    {
        $this->template_variables['name'] = $name;
    }

    public function setSelection($value) 
    {
        $this->template_variables['value'] = $value;
    }
    
    public function setRequestMethod($method)
    {
        $this->template_variables['method'] = $method;
    }
    
    public function setOptions(Array $options)
    {
        $selected = Request::get($this->template_variables['name']);
        foreach ($options as $key => $label) {
            $element = new SelectElement($key, $label, $selected === $key);
            $this->addElement($element);
        }
    }
}