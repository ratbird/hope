
<?php

/**
 * SubjectCondition.class.php
 * 
 * All conditions concerning the study subject in Stud.IP can be specified here.
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

class SubjectCondition extends UserFilterField
{
    // --- ATTRIBUTES ---
    public $valuesDbTable = 'studiengaenge';
    public $valuesDbIdField = 'studiengang_id';
    public $valuesDbNameField = 'name';
    public $userDataDbTable = 'user_studiengang';
    public $userDataDbField = 'studiengang_id';

    // --- OPERATIONS ---

    /**
     * @see UserFilterField::__construct
     */
    public function __construct($fieldId='') {
        $this->relations = array(
            'DegreeCondition' => array(
                'local_field' => 'abschluss_id',
                'foreign_field' => 'abschluss_id'
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
        return _("Studienfach");
    }

} /* end of class SubjectCondition */

?>