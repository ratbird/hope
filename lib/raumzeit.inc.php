<?
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
require_once('log_events.inc.php');
require_once('visual.inc.php');

define('DO_NOT_APPEND_MESSAGES', false);
/*
 * Command handlers
 */

function raumzeit_open() {
    global $sd_open;
    $termin = new SingleDate($_REQUEST['open_close_id']);
    if( ($metadate_id = $termin->getMetaDateID()) ){
        $sd_open[$metadate_id] = true;
    }
    $sd_open[$_REQUEST['open_close_id']] = true;
}

function raumzeit_close() {
    global $sd_open;
    $sd_open[$_REQUEST['open_close_id']] = false;
    unset ($sd_open[$_REQUEST['open_close_id']]);
}

function raumzeit_delete_singledate() {
    global $sem;

    $termin = $sem->getSingleDate($_REQUEST['sd_id'], $_REQUEST['cycle_id']);

    // We check we have warnings and need an approval before deleting the date
    $warning = array();

    // does the have issues?
    if ($termin->getIssueIds()) {
        if($GLOBALS["RESOURCES_ENABLE_EXPERT_SCHEDULE_VIEW"]){
            $warning[] = _("Diesem Termin ist im Ablaufplan ein Thema zugeordnet. Titel und Beschreibung des Themas bleiben erhalten und können in der Expertenansicht des Ablaufplans einem anderen Termin wieder zugeordnet werden.");
        } else {
            $warning[] = _("Diesem Termin ist ein Thema zugeordnet.");
        }
    }

    // does the date have a booked room?
    if ($GLOBALS['RESOURCES_ENABLE'] && $termin->hasRoom()) {
        $warning[] = _("Dieser Termin hat eine Raumbuchung, welche mit dem Termin gelöscht wird.");
    }

    // do we have warnings we need approval for?
    if (!$_REQUEST['approveDelete'] && sizeof($warning) > 0) {
            $params = array(
                'cmd' => 'delete_singledate',
                'cycle_id' => $_REQUEST['cycle_id'],
                'sd_id' => $_REQUEST['sd_id'],
                'approveDelete' => 'TRUE'
            );
            echo createQuestion( implode("\n", $warning) . "\n". _("Wollen Sie diesen Termin wirklich löschen?"), $params);
    }

    // no approval needed or already approved
    else {
        // deletion approved, delete show approval-message
        if ($_REQUEST['approveDelete']) {
            if($GLOBALS["RESOURCES_ENABLE_EXPERT_SCHEDULE_VIEW"]){
                $sem->createMessage(sprintf(_("Sie haben den Termin %s gelöscht, dem ein Thema zugeorndet war. Sie können das Thema in der %sExpertenansicht des Ablaufplans%s einem anderen Termin (z.B. einem Ausweichtermin) zuordnen."),
                    $termin->toString(), '<a href="'. URLHelper::getLink('themen.php?cmd=changeViewMode&newFilter=expert') .'">', '</a>'));
            } else {
                $sem->createMessage(sprintf(_("Der Termin %s wurde gelöscht!"), $termin->toString()));
            }
        }

        // no approval needed, delete unquestioned
        else {
            $sem->createMessage(sprintf(_("Der Termin %s wurde gelöscht!"), $termin->toString()));
        }
        $sem->deleteSingleDate($_REQUEST['sd_id'], $_REQUEST['cycle_id']);
    }
}

function raumzeit_undelete_singledate() {
    global $sem;

    $termin = $sem->getSingleDate($_REQUEST['sd_id'], $_REQUEST['cycle_id']);
    $sem->createMessage(sprintf(_("Der Termin %s wurde wiederhergestellt!"), $termin->toString()));
    $sem->unDeleteSingleDate($_REQUEST['sd_id'], $_REQUEST['cycle_id']);
}

