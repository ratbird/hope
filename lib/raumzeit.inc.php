<?
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
require_once('log_events.inc.php');
require_once('visual.inc.php');

/*
 * Command handlers
 */

function raumzeit_delete_singledate($sem) {
    $termin = $sem->getSingleDate(Request::option('sd_id'), Request::option('cycle_id'));

    // We check we have warnings and need an approval before deleting the date
    $warning = array();

    // does the have issues?
    if ($termin->getIssueIds()) {
        if($GLOBALS["RESOURCES_ENABLE_EXPERT_SCHEDULE_VIEW"]){
            $warning[] = _("Diesem Termin ist im Ablaufplan ein Thema zugeordnet. Titel und Beschreibung des Themas bleiben erhalten und k�nnen in der Expertenansicht des Ablaufplans einem anderen Termin wieder zugeordnet werden.");
        } else {
            $warning[] = _("Diesem Termin ist ein Thema zugeordnet.");
        }
    }

    // does the date have a booked room?
    if ($GLOBALS['RESOURCES_ENABLE'] && $termin->hasRoom()) {
        $warning[] = _("Dieser Termin hat eine Raumbuchung, welche mit dem Termin gel�scht wird.");
    }

    // do we have warnings we need approval for?
    if (!Request::get('approveDelete') && sizeof($warning) > 0) {
            $params = array(
                'cmd' => 'delete_singledate',
                'subcmd' => Request::option('subcmd'),
                'cycle_id' => Request::option('cycle_id'),
                'sd_id' => Request::option('sd_id'),
                'approveDelete' => 'TRUE'
            );
            $base_url = Request::option('subcmd') == 'cancel' ? '?#' . Request::option('sd_id') : '?';
            echo createQuestion( implode("\n", $warning) . "\n". _("Wollen Sie diesen Termin wirklich l�schen?"), $params, array(), $base_url);
    }

    // no approval needed or already approved
    else {
        // deletion approved, delete show approval-message
        if (Request::get('approveDelete')) {
            if($GLOBALS["RESOURCES_ENABLE_EXPERT_SCHEDULE_VIEW"]){
                $sem->createMessage(sprintf(_("Sie haben den Termin %s gel�scht, dem ein Thema zugeorndet war. Sie k�nnen das Thema in der %sExpertenansicht des Ablaufplans%s einem anderen Termin (z.B. einem Ausweichtermin) zuordnen."),
                    $termin->toString(), '<a href="'. URLHelper::getLink('themen.php?cmd=changeViewMode&newFilter=expert') .'">', '</a>'));
            } else {
                if ($termin->hasRoom()) {
                    $sem->createMessage(sprintf(_("Der Termin %s wurde gel�scht! <br> Die Buchung f�r den Raum %s wurde gel�scht."),
                        $termin->toString(), $termin->getRoom()));
                } else {
                    $sem->createMessage(sprintf(_("Der Termin %s wurde gel�scht!"), $termin->toString()));
                }
            }
        }

        // no approval needed, delete unquestioned
        else {
            $sem->createMessage(sprintf(_("Der Termin %s wurde gel�scht!"), $termin->toString()));
        }
        if (Request::option('subcmd') == 'cancel') {
            $sem->cancelSingleDate(Request::option('sd_id'), Request::option('cycle_id'));
            Request::set('singleDateID', Request::option('sd_id'));
        } else {
            $sem->deleteSingleDate(Request::option('sd_id'), Request::option('cycle_id'));
        }
    }
}

function raumzeit_undelete_singledate($sem) {
    if ($sem->unDeleteSingleDate(Request::option('sd_id'), Request::option('cycle_id'))) {
        $termin = new SingleDate(Request::option('sd_id'));
        $sem->createMessage(sprintf(_("Der Termin %s wurde wiederhergestellt!"), $termin->toString()));
    }
}

