<?php

/**
 * DegreeCondition.class.php
 * 
 * All conditions concerning the study degree in Stud.IP can be specified here.
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once(realpath(dirname(__FILE__).'/..').'/UserFilterField.class.php');

class DegreeCondition extends UserFilterField
{
    // --- ATTRIBUTES ---
    public $valuesDbTable = 'abschluss';
    public $valuesDbIdField = 'abschluss_id';
    public $valuesDbNameField = 'name';
    public $userDataDbTable = 'user_studiengang';
    public $userDataDbField = 'abschluss_id';

    /**
     * @see UserFilterField::__construct
     */
    public function __construct($fieldId='') {
        $this->relations = array(
            'SubjectCondition' => array(
                'local_field' => 'studiengang_id',
                'foreign_field' => 'studiengang_id'
            )
        );
        parent::__construct($fieldId);
    }

    /**
     * Get this field's display name.
     *
     * @return String
     */
    public function getName()
    {
        return _("Abschluss");
    }

} /* end of class DegreeCondition */

?>