function raumzeit_checkboxAction() {
    global $sem, $choosen;
    switch ($_REQUEST['checkboxAction']) {
        case 'chooseAll':
            break;

        case 'chooseNone':
            break;

        case 'invert':
            foreach ($_REQUEST['singledate'] as $val) {
                $choosen[$val] = TRUE;
            }
            break;

        case 'deleteChoosen':
            //TODO: what if deletion leads to an empty regular entry? -> the regular entry should be deleted too
            if (!$_REQUEST['singledate']) break;
            $msg = _("Folgende Termine wurden gelöscht:").'<br>';
            foreach ($_REQUEST['singledate'] as $val) {
                $termin = $sem->getSingleDate($val, $_REQUEST['cycle_id']);
                $msg .= '<li>'.$termin->toString().'<br>';
                unset($termin);
                $sem->deleteSingleDate($val, $_REQUEST['cycle_id']);
            }
            $sem->createMessage($msg);
            break;

        case 'unDeleteChoosen':
            if (!$_REQUEST['singledate']) break;    // if there were no singleDates choosen, stop.
            $msg = _("Folgende Termine wurden wieder hergestellt:").'<br>';
            foreach ($_REQUEST['singledate'] as $val) {
                if ($sem->unDeleteSingleDate($val, $_REQUEST['cycle_id'])) {        // undelete retrieved singleDate
                    $termin = $sem->getSingleDate($val, $_REQUEST['cycle_id']);     // retrieve singleDate
                    $msg .= $termin->toString().'<br>';                                                 // add string representation to message
                    unset($termin);                                                                                             // we never now, if the variable persists...
                }
            }
            $sem->createMessage($msg);
            break;

        case 'deleteAll':
            if ($_REQUEST['cycle_id']) {
                // we are about to delete a regular timeslot
                if ($_REQUEST['approveDeleteAll'] != TRUE) {    // security-question
                    $question = _("Sie haben ausgewählt, alle Termine eines regelmäßigen Eintrages zu löschen. Dies hat zur Folge, dass der regelmäßige Termin ebenfalls gelöscht wird.")
                        . "\n". _("Sind Sie sicher, dass Sie den regelmäßigen Eintrag **\"%s\"** löschen möchten?");
                    $question_time = $sem->metadate->cycles[$_REQUEST['cycle_id']]->toString();
                    $link_params = array (
                        'cmd'              => 'checkboxAction',
                        'checkboxAction'   => 'deleteAll',
                        'cycle_id'         => $_REQUEST['cycle_id'],
                        'approveDeleteAll' => 'TRUE'
                    );

                    echo createQuestion( sprintf( $question, $question_time ), $link_params );

                } else {                                            // deletion approved, so we do the job
                    $msg = sprintf(_("Der regelmäßige Termin \"%s\" wurde gelöscht."), '<b>'.$sem->metadate->cycles[$_REQUEST['cycle_id']]->toString().'</b>');
                    $sem->createMessage($msg);  // create a message
                    $sem->deleteCycle($_REQUEST['cycle_id']);
                }
            } else {
                // we are about to delete all irregular dates
                if ($_REQUEST['approveDeleteAll'] != TRUE) {    // security question
                    $question = _("Sie haben ausgewählt, alle unregelmäßigen Termine dieser Veranstaltung zu löschen.")
                        ."\n". _("Sind Sie sicher, dass Sie alle unregelmäßigen Termine dieser Veranstaltung löschen möchten?");
                    $link_params = array(
                        'cmd' => 'checkboxAction',
                        'checkboxAction' => 'deleteAll',
                        'cycle_id' => $_REQUEST['cycle_id'],
                        'approveDeleteAll' => 'TRUE'
                    );

                    echo createQuestion( $question, $link_params );

                } else {                                    // deletion approved, so we do the job
                    $msg = _("Folgende Termine wurden gelöscht:").'<br>';
                    $singleDates =& $sem->getSingleDates(); // get all irrgeular singleDates of the seminar
                    foreach ($singleDates as $key => $val) {                // walk through each and delete it
                        // TODO: this functionality should be better implemented into the Seminar.class.php
                        $msg .= $val->toString().'<br>';            // add string-representation of the current singleDate to the message
                        unset($sem->irregularSingleDates[$key]);    // we unset that singleDate, otherwise it would show up on the page although deleted already.
                        $val->delete();                                             // delete the singleDate
                    }
                    $sem->createMessage($msg);                          // create a message
                }
            }
            break;

        case 'chooseEvery2nd':
            break;
    }
}

