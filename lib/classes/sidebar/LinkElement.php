<?php
class LinkElement extends WidgetElement implements ArrayAccess
{
    /**
     * Create link by parsing a html chunk.
     *
     * @param String $html HTML chunk to parse
     * @param mixed  $icon Optional icon
     * @return LinkElement Link element from parsed html
     * @throws Exception if html can not be parsed
     */
    public static function fromHTML($html, $icon = null)
    {
        $matched = preg_match('~(<a(?P<attributes>(?:\s+\w+=".*?")+)>\s*(?P<label>.*?)\s*</a>)~s', $html, $match);
        if (!$matched) {
            throw new Exception('Could not parse html');
        }

        $attributes = self::parseAttributes($match['attributes']);
        $url        = $attributes['href'] ?: '#';
        unset($attributes['href']);

        return new self($match['label'], $url, $icon, $attributes);
    }
    
    /**
     * Parse a string of html attributes into an associative array.
     *
     * @param String $text String of html attributes
     * @return Array parsed attributes as key => value pairs
     * @see https://gist.github.com/rodneyrehm/3070128
     */
    protected static function parseAttributes($text)
    {
        $attributes = array();
        $pattern = '#(?(DEFINE)
                       (?<name>[a-zA-Z][a-zA-Z0-9-:]*)
                       (?<value_double>"[^"]+")
                       (?<value_single>\'[^\']+\')
                       (?<value_none>[^\s>]+)
                       (?<value>((?&value_double)|(?&value_single)|(?&value_none)))
                     )
                     (?<n>(?&name))(=(?<v>(?&value)))?#xs';
 
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match['n']] = isset($match['v'])
                                         ? trim($match['v'], '\'"')
                                         : null;
            }
        }
        return $attributes;
    }
    
    public $url;
    public $label;
    public $icon;
    public $active = false;
    public $attributes = array();
    
    /**
     * create a link for a widget
     *
     * @param String $label      Label/content of the link
     * @param String $url        URL/Location of the link
     * @param String $icon       Icon for the link, defaults to blank.gif
     * @param array  $attributes HTML-attributes for the a-tag in an associative array.
     */
    public function __construct($label, $url, $icon = null, $attributes = array())
    {
        parent::__construct();
        if ($icon && !$this->isURL($icon)) {
            $icon = Assets::image_path($icon);
        }
        $this->label      = $label;
        $this->url        = $url;
        $this->icon       = $icon;
        $this->attributes = $attributes;
    }
    
    public function setActive($active = true)
    {
        $this->active = $active;
    }

    public function asDialog($state = '')
    {
        if ($state !== false) {
            $this->attributes['data-dialog'] = $state;
        } else {
            unset($this->attributes['data-dialog']);
        }
    }

    public function setTarget($target)
    {
        if ($target) {
            $this->attributes['target'] = $target;
        } else {
            unset($this->attributes['target']);
        }
    }
    
    public function addClass($class)
    {
        $this->attributes['class'] = $this->attributes['class'] 
            ? $this->attributes['class']." ".$class 
            : $class;
    }
    
    public function render()
    {
        if ($this->active) {
            $this->addClass('active');
        }

        $attributes = array_map('htmlReady', (array)$this->attributes);

        if (isset($this->attributes['disabled']) && $this->attributes['disabled'] !== false) {
            $tag = 'span';
        } else {
            $tag = 'a';
            $attributes['href'] = $this->url;
        }
        
        $attr_str = '';
        foreach ($attributes as $key => $value) {
            $attr_str .= sprintf(' %s="%s"', $key, $value);
        }

        return sprintf('<%1$s%2$s>%3$s</%1$s>',
               $tag,
               $attr_str,
               htmlReady($this->label));
    }
    
    protected function isURL($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== FALSE;
    }
    
    // Array access for attributes
    
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }
    
    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }
    
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }
    
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
}