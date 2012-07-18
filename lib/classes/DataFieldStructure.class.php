<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/**
 *  DataFieldStructure.class.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Martin Gieseking  <mgieseki@uos.de>
 * @author      Marcus Lunzenauer <mlunzena@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */



class DataFieldStructure
{

  /**
   * <FieldDescription>
   *
   * @access private
   * @var <type>
   */
  var $data;


  /**
   * <FieldDescription>
   *
   * @access private
   * @var <type>
   */
  var $numEntries;


  function DataFieldStructure($data='')
  {
    $this->data = $data ? $data : array();
    if (!$this->data['datafield_id'])
      $this->data['datafield_id'] = !$this->data['datafield_id'] ? md5(uniqid('fdhdgg')) : $id;  # we always need a valid unique ID
    /*
     * Performance-Tuning: mdiedric
     * numEntries wird zur Zeit nicht direkt verwendet
     */
     # $this->numEntries = $this->numberOfUsedEntries();
  }


  function getID()               {return $this->data['datafield_id'];}
  function getName()             {return $this->data['name'];}
  function getType()             {return $this->data['type'];}
  function getTypeParam()        {return $this->data['typeparam'];}
  function getObjectClass()      {return $this->data['object_class'];}
  function getObjectType()       {return $this->data['object_type'];}
  function getPriority()         {return $this->data['priority'];}
  function getEditPerms()        {return $this->data['edit_perms'];}
  function getViewPerms()        {return $this->data['view_perms'];}
  function getIsRequired()       {return (bool)$this->data['is_required']; }
  function getDescription()      {return $this->data['description'];}
  

  function getCachedNumEntries() {
    if (is_null($this->numEntries)) {
      $this->numEntries = $this->numberOfUsedEntries();
    }
    return $this->numEntries;
  }

  function setID($v)               {$this->data['datafield_id'] = $v;}
  function setName($v)             {$this->data['name'] = $v;}
  function setTypeParam($v)        {$this->data['typeparam'] = $v;}
  function setObjectClass($v)      {$this->data['object_class'] = $v;}
  function setObjectType($v)       {$this->data['object_type'] = $v;}
  function setPriority($v)         {$this->data['priority'] = $v;}
  function setEditPerms($v)        {$this->data['edit_perms'] = $v;}
  function setViewPerms($v)        {$this->data['view_perms'] = $v;}
  function setIsRequired($v)        {$this->data['is_required'] = $v;}
  function setDescription($v)        {$this->data['description'] = $v;}

  function setType($v) {
    $this->data['type'] = $v;
    if (!in_array($v, array('selectbox', 'radio', 'combo')))
      $this->setTypeParam('');
  }


  /**
   * Returns an HTML fragment used for editing select boxes
   *
   * @param  string  the name of this datafield
   *
   * @return string  the HTML fragment
   */
  function getHTMLEditor($name) {
    $ret = '';
    if (in_array($this->getType(), array('selectbox', 'radio', 'combo'))) {
      $content = $this->getTypeParam();
      $ret = "<textarea name=\"$name\" cols=\"20\" rows=\"8\" wrap=\"off\">" . htmlReady($content) . "</textarea>";
    }
    return $ret;
  }


  /**
   * Returns the count of entries for this datafield.
   *
   * @return integer  the count of entries for this datafield
   */
  function numberOfUsedEntries()
  {
    $id = $this->data['datafield_id'];

    $query = "SELECT COUNT(range_id) FROM datafields_entries WHERE datafield_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($id));
    return $this->numEntries = $statement->fetchColumn();
  }

    /**
     * Returns a list of all datatype classes with an id as key and a name as
     * value.
     *
     * @return array() list of all datatype classes
     */
    public static function getDataClass()
    {
        return array(
            "sem"          => _("Veranstaltungen"),
            "inst"         => _("Einrichtungen"),
            "user"         => _("Benutzer"),
            "userinstrole" => _("Benutzerrollen in Einrichtungen"),
            "usersemdata"  => _("Benutzer-Zusatzangaben in VA"),
            "roleinstdata" => _("Rollen in Einrichtungen")
        );
    }

  /**
   * Return the mask for the given permission
   *
   * @static
   *
   * @param  string   the name of the permission
   *
   * @return integer  the mask for the permission
   */
  function permMask($perm) {
    static $masks = array("user" => 1, "autor" => 2, "tutor" => 4, "dozent" => 8, "admin" => 16, "root" => 32, "self" => 64,);
    return $masks[$perm];
  }


