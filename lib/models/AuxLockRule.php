<?php
/**
 * AuxLockRule.php - SORM for the aux data of a seminar
 *
 * Used to filter and sort the datafields of a course member
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       3.0
 * @property string lock_id database column
 * @property string id alias column for lock_id
 * @property string name database column
 * @property string description database column
 * @property string attributes database column
 * @property string sorting database column
 * @property string datafields computed column
 * @property string order computed column
 * @property Course course belongs_to Course
 */
class AuxLockRule extends SimpleORMap
{

    /**
     * Cache to avoid loading datafields for a user more than once
     */
    private $datafieldCache = array();

    protected static function configure()
    {
        $config['db_table'] = 'aux_lock_rules';
        $config['belongs_to'] = array(
            'course' => array(
                'class_name' => 'Course',
                'foreign_key' => 'lock_id',
                'assoc_foreign_key' => 'aux_lock_rule'));
        $config['additional_fields']['datafields'] = true;
        $config['additional_fields']['order'] = true;
        parent::configure($config);
    }

    /**
     * Returns the sorted and filtered datafields of an aux
     *
     * return array datafields as keys
     */
    public function getDatafields()
    {
        $sorting = unserialize($this->sorting);
        foreach (unserialize($this->attributes) as $key => $attr) {
            if (!$attr) {
                unset($sorting[$key]);
            }
        }
        asort($sorting);
        return $sorting;
    }

    /**
     * Updates a datafield of a courseMember by the given data
     *
     * @param object $member
     * @param object $data
     */
    public function updateMember($member, $data)
    {
        foreach ($data as $key => $value) {
            $datafield = current($this->getDatafield($member, $key));
            if ($datafield->isEditable()) {
                $datafield->setValueFromSubmit($value);
                $datafield->store();
            }
        }
    }

    /**
     * Returns an array of all entries of aux data in a course
     *
     * @param string $course if the course wasnt set automaticly by getting called
     * from a course it is possible to set it here
     * @return array formatted entries
     */
    public function getCourseData($course = null, $display_only = false)
    {

        // set course
        if (!$course) {
            $course = $this->course;
        }

        // fetch mapping data
        foreach (SimpleORMapCollection::createFromArray($course->members->findBy('status', 'dozent')->pluck('user')) as $dozent) {
            $dozentenlist[] = $dozent->getFullName();
        }
        $mapping = array(
            'vadozent' => join(', ', $dozentenlist),
            'vasemester' => $course->start_semester->name,
            'vatitle' => $course->name,
            'vanr' => $course->VeranstaltungsNummer,
        );
        $head_mapping = array(
            'vadozent' => _('Dozenten'),
            'vasemester' => _('Semester'),
            'vatitle' => _('Veranstaltungstitel'),
            'vanr' => _('Veranstaltungsnummer'),
        );

        // start collecting entries
        $result['head']['name'] = _('Name');

        // get all autors and users
        foreach ($course->members->findBy('status', array('autor', 'user'))->orderBy('vorname')->orderBy('nachname') as $member) {
            $new['name'] = $member->user->getFullName('full_rev');

            // get all datafields
            foreach ($this->datafields as $field => $useless_value_pls_refactor) {

                // if standard get it from the mapping else get it from the datafield
                if ($mapping[$field]) {
                    $result['head'][$field] = $head_mapping[$field];
                    $new[$field] = $mapping[$field];
                } else {
                    $datafield = $this->getDatafield($member, $field);
                    if ($datafield && current($datafield)->isVisible()) {
                        $result['head'][$field] = key($datafield);
                        if (!$display_only && current($datafield)->isEditable()) {
                            $new[$field] = current($datafield)->getHTML($member->user_id);
                        } else {
                            $new[$field] = current($datafield)->getDisplayValue();
                        }
                    }
                }
            }

            // push the result
            $result['rows'][$member->id] = $new;
        }
        return $result;
    }

    public function getMemberData($member)
    {
        $datafields = SimpleCollection::createFromArray(DatafieldEntryModel::findByModel($member));
        foreach ($this->datafields as $field => $useless_value_pls_refactor) {
            // since we have no only datafields we have to filter!
            if ($new = $datafields->findOneBy('datafield_id', $field)) {
                $result[] = $new;
            }
        }
        return $result;
    }

    /**
     * Caching for the datafields
     * @param type $member
     * @param type $fieldID
     * @return null
     */
    private function getDatafield($member, $fieldID)
    {
        if (!$this->datafieldCache[$member->id]) {
            $this->datafieldCache[$member->id] = SimpleCollection::createFromArray(DatafieldEntryModel::findByModel($member));
        }
        $field = $this->datafieldCache[$member->id]->findOneBy('datafield_id', $fieldID);
        if (!$field) {
            return null;
        }
        return array($field->name => $field->getTypedDatafield());
    }

}
?>