function raumzeit_bookRoom() {
    global  $sem;
    if (!$_REQUEST['singledate']) return;
    $resObj = ResourceObject::Factory($_REQUEST['room']);
    $raum = $resObj->getFormattedLink(TRUE, TRUE, TRUE);
    $termin_count = 0;
    $ex_termin_count = 0;

    if ($_REQUEST['room'] == 'retreat') {
        $msg = _("Für folgende Termine wurde die Raumbuchung aufgehoben:").'<br>';
    } else {
        $msg = sprintf(_("Für folgende Termine wurde der Raum \"%s\" gebucht:"), $raum)."<br>";
    }

    $error_msg = sprintf(_("Für folgende gelöschte Termine wurde Raum \"%s\" nicht gebucht:"), $raum)."<br>";
    foreach ($_REQUEST['singledate'] as $val) {
        $termin = $sem->getSingleDate($val, $_REQUEST['cycle_id']);
        if (!$termin->isExTermin()) {
            if ($sem->bookRoomForSingleDate($val, $_REQUEST['room'], $_REQUEST['cycle_id'])) {
                $termin_count++;
                $msg .= $termin->toString()."<br>";
            }
        } else {
            $error_msg .= $termin->toString()."<br>";
            $ex_termin_count++;
        }
        unset($termin);
    }
    if ($termin_count > 0) {
        $sem->createMessage($msg);
    }

    if (($ex_termin_count > 0 ) && ($_REQUEST['room'] != 'retreat')) {
        $sem->createError($error_msg);
    }
}

function raumzeit_selectSemester() {
    global $sem, $semester;

        if (!$semester) $semester = new SemesterData();

    $start_semester = $_REQUEST['startSemester'];
    $end_semester   = $_REQUEST['endSemester'];

        // The user meant actually to choose "1 Semester"
        if ($start_semester == $end_semester) {
            $end_semester = 0;
        }

    // test, if start semester is before the end semester
    // btw.: end_semester == 0 means a duration of one semester (ja logisch! :) )
    if ($end_semester != 0 && $end_semester != -1 && $start_semester >= $end_semester) {
        $sem->createError(_("Das Startsemester liegt nach dem Endsemester!"));
        return FALSE;
    } else {
        $sem->setStartSemester($_REQUEST['startSemester']);
        $sem->setEndSemester($_REQUEST['endSemester']);
        $sem->removeAndUpdateSingleDates();

            // apply new filter for choosen semester (if necessary)
            $current_semester = $semester->getCurrentSemesterData();

            // If the new duration includes the current semester, we set the semester-chooser to the current semester
            if ($current_semester['beginn'] >= $sem->getStartSemester() && $current_semester['beginn'] <= $sem->getEndSemesterVorlesEnde()) {
                $sem->setFilter($current_semester['beginn']);
            } else {
                // otherwise we set it to the first semester
                $sem->setFilter($sem->getStartSemester());
            }
    }
}

function raumzeit_addCycle() {
    global $sem, $newCycle;
    $sem->createInfo(_("Geben Sie nun unten die Zeiten für den neu zu erstellenden regelmäßigen Termin an!"));
    $newCycle=true;
}

