<?php
/**
* cancel_dates.php
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License as
* published by the Free Software Foundation; either version 2 of
* the License, or (at your option) any later version.
*
* @author      Andr� Noack <noack@data-quest.de>
* @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
* @category    Stud.IP
* @package     admin
*/

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/raumzeit/IssueDB.class.php';

class Course_CancelDatesController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        global $perm;

        parent::before_filter($action, $args);

        if (Request::get('termin_id')) {
            $this->dates[0] = new SingleDate(Request::option('termin_id'));
            $this->course_id = $this->dates[0]->range_id;
        }
        
        if (Request::get('issue_id')) {
            $this->issue_id = Request::option('issue_id');
            $this->dates = array_values(
                            array_map(function ($data) 
                            {
                                $d = new SingleDate();
                                $d->fillValuesFromArray($data);
                                return $d;
                            },
                            IssueDB::getDatesforIssue(Request::option('issue_id'))
                            )
            );
            $this->course_id = $this->dates[0]->range_id;
        }
        if (!get_object_type($this->course_id, array('sem')) ||
                SeminarCategories::GetBySeminarId($this->course_id)->studygroup_mode ||
                !$perm->have_studip_perm("tutor", $this->course_id)) {
            throw new Trails_Exception(400);
        }
        PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenAendernVonZeitenUndTerminen");
        PageLayout::setTitle(Course::findCurrent()->getFullname()." - " . _("Veranstaltungstermine absagen"));
        $this->set_content_type('text/html;charset=windows-1252');
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->response->add_header('X-Title', PageLayout::getTitle());
            $request = Request::getInstance();
            foreach ($request as $key => $value) {
                $request[$key] = studip_utf8decode($value);
            }
        }
    }

    function index_action()
    {
            $form_fields['comment'] = array('caption' => _("Kommentar"), 'type' => 'textarea', 'attributes' => array('rows' => 4, 'style' => 'width:100%'));
            $form_fields['snd_message'] = array('caption' => _("Benachrichtigung �ber ausfallende Termine an alle Teilnehmer verschicken"),'type' => 'checkbox', 'attributes' => array('style' => 'vertical-align:middle'));
            
            $form_buttons['save_close'] = array('caption' => _('OK'), 'info' => _("Termine absagen und Dialog schlie�en"));

            $form = new StudipForm($form_fields, $form_buttons, 'cancel_dates', false);

            if ($form->isClicked('save_close')) {
                $sem = Seminar::getInstance($this->course_id);
                $comment = $form->getFormFieldValue('comment');
                foreach ($this->dates as $date) {
                    $sem->cancelSingleDate($date->getTerminId(), $date->getMetadateId());
                    $date->setComment($comment);
                    $date->setExTermin(true);
                    $date->store();
                }
                if ($form->getFormFieldValue('snd_message') && count($this->dates)) {
                    $snd_messages = raumzeit_send_cancel_message($comment, $this->dates);
                    if ($snd_messages) {
                        $msg = sprintf(_("Es wurden %s Benachrichtigungen gesendet."), $snd_messages);
                    }
                }
                PageLayout::postMessage(MessageBox::success(_("Folgende Termine wurden abgesagt") . ($msg ? ' (' . $msg . '):' : ':'),
                     array_map(function ($d) {return $d->toString();}, $this->dates)));
                $this->redirect($this->url_for('course/dates'));
            }
            $this->form = $form;
    }

    function after_filter($action, $args)
    {
        if (Request::isXhr()) {
            foreach ($this->response->headers as $k => $v) {
                if ($k === 'Location') {
                    $this->response->headers['X-Location'] = $v;
                    unset($this->response->headers['Location']);
                    $this->response->set_status(200);
                    $this->response->body = '';
                }
            }
        }
        parent::after_filter($action, $args);
    }

}
