<?php
/**
 * StudipCoreFormat.php - Parser for core Stud.IP markup based on
 * {@link StudipFormat}. Markup from plugins is not support by this
 * parser.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Sebastian Hobert
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
class StudipCoreFormat extends TextFormat
{
    /**
     * list of core Stud.IP markup
     */
    private static $whitelist = array(
        'heading',
        'hrule',
        'list',
        'table',
        'indent',
        'bold',
        'simple_bold',
        'italics',
        'simple_italics',
        'underline',
        'simple_underline',
        'verb',
        'simple_verb',
        'big',
        'simple_big',
        'small',
        'simple_small',
        'super',
        'simple_super',
        'sub',
        'simple_sub',
        'strike',
        'admin_msg',
        'pre',
        'quote',
        'nop',
        'code',
        'media',
        'emails',
        'htmlAnchor',
        'htmlImg',
        'links'
    );
    
    /**
     * list of core Stud.IP markup 
     */
    private static $core_studip_rules = array();

    /**
     * Returns a list of all core markups. 
     *
     * @return array list of rules
     */
    public static function getCoreStudipMarkup() {
        if (empty(StudipFormat::$core_studip_rules)) {
            $studip_rules = StudipFormat::getStudipMarkups();
            
            foreach (self::$whitelist as $w) {
                self::$core_studip_rules[$w] = $studip_rules[$w];
            }
        }
        return self::$core_studip_rules;
    }

    /**
     * Initializes a new StudipCoreFormat instance.
     */
    public function __construct()
    {
        parent::__construct(self::getCoreStudipMarkup());
    }
}
