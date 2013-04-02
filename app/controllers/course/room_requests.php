<?php
# Lifter010: TODO
/**
 * room_requests.php - administration of room requests
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

require_once 'lib/resources/lib/RoomRequest.class.php';
require_once 'app/controllers/authenticated_controller.php';

class Course_RoomRequestsController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    function before_filter (&$action, &$args)
    {
        global $perm;

        $this->current_action = $action;

        parent::before_filter($action, $args);

        $course_id = $args[0];

        $this->course_id = Request::option('cid', $course_id);
        //$course_id == '-' means dialog called from Assi
        if ($course_id != '-') {
            if ($perm->have_perm('admin')) {
                //Navigation im Admin-Bereich:
                Navigation::activateItem('/admin/course/room_requests');
            } else {
                //Navigation in der Veranstaltung:
                Navigation::activateItem('/course/admin/room_requests');
            }

            if (!$this->course_id) {
                PageLayout::setTitle(_("Verwaltung von Raumanfragen"));
                $GLOBALS['view_mode'] = "sem";

                require_once 'lib/admin_search.inc.php';

                include 'lib/include/html_head.inc.php';
                include 'lib/include/header.php';
                include 'lib/include/admin_search_form.inc.php';  // will not return
                die(); //must not return
            }

            if (!get_object_type($this->course_id, array('sem')) ||
                SeminarCategories::GetBySeminarId($this->course_id)->studygroup_mode ||
                !$perm->have_studip_perm("tutor", $this->course_id)) {
                throw new Trails_Exception(400);
            }

            PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenAendernVonZeitenUndTerminen");
            PageLayout::setTitle(getHeaderLine($this->course_id)." - " ._("Verwaltung von Raumanfragen"));
        }
    }

    /**
     * Display the list of room requests
     */
    function index_action()
    {
        if (Request::isXhr()) {
            $request = RoomRequest::find(Request::option('request_id'));
            if (isset($request)) {
                $this->request = $request;
                $this->response->add_header('Content-Type', 'text/html; charset=windows-1252');
                return $this->render_template('course/room_requests/_request.php', null);
            }
        } else {
            $room_requests = RoomRequest::findBySQL(sprintf('seminar_id = %s ORDER BY seminar_id, metadate_id, termin_id', DbManager::get()->quote($this->course_id)));
            $this->room_requests = $room_requests;
            $this->request_id = Request::option('request_id');
            //Admin-Liste f�r den Admin
            if ($GLOBALS['perm']->have_studip_perm("admin",$this->course_id)) {
                $this->adminList = AdminList::getInstance()->getSelectTemplate($this->course_id);
            }
        }
    }

    /**
     * edit one room requests
     */
    function edit_action()
    {
        if (Request::option('new_room_request_type')) {
            $request = new RoomRequest();
            $request->seminar_id = $this->course_id;
            $request->user_id = $GLOBALS['user']->id;
            list($new_type, $id) = explode('_', Request::option('new_room_request_type'));
            if ($new_type == 'course') {
                if ($existing_request = RoomRequest::existsByCourse($this->course_id)) {
                    $request = RoomRequest::find($existing_request);
                }
            }
            if ($new_type == 'date') {
                $request->termin_id = $id;
                if ($existing_request = RoomRequest::existsByDate($id)) {
                    $request = RoomRequest::find($existing_request);
                }
            } elseif ($new_type == 'cycle') {
                $request->metadate_id = $id;
                if ($existing_request = RoomRequest::existsByCycle($id)) {
                    $request = RoomRequest::find($existing_request);
                }
            }
        } else {
            $request = RoomRequest::find(Request::option('request_id'));
        }
        $admission_turnout = Seminar::getInstance($this->course_id)->admission_turnout;

        $attributes = self::process_form($request, $admission_turnout);

        if (Request::submitted('save') || Request::submitted('save_close')) {
            if (!($request->getSettedPropertiesCount() || $request->getResourceId())) {
                PageLayout::postMessage(MessageBox::error(_("Die Anfrage konnte nicht gespeichert werden, da Sie mindestens einen Raum oder mindestens eine Eigenschaft (z.B. Anzahl der Sitzpl�tze) angeben m�ssen!")));
            } else {
                $request->setClosed(0);
                $this->request_stored = $request->store();
                if ($this->request_stored) {
                    PageLayout::postMessage(MessageBox::success(_("Die Raumanfrage und gew�nschte Raumeigenschaften wurden gespeichert")));
                }
                if (Request::submitted('save_close') && !Request::isXhr()) {
                    $this->redirect($this->url_for('index/'. $this->course_id));
                }
            }
        }

        if (!$request->isNew() && $request->isDirty()) {
            PageLayout::postMessage(MessageBox::info(_("Die �nderungen an der Raumanfrage wurden noch nicht gespeichert!")));
        }
        $room_categories = array_values(array_filter(getResourcesCategories(), create_function('$a', 'return $a["is_room"] == 1;')));
        if (!$request->getCategoryId() && count($room_categories) == 1) {
            $request->setCategoryId($room_categories[0]['category_id']);
        }
        $this->search_result = $attributes['search_result'];
        $this->search_by_properties = $attributes['search_by_properties'];
        $this->admission_turnout = $admission_turnout;
        $this->request = $request;
        $this->room_categories = $room_categories;
        $this->new_room_request_type = Request::option('new_room_request_type');
    }

    function edit_dialog_action()
    {
        if (Request::isXhr()) {
            foreach((array)$_REQUEST as $k => $v) {
                if (is_array($v)) {
                    array_walk_recursive($v, create_function('$v', 'if (!is_array($v)) $v = studip_utf8decode($v);'));
                    Request::set($k, $v);
                } else {
                    Request::set($k, studip_utf8decode($v));
                }
            }
            if ($this->course_id != '-') {
                $this->edit_action();
                $title = PageLayout::getTitle();
            } else {
                $sem_create_data =& $_SESSION['sem_create_data'];
                if (Request::option('new_room_request_type')) {
                    if ( $sem_create_data['room_requests'][Request::option('new_room_request_type')] instanceof RoomRequest) {
                        $request = clone $sem_create_data['room_requests'][Request::option('new_room_request_type')];
                    } else {
                        $request = new RoomRequest();
                        $request->seminar_id = '-';
                        $request->user_id = $GLOBALS['user']->id;
                        list($new_type, $id) = explode('_', Request::option('new_room_request_type'));
                        if ($new_type == 'date') {
                            $request->termin_id = Request::option('new_room_request_type');
                        } elseif ($new_type == 'cycle') {
                            $request->metadate_id = Request::option('new_room_request_type');
                        }
                    }
                    $room_request_form_attributes = self::process_form($request, $sem_create_data['sem_turnout']);
                    $this->search_result = $room_request_form_attributes['search_result'];
                    $this->search_by_properties = $room_request_form_attributes['search_by_properties'];
                    $this->admission_turnout = $sem_create_data['sem_turnout'];
                    $this->request = $request;
                    $room_categories = array_values(array_filter(getResourcesCategories(), create_function('$a', 'return $a["is_room"] == 1;')));
                    if (!$request->getCategoryId() && count($room_categories) == 1) {
                        $request->setCategoryId($room_categories[0]['category_id']);
                    }
                    $this->room_categories = $room_categories;
                    $this->new_room_request_type = Request::option('new_room_request_type');
                    $title = _("Verwaltung von Raumanfragen");
                    if ((Request::submitted('save') || Request::submitted('save_close'))) {
                       if ($request->getSettedPropertiesCount() || $request->getResourceId()) {
                            $sem_create_data['room_requests'][Request::option('new_room_request_type')] = $request;
                            $this->request_stored = true;
                            if (Request::submitted('save')) {
                                PageLayout::postMessage(MessageBox::success(_("Die Raumanfrage und gew�nschte Raumeigenschaften wurden gespeichert")));
                            }
                        } else {
                            PageLayout::postMessage(MessageBox::error(_("Die Anfrage kann noch nicht gespeichert werden, da Sie mindestens einen Raum oder mindestens eine Eigenschaft (z.B. Anzahl der Sitzpl�tze) angeben m�ssen!")));
                        }
                    }
                    $old_request = $sem_create_data['room_requests'][Request::option('new_room_request_type')];
                    if (!is_object($old_request)
                       || $request->category_id != $old_request->category_id
                       || $request->resource_id != $old_request->resource_id
                       || $request->getProperties() != $old_request->getProperties()
                       || $request->comment!= $old_request->comment) {
                       PageLayout::postMessage(MessageBox::info(_("Die �nderungen an der Raumanfrage wurden noch nicht gespeichert!")));
                    }
                }
            }
            if (Request::submitted('save_close') && isset($this->request_stored)) {
                return $this->render_json(array('auto_close' => true,
                                                'auto_reload' => $this->request_stored));
            } else {
                $this->render_template('course/room_requests/edit_dialog.php', null);
                $this->flash->discard();
                $content = $this->get_response()->body;
                $this->erase_response();
                return $this->render_json(array('title' => studip_utf8encode($title),
                                                'content' => studip_utf8encode($content)
                                                ));
            }
        } else {
            return $this->render_text('');
        }
    }

    function index_assi_action()
    {
        if (Request::isXhr() && $this->course_id == '-') {
            $this->response->add_header('Content-Type', 'text/html; charset=windows-1252');
            $sem_create_data =& $_SESSION['sem_create_data'];
            $options = array();
            if (Request::option('delete_room_request_type')) {
                unset($sem_create_data['room_requests'][Request::option('delete_room_request_type')]);
            }
            foreach ($sem_create_data['room_requests_options'] as $one) {
                if ($sem_create_data['room_requests'][$one['value']] instanceof RoomRequest) {
                    $options[$one['value']]['request'] = $sem_create_data['room_requests'][$one['value']];
                } else {
                    $options[$one['value']]['request'] = null;
                }
                $options[$one['value']]['name'] = $one['name'];
            }
            if (Request::option('request_id') !== null) {
                $this->request = $options[Request::option('request_id')]['request'];
                return $this->render_template('course/room_requests/_request.php', null);
            }
            $this->options = $options;
            return $this->render_template('course/room_requests/index_assi.php', null);
        } else {
            return $this->render_text('');
        }
    }

    /**
     * create a new room requests
     */
    function new_action()
    {
        $options = array();

        if (!RoomRequest::existsByCourse($this->course_id)) {
            $options[] = array('value' => 'course', 'name' => _('alle regelm��igen und unregelm��igen Termine der Veranstaltung'));
        }
        foreach (SeminarCycleDate::findBySeminar($this->course_id) as $cycle) {
            if (!RoomRequest::existsByCycle($cycle->getId())) {
                $name = _("alle Termine einer regelm��igen Zeit");
                $name .= ' (' . $cycle->toString('full') . ')';
                $options[] = array('value' => 'cycle_' . $cycle->getId(), 'name' => $name);
            }
        }
        foreach (SeminarDB::getSingleDates($this->course_id) as $date) {
            if (!RoomRequest::existsByDate($date['termin_id'])) {
                $name = _("Einzeltermin der Veranstaltung");
                $termin = new SingleDate($date['termin_id']);
                $name .= ' (' . $termin->toString() . ')';
                $options[] = array('value' => 'date_' . $date['termin_id'], 'name' => $name);
            }
        }
        $this->options = $options;
    }

    /**
     * delete one room requests
     */
    function delete_action()
    {
        $request = RoomRequest::find(Request::option('request_id'));
        if (!$request) {
            throw new Trails_Exception(403);
        }
        if (Request::isGet()) {
            $factory = new Flexi_TemplateFactory($this->dispatcher->trails_root . '/views/');
            $template = $factory->open('course/room_requests/_del.php');
            $template->action = $this->link_for('delete/' . $this->course_id, array('request_id' => $request->getid()));
            $template->question = sprintf(_('M�chten Sie die Raumanfrage "%s" l�schen?'), $request->getTypeExplained());
            $this->flash['message'] = $template->render();
        } else {
            CSRFProtection::verifyUnsafeRequest();
            if (Request::submitted('kill')) {
                if ($request->delete()) {
                    $this->flash['message'] = MessageBox::success("Die Raumanfrage wurde gel�scht.");
                }
            }
        }
        $this->redirect($this->url_for('index/'. $this->course_id));
    }

    /**
     * handle common tasks for the romm request form
     * (set properties, searching etc.)
     */
    static function process_form($request, $admission_turnout = null)
    {
        if (Request::submitted('room_request_form')) {
            CSRFProtection::verifyUnsafeRequest();
            if (Request::submitted('send_room')) {
                $request->setResourceId(Request::option('select_room'));
            } else {
                $request->setResourceId(Request::option('selected_room'));
            }
            if (Request::submitted('reset_resource_id')) {
                $request->setResourceId('');
            }
            if (Request::submitted('reset_room_type')) {
                $request->setCategoryId('');
            }
            if (Request::get('comment') !== null) {
                $request->setComment(Request::get('comment'));
            }

            if (!Request::submitted('reset_room_type')) {
                $request->setCategoryId(Request::option('select_room_type'));
            }
            //Property Requests
            if ($request->getCategoryId()) {
                $request_property_val = Request::getArray('request_property_val');
                foreach ($request->getAvailableProperties() as $prop) {
                    if ($prop["system"] == 2) { //it's the property for the seat/room-size!
                        if (Request::get('seats_are_admission_turnout') && $admission_turnout) {
                            $request->setPropertyState($prop['property_id'], $admission_turnout);
                        } else if (!Request::submitted('send_room_type')) {
                            $request->setPropertyState($prop['property_id'], abs($request_property_val[$prop['property_id']]));
                        }
                    } else {
                        $request->setPropertyState($prop['property_id'], $request_property_val[$prop['property_id']]);
                    }
                }
            }
            if ((Request::get('search_exp_room') && Request::submitted('search_room'))
            || Request::submitted('search_properties')) {
                $search_result = $request->searchRoomsToRequest(Request::get('search_exp_room'), Request::submitted('search_properties'));
                $search_by_properties = Request::submitted('search_properties');
            }
        }
        return compact('search_result', 'search_by_properties', 'request', 'admission_turnout');
    }

    function url_for($to = '', $params = array())
    {
        $whereto = 'course/room_requests/';
        if ($to === '') {
            $whereto .=  $this->current_action;
        } else {
            $whereto .=  $to;
        }
        $url = URLHelper::getURL($this->dispatcher->trails_uri . '/' . $whereto, $params);
        return $url;
    }

    function link_for($to = '', $params = array())
    {
        $whereto = 'course/room_requests/';
        if ($to === '') {
            $whereto .=  $this->current_action;
        } else {
            $whereto .=  $to;
        }
        $link = URLHelper::getLink($this->dispatcher->trails_uri . '/' . $whereto, $params);
        return $link;
    }

    function render_json($data){
        $this->set_content_type('application/json;charset=utf-8');
        return $this->render_text(json_encode($data));
    }
}
