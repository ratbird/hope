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
 * @author      André Noack <noack@data-quest.de>
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
            if ($new_type == 'date') {
                $request->termin_id = $id;
            } elseif ($new_type == 'cycle') {
                $request->metadate_id = $id;
            }
        } else {
            $request = RoomRequest::find(Request::option('request_id'));
        }
        $admission_turnout = Seminar::getInstance($this->course_id)->admission_turnout;

        $attributes = self::process_form($request, $admission_turnout);

        if (Request::submitted('save')) {
            if (!($request->getSettedPropertiesCount() || $request->getResourceId())) {
                PageLayout::postMessage(MessageBox::error(_("Die Anfrage konnte nicht gespeichert werden, da Sie mindestens einen Raum oder mindestens eine Eigenschaft (z.B. Anzahl der Sitzplätze) angeben müssen!")));
            } else {
                $request->setClosed(0);
                if ($request->store()) {
                    PageLayout::postMessage(MessageBox::success(_("Die Raumanfrage und gewünschte Raumeigenschaften wurden gespeichert")));
                }
            }
        }

        if ($request->isDirty()) {
            PageLayout::postMessage(MessageBox::info(_("Die Änderungen an der Raumanfrage wurden noch nicht gespeichert!")));
        }
        $room_categories = array_filter(getResourcesCategories(), create_function('$a', 'return $a["is_room"] == 1;'));
        if (!$request->getCategoryId() && count($room_categories) == 1) {
            $request->setCategoryId($room_categories[0]['category_id ']);
        }

        $this->search_result = $attributes['search_result'];
        $this->search_by_properties = $attributes['search_by_properties'];
        $this->admission_turnout = $admission_turnout;
        $this->request = $request;
        $this->room_categories = $room_categories;
        $this->new_room_request_type = Request::option('new_room_request_type');
    }

    /**
     * create a new room requests
     */
    function new_action()
    {
        $options = array();

        if (!RoomRequest::existsByCourse($this->course_id)) {
            $options[] = array('value' => 'course', 'name' => _('alle regelmäßigen und unregelmäßigen Termine der Veranstaltung'));
        }
        foreach (SeminarCycleDate::findBySeminar($this->course_id) as $cycle) {
            if (!RoomRequest::existsByCycle($cycle->getId())) {
                $name = _("alle Termine einer regelmäßigen Zeit");
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
            $template->question = sprintf(_('Möchten Sie die Raumanfrage "%s" löschen?'), $request->getTypeExplained());
            $this->flash['message'] = $template->render();
        } else {
            CSRFProtection::verifyUnsafeRequest();
            if (Request::submitted('kill')) {
                if ($request->delete()) {
                    $this->flash['message'] = MessageBox::success("Die Raumanfrage wurde gelöscht.");
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
                Request::set('save', 1);
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

            if (Request::option('select_room_type') && !Request::submitted('reset_room_type')) {
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
        $url = UrlHelper::getURL($this->dispatcher->trails_uri . '/' . $whereto, $params);
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
        $link = UrlHelper::getLink($this->dispatcher->trails_uri . '/' . $whereto, $params);
        return $link;
    }
}