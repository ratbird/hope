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

    function __construct($id = null)
    {
        $this->db_table = 'termine';
        $this->has_many = array(
            'topics' => array(
                'class_name' => 'CourseTopic',
                /*'assoc_foreign_key' =>
                    function($model,$params) {
                        $model->setValue('range_id', $params[0]->id);
                    },*/
                'assoc_func' => 'findByTermin_id',
                'on_delete' => 'delete',
                'on_store' => 'store',
                /*'foreign_key' =>
                    function($course) {
                        return array($course);
                    }*/
            ),
            'dozenten' => array(
                'class_name' => 'User',
                'assoc_func' => 'findDozentenByTermin_id',
                'on_delete' => 'delete',
                'on_store' => 'store'
            ),
            'groups' => array(
                'class_name' => 'Statusgruppen',
                'assoc_func' => 'findByTermin_id',
                'on_delete' => 'delete',
                'on_store' => 'store'
            )
        );
        parent::__construct($id);
    }
}