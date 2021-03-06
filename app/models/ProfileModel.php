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
     * @return array
     */
    function getDozentSeminars()
    {
        $semester = $courses = array();
        $semester[] = Semester::findNext();
        $semester[] = Semester::findCurrent();
        $semester[] = Semester::findByTimestamp(Semester::findCurrent()->beginn - 1);
        if (Config::get()->IMPORTANT_SEMNUMBER) {
            $field = 'veranstaltungsnummer';
        } else {
            $field = 'name';
        }
        $allcourses = new SimpleCollection(Course::findBySQL("INNER JOIN seminar_user USING(Seminar_id) WHERE user_id=? AND seminar_user.status='dozent' AND seminare.visible=1", array($this->current_user->id)));
        foreach (array_filter($semester) as $one) {
            $courses[$one->name] =
                $allcourses->filter(function ($c) use ($one) {
                    return $c->start_time <= $one->beginn &&
                        ($one->beginn <= ($c->start_time + $c->duration_time) || $c->duration_time == -1);
                })->orderBy($field);
            if (!$courses[$one->name]->count()) {
                unset($courses[$one->name]);
            }
        }
        return $courses;
    }

    /*
     * Get all informations about given user
     *
     * @return array
     */
    function getInstitutInformations()
    {
        $institutes = UserModel::getUserInstitute($this->current_user->user_id);

        foreach ($institutes as $id =>$inst_result) {

            if($inst_result['visible'] == 1) {
                $entries = DataFieldEntry::getDataFieldEntries(array($this->current_user->user_id, $inst_result['Institut_id']));

                if (!empty($entries)) {
                    foreach ($entries as $entry) {
                        $view = $entry->isVisible(null, false);
                        $show_star = false;

                        if (!$view && $entry->isVisible()) {
                            $view = true;
                            $show_star = true;
                        }

                        if (trim($entry->getValue()) && $view) {
                            $institutes[$id]['datafield'][] = array(
                                'name'      => $entry->getName(),
                                'value'     => $entry->getDisplayValue(),
                                'show_star' => $show_star,
                            );
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
            if ($entry->isVisible() && $entry->getDisplayValue()
                && Visibility::verify($entry->getID(), $this->current_user->user_id))
            {
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
            $array[$entry->getName()] = array(
                'content' => $entry->getDisplayValue(),
                'visible' => '(' . $entry->getPermsDescription() . ')',
            );
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
            $array[$entry->getName()] = array(
                'content' => $entry->getDisplayValue(),
                'visible' => '(' . $entry->getPermsDescription() . ')',
            );
        }
        return $array;
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