function raumzeit_doAddCycle() {
    global $sem, $newCycle;
    if ($cycle_id = $sem->addCycle($_REQUEST)) {    // the template 'addmetadate.tpl' has form-fields, just passed through here.
        $info = $sem->metadate->cycles[$cycle_id]->toString();
        $sem->createMessage(sprintf(_("Die regelmäßige Veranstaltungszeit \"%s\" wurde hinzugefügt!"),'<b>'.$info.'</b>'));
    } else {
        $sem->createError(_("Die regelmäßige Veranstaltungszeit konnte nicht hinzugefügt werden! Bitte überprüfen Sie Ihre Eingabe."));
        $newCycle = true;
    }
}

function raumzeit_editCycle() {
    global $sem;
    $sem->editCycle($_REQUEST);
}

function raumzeit_deleteCycle() {
    global $sem;
    $question = _("Sind Sie sicher, dass Sie den regelmäßigen Eintrag **\"%s\"** löschen möchten?");
    $question_time = $sem->metadate->cycles[$_REQUEST['cycle_id']]->toString();
    $link_params = array(
        'cmd' => 'doDeleteCycle',
        'cycle_id' => $_REQUEST['cycle_id']
    );

    echo createQuestion( sprintf( $question, $question_time ), $link_params );
}

function raumzeit_doDeleteCycle() {
    global $sem;
    $sem->createMessage(sprintf(_("Der regelm&auml;&szlig;ige Eintrag \"%s\" wurde gel&ouml;scht."), '<b>'.$sem->metadate->cycles[$_REQUEST['cycle_id']]->toString().'</b>'));
    $sem->deleteCycle($_REQUEST['cycle_id']);
}

function raumzeit_doAddSingleDate() {
    global $sem, $cmd;
    
    // check validity of the date
    if (!check_singledate($_REQUEST['day'], $_REQUEST['month'], $_REQUEST['year'], $_REQUEST['start_stunde'],
        $_REQUEST['start_minute'], $_REQUEST['end_stunde'], $_REQUEST['end_minute'])) {
        $sem->createError(_("Bitte geben Sie ein gültiges Datum und eine gültige Uhrzeit an!"));
        $cmd = 'createNewSingleDate';
    }

    // create date
    else {
        $termin = new SingleDate();
        $start = mktime($_REQUEST['start_stunde'], $_REQUEST['start_minute'], 0, $_REQUEST['month'], $_REQUEST['day'], $_REQUEST['year']);
        $ende = mktime($_REQUEST['end_stunde'], $_REQUEST['end_minute'], 0, $_REQUEST['month'], $_REQUEST['day'], $_REQUEST['year']);
        $termin->setTime($start, $ende);
        $termin->setDateType($_REQUEST['dateType']);
        $termin->store();

        if ($start < $sem->filterStart || $ende > $sem->filterEnd) {
            $sem->setFilter('all');
        }
        if (Request::get('room') == 'nothing') {
            $termin->setFreeRoomText(Request::get('freeRoomText'));
            $sem->addSingleDate($termin);
        } else {
            $sem->addSingleDate($termin);
            $sem->bookRoomForSingleDate($termin->getSingleDateID(), Request::get('room'));
        }
        $teachers = $sem->getMembers('dozent');
        foreach (Request::getArray('related_teachers') as $dozent_id) {
            if (in_array($dozent_id, array_keys($teachers))) {
                $termin->addRelatedPerson($dozent_id);
            }
        }
        $sem->createMessage(sprintf(_("Der Termin %s wurde hinzugefügt!"), '<b>'.$termin->toString().'</b>'));
        $sem->store();
    }
}