  /**
   * liefert String zu gegebener user_class-Maske
   *
   * @static
   *
   * @param  integer  the user class mask
   *
   * @return string   a string consisting of a comma separated list of
   *                  permissions
   */
  function getReadableUserClass($class) {
    static $classes = array(1 => "user", 2 => "autor", 4 => "tutor", 8 => "dozent", 16 => "admin", 32 => "root", 64 => "self");
    foreach ($classes as $key=>$val) {
      if ($class & $key) {
        if ($ret)
          $ret .= ", ";
        $ret .= $val;
      }
    }
    return $ret;
  }


  /**
   * Returns a collection of structures of datafields filtered by objectType,
   * objectClass and unassigned objectClasses.
   *
   * @static
   *
   * @param  type     <description>
   * @param  type     <description>
   * @param  boolean  <description>
   *
   * @return array    <description>
   */
  function getDataFieldStructures($objectType=NULL, $objectClass='', $includeNullClass=false)
  {
    $expr = $params = array();
    if (isset($objectType)) {
      $expr[] = "object_type = :object_type";
      $params[':object_type'] = $objectType;
    }

    if ($objectClass) {
      $expr[] = "(object_class & :object_class" .
                  ($includeNullClass ? ' OR object_class IS NULL)' : ')');
      $params[':object_class'] = $objectClass;
    }

    $expr = empty($expr) ? '' : 'WHERE ' . join(' AND ', $expr);

    $query = "SELECT *
              FROM datafields
              {$expr}
              ORDER BY object_class, priority, name";
    $statement = DBManager::get()->prepare($query);
    $statement->execute($params);

    $ret = array();
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
      $ret[$row['datafield_id']] = new DataFieldStructure($row);
    }

    return $ret;
  }


  # load structure information from database
  function load() {
    if ($this->getID()) {
      $query = "SELECT * FROM datafields WHERE datafield_id = ?";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array($this->getID()));
      $this->data = $statement->fetch(PDO::FETCH_ASSOC);
    }
  }


  function store() {
    $data = $this->data;
    $db = DbManager::get();

    $query = "SELECT * FROM datafields WHERE datafield_id = ?";
    $statement = $db->prepare($query);
    $statement->execute(array($data['datafield_id']));
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if ($row['datafield_id']) {
        $data = array_merge($row, $data);
    }

    if (!in_array($data['type'], array('selectbox', 'radio', 'combo'))) {
        $data['typeparam'] = '';
    }
    $data['object_class'] = (int)$data['object_class'] ? (int)$data['object_class'] : null;
    if ($row['datafield_id']) {
        $st = $db->prepare("UPDATE datafields ".
                "SET name=?, object_type=?, ".
                "object_class=?, edit_perms=?, priority=?, ".
                "view_perms=?, type=?, typeparam=?, is_required=?, description=?, chdate=UNIX_TIMESTAMP() WHERE datafield_id=?");
    } else {
        $st = $db->prepare("INSERT INTO datafields ".
                "SET name=?, object_type=?, ".
                "object_class=?, edit_perms=?, priority=?, ".
                "view_perms=?, type=?, typeparam=?, is_required=?, description=?, chdate=UNIX_TIMESTAMP(), mkdate=UNIX_TIMESTAMP(), datafield_id=?");
    }

    $st->execute(array($data['name'], $data['object_type'],
                $data['object_class'], $data['edit_perms'], (int)$data['priority'],
                $data['view_perms'], (string)$data['type'], (string)$data['typeparam'],(bool)$data['is_required'],(string)$data['description'], $data['datafield_id']));
    return $st->rowCount();
  }


  function remove($id='') {
    if (!$id) {
      $id = $this->getID();
    }
    $query = "DELETE FROM datafields WHERE datafield_id = ?";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($id));
    return $statement->rowCount() > 0;
  }


  function accessAllowed($perm, $watcher = "", $user = "") {

    # everybody may see the information
    if ($this->getViewPerms() == "all")
      return TRUE;

    # permission ist high enough
    if ($perm->have_perm($this->getViewPerms()))
      return TRUE;

    # user may see his own data
    if ($watcher != "" && $user != "" && $user == $watcher)
      return TRUE;

    # nothing matched...
    return FALSE;
  }


  function editAllowed($userPerms) {
    if (!$this->getEditPerms())
      $this->load();
    return DataFieldStructure::permMask($userPerms)
           >=
           DataFieldStructure::permMask($this->getEditPerms());
  }
}