function raumzeit_checkboxAction($sem) {
    // close any opened singledate, if we do a checkbox action
    if (Request::get('checkboxAction')) {
        Request::set('singleDateID', null);
    }

    switch (Request::option('checkboxAction')) {
        case 'cancel':
            //TODO: what if deletion leads to an empty regular entry? -> the regular entry should be deleted too
            $singledate = Request::getArray('singledate');
            if (empty($singledate)) break;
            $msg = _("Folgende Termine wurden gel�scht:").'<br>';
            $deleted_dates = array();
            foreach ($singledate as $val) {
                $termin = $sem->getSingleDate($val, Request::option('cycle_id'));
                $msg .= '<li>'.$termin->toString().'<br>';
                if (Request::get('cancel_comment') !== null) {
                    $sem->cancelSingleDate($val, Request::option('cycle_id'));
                    $termin = new SingleDate($val);
                    $termin->setComment(Request::get('cancel_comment'));
                    $termin->store();
                    $deleted_dates[] = $termin;
                } else {
                    $sem->deleteSingleDate($val, Request::option('cycle_id'));
                }
            }
            $sem->createMessage($msg);
            if (Request::int('cancel_send_message') && count($deleted_dates)) {
                $snd_messages = raumzeit_send_cancel_message(Request::get('cancel_comment'), $deleted_dates);
                if ($snd_messages) {
                    $sem->createMessage(sprintf(_("Es wurden %s Benachrichtigungen gesendet."), $snd_messages));
                }
            }
            break;

        case 'delete':
            $question = _('Sind Sie sicher, dass Sie die ausgew�hlten Termine l�schen m�chten?');
            $link_params = array(
                'cmd' => 'checkboxAction',
                'checkboxAction' => 'cancel',
                'singledate' => Request::getArray('singledate')
            );

            echo createQuestion($question, $link_params, array('singledate' => Request::getArray('singledate')));

            break;

        case 'takeplace':
             $singledate = Request::getArray('singledate');
            if (empty($singledate)) break;
               // if there were no singleDates choosen, stop.
            $msg = _("Folgende Termine wurden wieder hergestellt:").'<br>';
            foreach ($singledate as $val) {
                if ($sem->unDeleteSingleDate($val, Request::option('cycle_id'))) {        // undelete retrieved singleDate
                    $termin = new SingleDate($val);                                       // retrieve singleDate
                    $msg .= $termin->toString().'<br>';                                   // add string representation to message
                    unset($termin);                                                       // we never know, if the variable persists...
                }
            }
            $sem->createMessage($msg);
            break;
    }
}