function raumzeit_editDeletedSingleDate() {
    global $sem, $sd_open, $perm;

    if (!$perm->have_perm('dozent')) {
        $sem->createError(_("Ihnen fehlt die Berechtigung um den Kommentar von gelöschten Terminen zu ändern!"));
        return;
    }

    unset($sd_open[$_REQUEST['singleDateID']]); // we close the choosen singleDate, that it does not happen that we have multiple singleDates open -> could lead to confusion, which singleDate is meant to be edited
    if ($_REQUEST['cycle_id'] != '') {
        // the choosen singleDate is connected to a cycleDate
        $termin =& $sem->getSingleDate($_REQUEST['singleDateID'], $_REQUEST['cycle_id']);
    } else {
        // the choosen singleDate is irregular, so we can edit it directly
        $termin =& $sem->getSingleDate($_REQUEST['singleDateID']);
    }

    $old_comment = $termin->getComment();
    $termin->setComment($_REQUEST['comment']);
    if($termin->getComment() != $old_comment) {
        $sem->createMessage(sprintf(_("Der Kommtentar des gelöschten Termins %s wurde geändert."), '<b>'.$termin->toString().'</b>'));
    } else {
        $sem->createInfo(sprintf(_("Der gelöschte Termin %s wurde nicht verändert."), '<b>'.$termin->toString().'</b>'));
    }

    $termin->store();
}

