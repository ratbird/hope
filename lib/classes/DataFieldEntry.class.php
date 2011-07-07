<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
* DataFieldEntry.class.php - <short-description>
*
* Copyright (C) 2005 - Martin Gieseking  <mgieseki@uos.de>
* Copyright (C) 2007 - Marcus Lunzenauer <mlunzena@uos.de>
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License as
* published by the Free Software Foundation; either version 2 of
* the License, or (at your option) any later version.
*/
require_once 'lib/functions.php';
require_once 'config/config.inc.php';
require_once 'lib/classes/DataFieldStructure.class.php';
require_once 'lib/classes/SeminarCategories.class.php';

/**
 * Enter description here...
 *
 */
abstract class DataFieldEntry
{
    public $value;
    public $structure;
    public $rangeID;

    /**
     * Enter description here...
     *
     * @param unknown_type $structure
     * @param unknown_type $rangeID
     * @param unknown_type $value
     */
    function __construct($structure = null, $rangeID = '', $value = null)
    {
        $this->structure = $structure;
        $this->rangeID = $rangeID;
        $this->value = $value;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $range_id
     * @param unknown_type $object_type
     * @param unknown_type $object_class_hint
     * @return unknown
     */
    public static function getDataFieldEntries($range_id, $object_type = '', $object_class_hint = '')
    {
        if(! $range_id)
            return false; // we necessarily need a range ID
        if(is_array($range_id))
        { // rangeID may be an array ("classic" rangeID and second rangeID used for user roles)
            $secRangeID = $range_id[1];
            $rangeID = $range_id[0]; // to keep compatible with following code
            if('usersemdata' !== $object_type && 'roleinstdata' !== $object_type)
            {
                $object_type = 'userinstrole';
            }
            $clause1 = "AND sec_range_id='$secRangeID'";
        }
        else
        {
            $rangeID = $range_id;
        }
        if(! $object_type)
            $object_type = get_object_type($rangeID);
        if($object_type)
        {
            switch ($object_type)
            {
                case 'sem':
                    if($object_class_hint)
                    {
                        $object_class = SeminarCategories::GetByTypeId($object_class_hint);
                    }
                    else
                    {
                        $object_class = SeminarCategories::GetBySeminarId($rangeID);
                    }
                    $clause2 = "object_class=" . (int) $object_class . " OR object_class IS NULL";
                    break;
                case 'inst':
                case 'fak':
                    if($object_class_hint)
                    {
                        $object_class = $object_class_hint;
                    }
                    else
                    {
                        $query = "SELECT type FROM Institute WHERE Institut_id = '$rangeID'";
                        $object_class = DBManager::get()->query($query)->fetchColumn();
                    }
                    $object_type = "inst";
                    $clause2 = "object_class=" . (int) $object_class . " OR object_class IS NULL";
                    break;
                case 'roleinstdata': //hmm tja, vermutlich so
                    $clause2 = '1';
                    break;
                case 'user':
                case 'userinstrole':
                case 'usersemdata':
                    $object_class = is_object($GLOBALS['perm']) ? DataFieldStructure::permMask($GLOBALS['perm']->get_perm($rangeID)) : 0;
                    $clause2 = "((object_class & " . (int) $object_class . ") OR object_class IS NULL)";
                    break;
                case 'plugin':
                    $clause2 = '(object_class=' . (int) $object_class_hint . ')';
            }
            $query = "SELECT a.*, content ";
            $query .= "FROM datafields a LEFT JOIN datafields_entries b ON (a.datafield_id=b.datafield_id AND range_id = '$rangeID' $clause1) ";
            $query .= "WHERE object_type ='$object_type' AND ($clause2) ORDER BY object_class, priority";
            $rs = DBManager::get()->query($query);
            $entries = array();
            while($data = $rs->fetch(PDO::FETCH_ASSOC))
            {
                $struct = new DataFieldStructure($data);
                $entries[$data['datafield_id']] = DataFieldEntry::createDataFieldEntry($struct, $range_id, $data['content']);
            }
        }
        return $entries;
    }

    // @static
    //hmm wird das irgendwo gebraucht (und wenn ja wozu)?
    /*
    public static function getDataFieldEntriesBySecondRangeID ($secRangeID) {
        $db = new DB_Seminar;
        $query  = "SELECT *, a.datafield_id AS id ";
        $query .= "FROM datafields a JOIN datafields_entries b ON a.datafield_id=b.datafield_id ";
        $query .= "AND sec_range_id = '$secRangeID'";
        $db->query($query);
        while ($db->next_record()) {
            $data = array('datafield_id' => $db->f('id'), 'name' => $db->f('name'), 'type' => $db->f('type'),
            'typeparam' => $db->f('typeparam'), 'object_type' => $db->f('object_type'), 'object_class' => $db->f('object_class'),
            'edit_perms' => $db->f('edit_perms'), 'priority' => $db->f('priority'), 'view_perms' => $db->f('view_perms'));
            $struct = new DataFieldStructure($data);
            $entry = DataFieldEntry::createDataFieldEntry($struct, array($db->f('range_id'), $secRangeID), $db->f('content'));
            $entries[$db->f("id")] = $entry;
        }
        return $entries;
    }
    */

    /**
     * Enter description here...
     *
     * @return unknown
     */
    function store()
    {
        $st = DBManager::get()->prepare("SELECT content FROM datafields_entries WHERE datafield_id=? AND range_id=? AND sec_range_id=?");
        $ok = $st->execute(array($this->structure->getID(), (string)$this->getRangeID() , (string)$this->getSecondRangeID()));
        if ($ok) {
            $old_value = $st->fetchColumn();
        }
        $query = "INSERT INTO datafields_entries (content, datafield_id, range_id, sec_range_id, mkdate, chdate)
                     VALUES (?,?,?,?,UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
                     ON DUPLICATE KEY UPDATE content=?, chdate=UNIX_TIMESTAMP()";
        $st = DBManager::get()->prepare($query);
        $ret = $st->execute(array($this->getValue() , $this->structure->getID() , $this->getRangeID() , $this->getSecondRangeID() , $this->getValue()));
        if ($ret) {
            NotificationCenter::postNotification('DatafieldDidUpdate', $this, array('changed' => $st->rowCount(), 'old_value' => $old_value));
        }
        return $ret;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $range_id
     * @return unknown
     */
    public static function removeAll($range_id)
    {
        if(is_array($range_id))
        {
            list ($rangeID, $secRangeID) = $range_id;
        }
        else
        {
            $rangeID = $range_id;
            $secRangeID = "";
        }
        if($rangeID && ! $secRangeID)
        {
            $where = "range_id = ?";
            $param = array($rangeID);
        }
        if($rangeID && $secRangeID)
        {
            $where = "range_id = ? AND sec_range_id = ?";
            $param = array($rangeID , $secRangeID);
        }
        if(! $rangeID && $secRangeID)
        {
            $where = "sec_range_id = ?";
            $param = array($secRangeID);
        }
        if($where)
        {
            $st = DBManager::get()->prepare("DELETE FROM datafields_entries WHERE $where");
            $ret = $st->execute($param);
            return $ret;
        }
    }

    /**
     * Enter description here...
     *
     * @return array() of supported types
     */
    public static function getSupportedTypes()
    {
        return array("bool" , "textline" , "textarea" , "selectbox" , "date" , "time" , "email" , "phone" , "radio" , "combo" , "link");
    }

    /**
     * "statische" Methode: liefert neues Datenfeldobjekt zu gegebenem Typ
     *
     * @param unknown_type $structure
     * @param unknown_type $rangeID
     * @param unknown_type $value
     * @return unknown
     */
    public static function createDataFieldEntry($structure, $rangeID = '', $value = '')
    {
        if(! is_object($structure))
            return false;
        $type = $structure->getType();
        if(in_array($type, DataFieldEntry::getSupportedTypes()))
        {
            $entry_class = 'DataField' . ucfirst($type) . 'Entry';
            return new $entry_class($structure, $rangeID, $value);
        }
        else
        {
            return false;
        }
    }

    /**
     * Enter description here...
     *
     * @return string type of entry
     */
    public function getType()
    {
        $class = strtolower(get_class($this));
        return substr($class, 9, strpos($class, 'entry') - 9);
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $entities
     * @return unknown
     */
    public function getDisplayValue($entities = true)
    {
        if($entities)
            return htmlReady($this->getValue());
        return $this->getValue();
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Enter description here...
     *
     * @return string name
     */
    public function getName()
    {
        return $this->structure->getName();
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function getId()
    {
        return $this->structure->getID();
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $name
     * @return unknown
     */
    function getHTML($name = '')
    {
        return $name;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $v
     */
    public function setValue($v)
    {
        $this->value = $v;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $submitted_value
     */
    public function setValueFromSubmit($submitted_value)
    {
        $this->setValue(remove_magic_quotes($submitted_value));
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $v
     */
    public function setRangeID($v)
    {
        $this->rangeID = $v;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $v
     */
    public function setSecondRangeID($v)
    {
        $this->rangeID = array(is_array($this->rangeID) ? $this->rangeID[0] : $this->rangeID , $v);
    }

    /**
     * Enter description here...
     *
     * @return boolean
     */
    public function isValid()
    {
        return true;
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
    function numberOfHTMLFields()
    {
        return 1;
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
    function getRangeID()
    {
        if(is_array($this->rangeID))
        {
            list ($rangeID, ) = $this->rangeID;
        }
        else
        {
            $rangeID = $this->rangeID;
        }
        return $rangeID;
    }

    /**
     * Enter description here...
     *
     * @return unknown
     */
    function getSecondRangeID()
    {
        if(is_array($this->rangeID))
        {
            list (, $secRangeID) = $this->rangeID;
        }
        else
        {
            $secRangeID = "";
        }
        return $secRangeID;
    }

    /**
     * Enter description here...
     *
     * @return boolean
     */
    function isVisible()
    {
        $users_own_range = ($this->getRangeID() == $GLOBALS['user']->id ? $GLOBALS['user']->id : '');
        return $this->structure->accessAllowed($GLOBALS['perm'], $GLOBALS['user']->id, $users_own_range);
    }

    /**
     * Enter description here...
     *
     * @return boolean
     */
    function isEditable()
    {
        return $this->structure->editAllowed($GLOBALS['perm']->get_perm());
    }
}

class DataFieldBoolEntry extends DataFieldEntry
{

    function getHTML($name)
    {
        $field_name = $name . '[' . $this->structure->getID() . ']';
        if($this->getValue())
            $checked = 'checked';
        return "<input type=\"hidden\" name=\"$field_name\" value=\"0\">
        <input type=\"checkbox\" name=\"$field_name\" value=\"1\" $checked>";
    }

    function getDisplayValue($entities = true) //wofür ist $entities? wird nicht benutzt?!?
    {
        return $this->getValue() ? _('Ja') : _('Nein');
    }

    function setValueFromSubmit($submitted_value)
    {
        $this->setValue((int) $submitted_value);
    }
}

class DataFieldTextlineEntry extends DataFieldEntry
{

    function getHTML($name)
    {
        $field_name = $name . '[' . $this->structure->getID() . ']';
        $valattr = 'value="' . $this->getDisplayValue() . '"';
        return "<input type=\"text\" name=\"$field_name\" $valattr>";
    }
}

class DataFieldTextareaEntry extends DataFieldEntry
{

    function getHTML($name)
    {
        $field_name = $name . '[' . $this->structure->getID() . ']';
        return sprintf('<textarea name="%s" rows="6" cols="58">%s</textarea>', $field_name, $this->getDisplayValue());
    }
}

class DataFieldEmailEntry extends DataFieldEntry
{

    function getHTML($name)
    {
        $field_name = $name . '[' . $this->structure->getID() . ']';
        return sprintf('<input name="%s" value="%s" size="30">', $field_name, $this->getDisplayValue());
    }

    function isValid()
    {
        if($this->getValue())
            return preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/", strtolower($this->getValue()));
        return true;
    }
}

class DataFieldLinkEntry extends DataFieldEntry
{

    public function getHTML($name)
    {
        $field_name = $name . '[' . $this->structure->getID() . ']';
        return sprintf('<input name="%s" value="%s" size="30">', $field_name, $this->getValue()==''?'http://':htmlready($this->getValue()));
    }

    public function getDisplayValue($entities = true)
    {
        if ($entities) {
            return FixLinks(htmlReady($this->getValue()));
        }
        else {
            return $this->getValue();
        }
    }

    public function setValueFromSubmit($submitted_value)
    {
        if ($submitted_value == 'http://') {
            $this->setValue('');
        }
        else {
            $this->setValue(remove_magic_quotes($submitted_value));
        }
    }

    public function isValid()
    {
        return (preg_match('%^(https?|ftp)://%', $this->getValue()) || $this->getValue() == '');
    }
}

class DataFieldSelectboxEntry extends DataFieldEntry
{

    function __construct($struct, $range_id, $value)
    {
        parent::__construct($struct, $range_id, $value);
        list ($values, $is_assoc) = $this->getParams();
        $this->is_assoc_param = $is_assoc;
        $this->type_param = $values;
        reset($values);
        if(is_null($this->getValue()))
        {
            if(! $is_assoc)
            {
                $this->setValue(current($values)); // first selectbox entry is default
            }
            else
            {
                $this->setValue((string) key($values));
            }
        }
    }

    function getHTML($name)
    {
        $field_name = $name . '[' . $this->structure->getID() . ']';
        $ret = "<select name=\"$field_name\">";
        foreach($this->type_param as $pkey => $pval)
        {
            $value = $this->is_assoc_param ? (string) $pkey : $pval;
            $sel = $value == $this->getValue() ? 'selected' : '';
            $ret .= sprintf('<option value="%s" %s>%s</option>', htmlReady($value), $sel, htmlReady($pval));
        }
        return $ret . "</select>";
    }

    function getParams()
    {
        $ret = array();
        $i = 0;
        $is_assoc = false;
        foreach(array_map('trim', explode("\n", $this->structure->getTypeParam())) as $p)
        {
            if(strpos($p, '=>') !== false)
            {
                $is_assoc = true;
                list ($key, $value) = array_map('trim', explode('=>', $p));
                $ret[$key] = $value;
            }
            else
            {
                $ret[$i] = $p;
            }
            ++ $i;
        }
        return array($ret , $is_assoc);
    }

    function getDisplayValue($entities = true)
    {
        $value = $this->is_assoc_param ? $this->type_param[$this->getValue()] : $this->getValue();
        return $entities ? htmlReady($value) : $value;
    }
}

class DataFieldRadioEntry extends DataFieldSelectboxEntry
{

    function getHTML($name)
    {
        $field_name = $name . '[' . $this->structure->getID() . ']';
        $ret = '';
        foreach($this->type_param as $pkey => $pval)
        {
            $value = $this->is_assoc_param ? (string) $pkey : $pval;
            $ret .= sprintf('<input type="radio" value="%s" name="%s"%s> %s', htmlReady($value), $field_name, $value == $this->getValue() ? ' checked="checked"' : '', htmlReady($pval));
        }
        return $ret;
    }
}

class DataFieldComboEntry extends DataFieldEntry
{

    function __construct($struct, $range_id, $value)
    {
        parent::__construct($struct, $range_id, $value);
        if(is_null($this->getValue()))
        {
            $values = explode("\n", $this->structure->getTypeParam());
            $this->setValue(trim($values[0])); // first selectbox entry is default
        }
    }

    function numberOfHTMLFields()
    {
        return 2;
    }

    function setValueFromSubmit($value)
    {
        parent::setValueFromSubmit($value[$value['combo']]);
    }

    function getHTML($name)
    {
        $field_name = $name . '[' . $this->structure->getID() . ']';
        $values = array_map('trim', explode("\n", $this->structure->getTypeParam()));
        $id = $this->structure->getID();
        $ret = sprintf('<input type="radio" value="select" id="combo_%s_select" name="%s"%s>', $id, $field_name . '[combo]', ($select = in_array($this->value, $values)) ? ' checked="checked"' : '');
        $ret .= sprintf('<select onFocus="$(\'#combo_%s_select\').attr(\'checked\', true);" name="%s">', $id, $field_name . '[select]');
        foreach($values as $val)
        {
            $val = trim(htmlentities($val, ENT_QUOTES));
            $sel = $val == $this->getValue() ? 'selected' : '';
            $ret .= "<option value=\"$val\" $sel>$val</option>";
        }
        $ret .= "</select> ";
        $ret .= sprintf('<input type="radio" value="text" id="combo_%s_text" name="%s"%s>', $id, $field_name . '[combo]', $select ? '' : ' checked="checked"');
        if($this->value && ! $select)
            $valattr = 'value="' . $this->getDisplayValue() . '"';
        $ret .= sprintf('<input name="%s" onFocus="$(\'#combo_%s_text\').attr(\'checked\', true);" %s>', $field_name . '[text]', $id, $valattr);
        return $ret;
    }
}

class DataFieldPhoneEntry extends DataFieldEntry
{

    function numberOfHTMLFields()
    {
        return 3;
    }

    function setValueFromSubmit($value)
    {
        if(is_array($value))
        {
            parent::setValueFromSubmit(str_replace(' ', '', implode("\n", array_slice($value, 0, 3))));
        }
    }

    function getDisplayValue($entities = true)
    {
        list ($country, $area, $phone) = explode("\n", $this->value);
        if($country != '' || $area != '' || $phone != '')
        {
            if($country)
                $country = "+$country";
            if($area)
            {
                $area = "(0)$area";
                if($phone)
                    $area .= '/';
            }
            return "$country $area$phone";
        }
        else
        {
            return '';
        }
    }

    function getHTML($name)
    {
        $name = $name . '[' . $this->structure->getID() . '][]';
        $parts = explode("\n", $this->value);
        for($i = 3 - count($parts); $i > 0; $i --)
            array_unshift($parts, '');
        $size = array(3 , 6 , 10);
        $title = array(_('Landesvorwahl ohne f&uuml;hrende Nullen') , _('Ortsvorwahl ohne f&uuml;hrende Null') , _('Rufnummer'));
        $prefix = array('+' , '(0)' , ' / ');
        $ret = '';
        foreach($parts as $i => $part)
        {
            //      $part = preg_replace('/^0+(.*)$/', '\1', $part);
            $ret .= sprintf('%s<input name="%s" maxlength="%d" size="%d" value="%s" title="%s">', $prefix[$i], $name, $size[$i], $size[$i] - 1, htmlentities($part, ENT_QUOTES), $title[$i]);
        }
        $ret .= '<font size="-1">';
        $ret .= ' ' . _('z.B.:') . ' +<span style="border-style:inset; border-width:2px;"> 49 </span>';
        $ret .= ' (0)<span style="border-style:inset; border-width:2px;"> 541 </span>';
        $ret .= ' / <span style="border-style:inset; border-width:2px;"> 969-0000 </span>';
        $ret .= '</font>';
        return $ret;
    }

    function isValid()
    {
        if(trim($this->value) == '')
            return true;
        return preg_match('/^[1-9][0-9]*\n[1-9][0-9]+\n[1-9][0-9]+(-[0-9]+)?$/', $this->value);
    }
}

class DataFieldDateEntry extends DataFieldEntry
{

    function numberOfHTMLFields()
    {
        return 3;
    }

    function setValueFromSubmit($value)
    {
        if(is_array($value) && $value[0] != '' && $value[1] != '' && $value[2] != '')
        {
            parent::setValueFromSubmit("$value[2]-$value[1]-$value[0]");
        }
    }

    function getDisplayValue($entries = true)
    {
        if(preg_match('/(\d+)-(\d+)-(\d+)/', $this->value, $m))
            return "$m[3].$m[2].$m[1]";
        return '';
    }

    function getHTML($name)
    {
        $field_name = $name . '[' . $this->structure->getID() . '][]';
        $parts = explode('-', $this->value);
        $ret = sprintf('<input name="%s" maxlength="2" size="1" value="%s" title="'._("Tag").'">', $field_name, $parts[2]);
        $ret .= ". ";
        //TODO: was ist, wenn studip auf englisch eingestellt ist?!? lieber srfttime oder so benutzen...
        $months = array('' , 'Januar' , 'Februar' , 'März' , 'April' , 'Mai' , 'Juni' , 'Juli' , 'August' , 'September' , 'Oktober' , 'Novemember' , 'Dezember');
        $ret .= "<select name=\"$field_name\" title=\""._("Monat")."\">";
        foreach($months as $i => $m)
            $ret .= sprintf('<option %s value="%s">%s</option>', ($parts[1] == $i ? 'selected' : ''), $i, $m);
        $ret .= "</select> ";
        $ret .= sprintf('<input name="%s" maxlength="4" size="3" value="%s" title="'._("Jahr").'">', $field_name, $parts[0]);
        return $ret;
    }

    function isValid()
    {
        if(trim($this->value) == '')
            return true;
        $parts = explode("-", $this->value);
        $valid = preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/', $this->value);
        return trim($this->value) != '' && $valid && checkdate($parts[1], $parts[2], $parts[0]);
    }
}

class DataFieldTimeEntry extends DataFieldEntry
{

    function numberOfHTMLFields()
    {
        return 2;
    }

    function setValueFromSubmit($value)
    {
        if(is_array($value))
        {
            parent::setValueFromSubmit("$value[0]:$value[1]");
        }
    }

    function getHTML($name)
    {
        $name = $name . '[' . $this->structure->getID() . '][]';
        $parts = explode(':', $this->value);
        $ret = sprintf('<input name="%s" maxlength="2" size="1" value="%s" title="'._("Stunden").'">:', $name, $parts[0]);
        $ret .= sprintf('<input name="%s" maxlength="2" size="1" value="%s" title="'._("Minuten").'">', $name, $parts[1]);
        return $ret;
    }

    function isValid()
    {
        $parts = explode(':', $this->value);
        return $parts[0] >= 0 && $parts[0] <= 24 && $parts[1] >= 0 && $parts[1] <= 59;
    }
}
?>
