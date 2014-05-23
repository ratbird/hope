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
        $content = '';

        NotificationCenter::postNotification('HelpbarWillRender', $this);

        if ($this->hasWidgets()) {
            $template = $GLOBALS['template_factory']->open('helpbar/helpbar');
            $template->widgets = $this->widgets;
            $content = $template->render();
        }

        NotificationCenter::postNotification('HelpbarDidRender', $this);

        return $content;
    }
}