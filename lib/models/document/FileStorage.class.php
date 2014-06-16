<?php

/**
 * FileStorage.class.php 
 * 
 * Die Klasse implementiert das Modell zur pysikalischen Speicherung von Dateien 
 * in dem in der config.local.inc.php in der globalen Variablen "$USER_DOC_PATH" 
 * angegebenen Verzeichnis.   
 *
 * FileStorage wird von der StudipDocumentAPI in der Version 3.1 noch nicht direkt 
 * aufgerufen. Sie dient der Klasse DBStorage zunaechst als reine Hilfsklasse 
 * zum Umgang mit den von dieser verwalteten Dateien. 
 * 
 * @category    Stud.IP
 * @version     3.1
 * 
 * @author      Gerd Hoffmann <gerd.hoffmann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0
 * @copyright   2014 Carl von Ossietzky Universitaet Oldenburg
 */


//require_once 'lib/classes/document/StudipStorage.php';


class FileStorage
 {
  public static function removeFile($doc_list)
   {
    global $USER_DOC_PATH;
    
    chdir($USER_DOC_PATH);
    
    $max = count($doc_list);
    $success = true;
    
    for ($i = 0; $i <= $max; $i++)
     {
      $file = $doc_list[$i];
      
      if(!unlink($file))
       $success = false;
      $i++;
     }
     
    return $success;
   }
 }