<?php
class SearchWidget extends SidebarWidget
{
    public function __construct()
    {
        $this->title = _('Suche');
    }
    
    public function add($title, $url, $needle_name = 'q', $filters = array(), $options = array())
    {
        $template = $GLOBALS['template_factory']->open('sidebar/search-element');

        $id = $options['id'] ?: ('search-' . md5(uniqid('search', true)));
        SkipLinks::addIndex($title, $id);

        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            $url = str_replace('?' . $query , '', $url);
            parse_str(html_entity_decode($query) ?: '', $query_params);
        } else {
            $query_params = array();
        }

        $template->url    = URLHelper::getLink($url);
        $template->params = $query_params;
        
        $template->title       = $title;
        $template->id          = $id;
        $template->method      = $options['method'] ?: 'get';
        $template->needle_name = $needle_name;
        $template->needle      = Request::get($needle_name, $options['needle'] ?: '');
        $template->placeholder = $options['placeholder'] ?: _('Suchbegriff eingeben');
        $template->filters     = $filters;

        $this->addElement(new WidgetElement($template->render()));
    }

}