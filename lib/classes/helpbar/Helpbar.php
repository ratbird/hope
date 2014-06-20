<?php
/**
 * Help section
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @since 3.1
 */
class Helpbar extends WidgetContainer
{
    protected $json_directory;
    protected $open = false;
    protected $should_render = true;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->json_directory = $GLOBALS['STUDIP_BASE_PATH'] . '/doc/helpbar';
    }
    
    /**
     * @todo Adjust this to db BEFORE release
     */
    public function load($identifier, $variables = array(), $language = null)
    {
        $language = $language ?: substr($GLOBALS['user']->preferred_language, 0, 2);

        $jsonfile = sprintf('%s/%s/%s.json',
                            $this->json_directory,
                            strtolower($language),
                            $identifier);

        if (!file_exists($jsonfile) && $language !== 'de') {
            $language = 'de';
            $jsonfile = sprintf('%s/%s/%s.json',
                                $this->json_directory,
                                strtolower($language),
                                $identifier);

        }

        if (!file_exists($jsonfile) || !is_readable($jsonfile)) {
            throw new InvalidArgumentException('Helpbar for identifier "' . $identifier . '" not found or not readable.');
        }

        $json = studip_utf8decode(json_decode(file_get_contents($jsonfile), true));
        if ($json === null) {
            throw new RuntimeException('Helpbar content for identifier "' . $identifier . '" could not be loaded.');
        }
        
        foreach ($json as $row) {
            if (!empty($row['icon'])) {
                $icon = sprintf('icons/16/white/%s.png', $row['icon']);
            }
            $this->addPlainText($row['label'] ?: '',
                                $this->interpolate($row['text'], $variables),
                                $icon ?: null);
        }
    }
    
    protected function interpolate($string, $variables = array())
    {
        if (is_array($string)) {
            return array_map(array($this, 'interpolate'), $string, array_pad(array(), count($string), $variables));
        }

        $replaces = array();
        foreach ($variables as $needle => $replace)
        {
            $replaces['#{' . $needle . '}'] = $replace;
        }
        return str_replace(array_keys($replaces), array_values($replaces), $string);
    }

    public function addPlainText($label, $text, $icon = null)
    {
        if (is_array($text)) {
            $first = array_shift($text);
            $this->addPlainText($label, $first, $icon);
            
            foreach ($text as $item) {
                $this->addPlainText('', $item);
            }

            return;
        }
        
        if ($label) {
            $content = sprintf('<strong>%s</strong><p>%s</p>',
                            htmlReady($label), formatReady($text));
        } else {
            $content = sprintf('<p>%s</p>', formatReady($text));
        }

        $widget = new HelpbarWidget();
        $widget->setIcon($icon);
        $widget->addElement(new WidgetElement($content));
        $this->addWidget($widget);
    }
    
    public function addText($label, $id)
    {
        $widget = new HelpbarWidget();
        $widget->addElement(new HelpbarTextElement($label, $id));
        $this->addWidget($widget, 'help-' . $id);
    }
    
    public function addLink($label, $url, $icon = false, $target = false, $attributes = array())
    {
        $id = md5($url);

        $element = new LinkElement($label, $url);
        $element->attributes = $attributes;
        $element->setTarget($target);

        $widget = new HelpbarWidget();
        $widget->addElement($element);
        $widget->setIcon($icon);

        $this->addWidget($widget, 'help-' . $id);
    }
    
    public function insertLink($label, $url, $icon = false, $target = false, $attributes = array())
    {
        $id = md5($url);

        $element = new LinkElement($label, $url);
        $element->attributes = $attributes;
        $element->setTarget($target);

        $widget = new HelpbarWidget();
        $widget->addElement($element);
        $widget->setIcon($icon);

        $this->insertWidget($widget, ':first', 'help-' . $id);
    }
    
    public function open($state = true)
    {
        $this->open = $state;
    }
    
    public function shouldRender($state = true)
    {
        $this->should_render = $state;
    }
    
    /**
     * Renders the help bar.
     * The helpbar will only be rendered if it actually contains any widgets.
     * It will use the template "helpbar.php" located at "templates/helpbar".
     * A notification is dispatched before and after the actual rendering
     * process.
     *
     * @return String The HTML code of the rendered helpbar.
     */
    public function render()
    {
        // add tour links
        if (Config::get()->TOURS_ENABLE) {
            $widget = new HelpbarTourWidget();
            if ($widget->hasElements()) {
                $this->addWidget($widget);
            }
            $tour_data = $widget->tour_data;
        }

        $content = '';

        NotificationCenter::postNotification('HelpbarWillRender', $this);

        if ($this->should_render && $this->hasWidgets()) {
            $template = $GLOBALS['template_factory']->open('helpbar/helpbar');
            $template->widgets   = $this->widgets;
            $template->open      = $this->open;
            $template->tour_data = $tour_data;
            $content = $template->render();
        }

        NotificationCenter::postNotification('HelpbarDidRender', $this);

        return $content;
    }
}