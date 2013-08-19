<?php

require_once "ExportAPI/exportFormat.php";
require_once "ExportAPI/exportElement.php";
require_once "ExportAPI/exportAutoloader.php";

/**
 * exportDoc - a format to export any content without having to worry about
 * output
 *
 * The exportDoc(ument) is a container for a heap of elements. There are two
 * ways to add elements to the container:
 * 
 * 
 * Add content with php:
 * 
 * //create new exportDocument
 * $export = new exportDoc();
 * 
 * //create a new text element
 * $text = $export->add('text');
 * 
 * //edit the text element (elements are found in the elements folder and should
 * //have a documentation of their own
 * $text->setText('Some basic text');
 * 
 * //finally export with a specific format (e.g. pdf)
 * $export->export('pdf');
 * 
 * 
 * Add content with xml:
 * 
 * There are two possible ways of loading an exportDoc with xml
 * 
 * - $export->loadXML('<xmlcode></xmlcode>');
 * - put a xml file into the templates folder and $export->loadTemplate()
 * 
 * For further instructions check the example.xml
 * 
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
class exportDoc extends SimpleORMap {

    /**
     * @var string Name of the produced file 
     */
    public $filename = "export";
    private $elements = array();
    private $savedTemplates;
    private $formats;
    private $permission;
    private $context;
    private $isEditable;
    private $xml;

    public function __construct($id = null) {
        $this->db_table = 'export_templates';
        parent::__construct($id);
        if ($this->isNew()) {
            $this->user_id = $GLOBALS['user']->user_id;
        } else {
            if ($this->user_id != $GLOBALS['user']->user_id) {
                return false;
            }
        }
    }

    /**
     * Returns an exportlink for a template
     * @param array Stringarray with the wanted params
     * @return string Exportlink
     */
    public static function link($params) {
        $tmp = array("index");
        if (is_array($params)) {
            $tmp = array_merge($tmp, $params);
        } else {
            $tmp[] = $params;
        }
        return URLHelper::getLink("dispatch.php/export/" . join("/", $tmp));
    }

    /**
     * Loads an exportDoc by a XML string
     * 
     * @param string XML string
     */
    public function loadXML($xml) {
        $this->xml = simplexml_load_string($xml);
    }

    /**
     * Returns all saved templates of the database
     * 
     * @return array Array of all saved templates
     */
    public function getSavedTemplates() {
        if (!$this->savedTemplates) {
            $this->loadSavedTemplates();
        }
        return $this->savedTemplates;
    }

    /**
     * Loads a template from the template folder
     * 
     * @param array $args Arguments to substitute all $params
     */
    public function loadTemplate($args) {
        $this->template = array_shift($args);
        $this->params = array_merge($args, Request::getArray('param'));
        $filename = __DIR__ . '/ExportAPI/templates/' . $this->template . '.xml';
        
        // check if file exists and is well formed xml
        if (!file_exists($filename) || !$tmp = simplexml_load_file($filename))
            return false;
        $this->xml = preg_replace_callback("/\\$[0-9]+/", array($this, 'replace_params'), $tmp->asXML());
                
        $tmp = $tmp->asXML();
        
        $permissions = $this->getXML()->permissions;
        if ($permissions) {
            if ($permissions->context) {
                if (!$GLOBALS['perm']->have_studip_perm((string) $permissions->usertype, (string) $permissions->context))
                    return false;
            } else {
                if (!$GLOBALS['perm']->have_perm((string) $permissions->usertype))
                    return false;
            }
        }
        return true;
    }

    /**
     * Adds an element to the exportDocument and returns it for further usage
     * 
     * @param string Typename of the requested element
     * 
     * @return mixed object of new element if found. Otherwise false
     */
    public function add($type) {
        $classname = "export" . ucfirst($type);
        if (class_exists($classname)) {
            $result = new $classname;
            $this->elements[] = $result;
            return $result;
        }
        return false;
    }

    /**
     * Launches the export of a document
     * 
     * @param string format to use for the export. If not set it will check if
     * the exportDocument got its format already set or the template forces or
     * recommends a format.
     */
    public function export($format = null) {
        $format = $format ? : $this->format;
        $format = $format ? : $this->getDefaultFormat();
        $this->loadElements();
        $classname = "Export$format";
        $export = new $classname;
        $export->setContent($this->elements);
        $export->filename = $this->filename;
        $export->export();
    }

    /**
     * Returns the defaultFormat recommended by a template
     * 
     * @return string Format
     */
    private function getDefaultFormat() {
        $formats = $this->getFormats();
        return $formats[0];
    }

    /**
     * Substitutionfunction for the $params in a given XML file
     * 
     * @param string The hit (e.g. $0, $1, $42, ...)
     * 
     * @return string The replacement
     */
    private function replace_params($hit) {
        $index = substr($hit[0], 1);
        if ($this->params[$index]) {
            return $this->params[$index];
        }
        return "";
    }

    /**
     * Returns if the template has forced formats
     * 
     * @return boolean <b>true</b>if the template has forced formats otherwise
     * <b>false</b>
     */
    private function hasForcedFormats() {
        return empty($this->formats['forced']);
    }

    /**
     * Returns all given formats for the exportDocument
     * 
     * @return array Stringarray of all formats for the exportDocument
     */
    public function getFormats() {
        if (!$this->formats) {
            $this->loadFormats();
        }
        $existing = $this->loadExistingFormats();
        $this->formats['forced'] = array_intersect($this->formats['forced'], $existing);
        if (!$this->formats['forced']) {
            if ($this->formats['recommended']) {
                $result = $this->formats['recommended'];
                $diff = (array_diff($existing, $result));
                foreach ($diff as $new) {
                    array_push($result, $new);
                }
                return $result;
            } else {
                return $this->loadExistingFormats();
            }
        } else {
            return $this->formats['forced'];
        }
    }

    /**
     * Loads all existing formats
     * 
     * @return array Stringarray of all existing formats
     */
    private function loadExistingFormats() {
        $formats = glob(__DIR__ . "/ExportAPI/formats/Export*");
        foreach ($formats as &$name) {
            $name = substr(basename($name), 6, -4);
        }
        $this->formats['existing'] = $formats;
        return $formats;
    }

    /**
     * Loads all recommended and forced formats
     */
    private function loadFormats() {
        $xml = $this->getXML();
        if ($xml->format) {
            $this->formats['recommended'] = array();
            $this->formats['forced'] = array();
            foreach ($xml->format->children() as $format) {
                switch ($format->getName()) {
                    case "recommended":
                        array_push($this->formats['recommended'], (string) $format);
                        break;
                    case "forced":
                        array_push($this->formats['forced'], (string) $format);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * Checks if a loaded template has editable fields
     * 
     * @return boolean <b>true</b> if it has editable fields, <b>false</b> if
     * there are no fields to edit
     */
    public function isEditable() {
        $this->loadElements();
        return $this->isEditable;
    }

    /**
     * loads all the elements of a exportDocument from the given XML
     */
    private function loadElements() {
        if (!$this->elements) {
            $xml = $this->getXML();
            foreach ($xml->elements->children() as $child) {
                if ($new = $this->add($child->getName())) {
                    $new->load($child);
                    $this->isEditable ? : $this->isEditable = $new->isEditable();
                }
            }
        }
    }

    /**
     * Returns the simplexml object for the exportDocument
     * 
     * @return object simplexml object
     */
    public function getXML() {
        return simplexml_load_string($this->xml);
    }

    /**
     * Returns the XML String of the exportDocument
     * 
     * @return string The XML String of the exportDocument
     */
    public function getXMLString() {
        return $this->xml;
    }

    /**
     * Previews the exportDocument in HTML code. This is important if u want to
     * give users the possibility to change the marked spots in a XML File
     * 
     * @return array Array of all HTML Parts of the preview
     */
    public function preview() {
        $this->loadElements();
        $preview = array();
        foreach ($this->elements as $element) {
            $preview[] = $element->preview($elementCount);
            $elementCount++;
        }
        return $preview;
    }

    /**
     * Returns the paramstring for trails
     * 
     * @return string Paramstring
     */
    public function getParamString() {
        foreach ($this->params as $param) {
            $result .= "/$param";
        }
        return $result;
    }

    /**
     * Passes a part of the edit array to the specific element
     * 
     * @param array $edits All the edits
     */
    public function editTemplate($edits) {
        $this->edits = $edits;
        $this->loadElements();
        foreach ($this->elements as $element) {
            if ($element->isEditable()) {
                $element->edit(array_shift($edits));
            }
        }
    }

    /**
     * Saves an edited template to the database
     * 
     * @param string Requested format type
     * @param string Requested name
     */
    public function save($format, $name) {
        $this->format = $format;
        $this->name = $name;
        $this->edits = serialize($this->edits);
        $this->params = serialize($this->params);
        $this->store();
        $this->edits = unserialize($this->edits);
        $this->params = unserialize($this->params);
    }

    /**
     * Loads all saved templates for a user
     */
    private function loadSavedTemplates() {
        $this->savedTemplates = $this->findBySQL("user_id = ? AND template = ?", array($GLOBALS['user']->user_id, $this->template));
    }

    /**
     * Loads a saved template and applies the changes
     * 
     * @return boolean
     */
    public function loadSavedTemplate() {
        $this->edits = unserialize($this->edits);
        $this->params = unserialize($this->params);
        $load = array($this->template);
        $this->loadTemplate(array_merge($load, $this->params));
        $this->editTemplate($this->edits);
        return true;
    }

    /**
     * Returns the permission needed to open a template
     * 
     * @return mixed false if no permission is given otherwise the needed
     * permission
     */
    public function getPermission() {
        if (!$this->permission) {
            $xml = $this->getXML();
            $this->permission = (string) $xml->permissions->usertype;
        }
        return $this->permission ? : false;
    }

    /**
     * Returns the needed context
     * 
     * @return mixed false if no context is given otherwise the needed
     * context
     */
    public function getContext() {
        if (!$this->context) {
            $xml = $this->getXML();
            $this->context = (string) $xml->permissions->context;
        }
        return $this->context ? : false;
    }
    
}

?>