function raumzeit_editSingleDate() {
    global $sem, $sd_open;    
    if (!Request::submitted("editSingleDate_button")) {
        return;
    }

    // generate time-stamps we can compare directly
    $start = mktime($_REQUEST['start_stunde'], $_REQUEST['start_minute'], 0, $_REQUEST['month'], $_REQUEST['day'], $_REQUEST['year']);
    $ende = mktime($_REQUEST['end_stunde'], $_REQUEST['end_minute'], 0, $_REQUEST['month'], $_REQUEST['day'], $_REQUEST['year']);

    // get request-variables
    $termin_id = Request::get('singleDateID');      // TODO: SQL-Injection?
    $cycle_id  = Request::get('cycle_id', null);

    // we close the chosen singleDate, we do not want to have multiple singleDates open
    //  -> could lead to confusion, which singleDate is meant to be edited
    unset($sd_open[$_REQUEST['singleDateID']]);

    if ($cycle_id) {   // regelmäßiger Termin
        // the choosen singleDate is connected to a cycleDate
        $termin =& $sem->getSingleDate($termin_id, $cycle_id);

        // if we have changed the time of the date, it is not a regular time-slot
        // any more, so we have to move it to the irregularSingleDates of the seminar
        if (($termin->getStartTime() != $start) || ($termin->getEndTime() != $ende)) {
            // duplicate the singledate, move one copy to the seminar's irregular dates,
            // modify the other copy to remain as a regular ex-termin
            $ireg_termin = clone $termin;

            $termin->termin_id = md5(uniqid());
            $termin->setExTermin(true);
            $termin->store();

            $ireg_termin->setTime($start, $ende);
            $ireg_termin->setMetaDateID(null);
            $ireg_termin->store();
            $sem->addSingleDate($ireg_termin);

            // create messages
            $sem->createInfo(sprintf(_('Der Termin %s wurde aus der Liste der regelmäßigen Termine '
                . 'gelöscht und als unregelmäßiger Termin eingetragen, da Sie die Zeiten des Termins '
                . 'verändert haben, so dass dieser Termin nun nicht mehr regelmäßig ist.'),
                '<b>'.$termin->toString().'</b>')
            );

            if ($ireg_termin->getStartTime() < $termin->getStartTime()
                || $ireg_termin->getEndTime() > $termin->getEndTime()) {
                $sem->createInfo(_('Die Raumbuchung für diesen Termin wurde aufgehoben, '
                    . 'da sich der neue Zeitraum außerhalb des Alten befindet!'));
            }
            $sem->appendMessages($ireg_termin->getMessages());

        } else {
            // we did not change the times, so we can edit the regular singleDate
            $sem->bookRoomForSingleDate($termin->getSingleDateID(), $_REQUEST['room_sd'], $cycle_id);
            $termin->setDateType($_REQUEST['dateType']);
            $termin->setFreeRoomText($_REQUEST['freeRoomText_sd']);
            $sem->createMessage(sprintf(_("Der Termin %s wurde geändert!"), '<b>'.$termin->toString().'</b>'));
            $termin->store();
            $sem->appendMessages($termin->getMessages());
        }
        $sem->readSingleDatesForCycle($cycle_id, true);
        $termin->clearRelatedPersons();
        $teachers = $sem->getMembers('dozent');
        foreach (Request::getArray('related_teachers') as $dozent_id) {
            if (in_array($dozent_id, array_keys($teachers))) {
                $termin->addRelatedPerson($dozent_id);
            }
        }
        $termin->store();
    }

    // unregelmäßiger Termin
    else {
        // the choosen singleDate is irregular, so we can edit it directly
        $termin =& $sem->getSingleDate($termin_id);

        $bookRoom = false;
        if ($start >= $termin->date && $ende <= $termin->end_time) {
            $bookRoom = true;
        } else {
            if (!$_REQUEST['approveChange'] && $termin->hasRoom()) {
                $zw_termin = new SingleDate();
                $zw_termin->date = $start;
                $zw_termin->end_time = $ende;

                // parameters to be resent on positive answer
                foreach (words('day month year start_stunde start_minute end_stunde '
                    . 'end_minute related_teachers room_sd freeRoomText_sd dateType cmd '
                    . 'singleDateID cycle_id') as $param) {
                    $url_params[$param] = $_REQUEST[$param];
                }

                $url_params['approveChange'] = true;
                $url_params['editSingleDate_button'] = true;

                echo createQuestion( sprintf(_("Wenn Sie den Termin am %s auf %s ändern,".
                        " verlieren Sie die Raumbuchung. Sind Sie sicher, dass Sie diesen Termin ändern möchten?"),
                        '**'. $termin->toString() .'**',  '**'. $zw_termin->toString() .'**'),
                        $url_params);

                unset($zw_termin);
                return;
            }
        }

        if ( $termin->setTime($start, $ende)
            || $termin->getFreeRoomText() != $_REQUEST['freeRoomText_sd']
            || $termin->getDateType != $_REQUEST['dateType'] ) {

            $termin->setDateType($_REQUEST['dateType']);
            $termin->setFreeRoomText($_REQUEST['freeRoomText_sd']);
            $termin->clearRelatedPersons();
            $teachers = $sem->getMembers('dozent');
            foreach (Request::getArray('related_teachers') as $dozent_id) {
                if (in_array($dozent_id, array_keys($teachers))) {
                    $termin->addRelatedPerson($dozent_id);
                }
            }
            $termin->store();
            if ($bookRoom) {
                $sem->bookRoomForSingleDate($termin_id, $_REQUEST['room_sd']);
            } else {
                $termin->killAssign();
                $sem->createInfo(sprintf(_("Die Raumbuchung für den Termin %s wurde aufgehoben, da die neuen Zeiten außerhalb der Alten liegen!"), '<b>'. $termin->toString() .'</b>'));
            }
            $sem->createMessage(sprintf(_("Der Termin %s wurde geändert!"), '<b>'.$termin->toString().'</b>'));
        }
        $sem->appendMessages($termin->getMessages());
    }
}


function raumzeit_freeText() {
    global $sem, $sd_open;
    if (is_array($_REQUEST['singledate'])) {
        foreach($_REQUEST['singledate'] as $termin_id)
        {
            if ($_REQUEST['cycle_id'] != '') {
                $termin = $sem->getSingleDate($termin_id, $_REQUEST['cycle_id']);
                $sem->bookRoomForSingleDate($termin_id, $_REQUEST['room'], $_REQUEST['cycle_id']);
                $sem->metadate->cycles[$_REQUEST['cycle_id']]->termine = null;
            } else {
                $termin = $sem->getSingleDate($termin_id);
                $sem->bookRoomForSingleDate($termin_id, $_REQUEST['room']);
                $sem->irregularSingleDates = null;
            }
            if ($termin->setTime($start, $ende) //äh $start, $ende? woher?
                    || $termin->getFreeRoomText()!=$_REQUEST['freeRoomText']
                    || $termin->getDateType!=$_REQUEST['dateType'] ) {

                $termin->setFreeRoomText($_REQUEST['freeRoomText']);
                $termin->store();
                $sem->createMessage(sprintf(_("Der Termin %s wurde geändert!"), '<b>'.$termin->toString().'</b>'));
            }
            $sem->appendMessages($termin->getMessages());
        }
    } else {
        $sem->createInfo(_("Sie haben keinen Termin ausgewählt!"));
    }
}

