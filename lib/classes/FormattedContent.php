<?php
use Studip\Markup;

/**
 * Storage for formatted content
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 * @since   Stud.IP 3.4
 */
class FormattedContent
{
    protected $format;
    protected $formatted_content = null;

    /**
     * Constructs the object for the given content (with the given format).
     * If no format is provided, the default StudipFormat will be used.
     *
     * @param String $content Content to format
     * @param mixed $format   Used format (optional, defaults to StudipFormat)
     */
    public function __construct($content, StudipFormat $format = null)
    {
        $this->format = $format ?: new StudipFormat;

        $formatted = Markup::apply($this->format, $content, false);
        $wrapped   = sprintf(FORMATTED_CONTENT_WRAPPER, $formatted);

        $this->formatted_content = $wrapped;
    }

    /**
     * Returns the formatted content (optionally with the opengraph data
     * included).
     *
     * @return String containg the formatted content as html
     */
    public function getContent()
    {
        return $this->formatted_content;
    }

    /**
     * Converts the object to a string by returning the formatted content.
     * This will be used a lot since formatReady() is usually expected to
     * return a string.
     *
     * @return String containing the formatted content as html
     */
    public function __toString()
    {
        return $this->getContent();
    }
}