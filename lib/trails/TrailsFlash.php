<?php
/**
 * TrailsFlash.php
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author      Marcus Lunzenauer <mlunzena@uos.de>
 * @copyright   2007 (c) Authors
 * @category    Stud.IP
 * @package     trails
 */

/**
 * The flash provides a way to pass temporary objects between actions.
 * Anything you place in the flash will be exposed to the very next action and
 * then cleared out. This is a great way of doing notices and alerts, such as
 * a create action that sets
 * <tt>$flash->set('notice', "Successfully created")</tt>
 * before redirecting to a display action that can then expose the flash to its
 * template.
 */
class TrailsFlash implements ArrayAccess
{
    /**
     * @ignore
     */
    public $flash = array(), $used = array();

    /**
     * <MethodDescription>
     *
     * @return type       <description>
     */
    static function instance()
    {
        if (! isset($_SESSION)) {
            throw new TrailsSessionRequiredException();
        }
        if (! isset($_SESSION['trails_flash'])) {
            $_SESSION['trails_flash'] = new TrailsFlash();
        }
        return $_SESSION['trails_flash'];
    }

    function offsetExists($offset)
    {
        return isset($this->flash[$offset]);
    }

    function offsetGet($offset)
    {
        return $this->get($offset);
    }

    function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    function offsetUnset($offset)
    {
        unset($this->flash[$offset],
        $this->used[$offset]);
    }

    /**
     * Used internally by the <tt>keep</tt> and <tt>discard</tt> methods
     * use()               # marks the entire flash as used
     * use('msg')          # marks the "msg" entry as used
     * use(null, false)    # marks the entire flash as unused
     * # (keeps it around for one more action)
     * use('msg', false)   # marks the "msg" entry as unused
     * # (keeps it around for one more action)
     *
     * @param mixed  a key.
     * @param bool   used flag.
     *
     * @return void
     */
    function _use($k = NULL, $v = TRUE)
    {
        if ($k) {
            $this->used[$k] = $v;
        } else {
            foreach ($this->used as $k => $value) {
                $this->_use($k, $v);
            }
        }
    }

    /**
     * Marks the entire flash or a single flash entry to be discarded by the end
     * of the current action.
     *
     * $flash->discard()             # discards entire flash
     * # (it'll still be available for the
     * # current action)
     * $flash->discard('warning')    # discard the "warning" entry
     * # (it'll still be available for the
     * # current action)
     *
     * @param mixed  a key.
     *
     * @return void
     */
    function discard($k = NULL)
    {
        $this->_use($k);
    }

    /**
     * Returns the value to the specified key.
     *
     * @param mixed  a key.
     *
     * @return mixed the key's value.
     */
    function &get($k)
    {
        $return = NULL;
        if (isset($this->flash[$k])) {
            $return = & $this->flash[$k];
        }
        return $return;
    }

    /**
     * Keeps either the entire current flash or a specific flash entry available
     * for the next action:
     *
     * $flash->keep()           # keeps the entire flash
     * $flash->keep('notice')   # keeps only the "notice" entry, the rest of
     * # the flash is discarded
     *
     * @param mixed  a key.
     *
     * @return void
     */
    function keep($k = NULL)
    {
        $this->_use($k, FALSE);
    }

    /**
     * Sets a key's value.
     *
     * @param mixed  a key.
     * @param mixed  its value.
     *
     * @return void
     */
    function set($k, $v)
    {
        $this->keep($k);
        $this->flash[$k] = $v;
    }

    /**
     * Sets a key's value by reference.
     *
     * @param mixed  a key.
     * @param mixed  its value.
     *
     * @return void
     */
    function set_ref($k, &$v)
    {
        $this->keep($k);
        $this->flash[$k] = & $v;
    }

    /**
     * <MethodDescription>
     *
     * @return type       <description>
     */
    function sweep()
    {
        # remove used values
        foreach (array_keys($this->flash) as $k) {
            if ($this->used[$k]) {
                unset($this->flash[$k], $this->used[$k]);
            } else {
                $this->_use($k);
            }
        }
        # cleanup if someone meddled with flash or used
        $fkeys = array_keys($this->flash);
        $ukeys = array_keys($this->used);
        foreach (array_diff($fkeys, $ukeys) as $k => $v) {
            unset($this->used[$k]);
        }
    }

    /**
     * <MethodDescription>
     *
     * @return type       <description>
     */
    function __toString()
    {
        $values = array();
        foreach ($this->flash as $k => $v) {
            $values[] = sprintf("'%s': [%s, '%s']", $k, var_export($v, TRUE), $this->used[$k] ? "used" : "unused");
        }
        return "{" . join(", ", $values) . "}\n";
    }

    /**
     * <MethodDescription>
     *
     * @param  type       <description>
     *
     * @return type       <description>
     */
    function __sleep()
    {
        $this->sweep();
        return array('flash', 'used');
    }

    /**
     * <MethodDescription>
     *
     * @param  type       <description>
     *
     * @return type       <description>
     */
    function __wakeUp()
    {
        $this->discard();
    }
}