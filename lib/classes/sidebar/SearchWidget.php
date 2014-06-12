<?php
class SearchWidget extends SidebarWidget
{
    const INDEX = 'search';

    protected $url;
    protected $needles = array();
    protected $filters = array();
    protected $method = 'get';
    protected $id = null;

    public function __construct($url = '')
    {
        parent::__construct();
        
        $this->url      = $url;
        $this->title    = _('Suche');
        $this->template = 'sidebar/search-widget';

        $this->needles = $needles;
        $this->filters = $filters;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function getId()
    {
        return $this->id;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }
    
    public function getMethod()
    {
        return $this->method;
    }

    public function addNeedle($label, $name, $placeholder = false)
    {
        $value = Request::get($name);
        $this->needles[] = compact(words('label name placeholder value'));
    }

    public function addFilter($label, $key)
    {
        $this->filters[$key] = $label;
    }

    public function hasElements()
    {
        return count($this->elements) + count($this->needles) + count($this->filters) > 0;
    }

    public function render($variables = array())
    {
        $query = parse_url($this->url, PHP_URL_QUERY);
        if ($query) {
            $this->url = str_replace('?' . $query , '', $this->url);
            parse_str(html_entity_decode($query) ?: '', $query_params);
        } else {
            $query_params = array();
        }

        $this->template_variables['url']     = URLHelper::getLink($this->url);
        $this->template_variables['url_params'] = $query_params;

        $this->template_variables['method']     = $this->method;
        $this->template_variables['id']      = $this->id;

        $this->template_variables['needles'] = $this->needles;
        $this->template_variables['filters'] = $this->filters;
        
        $this->template_variables['has_data'] = $this->hasData();

        return parent::render($variables);
    }
    
    protected function hasData()
    {
        if (!Request::method() === strtoupper($this->method)) {
            return false;
        }

        foreach ($this->needles as $needle) {
            if (Request::get($needle['name'])) {
                return true;
            }
        }

        return false;
    }

}