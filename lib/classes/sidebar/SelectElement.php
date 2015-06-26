<?php
/**
 * Model for a select element of the select widget.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 * @since   Stud.IP 3.1
 */
class SelectElement extends WidgetElement
{
    protected $id;
    protected $label;
    protected $active;
    
    /**
     * Constructs the element with an id (value of the according option
     * element) and a label (text content of the according option
     * element).
     *
     * @param String $id     Id of the element
     * @param String $label  Label/text content of the element
     * @param bool   $active Indicates whether the element is active
     */
    public function __construct($id, $label, $active = false)
    {
        $this->id     = $id;
        $this->label  = $label;
        $this->active = $active;
    }

    /**
     * Sets the id of the element.
     *
     * @param String $id Id of the element
     */
    public function setId($id) 
    {
        $this->id = $id;
    }

    /**
     * Sets the label/text content of the element.
     *
     * @param String $label Label/text content of the element
     */
    public function setLabel($label) 
    {
        $this->label = $label;
    }

    /**
     * Returns the id of the element.
     *
     * @return String Id of the element
     */
    public function getId() 
    {
        return $this->id;
    }

    /**
     * Returns the label/text content of the element. The label is stripped
     * of all leading whitespace.
     *
     * @return String Label/text content of the element
     * @see SelectElement::getIndentLevel
     */
    public function getLabel() 
    {
        return ltrim($this->label, ' ');
    }

    /**
     * Sets the activate of the element.
     *
     * @param bool $active Indicates whether the element is active (optional,
     *                     defaults to true)
     */
    public function setActive($active = true)
    {
        $this->active = $active;
    }

    /**
     * Returns whether the element is active.
     *
     * @return bool indicating whether the element is active
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Returns the indentation level of the element based on the number
     * of leading whitespace characters. This is used to indent the label
     * in the according option element.
     *
     * @return int Indentation level
     */
    public function getIndentLevel()
    {
        return strlen($this->label) - strlen(ltrim($this->label));
    }

    /**
     * Renders the element (well, not really - this returns it's label).
     *
     * @return String The label/text content of the element
     * @todo   What should this method actually do?
     */
    public function render()
    {
        return $this->getLabel();
    }
}