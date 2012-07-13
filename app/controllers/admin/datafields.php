<?php
# Lifter010: TODO
/**
 * datafields.php - controller class for the datafields
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Nico Müller <nico.mueller@uni-oldenburg.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 * @since       2.1
 */

//Imports
require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/DataFieldStructure.class.php';
require_once 'lib/classes/DataFieldEntry.class.php';

class Admin_DatafieldsController extends AuthenticatedController
{
    public $user_status = array(
        'user'   =>  1,
        'autor'  =>  2,
        'tutor'  =>  4,
        'dozent' =>  8,
        'admin'  => 16,
        'root'   => 32,
    );

    /**
     * Common tasks for all actions.
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // ajax
        if (@$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            $this->via_ajax = true;
            $this->set_layout(null);
        }

        // user must have root permission
        $GLOBALS['perm']->check('root');

        // set navigation
        Navigation::activateItem('/admin/config/datafields');
        PageLayout::setTitle(_("Verwaltung von generischen Datenfeldern"));
    }

    /**
     * Maintenance view for the datafield view
     *
     * @param $class static types for datafields
     */
    public function index_action($class = null)
    {
        $class_filter = Request::option('class_filter', null);
        if ($class_filter == '-1') {
            $class_filter = null;
        }

        if (!is_null($class_filter)) {
            $this->datafields_list = array(
                $class_filter => DataFieldStructure::getDataFieldStructures($class_filter),
            );
        } else {
            $this->datafields_list = array(
                'sem'          => DataFieldStructure::getDataFieldStructures('sem'),
                'inst'         => DataFieldStructure::getDataFieldStructures('inst'),
                'user'         => DataFieldStructure::getDataFieldStructures('user'),
                'userinstrole' => DataFieldStructure::getDataFieldStructures('userinstrole'),
                'usersemdata'  => DataFieldStructure::getDataFieldStructures('usersemdata'),
                'roleinstdata' => DataFieldStructure::getDataFieldStructures('roleinstdata'),
            );
        }

        // set variables for view
        $this->class_filter = $class_filter;
        $this->allclasses = DataFieldStructure::getDataClass();
        $this->current_class = $class;
        $this->allclass = array_keys($this->allclasses);
        $this->edit_id = Request::option('edit_id');
        
    }

    /**
     * Edit a datatyp
     *
     * @param md5 $datafield_id
     */
    public function edit_action($datafield_id)
    {
        $this->response->add_header('Content-Type', 'text/html; charset=windows-1252');
        if (Request::submitted('uebernehmen')) {
            $struct = new DataFieldStructure(compact('datafield_id'));
            $struct->load();
            if (Request::get('datafield_name')) {
                $struct->setName(Request::get('datafield_name'));
                $struct->setObjectClass(array_sum(Request::getArray('object_class')));
                $struct->setEditPerms(Request::get('edit_perms'));
                $struct->setViewPerms(Request::get('visibility_perms'));
                $struct->setPriority(Request::get('priority'));
                $struct->setType(Request::get('datafield_type'));
                $struct->setIsRequired(Request::get('is_required'));
                $struct->setDescription(Request::get('description'));
                $struct->store();

                $this->flash['success'] = _('Die Änderungen am generischen Datenfeld wurden übernommen.');
            } else {
                $this->flash['error'] = _("Es wurde keine Bezeichnung eingetragen!");
            }

            if ($this->via_ajax || Request::get('datafield_name')) {
                $this->redirect('admin/datafields/index/'.$struct->getObjectType().'#item_'.$datafield_id);
            }
        }

        //save changes
        if (Request::submitted('save')) {
            $struct = new DataFieldStructure(compact('datafield_id'));
            $struct->setTypeParam(Request::get('typeparam'));
            $struct->store();
            $this->flash['success'] = _('Die Parameter wurden übernommen.');
            $this->redirect('admin/datafields/index/'.$struct->getObjectType().'#item_'.$datafield_id);
        }

        // set variables for view
        $struct = new DataFieldStructure(compact('datafield_id'));
        $struct->load();
        $this->allclasses = DataFieldStructure::getDataClass();
        $this->item = $struct;
        $this->datafield_id = $struct->getID();
        $this->type = $struct->getType();
    }

    /**
     * Create a new Datafield
     *
     * @param $type static types for datafields
     */
    public function new_action($type = null)
    {
        if (Request::submitted('anlegen')) {
            if (Request::get('datafield_name')) {
                $datafield_id = md5(uniqid(Request::get('datafield_name').time()));
                $struct = new DataFieldStructure(compact('datafield_id'));
                $struct->setName(Request::get('datafield_name'));
                $struct->setObjectType($type);
                $struct->setObjectClass(array_sum(Request::getArray('object_class')));
                $struct->setEditPerms(Request::get('edit_perms'));
                $struct->setViewPerms(Request::get('visibility_perms'));
                $struct->setPriority(Request::get('priority'));
                $struct->setType(Request::get('datafield_typ'));
                if(in_array($type, array('sem')))
                {
                	$struct->setDescription(Request::get('description'));
                	$struct->setIsRequired(Request::get('mandatory'));
                }
                $struct->store();

                $this->flash['success'] = _('Das neue generische Datenfeld wurde angelegt.');
                $this->redirect('admin/datafields/index/'.$struct->getObjectType().'#item_'.$struct->getID());
            } else {
                $this->flash['error'] = _('Es wurde keine Bezeichnung eingetragen!');
            }
        }

        if (Request::submitted('auswaehlen')) {
            $type = Request::get('datafield_type');
        }

        $this->allclasses = DataFieldStructure::getDataClass();
        $this->object_type = DataFieldStructure::getDataClass();
        $this->type_name = $this->object_type[$type];
        $this->object_typ = $type;
    }

    /**
     * Delete a datafield
     *
     * @param md5 $datafield_id
     * @param string $name
     */
    public function delete_action($datafield_id)
    {

        $struct = new DataFieldStructure(compact('datafield_id'));
        $struct->load();
        $type = $struct->getObjectType();
        $name = $struct->getName();
        if (Request::int('delete') == 1) {
            $struct->remove();
            $this->flash['success'] = _('Das Datenfeld wurde erfolgreich gelöscht!');
        } elseif (!Request::get('back')) {
            $this->datafield_id = $datafield_id;
            $this->flash['delete'] = compact('datafield_id', 'name');
        }

        $this->redirect('admin/datafields/index/'.$type.'#'.$type);
    }
}
