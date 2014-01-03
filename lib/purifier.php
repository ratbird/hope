<?php namespace Purifier;
/**
 * purifier.php - Simplified interface to HTML Purifier.
 *
 * This module exports just one function: `purify`. Use it like this:
 *
 *   $clean_html = Purifier\purify($dirty_html).
 *
 **
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category    Stud.IP
 * @copyright   (c) 2013 Stud.IP e.V.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @since       File available since Release 3.0
 * @author      Robert Costa <rcosta@uos.de>
 */
require_once 'vendor/HTMLPurifier/HTMLPurifier.auto.php';
require_once 'utils.php';

/**
 * Remove invalid <img src> attributes.
 */
class AttrTransform_Image_Source extends \HTMLPurifier_AttrTransform {
    /**
     * Implements abstract method of base class.
     */
    function transform($attr, $config, $context) {
        $attr['src'] = \Utils\getMediaUrl($attr['src']);
        return $attr;
    }
}

/**
 * Create HTML purifier instance with Stud.IP-specific configuration.
 * @return HTMLPurifier A new instance of the HTML purifier.
 */
function createPurifier() {
    $config = \HTMLPurifier_Config::createDefault();
    $config->set('Core.Encoding', 'ISO-8859-1');
    $config->set('Core.RemoveInvalidImg', true);
    $config->set('Attr.AllowedFrameTargets', array('_blank'));
    $config->set('Attr.AllowedRel', array('nofollow'));

    // avoid <img src="evil_CSRF_stuff">
    $def = $config->getHTMLDefinition(true);
    $img = $def->addBlankElement('img');
    $img->attr_transform_post[] = new AttrTransform_Image_Source();

    return new \HTMLPurifier($config);
}

/**
 * Call HTMLPurifier to create safe HTML.
 *
 * @param   string $dirty_html  Unsafe or 'uncleaned' HTML code.
 * @return  string              Clean and safe HTML code.
 */
function purify($dirty_html) {
    // remember created purifier so it doesn't have to be created again
    static $purifier = NULL;
    if ($purifier === NULL) {
        $purifier = createPurifier();
    }
    return $purifier->purify($dirty_html);
}
