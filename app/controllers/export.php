<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of export
 *
 * @author flo
 */
require_once 'app/controllers/authenticated_controller.php';

class exportController extends AuthenticatedController {

    function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);

        //set the title of the page
        PageLayout::setTitle(_('Export'));

        //load the default layout
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));

        $this->flash['args'] = Request::get("args") ? : serialize($args);

        $this->iconpath = $this->url_for('export/icon');

        foreach ($args as $arg) {
            $this->argstring .= "/$arg";
        }
        $this->exportpath = $this->url_for('export/export') . $this->argstring;
    }

    /**
     * Controller to load a png out of the lib folder and display it
     * 
     * @param type string name of the format
     */
    function icon_action($type) {
        header("Content-type: image/png");
        imagePng(exportDoc::image($type));
        $this->render_nothing();
    }

    /**
     * Basic export controller to work on a template
     */
    function index_action() {
        if (func_num_args() < 1) {
            $this->error[] = _('Kein Template angegeben');
            $this->render_action("error");
            return false;
        }

        if ($this->flash['export']) {
            $export = $this->flash['export'];
        } else {
            $export = new exportDoc();
            if (!$export->loadTemplate(unserialize($this->flash['args']))) {
                $this->error[] = _('Template konnte nicht geladen werden');
                $this->render_action("error");
                return false;
            }
        }

        if ($export->getPermission() && !$GLOBALS['perm']->have_perm($export->getPermission())) {
            $this->error[] = _('Keine Berechtigung');
            $this->render_action("error");
            return false;
        }

        if (!$GLOBALS['perm']->have_studip_perm($export->getPermission(), $export->getContext())) {
            $this->error[] = $export->getPermission() . " - " . $export->getContext();
            $this->render_action("error");
            return false;
        }

        $this->formats = $export->getFormats();
        $this->savelink = $this->url_for("export/save/" . $export->template . $export->getParamString());

        if ($export->isEditable()) {
            $this->templates = array();
            foreach ($export->getSavedTemplates() as $template) {
                $tmp['delete'] = $this->url_for("export/removeTemplate/" . $template->id . $this->argstring);
                $tmp['export'] = $this->url_for("export/exportTemplate", $template->id);
                $tmp['name'] = $template->name;
                $tmp['format'] = $template->format;
                $this->templates[] = $tmp;
            }
            $this->preview = $export->preview();
            foreach ($this->formats as $format) {
                $this->exportlink[$format] = $this->url_for("export/export/" . $export->template . $export->getParamString() . "?format=" . $format);
            }
            //$this->exportlink = $this->url_for("export/export/" . $export->getTemplate() . $export->getParamString());
        } else {
            if (count($this->formats) == 1) {
                $export->export($this->formats[0]);
                die;
            }
            $this->render_action("exportOnly");
        }
    }

    /**
     * Export controller to actually export a template
     */
    function export_action() {
        $export = new exportDoc();
        if (!$export->loadTemplate(func_get_args())) {
            $this->render_action("error");
                        return false;
        }

        /* if (Request::getArray('edit')) {
          $export->editTemplate(Request::getArray('edit'));
          } */
        $export->export(Request::get('format'));
        $this->render_nothing();
    }

    function save_action() {
        $export = new exportDoc();
        if (!$export->loadTemplate(func_get_args())) {
            
            $this->render_action("error");
                        return false;
        }
        $export->editTemplate(Request::getArray('edit'));
        $name = Request::get('templatename') ? : _("Neue Vorlage");
        $export->save(Request::get('format'), $name);
        //$this->flash['export'] = $export;
        $target = "export/index" . $this->argstring;
        $this->redirect($target);
    }

    function exportTemplate_action($id) {
        $export = new exportDoc($id);
        if ($export->loadSavedTemplate()) {
            $export->export();
            $this->render_nothing();
        } else {
            $this->redirect("export/error");
        }
    }

    function error_action() {
        
    }

    function removeTemplate_action($id) {
        $export = new exportDoc($id);
        $export->delete();
        foreach (array_slice(func_get_args(), 1) as $tmp) {
            $argstring .= "/$tmp";
        }
        $this->redirect("export/index$argstring");
    }

}

?>