function raumzeit_bookRoom($sem) {
    $singledate = Request::getArray('singledate');
    if (empty ($singledate)) return;
    $resObj = ResourceObject::Factory(Request::option('room'));
    $raum = $resObj->getFormattedLink(TRUE, TRUE, TRUE);
    $termin_count = 0;
    $ex_termin_count = 0;

    if (Request::get('room') == 'retreat') {
        $msg = _("F�r folgende Termine wurde die Raumbuchung aufgehoben:").'<br>';
    } else {
        $msg = sprintf(_("F�r folgende Termine wurde der Raum \"%s\" gebucht:"), $raum)."<br>";
    }

    $error_msg = sprintf(_("F�r folgende gel�schte Termine wurde Raum \"%s\" nicht gebucht:"), $raum)."<br>";
    foreach ($singledate as $val) {
        $termin = $sem->getSingleDate($val, Request::option('cycle_id'));
        if (!$termin->isExTermin()) {
            if ($sem->bookRoomForSingleDate($val, Request::option('room'), Request::option('cycle_id'))) {
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

    if (($ex_termin_count > 0 ) && (Request::option('room') != 'retreat')) {
        $sem->createError($error_msg);
    }
}

function raumzeit_selectSemester($sem) {
    global $semester;

    if (!$semester) $semester = new SemesterData();

    $start_semester = Request::quoted('startSemester');
    $end_semester   = Request::quoted('endSemester');

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
        $sem->setStartSemester(Request::quoted('startSemester'));
        $sem->setEndSemester(Request::quoted('endSemester'));
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

function raumzeit_addCycle($sem) {
    global $newCycle;
    $sem->createInfo(_("Geben Sie nun unten die Zeiten f�r den neu zu erstellenden regelm��igen Termin an!"));
    $newCycle=true;
}

function raumzeit_doAddCycle($sem) {
    global $newCycle;
    if ($cycle_id = $sem->addCycle($_REQUEST)) {    // the template 'addmetadate.tpl' has form-fields, just passed through here.
        $info = $sem->metadate->cycles[$cycle_id]->toString();
        $sem->createMessage(sprintf(_("Die regelm��ige Veranstaltungszeit \"%s\" wurde hinzugef�gt!"),'<b>'.$info.'</b>'));
    } else {
        $sem->createError(_("Die regelm��ige Veranstaltungszeit konnte nicht hinzugef�gt werden! Bitte �berpr�fen Sie Ihre Eingabe."));
        $newCycle = true;
    }
}

function raumzeit_editCycle($sem) {
    $sem->editCycle($_REQUEST);
}

function raumzeit_deleteCycle($sem) {
    $question = _("Sind Sie sicher, dass Sie den regelm��igen Eintrag **\"%s\"** l�schen m�chten?");
    $question_time = $sem->metadate->cycles[Request::option('cycle_id')]->toString();
    $link_params = array(
        'cmd' => 'doDeleteCycle',
        'cycle_id' => Request::option('cycle_id')
    );

    echo createQuestion( sprintf( $question, $question_time ), $link_params );
}

function raumzeit_doDeleteCycle($sem) {
    $sem->createMessage(sprintf(_("Der regelm&auml;&szlig;ige Eintrag \"%s\" wurde gel&ouml;scht."), '<b>'.$sem->metadate->cycles[Request::option('cycle_id')]->toString().'</b>'));
    $sem->deleteCycle(Request::option('cycle_id'));
}
function raumzeit_checkDate($date, $startStunde,$startMinute,$endStunde,$endMinute){
    if(strlen($date)<8
            ||($startStunde<0 || $startStunde>23)
            ||($startMinute<0 || $startMinute>59)
            ||($endStunde<0   ||$endStunde>23)
            ||($endMinute<0   ||$endMinute>59)
            ||!is_int($startStunde) || !is_int($endStunde)
            ||!is_int($startMinute) || !is_int($endMinute)

            ){
        return FALSE;
    } else {
        return TRUE;
    }
}
function raumzeit_doAddSingleDate($sem) {
    global $cmd;
    //check date aus calendar_functions.inc.php herausgezogen und fuer datepicker angepasst
    if(!raumzeit_checkDate(Request::get('startDate'), Request::int('start_stunde'), Request::int('start_minute'), Request::int('end_stunde'), Request::int('end_minute'))){
        $sem->createError(_("Bitte geben Sie ein g�ltiges Datum und eine g�ltige Uhrzeit an!"));
        $cmd = 'createNewSingleDate';
    }


    // create date
    else {
        $termin = new SingleDate();
        //dates[0]=day, dates[1]=month,dates[2]=year
        $dates = explode('.', Request::get('startDate'));
        $start = mktime(Request::int('start_stunde'), Request::int('start_minute'), 0, $dates[1], $dates[0], $dates[2]);
        $ende = mktime(Request::int('end_stunde'), Request::int('end_minute'), 0, $dates[1], $dates[0], $dates[2]);
        $termin->setTime($start, $ende);
        $termin->setDateType(Request::get('dateType'));
        $termin->store();

        if ($start < $sem->filterStart || $ende > $sem->filterEnd) {
            $sem->setFilter('all');
        }
        if (!Request::get('room') || Request::get('room') === 'nothing') {
            $termin->setFreeRoomText(Request::get('freeRoomText'));
            $termin->store();
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
        $sem->createMessage(sprintf(_("Der Termin %s wurde hinzugef�gt!"), '<b>'.$termin->toString().'</b>'));
        $sem->store();
    }
}

function raumzeit_editDeletedSingleDate($sem) {
    if (!$GLOBALS['perm']->have_perm('dozent')) {
        $sem->createError(_("Ihnen fehlt die Berechtigung um den Kommentar von gel�schten Terminen zu �ndern!"));
        return;
    }

    if (Request::option('cycle_id') != '') {
        // the choosen singleDate is connected to a cycleDate
        $termin =& $sem->getSingleDate(Request::option('singleDateID'), Request::option('cycle_id'));
    } else {
        // the choosen singleDate is irregular, so we can edit it directly
       $termin = new SingleDate(Request::option('singleDateID'));
    }

    $old_comment = $termin->getComment();
    $termin->setComment(Request::get('cancel_comment'));
    if($termin->getComment() != $old_comment) {
        $sem->createMessage(sprintf(_("Der Kommtentar des gel�schten Termins %s wurde ge�ndert."), '<b>'.$termin->toString().'</b>'));
    } else {
        $sem->createInfo(sprintf(_("Der gel�schte Termin %s wurde nicht ver�ndert."), '<b>'.$termin->toString().'</b>'));
    }

    $termin->store();

    if (Request::int('cancel_send_message')) {
        $snd_messages = raumzeit_send_cancel_message(Request::get('cancel_comment'), $termin);
        if ($snd_messages) {
            $sem->createInfo(sprintf(_("Es wurden %s Benachrichtigungen gesendet."), $snd_messages));
        }
    }
    Request::set('singleDateID', null);
}

function raumzeit_editSingleDate($sem) {
    if (!Request::submitted("editSingleDate_button")) {
        return;
    }
    // generate time-stamps we can compare directly
    //dates[0]=day, dates[1]=month,dates[2]=year
        $dates = explode('.', Request::get('startDate'));
        $start = mktime(Request::int('start_stunde'), Request::int('start_minute'), 0,$dates[1],$dates[0],$dates[2]);
        $ende = mktime(Request::int('end_stunde'), Request::int('end_minute'), 0, $dates[1],$dates[0],$dates[2]);

    // get request-variables
    $termin_id = Request::option('singleDateID');
    $cycle_id  = Request::option('cycle_id', null);

    // we close the chosen singleDate, we do not want to have multiple singleDates open
    //  -> could lead to confusion, which singleDate is meant to be edited
    // unset($sd_open[Request::option('singleDateID')]);

    if ($cycle_id) {   // regelm��iger Termin
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
            $sem->createInfo(sprintf(_('Der Termin %s wurde aus der Liste der regelm��igen Termine '
                . 'gel�scht und als unregelm��iger Termin eingetragen, da Sie die Zeiten des Termins '
                . 'ver�ndert haben, so dass dieser Termin nun nicht mehr regelm��ig ist.'),
                '<b>'.$termin->toString().'</b>')
            );

            if ($ireg_termin->getStartTime() < $termin->getStartTime()
                || $ireg_termin->getEndTime() > $termin->getEndTime()) {
                $sem->createInfo(_('Die Raumbuchung f�r diesen Termin wurde aufgehoben, '
                    . 'da sich der neue Zeitraum au�erhalb des Alten befindet!'));
            }
            $sem->appendMessages($ireg_termin->getMessages());

        } else {
            // we did not change the times, so we can edit the regular singleDate
            $termin->setDateType($_REQUEST['dateType']);

            if (Request::option('action') == 'room') {
                if ($resObj = $termin->bookRoom(Request::option('room_sd'))) {
                    $sem->createMessage(sprintf(_("Der Termin %s wurde ge�ndert und der Raum %s gebucht, etwaige freie Ortsangaben wurden entfernt."),
                        '<b>'. $termin->toString() .'</b>',
                        '<b>'. $resObj->getName() .'</b>'));
                } else {
                    $sem->createError(sprintf(_("Der angegebene Raum konnte f�r den Termin %s nicht gebucht werden!"),
                        '<b>'. $termin->toString() .'</b>'));
                }
            } else if (Request::option('action') == 'noroom') {
                $termin->killAssign();
                $sem->createMessage(sprintf(_("Der Termin %s wurde ge�ndert, etwaige freie Ortsangaben und Raumbuchungen wurden entfernt."), '<b>'.$termin->toString().'</b>'));
            } else if (Request::option('action') == 'freetext') {
                $termin->setFreeRoomText(Request::quoted('freeRoomText_sd'));
                $termin->killAssign();
                $sem->createMessage(sprintf(_("Der Termin %s wurde ge�ndert, etwaige Raumbuchung wurden entfernt und stattdessen der angegebene Freitext eingetragen!"), '<b>'.$termin->toString().'</b>'));
            }

            $sem->appendMessages($termin->getMessages());
        }

        $sem->readSingleDatesForCycle($cycle_id, true);
        $termin->clearRelatedPersons();

        // add persons performing at this date
        $teachers = $sem->getMembers('dozent');
        $teacher_added = false;

        foreach (explode(',', Request::get('related_teachers')) as $dozent_id) {
            if (in_array($dozent_id, array_keys($teachers)) !== false) {
                $teacher_added = true;
                $termin->addRelatedPerson($dozent_id);
            }
        }

        if (!$teacher_added) {
            $sem->createInfo(_("Jeder Termin muss mindestens eine Person haben, die ihn durchf�hrt!"));
        }

        $termin->store();
        NotificationCenter::postNotification("CourseDidChangeSchedule", $sem);
    }

    // unregelm��iger Termin
    else {
        // the choosen singleDate is irregular, so we can edit it directly
        $termin =& $sem->getSingleDate($termin_id);

        $bookRoom = false;
        if ($start >= $termin->date && $ende <= $termin->end_time) {
            $bookRoom = true;
        } else {
            if (!Request::get('approveChange') && $termin->hasRoom()) {
                $zw_termin = new SingleDate();
                $zw_termin->date = $start;
                $zw_termin->end_time = $ende;

                // parameters to be resent on positive answer
                foreach (words('startDate start_stunde start_minute end_stunde '
                    . 'end_minute related_teachers room_sd freeRoomText_sd dateType cmd '
                    . 'singleDateID cycle_id action') as $param) {
                    $url_params[$param] = Request::get($param);
                }

                $url_params['approveChange'] = true;
                $url_params['editSingleDate_button'] = true;

                echo createQuestion( sprintf(_("Wenn Sie den Termin am %s auf %s �ndern,".
                        " verlieren Sie die Raumbuchung. Sind Sie sicher, dass Sie diesen Termin �ndern m�chten?"),
                        '**'. $termin->toString() .'**',  '**'. $zw_termin->toString() .'**'),
                        $url_params);

                unset($zw_termin);
                return;
            }
        }

        if ( $termin->setTime($start, $ende)
            || $termin->getFreeRoomText() != Request::get('freeRoomText_sd')
            || $termin->getDateType != Request::get('dateType') ) {

            $termin->setDateType(Request::get('dateType'));

            $termin->clearRelatedPersons();
            $teachers = $sem->getMembers('dozent');
            foreach (explode(',', Request::get('related_teachers')) as $dozent_id) {
                if (in_array($dozent_id, array_keys($teachers))) {
                    $termin->addRelatedPerson($dozent_id);
                }
            }

            if (Request::option('action') == 'room') {
                if ($bookRoom) {
                    $termin->bookRoom(Request::option('room_sd'));
                } else {
                    $termin->killAssign();
                    $sem->createInfo(sprintf(_("Die Raumbuchung f�r den Termin %s wurde aufgehoben, da die neuen Zeiten au�erhalb der Alten liegen!"), '<b>'. $termin->toString() .'</b>'));
                }
            } else if (Request::option('action') == 'noroom') {
                $termin->killAssign();
                $sem->createMessage(sprintf(_("Der Termin %s wurde ge�ndert, etwaige freie Ortsangaben und Raumbuchungen wurden entfernt."), '<b>'.$termin->toString().'</b>'));
            } else if (Request::option('action') == 'freetext') {
                $termin->killAssign();
                $termin->setFreeRoomText(Request::get('freeRoomText_sd'));
                $sem->createMessage(sprintf(_("Der Termin %s wurde ge�ndert, eine etwaige Raumbuchung wurden entfernt und stattdessen der angegebene Freitext eingetragen!"),
                    '<b>'. $termin->toString() .'</b>'));
            }
            $termin->store();

            NotificationCenter::postNotification("CourseDidChangeSchedule", $sem);
            $sem->createMessage(sprintf(_("Der Termin %s wurde ge�ndert!"), '<b>'.$termin->toString().'</b>'));
        }
        $sem->appendMessages($termin->getMessages());
    }
}

function raumzeit_removeRequest($sem) {
    $termin =& $sem->getSingleDate(Request::option('singleDateID'), Request::option('cycle_id'));
    // logging >>>>>>
    log_event("SEM_DELETE_SINGLEDATE_REQUEST", $sem->getId(), $termin->toString());
    // logging <<<<<<
    $termin->removeRequest();
    $sem->createMessage(sprintf(_("Die Raumanfrage f�r den Termin %s wurde gel�scht."), $termin->toString()));
}

function raumzeit_removeSeminarRequest($sem) {
    $sem->removeSeminarRequest();
    $sem->createMessage(sprintf(_("Die Raumanfrage f�r die Veranstaltung wurde gel�scht.")));
}

function raumzeit_removeMetadateRequest($sem) {
    $request_id = RoomRequest::existsByCycle(Request::option('metadate_id'));
    if ($request_id) {
        $cycles = $sem->getCycles();
        $cycle = $cycles[Request::option('metadate_id')];
        // logging >>>>>>
        log_event("SEM_DELETE_METADATE_REQUEST", $sem->getId(), $cycle->toString());
        // logging <<<<<<
        $sem->createMessage(sprintf(_("Die Raumanfrage f�r die regelm��ige Zeit %s wurde gel�scht."), htmlReady($cycle->toString())));
        return RoomRequest::find($request_id)->delete();
    }
}

function raumzeit_MoveCycle($sem) {
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
    $sem->createMessage(_("Die regelm��igen Zeiten wurden neu geordnet."));
}

/**
 * Adds/deletes or updates (sets) the related persons of a given date.
 * This function is called from seminar-class, when something has changed in a
 * single- or metadate. It needs request-params to do something.
 */
function raumzeit_bulkAction($sem) {
    // related persons
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
        $sem->createMessage(_("Zust�ndige Personen f�r die Termine wurden ge�ndert."));
    /*} else {
        $sem->createInfo(_("An den Zuordnungen von Personen zu Terminen hat sich nichts ge�ndert."));*/
    }

    foreach($singledates as $termin_id) {
        if (Request::option('cycle_id') != '') {
            $termin = $sem->getSingleDate($termin_id, Request::option('cycle_id'));
        } else {
            $termin = $sem->getSingleDate($termin_id);
        }

        if (Request::option('action') == 'room') {
            $termin->bookRoom(Request::option('room'));
            if (Request::option('cycle_id') != '') {
                $sem->metadate->cycles[Request::option('cycle_id')]->termine = null;
            } else {
                $sem->irregularSingleDates = null;
            }
        } else if (Request::option('action') == 'freetext') {
            if ($termin->getFreeRoomText() != Request::get('freeRoomText')) {
                $termin->setFreeRoomText(Request::quoted('freeRoomText'));
                $termin->killAssign();
                $sem->createMessage(sprintf(_("Der Termin %s wurde ge�ndert, eine etwaige Raumbuchung wurden entfernt und stattdessen der angegebene Freitext eingetragen!"),
                    '<b>'. $termin->toString() .'</b>'));
            }
        } else if (Request::option('action') == 'noroom') {
            $termin->killAssign();
        }

        $termin->store();
        $sem->appendMessages($termin->getMessages());
    }
}
