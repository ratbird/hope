<?php
/**
 * TrailsInflector.php
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
 * The Inflector class is a namespace for inflections methods.
 *
 */
class TrailsInflector
{

    /**
     * Returns a camelized string from a lower case and underscored string by
     * replacing slash with underscore and upper-casing each letter preceded
     * by an underscore. TODO
     *
     * @param string String to camelize.
     *
     * @return string Camelized string.
     */
    static function camelize($word)
    {
        $parts = explode('/', $word);
        foreach ($parts as $key => $part) {
            $parts[$key] = str_replace(' ', '', ucwords(str_replace('_', ' ', $part)));
        }
        return join('_', $parts);
    }

    /**
     * <MethodDescription>
     *
     * @param type <description>
     *
     * @return type <description>
     */
    static function underscore($word)
    {
        $parts = explode('_', $word);
        foreach ($parts as $key => $part) {
            $parts[$key] = preg_replace('/(?<=\w)([A-Z])/', '_\\1', $part);
        }
        return strtolower(join('/', $parts));
    }
}