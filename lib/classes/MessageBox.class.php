<?php
# Lifter010: TODO
/**
 * MessageBox.class.php
 *
 * html boxes for different kinds of messages
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL Licence 2
 * @category    Stud.IP
 * @package     layout
 * @since       1.10
 *
 */

/**
 * class MessageBox
 *
 * usage:
 *
 * echo MessageBox::error('Nachricht', array('optional details'));
 *
 * use the optional parameter $close_details for displaying the message box with
 * closed details
 *
 * echo MessageBox::success('Nachricht', array('optional details'), true);
 *
 */
class MessageBox
{
    /**
     * type and contents of the message box
     */
    public $class, $message, $details, $close_details;

    /**
     * This function returns an exception message box. Use it only for system errors
     * or security related problems.
     *
     * @param string $message
     * @param array() $details
     * @param boolean $close_details
     * @return object MessageBox object
     */
    public static function exception($message, $details = array(), $close_details = false)
    {
        return new MessageBox('exception', $message, $details, $close_details);
    }

    /**
     * This function returns an error message box. Use it for validation errors,
     * problems and other wrong user input.
     *
     * @param string $message
     * @param array() $details (optional)
     * @param boolean $close_details (optional)
     * @return object MessageBox object
     */
    public static function error($message, $details = array(), $close_details = false)
    {
        return new MessageBox('error', $message, $details, $close_details);
    }

    /**
     * This function returns a success message box. Use it for confirmation of user
     * interaction.
     *
     * @param string $message
     * @param array() $details (optional)
     * @param boolean $close_details (optional)
     * @return object MessageBox object
     */
    public static function success($message, $details = array(), $close_details = false)
    {
        return new MessageBox('success', $message, $details, $close_details);
    }

    /**
     * This function returns an info message box. Use it to report neutral
     * informations.
     *
     * @param string $message
     * @param array() $details (optional)
     * @param boolean $close_details (optional)
     * @return object MessageBox object
     */
    public static function info($message, $details = array(), $close_details = false)
    {
        return new MessageBox('info', $message, $details, $close_details);
    }

    /**
     * Initializes a new MessageBox object of the given class.
     *
     * @param string $class the type of this message
     * @param string $message
     * @param array() $details (optional)
     * @param boolean $close_details (optional)
     */
    protected function __construct($class, $message, $details = array(), $close_details = false)
    {
        $this->class         = $class;
        $this->message       = $message;
        $this->details       = $details;
        $this->close_details = $close_details;
    }

    /**
     * This method renders a MessageBox object to a string.
     *
     * @return string   html output of the message box
     */
    public function __toString()
    {
        $params = array(
            'class'         => $this->class,
            'message'       => $this->message,
            'details'       => $this->details,
            'close_details' => $this->close_details
        );

        return $GLOBALS['template_factory']->render('shared/message_box', $params);
    }
}
