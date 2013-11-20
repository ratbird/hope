<?php
/**
 * ProfileModel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      David Siegfried <david.siegfried@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

class ProfileModel
{
    protected $perm;
    /**
     * Internal current selected user id
     * @var String
     */
    protected $current_user;

    /**
     * Internal current logged in user id
     * @var String
     */
    protected $user;

    /**
     * Internal user homepage visbilities
     * @var array
     */
    protected $visibilities;

    /**
     * Get informations on depending selected user
     * @param String $current_user
     * @param String $user
     */
    function __construct($current_user, $user)
    {
        $this->current_user = User::find($current_user);
        $this->user         = User::find($user);
        $this->visibilities = $this->getHomepageVisibilities();
        $this->perm         = $GLOBALS['perm'];
    }

    /**
     * Get the homepagevisibilities
     *
     * @return array
     */
    function getHomepageVisibilities()
    {
        $visibilities = get_local_visibility_by_id($this->current_user->user_id, 'homepage');
        if (is_array(json_decode($visibilities, true))) {
            return json_decode($visibilities, true);
        }
        return array();
    }

    /**
     * Checks the visibility value
     *
     * @return boolean
     */
    function checkVisibility($param)
    {
        return Visibility::verify($param, $this->current_user->user_id);
    }

    /**
     * Returns the visibility value
     *
     * @return String
     */
    function getVisibilityValue($param, $visibility = '')
    {
        if (Visibility::verify($visibility ?: $param, $this->current_user->user_id)) {
            return $this->current_user->$param;
        }
        return false;
    }

    /**
     * Returns a specific value of the visibilies
     * @param String $param
     * @return String
     */

    function getSpecificVisibilityValue($param) {
        if (!empty($this->visibilities[$param])) {
            return $this->visibilities[$param];
        }
        return false;
    }

    /**
     * Creates an array with all seminars
     *
     * @uses DbView
     * @return array
     */
    function getDozentSeminars()
    {
        $all_semester           = SemesterData::GetSemesterArray();
        $current_semester_index = SemesterData::GetInstance()->GetSemesterIndexById(Semester::findCurrent()->semester_id);

        if ($current_semester_index && isset($all_semester[$current_semester_index + 1])) {
            $start_semester_index = $current_semester_index + 1;
        } else {
            $start_semester_index = count($all_semester) - 1;
        }

        $view = new DbView();
        $seminare = array();
        for ($i = $start_semester_index; $i > $start_semester_index - 3; --$i) {
            $view->params[0] = $this->current_user->user_id;
            $view->params[1] = 'dozent';
            $view->params[2] = " HAVING (sem_number <= $i AND (sem_number_end >= $i OR sem_number_end = -1)) ";
            $snap = new DbSnapshot($view->get_query("view:SEM_USER_GET_SEM"));

            if ($snap->numRows) {
                $sem_name = $all_semester[$i]['name'];
                $snap->sortRows('Name');

                while ($snap->nextRow()) {
                    $ver_name = $snap->getField('Name');
                    $sem_number_start = $snap->getField('sem_number');
                    $sem_number_end = $snap->getField('sem_number_end');

                    if ($sem_number_start != $sem_number_end) {
                        $ver_name .= ' (' . $all_semester[$sem_number_start]['name'] . ' - ';
                        $ver_name .= (($sem_number_end == -1) ? _('unbegrenzt') : $all_semester[$sem_number_end]['name']) . ')';
                    }

                    $seminare[$sem_name][$snap->getField('Seminar_id')] = $ver_name;
                }
            }
        }

        return $seminare;
    }

    /*
     * Get all informations about given user
     *
     * @return array
     */
    function getInstitutInformations()
    {
        $institutes = UserModel::getUserInstitute($this->current_user->user_id);

        foreach($institutes as $id =>$inst_result) {

            if($inst_result['visible'] == 1) {
                $entries = DataFieldEntry::getDataFieldEntries(array($this->current_user->user_id, $inst_result['Institut_id']));

                if (!empty($entries)) {
                    foreach ($entries as $entry) {
                        $perms = $entry->structure->getViewPerms();

                        if($perms) {
                            $view = DataFieldStructure::permMask($this->user->perms) >= DataFieldStructure::permMask($perms);
                            $show_star = false;

                            if (!$view && ($this->current_user->user_id == $this->user->user_id)) {
                                $view = true;
                                $show_star = true;
                            }

                            if (trim($entry->getValue()) && $view) {
                                $institutes[$id]['datafield'][] = array(
                                    'name'  => $entry->getName(),
                                    'value' => $entry->getDisplayValue()
                                );

                                if ($show_star) $institutes[$id]['datafield'][]['show_star'] = true;
                            }
                        }
                    }
                }
                $institutes[$id]['role'] = Statusgruppen::getUserRoles($inst_result['Institut_id'], $this->current_user->user_id);                
            } else {
                unset($institutes[$id]);
            }
        }

        return $institutes;
    }

    /**
     * Collect user datafield informations
     *
     * @return array
     */
    function getDatafields()
    {
        // generische Datenfelder aufsammeln
        $short_datafields = array();
        $long_datafields  = array();
        foreach (DataFieldEntry::getDataFieldEntries($this->current_user->user_id, 'user') as $entry) {
            if (($entry->structure->accessAllowed($this->perm, $this->user->user_id, $this->current_user->user_id)
                    && Visibility::verify($entry->structure->getID(), $this->current_user->user_id))
                            && $entry->getDisplayValue()) {
                if ($entry instanceof DataFieldTextareaEntry) {
                    $long_datafields[] = $entry;
                } else {
                    $short_datafields[] = $entry;
                }
            }
        }

        return array(
            'long'  => $long_datafields,
            'short' => $short_datafields,
        );
    }

    /**
     * Filter long datafiels from the datafields
     *
     * @return array
     */
    function getLongDatafields()
    {
        $datafields = $this->getDatafields();
        $array      = array();

        if (empty($datafields)) {
            return null;
        }

        foreach ($datafields['long'] as $entry) {
            if ($this->checkVisibility($entry->getName())) {
                $vperms = $entry->structure->getViewPerms();
                $visible = ('all' == $vperms)
                         ? '(' . _('sichtbar für alle') . ')'
                         : '(' . sprintf(_('sichtbar nur für Sie und alle %s'), $this->prettyViewPermString($vperms)) . ')';
                $array[$entry->getName()]['content'] = $entry->getDisplayValue();
                $array[$entry->getName()]['visible'] = $visible;
            }
        }

        return $array;
    }

    /**
     * Filter short datafiels from the datafields
     *
     * @return array
     */
    function getShortDatafields()
    {
        $shortDatafields = $this->getDatafields();
        $array = array();

        if (empty($shortDatafields)) {
            return null;
        }

        foreach ($shortDatafields['short'] as $entry) {
            $vperms = $entry->structure->getViewPerms();
            $visible = ('all' == $vperms)
                     ? '(' . _('sichtbar für alle') . ')'
                     : '(' . sprintf(_('sichtbar nur für Sie und alle %s'), $this->prettyViewPermString($vperms)) . ')';
            $array[$entry->getName()] = array(
                'content' => $entry->getDisplayValue(),
                'visible' => $visible,
            );
        }
        return $array;
    }

    /**
     * Generates a full status description depending on the the perms
     *
     * @param String $viewPerms
     * @return string
     */
    function prettyViewPermString ($viewPerms)
    {
        switch ($viewPerms) {
            case 'all':
                return _('alle');
                break;
            case 'root':
                return _('SystemadministratorInnen');
                break;
            case 'admin':
                return _('AdministratorInnen');
                break;
            case 'dozent':
                return _('DozentInnen');
                break;
            case 'tutor':
                return _('TutorInnen');
                break;
            case 'autor':
                return _('Studierenden');
                break;
            case 'user':
                return _('NutzerInnen');
                break;
        }
        return '';
    }


    /**
     * Get the decorated StudIP-Kings information
     * @return String
     */
    function getKingsInformations()
    {
        $is_king = StudipKing::is_king($this->current_user->user_id, TRUE);

        $result = '';
        foreach ($is_king as $type => $text) {
            $type = str_replace('_', '-', $type);
            $result .= Assets::img("crowns/crown-$type.png", array('alt' => $text, 'title' => $text));
        }

        return $result ?: null;
    }
}
