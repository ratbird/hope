<?php
/*
 * Copyright (c) 2011 mlunzena@uos.de, aklassen@uos.de
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


namespace Studip;

require('Interactable.class.php');

/**
 * Represents an HTML link element.
 */
class LinkButton extends Interactable
{
    /**
     * Easy factory method to get a LinkButton instance.
     * All parameters are optional.
     *
     * @code
     * echo LinkButton::get();
     * # => <button type="submit" name="ok">ok</button>
     *
     * echo LinkButton::get('yes')
     * # => <a class="button" href="?">yes</a>
     *
     * echo LinkButton::get('example', 'http://www.example.com')
     * # => <a class="button" href="http://www.example.com">example</a>
     *
     * echo LinkButton::get('yes', array('a' => 1, 'b' => 2))
     * # => <a class="button" a="1" b="2" href="?">yes</a>
     *
     * echo Button::get('example', 'http://www.example.com', array('a' => 1, 'b' => 2)),
     * # => <a class="button" a="1" b="2" href="http://www.example.com">example</a>
     * @endcode
     *
     * @param string $label      the label of the link
     * @param string $url        the target url for the link
     * @param array  $attributes the attributes of the link element
     */

    function initialize($label, $url, $attributes)
    {
        $this->attributes['href'] = $url ?: @\URLHelper::getURL();
    }

    /**
     * @return  returns a HTML representation of this button.
     */
    function __toString()
    {
        // add "button" to attribute "class"
        @$this->attributes["class"] .= " button";

        $attributes = array();
        ksort($this->attributes);
        foreach ($this->attributes as $k => $v) {
            $attributes[] = sprintf(' %s="%s"', $k, htmlReady($v));
        }
        // TODO: URLHelper...?!
        return sprintf('<a%s>%s</a>',
                       join('', $attributes),
                       htmlReady($this->label));
    }
}