function raumzeit_removeRequest() {
    global $sem;

    $termin =& $sem->getSingleDate($_REQUEST['singleDateID'], $_REQUEST['cycle_id']);
    // logging >>>>>>
    log_event("SEM_DELETE_SINGLEDATE_REQUEST", $sem->getId(), $termin->toString());
    // logging <<<<<<
    $termin->removeRequest();
    $sem->createMessage(sprintf(_("Die Raumanfrage für den Termin %s wurde gelöscht."), $termin->toString()));
}

function raumzeit_removeSeminarRequest() {
    global $sem;
    $sem->removeSeminarRequest();
    $sem->createMessage(sprintf(_("Die Raumanfrage für die Veranstaltung wurde gelöscht.")));
}

function raumzeit_MoveCycle() {
    global $sem;
    $cycle_id = Request::option('cycle_id');
    $direction = Request::option('direction');
    $cycles = $sem->getCycles();
    $cycle_ids = array_keys($cycles);
    $pos = array_search($cycle_id, $cycle_ids);
    if ($direction == "up"){
        if($pos > 0){
            $cycle_ids[$pos] = $cycle_ids[$pos - 1];
            $cycle_ids[$pos - 1] = $cycle_id;
        } else {
            $cycle_ids[] = array_shift($cycle_ids);
        }
    } elseif (isset($cycle_ids[$pos + 1])){
        $cycle_ids[$pos] = $cycle_ids[$pos + 1];
        $cycle_ids[$pos + 1] = $cycle_id;
    } else {
        array_unshift($cycle_ids, array_pop($cycle_ids));
    }
    foreach ($cycle_ids as $sort => $id) {
        $cycles[$id]->sorter = $sort;
    }
    $sem->metadate->sortCycleData();
    $sem->createMessage(_("Die regelmäßigen Zeiten wurden neu geordnet."));
}

/**
 * Adds/deletes or updates (sets) the related persons of a given date.
 * This function is called from seminar-class, when something has changed in a
 * single- or metadate. It needs request-params to do something.
 * @return boolean true if something changed and false if nothing happened
 */
function raumzeit_related_persons_action_do() {
    global $sem;
    $singledates = Request::getArray('singledate');
    $persons = Request::getArray('related_persons');
    $action = Request::get('related_persons_action');
    $something_done = false;
    if (in_array($action, array('add', 'delete'))) {
        foreach ($singledates as $singledate) {
            $singledate = new SingleDate($singledate);
            if ($singledate->getSeminarID() === $sem->getId()) {
                foreach ($persons as $user_id) {
                    $singledate->{$action."RelatedPerson"}($user_id);
                    $something_done = true;
                }
            }
            $singledate->store();
        }
    } elseif($action === "set") {
        foreach ($singledates as $singledate) {
            $singledate = new SingleDate($singledate);
            if ($singledate->getSeminarID() === $sem->getId()) {
                $singledate->clearRelatedPersons();
                foreach ($persons as $user_id) {
                    $singledate->addRelatedPerson($user_id);
                    $something_done = true;
                }
            }
            $singledate->store();
        }
    }
    if ($something_done) {
        $sem->createMessage(_("Zuständige Personen für die Termine wurden geändert."));
    } else {
        $sem->createInfo(_("An den Zuordnungen von Personen zu Terminen hat sich nichts geändert."));
    }
    return $something_done;
}

