<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author     Rasmus Fuhse <fuhse@data-quest.de>
 * @copyright   2014 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 */

class CourseDate extends SimpleORMap {

    static public function findBySeminar_id($seminar_id)
    {
        return self::findBySQL("range_id = ? ORDER BY date ", array($seminar_id));
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'termine';
        $config['has_many']['topics'] = array(
            'class_name' => 'CourseTopic',
            'assoc_func' => 'findByTermin_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['has_many']['statusgruppen'] = array(
            'class_name' => 'Statusgruppen',
            'assoc_func' => 'findByTermin_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        $config['has_many']['dozenten'] = array(
            'class_name' => 'User',
            'assoc_func' => 'findDozentenByTermin_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        );
        parent::configure($config);
    }

}