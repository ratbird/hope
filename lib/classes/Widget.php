<?php
/**
 * Generic Widget
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @since 3.1
 */
class Widget
{
    /**
     * Contains the elements of the widget.
     */
    protected $elements = array();

    /**
     * Contains the template used to render the widget.
     */
    protected $template = 'widgets/widget';

    /**
     * Contains additional template variables
     */
    protected $template_variables = array();

    /**
     * Layout for this widget
     */
    protected $layout = 'widgets/widget-layout';

    /**
     * Add an element to the widget.
     *
     * @param WidgetElement $element The actual element
     * @param String        $index   Index/name of the element
     */
    public function addElement(WidgetElement $element, $index = null)
    {
        $index = $index ?: $this->guessIndex($element);
        
        $this->elements[$index] = $element;
    }

    /**
     * Insert an element before a specific other element or at the end of the
     * list if the specified position is invalid.
     *
     * @param WidgetElement $element      The actual element
     * @param String        $before_index Insert element before this element.
     * @param String        $index        Index/name of the element
     */
    public function insertElement(WidgetElement $element, $before_index, $index = null)
    {
        $index = $index ?: $this->guessIndex($element);
        
        $inserted = false;
        
        $elements = array();
        foreach ($this->elements as $idx => $elmnt) {
            if ($idx === $index) {
                $inserted = true;
                $elements[$index] = $element;
            }
            $elements[$idx] = $elmnt;
        }

        if (!$inserted) {
            $elements[$index] = $element;
        }

        $this->elements = $elements;
    }
    
    /**
     * Tries to guess an appropriate index name for the element.
     *
     * @param WidgetElement $element The element in question
     * @return String Appropriate index name
     */
    protected function guessIndex(WidgetElement $element)
    {
        $class = get_class($element);
        if ($class !== 'WidgetElement') {
            $index = strtolower($class);
            $index = str_replace('element', '', $index);
            $index .= '-' . md5(serialize($element));
        } else {
            $index = md5(serialize($element));
        }

        $temp    = $index;
        $counter = 0;
        while (array_key_exists($temp, $this->elements)) {
            $temp = sprintf('%s-%u', $index, $counter++);
        }
        $index = $temp;

        return $index;
    }
    
    /**
     * Retrieve the element at the specified position.
     *
     * @param String $index Index/name of the element to retrieve.
     * @return WidgetElement The element at the specified position.
     * @throws Exception if the specified position is invalid
     */
    public function getElement($index)
    {
        if (!isset($this->elements[$index])) {
            throw new Exception('Trying to retrieve unknown widget element "' . $index . '"');
        }
        return $this->elements[$index];
    }
   
    /**
     * Removes the element at the specified position.
     *
     * @param String $index Index/name of the element to remove.
     * @throws Exception if the specified position is invalid
     */ 
    public function removeElement($index)
    {
        if (!isset($this->elements[$index])) {
            throw new Exception('Trying to remove unknown widget element "' . $index . '"');
        }
        unset($this->elements[$index]);
    }
    
    /**
     * Returns whether this widget has any elements.
     *
     * @return bool True if the widget has at least one element, false
     *              otherwise.
     */
    public function hasElements()
    {
        return count($this->elements) > 0;
    }

    /**
     * Renders the widget.
     * The widget will only be rendered if it contains at least one element.
     *
     * @return String The THML code of the rendered sidebar widget
     */
    public function render($variables = array())
    {
        $content = '';

        if ($this->hasElements()) {
            $layout = $GLOBALS['template_factory']->open($this->layout);

            $template = $GLOBALS['template_factory']->open($this->template);
            $template->set_layout($layout);
            $template->set_attributes($variables + $this->template_variables);
            $template->elements = $this->elements;
            $content = $template->render();
        }
        
        return $content;
    }
}
