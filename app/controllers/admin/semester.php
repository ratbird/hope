<?php
/**
 * semester.php - model class for the semester-administration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Hermann Schröder <hermann.schroeder@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 * @since       Stud.IP version 2.1
 */
require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/SemesterData.class.php';
require_once 'lib/classes/HolidayData.class.php';
require_once 'lib/classes/Semester.class.php';

class Admin_SemesterController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        global $perm;

        parent::before_filter($action, $args);

        # user must have root permission
        $perm->check('root');

        //setting title and navigation
        PageLayout::setTitle(_("Verwaltung von Semestern und Ferien"));
        Navigation::activateItem('/admin/config/semester');

        //Infobox
        $this->infobox = $this->getInfobox();
    }

    /**
     * Display all informations about the semesters and holidays
     */
    public function index_action()
    {
        $this->semesters = SemesterData::getInstance(true)->getAllSemesterData();
        $this->holidays = HolidayData::GetInstance()->getAllHolidays();
    }

    /**
     *
     * @param md5 $id
     * This method deletes holiday and semester
     */
    public function delete_action($id = null, $mode)
    {
        $this->flash['mode'] = $mode;

        //delete semester
        if($mode == "semester") {
            $semester_count = Semester::countAbsolutSeminars($id);
            if ($semester_count > 0) {
                $this->flash['error'] = _("Semester, in denen Veranstaltungen liegen, können nicht gelöscht werden!");
            } else {
                 $this->flash['delete'] = SemesterData::getInstance()->getSemesterData($id);
            }

            //sicherheitsabfrage
            if (Request::get('delete') == 1 && $semester_count == 0) {
                SemesterData::getInstance()->deleteSemester($id);
                $this->flash['success'] = _("Das Semester wurde erfolgreich gelöscht");
            } elseif(Request::get('back')) {
                 unset($this->flash['delete']);
            }
        //delete holiday
        } elseif( $mode == "holiday" ) {
            $this->flash['delete'] = HolidayData::getInstance()->getHolidayData($id);

            //sicherheitsabfrage
            if( Request::get('delete') == 1 ) {
                HolidayData::getInstance()->deleteHoliday($id);
                $this->flash['success'] = _("Die Ferien wurden erfolgreich gelöscht");
            } elseif(Request::get('back')) {
                unset($this->flash['delete']);
            }
        }
        $this->redirect('admin/semester');
    }

    /**
     * This method edits and adds new semester
     * @param md5 $id of a semester
     */
    public function edit_semester_action($id = null)
    {
        if (!is_null($id)) {
            //get infos
            $this->semester = SemesterData::getInstance()->getSemesterData($id);
            if (Semester::countAbsolutSeminars($id) > 0) {
                $this->noteditable = true;
            }

            //save changes
            if (Request::submitted('speichern')) {
                $semester = array(
                    'semester_id' => $id,
                    'name' => Request::get('name'),
                    'description' => Request::get('description'),
                    'semester_token' => $this->semester['semester_token'],
                    'beginn' => $this->getTimeStamp(Request::get('beginn')),
                    'ende' => $this->getTimeStamp(Request::get('ende')),
                    'vorles_beginn' => $this->getTimeStamp(Request::get('vorles_beginn')),
                    'vorles_ende' => $this->getTimeStamp(Request::get('vorles_ende')),
                    'past' => $this->semester['past'],
                    'first_sem_week' => $this->semester['first_sem_week'],
                    'last_sem_week' => $this->semester['last_sem_week']
                );

                //check parameters
                if(!$this->validateSemester($semester)) {
                    $this->message = MessageBox::error(_("Ihre eingegeben Daten sind ungültig."));
                } elseif (!$this->checkOverlap($semester)) {
                    if (SemesterData::getInstance()->updateExistingSemester($semester)) {
                        $this->flash['success'] = _("Das Semester wurde erfolgreich gespeichert.");
                        $this->redirect('admin/semester');
                    } else {
                        $this->message = MessageBox::error(_("Fehler bei der Speicherung Ihrer Daten. Bitte überprüfen Sie Ihre Angaben."));
                    }
                } else {
                    $this->message = MessageBox::error(_("Bitte überprüfen Sie die Zeitangaben, da sie sich mit einem anderen Semester überlappen."));
                }
                $this->semester = $semester;
            }
        }
        // add new semester
        elseif (Request::submitted('anlegen')) {
            $this->semester = array(
                'name' => Request::get('name'),
                'description' => Request::get('description'),
                'beginn' => $this->getTimeStamp(Request::get('beginn')),
                'ende' => $this->getTimeStamp(Request::get('ende')),
                'vorles_beginn' => $this->getTimeStamp(Request::get('vorles_beginn')),
                'vorles_ende' => $this->getTimeStamp(Request::get('vorles_ende')),
            );

            //check parameters
            if(!$this->validateSemester($this->semester)) {
                $this->message = MessageBox::error(_("Ihre eingegeben Daten sind ungültig."));
            } elseif (!$this->checkOverlap($this->semester)) {
                if (SemesterData::getInstance()->insertNewSemester($this->semester)) {
                    $this->flash['success'] = _("Das Semester wurde erfolgreich gespeichert.");
                    $this->redirect('admin/semester');
                } else {
                    $this->message = MessageBox::error(_("Fehler bei der Speicherung Ihrer Daten. Bitte überprüfen Sie Ihre Angaben."));
                }
            } else {
                $this->message = MessageBox::error(_("Bitte überprüfen Sie die Zeitangaben, da sie sich mit einem anderen Semester überlappen."));
            }
        }
    }

    /**
     * This method edits and adds new holidays
     *
     * @param md5 $id of aholiday
     */
    public function edit_holidays_action($id = NULL)
    {
        $this->is_new = true;
        if (!is_null($id) && !Request::submitted('anlegen')) {
            $this->is_new = false;
            $this->holiday = HolidayData::getInstance()->getHolidayData($id);

            //save changes
            if(Request::submitted('speichern')) {
                $holiday = array(
                    'holiday_id' => $id,
                    'name' => Request::get('name'),
                    'description' => Request::get('description'),
                    'beginn' => $this->getTimeStamp(Request::get('beginn')),
                    'ende' => $this->getTimeStamp(Request::get('ende'))
                );

                if($holiday['beginn'] == false || $holiday['ende'] == false
                    || $holiday['name'] == "" || $holiday['beginn'] > $holiday['ende'] ) {
                    $this->message = MessageBox::error(_("Ihre eingegeben Daten sind ungültig."));
                    $this->holiday = $holiday;
                } elseif (HolidayData::getInstance()->updateExistingHoliday($holiday)) {
                    $this->flash['success'] = _("Die Ferien wurden erfolgreich gespeichert.");
                    $this->redirect('admin/semester');
                }
            }
        }

        // add new holiday
        if(Request::submitted('anlegen')) {
            $this->holiday = array(
                'name' => Request::get('name'),
                'description' => Request::get('description'),
                'beginn' => $this->getTimeStamp(Request::get('beginn')),
                'ende' => $this->getTimeStamp(Request::get('ende'))
            );

            if($this->holiday['beginn'] == false || $this->holiday['ende'] == false
                || $this->holiday['name'] == ""
                || $this->holiday['beginn'] > $this->holiday['ende'] ) {
                $this->message = MessageBox::error(_("Ihre eingegeben Daten sind ungültig."));
            } elseif (HolidayData::getInstance()->insertNewHoliday($this->holiday)) {
                $this->flash['success'] = _("Die Ferien wurden erfolgreich gespeichert.");
                $this->redirect('admin/semester');
            }
        }
    }

    /*
     * This method was adopted from the old version.
     * Examination of overlap
     */
    private function checkOverlap($semesterdata)
    {
        $allSemesters = SemesterData::getInstance()->getAllSemesterData();

        foreach ($allSemesters as $semester) {
            if (($semesterdata["beginn"] < $semester["beginn"]) && ($semesterdata["ende"] > $semester["ende"])) {
                if ($semesterdata["semester_id"] != $semester["semester_id"]) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * validate a semesterdata array()
     *
     * @param array() $semester
     * @return boolean
     */
    private function validateSemester($semester)
    {
        if ($semester['beginn'] == false || $semester['ende'] == false
            || $semester['vorles_beginn'] == false
            || $semester['vorles_ende'] == false
            || $semester['name'] == ""
            || $semester['beginn'] > $semester['vorles_beginn']
            || $semester['beginn'] > $semester['ende']
            || $semester['beginn'] > $semester['vorles_ende']
            || $semester['ende'] < $semester['vorles_ende']
            || $semester['ende'] < $semester['beginn']
            || $semester['ende'] < $semester['vorles_beginn']
            || $semester['vorles_ende'] < $semester['vorles_beginn']) {
            return false;
        }
        return true;
    }

    /**
     * checks a string, if it is a valid date
     * @param string $date
     * @return timestamp or false
     */
    private function getTimeStamp($date)
    {
        if (!empty($date)) {
            $date_array = explode('.', $date);
            if (checkdate($date_array[1], $date_array[0], $date_array[2])) {
                return strtotime($date);
            }
        }
        return false;
    }

    /**
     * This method returns an infobox
     */
    private function getInfobox()
    {
        $infobox = array('picture' => 'infobox/board1.jpg');
        $aktionen[] = array(
            "text" => '<a href="'.$this->url_for('admin/semester/edit_semester').'">'._('Neues Semester anlegen').'</a>',
            "icon" => "icons/16/black/plus.png");
        $aktionen[] = array(
            "text" => '<a href="'.$this->url_for('admin/semester/edit_holidays').'">'._('Neue Ferien anlegen').'</a>',
            "icon" => "icons/16/black/plus.png");
        $infobox['content'] = array(
            array(
                'kategorie' => _("Aktionen"),
                'eintrag'   => $aktionen
            ),
            array(
                'kategorie' => _("Information"),
                'eintrag'   => array(
                    array(
                        "text" => _("Auf dieser Seite werden die Semester und Ferien aufgelistet."),
                        "icon" => "icons/16/black/info.png"
                    ),
                    array(
                        "text" => _("Die Daten müssen im Format tt.mm.jjjj eingegeben werden."),
                        "icon" => "icons/16/black/info.png"
                    )
                )
            )
        );
        return $infobox;
    }
}