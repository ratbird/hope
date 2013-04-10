<?php
# Lifter010: TODO
/**
 * semester.php - model class for the semester-administration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Hermann Schr�der <hermann.schroeder@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 * @since       2.1
 */
require_once 'app/controllers/authenticated_controller.php';

class Admin_SemesterController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        parent::before_filter($action, $args);

        // ajax
        if (@$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            $this->via_ajax = true;
            $this->set_layout(null);
        }

        # user must have root permission
        $GLOBALS['perm']->check('root');

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
        $this->semesters = Semester::getAll(true);
        $this->holidays = SemesterHoliday::getAll(true);
    }

    /**
     * This method deletes holiday and semester
     * @param md5 $id
     */
    public function delete_action($id = null, $mode)
    {
        $this->flash['mode'] = $mode;

        if(!Request::get('back')) {
            //delete semester
            if($mode == "semester") {
                $semester_count = Semester::countAbsolutSeminars($id);
                if ($semester_count > 0) {
                    PageLayout::postMessage(MessageBox::error(_("Semester, in denen Veranstaltungen liegen, k�nnen nicht gel�scht werden!")));
                } else {
                     $this->flash['delete'] = Semester::find($id);
                }

                //sicherheitsabfrage
                if (Request::get('delete') == 1 && $semester_count == 0) {
                    if (($semester = Semester::find($id)) && $semester->delete()) {
                        PageLayout::postMessage(MessageBox::success(_("Das Semester wurde erfolgreich gel�scht")));
                    }
                    $this->flash->discard();
                }
            //delete holiday
            } elseif( $mode == "holiday" ) {
                $this->flash['delete'] = SemesterHoliday::find($id);

                //sicherheitsabfrage
                if( Request::get('delete') == 1 ) {
                    if (($holiday = SemesterHoliday::find($id)) && $holiday->delete()) {
                        PageLayout::postMessage(MessageBox::success(_("Die Ferien wurden erfolgreich gel�scht")));
                    }
                    $this->flash->discard();
                }
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
        $this->response->add_header('Content-Type', 'text/html; charset=windows-1252');
        if (!is_null($id)) {
            //get infos
            $this->semester = Semester::find($id);
            if (Semester::countAbsolutSeminars($id) > 0) {
                $this->noteditable = true;
            }

            //save changes
            if (Request::submitted('speichern')) {
                $semester = Semester::find($id);
                $data = array(
                    'name' => Request::get('name', $semester->name),
                    'description' => Request::get('description', $semester->description),
                    'semester_token' => $semester->semester_token,
                    'beginn' => Request::get('beginn') ? $this->getTimeStamp(Request::get('beginn')) : $semester->beginn,
                    'ende' => Request::get('ende') ? $this->getTimeStamp(Request::get('ende'), '23:59:59') : $semester->ende,
                    'vorles_beginn' => Request::get('vorles_beginn') ? $this->getTimeStamp(Request::get('vorles_beginn')) : $semester->vorles_beginn,
                    'vorles_ende' => Request::get('vorles_ende') ? $this->getTimeStamp(Request::get('vorles_ende'), '23:59:59') : $semester->vorles_ende
                );
                $semester->setData($data);
                //check parameters
                if(!$this->validateSemester($semester)) {
                    PageLayout::postMessage(MessageBox::error(_("Ihre eingegebenen Daten sind ung�ltig.")));
                } elseif (!$this->checkOverlap($semester)) {
                    if (($ok = $semester->store()) !== false) {
                        if ($ok > 0) {
                            PageLayout::postMessage(MessageBox::success(_("Das Semester wurde erfolgreich gespeichert.")));
                        }
                        $this->redirect('admin/semester');
                    } else {
                        PageLayout::postMessage(MessageBox::error(_("Fehler bei der Speicherung Ihrer Daten. Bitte �berpr�fen Sie Ihre Angaben.")));
                    }
                } else {
                    PageLayout::postMessage(MessageBox::error(_("Bitte �berpr�fen Sie die Zeitangaben, da sie sich mit einem anderen Semester �berlappen.")));
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
                'ende' => $this->getTimeStamp(Request::get('ende'), '23:59:59'),
                'vorles_beginn' => $this->getTimeStamp(Request::get('vorles_beginn')),
                'vorles_ende' => $this->getTimeStamp(Request::get('vorles_ende'), '23:59:59'),
            );

            //check parameters
            if(!$this->validateSemester($this->semester)) {
                PageLayout::postMessage(MessageBox::error(_("Ihre eingegebenen Daten sind ung�ltig.")));
            } elseif (!$this->checkOverlap($this->semester)) {
                $semester = new Semester();
                $semester->setData($this->semester);
                if ($semester->store()) {
                     PageLayout::postMessage(MessageBox::success(_("Das Semester wurde erfolgreich gespeichert.")));
                    $this->redirect('admin/semester');
                } else {
                    PageLayout::postMessage(MessageBox::error(_("Fehler bei der Speicherung Ihrer Daten. Bitte �berpr�fen Sie Ihre Angaben.")));
                }
            } else {
                PageLayout::postMessage(MessageBox::error(_("Bitte �berpr�fen Sie die Zeitangaben, da sie sich mit einem anderen Semester �berlappen.")));
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
            $this->holiday = SemesterHoliday::find($id);

            //save changes
            if(Request::submitted('speichern')) {
                $holiday = array(
                    'name' => Request::get('name'),
                    'description' => Request::get('description'),
                    'beginn' => $this->getTimeStamp(Request::get('beginn')),
                    'ende' => $this->getTimeStamp(Request::get('ende'), '23:59:59')
                );
                $this->holiday->setData($holiday);
                if($holiday['beginn'] == false || $holiday['ende'] == false
                    || $holiday['name'] == "" || $holiday['beginn'] > $holiday['ende'] ) {

                    $details = array();
                    if ($holiday['beginn'] == false) {
                        $details[] = _("Bitte geben Sie einen Ferienbeginn ein.");
                    }
                    if ($holiday['ende'] == false) {
                        $details[] = _("Bitte geben Sie ein Ferienende ein.");
                    }
                    if ($holiday['name'] == "") {
                        $details[] = _("Bitte geben Sie einen Namen ein.");
                    }
                    if ($holiday['beginn'] > $holiday['ende']) {
                        $details[] = _("Das Ferienende liegt vor dem Beginn.");
                    }
                    PageLayout::postMessage(MessageBox::error(_("Ihre eingegebenen Daten sind ung�ltig."), $details));

                } elseif ($this->holiday->store() !== false) {
                    PageLayout::postMessage(MessageBox::success(_("Die Ferien wurden erfolgreich gespeichert.")));
                    $this->redirect('admin/semester');
                }
            }
        }

        // add new holiday
        if(Request::submitted('anlegen')) {
            $holiday = array(
                'name' => Request::get('name'),
                'description' => Request::get('description'),
                'beginn' => $this->getTimeStamp(Request::get('beginn')),
                'ende' => $this->getTimeStamp(Request::get('ende'), '23:59:59')
            );
            $this->holiday = new SemesterHoliday();
            $this->holiday->setData($holiday);
            if($this->holiday['beginn'] == false || $this->holiday['ende'] == false
                || $this->holiday['name'] == ""
                || $this->holiday['beginn'] > $this->holiday['ende'] ) {
                $details = array();
                if ($holiday['beginn'] == false) {
                    $details[] = _("Bitte geben Sie einen Ferienbeginn ein.");
                }
                if ($holiday['ende'] == false) {
                    $details[] = _("Bitte geben Sie ein Ferienende ein.");
                }
                if ($holiday['name'] == "") {
                    $details[] = _("Bitte geben Sie einen Namen ein.");
                }
                if ($holiday['beginn'] > $holiday['ende']) {
                    $details[] = _("Das Ferienende liegt vor dem Beginn.");
                }
                PageLayout::postMessage(MessageBox::error(_("Ihre eingegebenen Daten sind ung�ltig."), $details));
            } elseif ($this->holiday->store()) {
                PageLayout::postMessage(MessageBox::success(_("Die Ferien wurden erfolgreich gespeichert.")));
                $this->redirect('admin/semester');
            }
        }
    }

    /**
     * This method was adopted from the old version.
     * Examination of overlap
     *
     * @param array() $semesterdata
     * @return bool
     */
    private function checkOverlap($semesterdata)
    {
        $allSemesters = Semester::getAll();

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
     * Validate a semesterdata array()
     *
     * @param array() $semester
     * @return bool
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
    private function getTimeStamp($date, $time = '')
    {
        if (!empty($date)) {
            $date_array = explode('.', $date);
            if (checkdate($date_array[1], $date_array[0], $date_array[2])) {
                return strtotime($date . ' ' . $time);
            }
        }
        return false;
    }

    /**
     * Return the infobox for this controller.
     */
    private function getInfobox()
    {
        $infobox = array('picture' => 'infobox/board1.jpg');
        $aktionen[] = array(
            "text" => '<a href="'.$this->url_for('admin/semester/edit_semester').'">'._('Neues Semester anlegen').'</a>',
            "icon" => "icons/16/black/add.png");
        $aktionen[] = array(
            "text" => '<a href="'.$this->url_for('admin/semester/edit_holidays').'">'._('Neue Ferien anlegen').'</a>',
            "icon" => "icons/16/black/add.png");
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
                        "text" => _("Die Daten m�ssen im Format tt.mm.jjjj eingegeben werden."),
                        "icon" => "icons/16/black/info.png"
                    ),
                    array(
                        "text" => _("Das Startdatum kann nur bei Semestern ge�ndert werden, in denen keine Veranstaltungen liegen!"),
                        "icon" => "icons/16/black/info.png"
                    )
                )
            )
        );
        return $infobox;
    }
}