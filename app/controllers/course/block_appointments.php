<?php
/**
 * block_appointments.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 */

require_once 'app/controllers/authenticated_controller.php';

class Course_BlockAppointmentsController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        global $perm;

        parent::before_filter($action, $args);

        $course_id = $args[0];

        $this->course_id = Request::option('cid', $course_id);
        if (!get_object_type($this->course_id, array('sem')) ||
        SeminarCategories::GetBySeminarId($this->course_id)->studygroup_mode ||
        !$perm->have_studip_perm("tutor", $this->course_id)) {
            throw new Trails_Exception(400);
        }
        $this->set_layout(null);
        PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenAendernVonZeitenUndTerminen");
        PageLayout::setTitle(getHeaderLine($this->course_id)." - " . _("Blockveranstaltungstermine anlegen"));
    }

    function index_action()
    {
        if (Request::isXhr()) {

            $title = PageLayout::getTitle();
            $form_fields['start_day'] = array('type' => 'text', 'size' => '10', 'required' => true, 'caption' => _("Startdatum"));
            $form_fields['start_day']['attributes'] = array('class' => 'hasDatePicker');
            $form_fields['end_day'] = array('type' => 'text', 'size' => '10', 'required' => true, 'caption' => _("Enddatum"));
            $form_fields['end_day']['attributes'] = array('class' => 'hasDatePicker');
            $form_fields['start_time'] = array('type' => 'time', 'separator' => ':', 'required' => true,'caption' => _("Start"));
            $form_fields['end_time'] = array('type' => 'time', 'separator' => ':', 'required' => true,'caption' => _("Ende"));
            $form_fields['termin_typ'] = array('type' => 'select', 'caption' => _("Art der Termine"));
            $form_fields['termin_typ']['options'] = array_map(function($v, $k){return array('name' => $v['name'], 'value' => $k);}, $GLOBALS['TERMIN_TYP'], array_keys($GLOBALS['TERMIN_TYP']));
            $form_fields['days'] = array('type' => 'selectbox', 'multiple' => true, 'default_value' => '0');
            $form_fields['days']['options'][0] = array('name' => _("Jeden Tag") , 'value' => 0);
            $form_fields['date_count'] = array('type' => 'select', 'caption' => _("Anzahl"), 'options' => range(1,5));
            $start_ts = strtotime('this monday');
            foreach (range(0,6) as $d) {
                $form_fields['days']['options'][] = array('name' => strftime('%A', strtotime("+$d day", $start_ts)) , 'value' => $d + 1);
            }

            $form_buttons['save_close'] = array('caption' => _('Erstellen'), 'info' => _("Termine erstellen und Dialog schließen"));
            $form_buttons['preview'] = array('caption' => _('Vorschau'), 'info' => _("zu erstellende Termine anzeigen"));
            $form_buttons['close'] = array('caption' => _('Abbrechen'), 'info' => _('Abbrechen und schließen'));
            $form_buttons['close']['attributes'] = array('onClick' => 'STUDIP.BlockAppointmentsDialog.dialog.dialog(\'close\');return false;');

            $form = new StudipForm($form_fields, $form_buttons, 'block_appointments', false);

            if ($form->isSended()) {
                $errors = array();
                $start_day = $form->getFormFieldValue('start_day') ? strtotime($form->getFormFieldValue('start_day')) : '';
                $end_day = $form->getFormFieldValue('end_day') ? strtotime($form->getFormFieldValue('end_day')) : '';
                if (!($start_day && $end_day && $start_day <= $end_day)) {
                    $errors[] = _("Bitte geben Sie korrekte Werte für Start- und Enddatum an!");
                } else {
                    $start_time = strtotime($form->getFormFieldValue('start_time'), $start_day);
                    $end_time = strtotime($form->getFormFieldValue('end_time'), $start_day);
                    if (!($start_time && $end_time && $start_time < $end_time)) {
                        $errors[] = _("Bitte geben Sie korrekte Werte für Start- und Endzeit an!");
                    }
                }
                $termin_typ = (int)$form->getFormFieldValue('termin_typ');
                $days = $form->getFormFieldValue('days');
                if (!is_array($days)) {
                    $errors[] = _("Bitte wählen Sie mindestens einen Tag aus!");
                }
                if (count($errors)) {
                    PageLayout::postMessage(MessageBox::error(_("Bitte korrigieren Sie Ihre Eingaben:"), $errors));
                } else {
                    $dates = array();
                    $date_count = $form->getFormFieldValue('date_count');
                    $delta = $end_time - $start_time;
                    $last_day = strtotime($form->getFormFieldValue('start_time'), $end_day);
                    $every_day = in_array(0, $days);
                    for ($t = $start_time; $t <= $last_day; $t = strtotime('+1 day', $t)) {
                        if ($every_day || in_array(strftime('%u',$t), $days)) {
                            for ($i = 1; $i <= $date_count; $i++) {
                                $date = new SingleDate();
                                $date->setDateType($termin_typ);
                                $date->setSeminarID($this->course_id);
                                $date->date = $t;
                                $date->end_time = $t + $delta;
                                $dates[] = $date;
                            }
                        }
                    }
                    if (count($dates)) {
                        if ($form->isClicked('preview')) {
                            $dates_created = array_map(function($d){return $d->toString();}, $dates);
                            if ($date_count > 1) {
                                $dates_created = array_count_values($dates_created);
                                $dates_created = array_map(function($k,$v){return $k . ' (' . $v . 'x)';}, array_keys($dates_created), array_values($dates_created));
                            }
                            PageLayout::postMessage(MessageBox::info(_("Folgende Termine ergeben sich aus Ihren Angaben:"), $dates_created ));
                        }
                        if ($form->isClicked('save_close')) {
                            $dates_created = array_filter(array_map(function($d){return $d->store() ? $d->toString() : null;}, $dates));
                            if ($date_count > 1) {
                            	$dates_created = array_count_values($dates_created);
                            	$dates_created = array_map(function($k,$v){return $k . ' (' . $v . 'x)';}, array_keys($dates_created), array_values($dates_created));
                            }
                            PageLayout::postMessage(MessageBox::success(_("Folgende Termine wurden erstellt:"), $dates_created));
                            return $this->render_json(array('auto_close' => true,
                                                            'auto_reload' => true));
                        }
                    } else {
                        PageLayout::postMessage(MessageBox::error(_("Keiner der ausgewählten Tage liegt in dem angegebenen Zeitraum!")));
                    }
                }
            }
            $this->form = $form;
            $this->render_template('course/block_appointments/index.php');
            $this->flash->discard();
            $content = $this->get_response()->body;
            $this->erase_response();
            return $this->render_json(array('title' => studip_utf8encode($title),
                                            'content' => studip_utf8encode($content)));
        } else {
            return $this->render_text('');
        }

    }

    function render_json($data){
        $this->set_content_type('application/json;charset=utf-8');
        return $this->render_text(json_encode($data));
    }
}