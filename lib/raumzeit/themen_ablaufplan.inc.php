<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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

    $termin = new SingleDate(Request::option('singledate_id'));
    $termin->addIssueID($issue->getIssueID());
    $termin->store();
    $sem->createMessage(_("Folgendes Thema wurde hinzugef�gt:") .'<br>'. htmlReady($issue->toString()));
}

function themen_changeIssue() {
    global $sem, $themen;

    $msg .= sprintf(_("Das Thema \"%s\" wurde ge�ndert."), htmlReady($themen[Request::option('issue_id')]->toString())) . '<br>';
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

function themen_openAll() {
    global $sem, $openAll;

    $sem->createInfo(_("Es wurden alle Termine ge&ouml;ffnet. Sie k&ouml;nnen diese nun bearbeiten."));
    $openAll = TRUE;
}

function themen_closeAll() {
    global $sem, $openAll;

    unset($openAll);
}

function themen_saveAll() {
    global $sem, $themen, $changeTitle, $changeForum, $changeDescription, $changeFile, $id;

    $msg = _("Folgende Termine wurden bearbeitet:").'<br>';
    foreach ($changeTitle as $key => $val) {    // we use the changeTitle-array for running through all themes ($key = issue_id and $val = title)

        unset($termin);
        if (($changeTitle[$key] != '') || ($changeDescription[$key] != '') || ($changeForum[$key] == 'on') || ($changeFile[$key] == 'on')
                || Request::get('createAllFileFolders') == 'on' || Request::get('createAllForumFolders') == 'on') {
            $termin = new SingleDate($key);
            $issue_ids = $termin->getIssueIDs();
            if (sizeof($issue_ids) == 0) {
                $issue = new Issue(array('seminar_id' => $id));
                $cur_issue_id = $issue->getIssueID();
                $termin->addIssueID($cur_issue_id);
                $termin->store();
                $themen[$issue->getIssueID()] =& $issue;
                $issue->store();
                unset($issue);
            } else {
                $cur_issue_id = array_pop($issue_ids);
            }

            if (!$already_worked_on[$cur_issue_id]) {
                $already_worked_on[$cur_issue_id] = true;
                $forumValue = ($changeForum[$key] == 'on') ? true : false;
                $fileValue = ($changeFile[$key] == 'on') ? true : false;

                if (Request::quoted('createAllForumFolders') == 'on') $forumValue = true;
                if (Request::quoted('createAllFileFolders') == 'on')  $fileValue = true;

                if (    ($themen[$cur_issue_id]->getTitle() != $val) ||
                        ($themen[$cur_issue_id]->getDescription() != $changeDescription[$key]) ||
                        $forumValue ||
                        ($themen[$cur_issue_id]->hasFile() != $fileValue)
                     ) {
                    $msg .= $termin->toString() .'<br>';
                }
                $themen[$cur_issue_id]->setTitle($val);
                $themen[$cur_issue_id]->setDescription($changeDescription[$key]);
                $themen[$cur_issue_id]->setForum($forumValue);
                $themen[$cur_issue_id]->setFile($fileValue);
                $themen[$cur_issue_id]->store();
            }
        }
    }

    // add changed dates to message
    $sem->createMessage($msg);

    $msg = _("Folgende weitere Aktionen wurden durchgef�hrt:").'<br>';
    $initial_length = strlen($msg);

    foreach ($themen as $val) {
        if ($zw = $val->getMessages()) {
            foreach ($zw as $iss_msg) {
                $msg .= $iss_msg. '<br>';
            }
        }
    }
    if (strlen($msg) > $initial_length) {
        // add additional changes to message
        $sem->createMessage($msg);
    }
}
