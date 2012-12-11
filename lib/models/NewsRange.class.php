<?php
/**
 * NewsRange.class.php - model class for table Institute
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2012 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
class NewsRange extends SimpleORMap
{

    function __construct($id = array())
    {
        $this->db_table = 'news_range';
        $this->belongs_to['user'] = array('class_name' => 'User',
                                           'foreign_key' => 'range_id');
        $this->belongs_to['course'] = array('class_name' => 'Course',
                                           'foreign_key' => 'range_id');
        $this->belongs_to['institute'] = array('class_name' => 'Institute',
                                           'foreign_key' => 'range_id');
        $this->additional_fields['type'] = array('get' => 'getType');
        $this->additional_fields['name'] = array('get' => 'getName');
        parent::__construct($id);
    }

    function getType()
    {
        return get_object_type($this->range_id, array('sem','inst','user'));
    }

    function getName()
    {
        switch ($this->type) {
            case 'global':
                return 'Stud.IP';
                break;
            case 'sem':
                return $this->course->name;
                break;
            case 'user':
                return $this->user->getFullname();
                break;
            case 'inst':
            case 'fak':
                return $this->institute->name;
                break;
            }
    }
}
?>