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
 * Represents an HTML button element.
 */
class Button extends Interactable
{

    /**
     * Easy factory method to get a Button instance.
     * All parameters are optional.
     *
     * @code
     * echo Button::get();
     * # => <button type="submit" name="ok">ok</button>
     *
     * echo Button::get('yes')
     * # => <button type="submit" name="yes">yes</button>
     *
     * echo Button::get('yes', 'aName')
     * # => <button type="submit" name="aName">yes</button>
     *
     * echo Button::get('yes', array('a' => 1, 'b' => 2))
     * # => <button type="submit" a="1" b="2" name="yes">yes</button>
     *
     * echo Button::get('yes', 'aName', array('a' => 1, 'b' => 2)),
     * # => <button type="submit" a="1" b="2" name="aName">yes</button>
     * @endcode
     *
     * @param string $label      the label of the button
     * @param string $name       the name attribute of the button element
     * @param array  $attributes the attributes of the button element
     */

    function initialize($label, $name, $attributes)
    {
        $this->attributes['name'] = $name ?: $this->label;
    }

    /**
     * @return  returns a HTML representation of this button.
     */
    function __toString()
    {
        $attributes = array();
        ksort($this->attributes);
        foreach ($this->attributes as $k => $v) {
            $attributes[] = sprintf(' %s="%s"', $k, htmlReady($v));
        }

        return sprintf('<button type="submit"%s>%s</button>',
                       join('', $attributes),
                       htmlReady($this->label));
    }
}
