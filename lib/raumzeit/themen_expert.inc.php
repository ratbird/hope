<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
function themen_autoAssign() {
    global $sem, $cycle_id;

    $sem->autoAssignIssues(Request::getArray('themen'), $cycle_id);
}

function themen_changeChronoGroupedFilter() {
    global $chronoGroupedFilter;
    $chronoGroupedFilter = Request::quoted('newFilter');
}

function themen_chronoAutoAssign() {
    global $sem;

    $themen = Request::getArray('themen');

    $termine = getAllSortedSingleDates($sem);
    foreach ($termine as $singledate_id => $singledate) {
        if (!$singledate->isExTermin() && !$singledate->getIssueIDs()) {
            if ($data = array_shift($themen)) {
                $singledate->addIssueID($data);
                $singledate->store();
            } else {
                break;
            }
        }
    }
}

function themen_open() {
   $_SESSION['issue_open'][Request::option('open_close_id')] = true;
}

function themen_close() {
    $_SESSION['issue_open'][Request::option('open_close_id')] = false;
    unset ($_SESSION['issue_open'][Request::option('open_close_id')]);
}

function themen_doAddIssue() {
    global $id, $sem;

    $issue = new Issue(array('seminar_id' => $id));
    $issue->setTitle(Request::get('theme_title'));
    $issue->setDescription(Request::get('theme_description'));
    $issue->setForum((Request::get('forumFolder') == 'on') ? TRUE : FALSE);
    $issue->setFile((Request::get('fileFolder') == 'on') ? TRUE : FALSE);
    $sem->addIssue($issue);     // sets $issue->priority
    $issue->store();
    $sem->createMessage(_("Folgendes Thema wurde hinzugefügt:").'<br><li>'.htmlReady($issue->toString()));
}

function themen_deleteIssueID() {
    global $sem ;

    $termin = $sem->getSingleDate(Request::option('sd_id'), Request::option('cycle_id'));
    $termin->deleteIssueID(Request::option('issue_id'));
}

function themen_changeIssue() {
    global $sem, $themen;
    
    $msg .= sprintf(_("Das Thema \"%s\" wurde geändert."), htmlReady($themen[Request::option('issue_id')]->toString())) . '<br>';
    $themen[Request::option('issue_id')]->setDescription(Request::get('theme_description'));
    $themen[Request::option('issue_id')]->setTitle(Request::get('theme_title'));
    $themen[Request::option('issue_id')]->setForum((Request::get('forumFolder') == 'on') ? TRUE : FALSE);
    $themen[Request::option('issue_id')]->setFile((Request::get('fileFolder') == 'on') ? TRUE : FALSE);
    $themen[Request::option('issue_id')]->store();
    if ($zw = $themen[Request::option('issue_id')]->getMessages()) {
        foreach ($zw as $val) {
            $msg .= $val.'<br>';
        }
    }
    $sem->createMessage($msg);
}

function themen_deleteIssue() {
    global $sem, $themen;
    $sem->createMessage(_("Folgendes Thema wurde gelöscht:").'<br><li>'.htmlReady($themen[Request::option('issue_id')]->toString()));
    $sem->deleteIssue(Request::option('issue_id'));
}

function themen_addIssue() {
    global $sem, $cmd, $id;

    $numIssues = Request::int('numIssues');
    if ($numIssues > 20) {      // for security reasons, it should not be possible to add thousands of issues at one time
        unset($cmd);
        unset($numIssues);
        break;
    }
    if ($numIssues > 1) {
        $sem->createMessage(sprintf(_("Es wurden %s Themen hinzugefügt."), $numIssues));
        for ($i = 1; $i <= $numIssues; $i++) {
            $issue = new Issue(array('seminar_id' => $id));
            $issue->setTitle(_("Thema").' '.$i);
            $issue->store();
            $sem->addIssue($issue);
            unset($issue);
        }
        unset($cmd);
    }
}

function themen_changePriority() {
    global $sem, $themen;
    if ($themen[Request::option('issueID')]->getPriority() > Request::get('newPriority')) {
        $sem->createMessage(sprintf(_("Das Thema \"%s\" wurde um eine Position nach oben verschoben."), htmlReady($themen[Request::option('issueID')]->toString())));
    } else {
        $sem->createMessage(sprintf(_("Das Thema \"%s\" wurde um eine Position nach unten verschoben."), htmlReady($themen[Request::option('issueID')]->toString())));
    }
    $sem->changeIssuePriority(Request::option('issueID'), Request::get('newPriority'));
}

function themen_openAll() {
    global $sem, $openAll;

    $sem->createInfo(_("Es wurden alle Themen geöffnet. Sie können diese nun unten bearbeiten."));
    $openAll = TRUE;
}

function themen_saveAll() {
    global $sem, $themen, $changeTitle, $changeFile, $changeForum, $changeDescription;
    
    $msg = _("Folgende Themen wurden bearbeitet:").'<br>';
    foreach ($changeTitle as $key => $val) {    // we use the changeTitle-array for running through all themes ($key = issue_id and $val = title)
        $forumValue = ($changeForum[$key] == 'on') ? TRUE : FALSE;
        $fileValue = ($changeFile[$key] == 'on') ? TRUE : FALSE;
        if (    ($themen[$key]->getTitle() != $val) ||
                ($themen[$key]->getDescription() != $changeDescription[$key]) ||
                ($themen[$key]->hasForum() != $forumValue) ||
                ($themen[$key]->hasFile() != $fileValue)
             ) {
            $msg .= '<li>'.htmlReady($themen[$key]->toString()).'<br>';
        }
        $themen[$key]->setTitle($val);
        $themen[$key]->setDescription($changeDescription[$key]);
        $themen[$key]->setForum($forumValue);
        $themen[$key]->setFile($fileValue);
        $themen[$key]->store();
    }

    $msg .= '<br>'._("Folgende weitere Aktionen wurde durchgeführt:").'<br>';

    foreach ($themen as $val) {
        if ($zw = $val->getMessages()) {
            foreach ($zw as $iss_msg) {
                $msg .= '<li>'.$iss_msg.'<br>';
            }
        }
    }
    $sem->createMessage($msg);
}

function themen_checkboxAction() {
    global $sem, $choosen;

    switch (Request::option('checkboxActionCmd')) {
        case 'chooseAll':
            break;

        case 'chooseNone':
            break;

        case 'invert':
            $themen = Request::getArray('themen');
            foreach ($themen as $val) {
                $choosen[$val] = TRUE;
            }
            break;

        case 'deleteChoosen':
            $themen = Request::getArray('themen');
            if (empty($themen)) break;
            $msg = _("Folgende Themen wurden gelöscht:").'<br>';
            foreach ($themen as $val) {
                $thema =& $sem->getIssue($val);
                $msg .= '<li>'.htmlReady($thema->toString()).'<br>';
                unset($thema);
                $sem->deleteIssue($val);
            }
            $sem->createMessage($msg);
            break;

        case 'deleteAll':
            if (!Request::option('approveDeleteAll')) {    // security-question
                echo createQuestion(
                    _('Sind Sie sicher, dass Sie alle Themen löschen möchten?'),
                    array('cmd' => 'checkboxAction', 'checkboxActionCmd' => 'deleteAll', 'approveDeleteAll' => '1')
                );
            } else {                                            // deletion approved, so we do the job
                $msg = _("Alle Themen wurden gelöscht.");
                $sem->createMessage($msg);  // create a message
                foreach ($sem->getIssues() as $id => $issue) {
                    $sem->deleteIssue($id);
                }
            }
            break;

    }
}

?>
