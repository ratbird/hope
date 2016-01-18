<?php

/**
 * SemesterOfStudyCondition.class.php
 * 
 * All conditions concerning the semester of study in Stud.IP can be specified here.
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

class SemesterOfStudyCondition extends UserFilterField
{
    // --- ATTRIBUTES ---
    public $valuesDbTable = 'user_studiengang';
    public $valuesDbIdField = 'semester';
    public $valuesDbNameField = 'semester';
    public $userDataDbTable = 'user_studiengang';
    public $userDataDbField = 'semester';

    // --- OPERATIONS ---

    /**
     * @see UserFilterField::__construct
     */
    public function __construct($fieldId='') {
        parent::__construct($fieldId);
        $this->validValues = array();
        $this->relations = array(
            'DegreeCondition' => array(
                'local_field' => 'abschluss_id',
                'foreign_field' => 'abschluss_id'
            ),
            'SubjectCondition' => array(
                'local_field' => 'studiengang_id',
                'foreign_field' => 'studiengang_id'
            )
        );
        $this->validCompareOperators = array(
            '>=' => _('mindestens'),
            '<=' => _('höchstens'),
            '=' => _('ist'),
            '!=' => _('ist nicht')
        );
        // Initialize to some value in case there are no semester numbers.
        $maxsem = 15;
        // Calculate the maximal available semester.
        $stmt = DBManager::get()->query("SELECT MAX(".$this->valuesDbIdField.") AS maxsem ".
            "FROM `".$this->valuesDbTable."`");
        if ($current = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($current['maxsem']) {
                $maxsem = $current['maxsem'];
            }
        }
        for ($i=1 ; $i<=$maxsem ; $i++) {
            $this->validValues[$i] = $i;
        }
    }

    /**
     * Get this field's display name.
     *
     * @return String
     */
    public function getName()
    {
        return _("Fachsemester");
    }

    /**
     * Gets the value for the given user that is relevant for this
     * condition field. Here, this method looks up the semester of study 
     * for the user. If the user studies more than one subject, these values
     * can be different for each entry, so as additional context a subject
     * or a degree or both can be given.
     * 
     * @param  String $userId User to check.
     * @param  Array additional conditions that are required for check.
     * @return The value(s) for this user.
     */
    public function getUserValues($userId, $additional=null) {
        $result = array();
        $query = "SELECT DISTINCT `".$this->userDataDbField."` ".
            "FROM `".$this->userDataDbTable."` ".
            "WHERE `user_id`=?"; 
        $parameters = array($userId);
        // Additional requirements given...
        if (is_array($additional)) {
            // .. such as subject of study...
            if ($array['studiengang_id']) {
                $query .= " AND studiengang_id=?";
                $parameters[] = $array['studiengang_id'];
            }
            // ... or degree.
            if ($array['abschluss_id']) {
                $query .= " AND abschluss_id=?";
                $parameters[] = $array['abschluss_id'];
            }
        }
        // Get semester of study for user.
        $stmt = DBManager::get()->prepare($query);
        $stmt->execute($parameters);
        while ($current = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $current[$this->userDataDbField];
        }
        return $result;
    }

} /* end of class SemesterOfStudyCondition */

?>
