<?php

/**
 * DBStorage.class.php 
 * 
 * Die Klasse implementiert das Modell zur datenbankgestuetzten Verwaltung 
 * von Dateien.   
 *
 *
 * @category    Stud.IP
 * @version     3.1
 * 
 * @author      Gerd Hoffmann <gerd.hoffmann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0
 * @copyright   2014 Carl von Ossietzky Universitaet Oldenburg
 */


//require_once 'lib/classes/document/StudipStorage.php';
//require_once 'FileStorage.class.php';


class DBStorage
 {     
  public static function authRootDir($entity, $entity_id)
   {
    $db = DBManager::get();
    
    switch ($entity)
     {
      case "user":
       $table = 'doc_user';
       break;
       
      case "seminar":
       //$table = "doc_seminar";
       break;
     }
     
    $dbSelect = "SELECT COUNT(*) FROM ". $table. " WHERE user_id = '". $entity_id. 
     "'". " AND type = 'dir' AND name = '". DIRECTORY_SEPARATOR. "'";         
                         
    $result = $db -> query($dbSelect);
    
    if ($result -> fetchColumn() > 0)
      return true; 
     else
      return false;
   }
   
   
  public static function createRootDir($entity, $entity_id)
   {       
    $db = DBManager::get();
    
    switch ($entity)
     {
      case "user":

       global $user;
       
       $dbInsert = $db -> prepare("INSERT INTO doc_user (id, user_id, type, name, 
        author, mkdate, size, env, stage, description, storage) VALUES (:id,
        :user_id, :type, :name, :author, :mkdate, :size, :env, :stage, :description, 
        :storage)");
         
       $id = md5(uniqid(rand(), true));
       $dirName = DIRECTORY_SEPARATOR;
       $type = 'dir';
       $author = $user -> Vorname. chr(160). $user -> Nachname;
       $mkdate = time();
       $size = '0';
       $stage = 0;
       $description = 'Möglicher Beschreibungstext des Dateibereichs';
       $storage = '1';
 
       $dbInsert -> bindParam('id', $id);
       $dbInsert -> bindParam('user_id', $entity_id);
       $dbInsert -> bindParam('name', $dirName);
       $dbInsert -> bindParam('type', $type);
       $dbInsert -> bindParam('author', $author);
       $dbInsert -> bindParam('mkdate', $mkdate);
       $dbInsert -> bindParam('size', $size);
       $dbInsert -> bindParam('env', $id);
       $dbInsert -> bindParam('stage', $stage);
       $dbInsert -> bindParam('description', $description);
       $dbInsert -> bindParam('storage', $storage);
       break;
       
      case "seminar":
       //
       break;
     }
            
    $dbInsert -> execute();
   }
   
   
  public static function deleteRootDir($entity, $entity_id)
   {
    $entity_exists = DBStorage::authRootDir($entity, $entity_id);
    
    if ($entity_exists)
     {
      $db = DBManager::get();
    
      switch ($entity)
       {
        case "user":
         $table = 'doc_user';
         break;
       
        case "seminar":
         //$table = 'doc_seminar';
         break;
       } 
      
      $dbSelect = "SELECT id, name FROM ". $table. " WHERE user_id = '". $entity_id. 
       "'". " AND NOT name = '". DIRECTORY_SEPARATOR. "'" ;
              
      $doc_set = $db -> query($dbSelect);
      $doc_delete = false;
      
      if ($doc_set != null)
       {
        $i = 0;
        foreach($doc_set as $row)
         {
          $doc_list[$i] = $row['id'];
          $i++;
         }    
    
        $doc_delete = FileStorage::removeFile($doc_list);
       }
       
      if ($doc_set == null || $doc_delete)
       {
        $dbDelete = $db -> prepare("DELETE FROM ". $table. " WHERE user_id = '". 
         $entity_id. "'");
                             
        $remove = $db -> execute($dbDelete);
        
        return $remove;
       }
     }
    
    return false;
   }
 }
