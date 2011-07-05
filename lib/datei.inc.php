<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter005: TODO
# Lifter010: TODO
/*

datei.inc.php - basale Routinen zur Dateiverwaltung, dienen zum Aufbau des Ordnersystems
Copyright (C) 2001 Stefan Suchi <suchi@gmx.de>, Cornelis Kater <ckater@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

require_once('vendor/idna_convert/idna_convert.class.php');
require_once('lib/classes/StudipDocument.class.php');
require_once('lib/classes/StudipDocumentTree.class.php');
require_once('lib/raumzeit/IssueDB.class.php');


if ($GLOBALS['ZIP_USE_INTERNAL']) {
    include_once('vendor/pclzip/pclzip.lib.php');
}

function readfile_chunked($filename) {
   $chunksize = 1024 * 1024; // how many bytes per chunk
   $bytes = 0;
   $handle = fopen($filename, 'rb');
   if ($handle === false) {
       return false;
   }
   while (!feof($handle)) {
       $buffer = fread($handle, $chunksize);
       $bytes += strlen($buffer);
       echo $buffer;
   }
   fclose($handle);
   return $bytes; // return num. bytes delivered like readfile() does.
}

function parse_header($header){
    $ret = array();
    if (!is_array($header)){
        $header = explode("\n",trim($header));
    }
    if (preg_match("|^HTTP/[^\s]*\s(.*?)\s|", $header[0], $status)) {
        $ret['response_code'] = $status[1];
        $ret['response'] = trim($header[0]);
    } else {
        return $ret;
    }
    for($i = 0; $i < count($header); ++$i){
        $parts = null;
        if(trim($header[$i]) == "") break;
        $matches = preg_match('/^\S+:/', $header[$i], $parts);
        if ($matches){
            $key = trim(substr($parts[0],0,-1));
            $value = trim(substr($header[$i], strlen($parts[0])));
            $ret[$key] = $value;
        } else {
            $ret[trim($header[$i])] = trim($header[$i]);
        }
    }
    return $ret;
}

function parse_link($link, $level=0) {
    global $name, $the_file_name, $the_link, $locationheader, $parsed_link, $link_update;
    if ($level > 3)
        return FALSE;
    if ($link == "***" && $link_update)
        $link = getLinkPath($link_update);
    if (substr($link,0,6) == "ftp://") {
        // Parsing an FTF-Adress
        $url_parts = @parse_url( $link );
        $documentpath = $url_parts["path"];

        if (strpos($url_parts["host"],"@")) {
            $url_parts["pass"] .= "@".substr($url_parts["host"],0,strpos($url_parts["host"],"@"));
            $url_parts["host"] = substr(strrchr($url_parts["host"],"@"),1);
        }

        if (preg_match('/[^a-z0-9_.-]/i',$url_parts['host'])){ // exists umlauts ?
            $IDN = new idna_convert();
            $out = $IDN->encode(utf8_encode($url_parts['host'])); // false by error
            $url_parts['host'] = ($out)? $out : $url_parts['host'];
        }

        $ftp = ftp_connect($url_parts["host"]);

        if (!$url_parts["user"]) $url_parts["user"] = "anonymous";
        if (!$url_parts["pass"]) {
            $mailclass = new studip_smtp_class;
            $mailtmp = $mailclass->localhost;
            if ($mailtmp == "127.0.0.1") $mailtmp = "localhost.de";
            $url_parts["pass"] = "wwwrun%40".$mailtmp;
        }
        if (!@ftp_login($ftp,$url_parts["user"],$url_parts["pass"])) {
            ftp_quit($ftp);
            return FALSE;
        }
        $parsed_link["Content-Length"] = ftp_size($ftp, $documentpath);
        ftp_quit($ftp);
        if ($parsed_link["Content-Length"] != "-1")
            $parsed_link["HTTP/1.0 200 OK"] = "HTTP/1.0 200 OK";
        else
            $parsed_link = FALSE;
        $url_parts["pass"] = preg_replace("!@!","%40",$url_parts["pass"]);
        $the_link = "ftp://".$url_parts["user"].":".$url_parts["pass"]."@".$url_parts["host"].$documentpath;
        return $parsed_link;

    } else {
        $url_parts = @parse_url( $link );
        if (!empty( $url_parts["path"])){
            $documentpath = $url_parts["path"];
        } else {
            $documentpath = "/";
        }
        if ( !empty( $url_parts["query"] ) ) {
            $documentpath .= "?" . $url_parts["query"];
        }
        $host = $url_parts["host"];
        $port = $url_parts["port"];

        if (substr($link,0,8) == "https://") {
            $ssl = TRUE;
            if (empty($port)) $port = 443;
        } else {
            $ssl = FALSE;
        }
        if (empty( $port ) ) $port = "80";

        if (preg_match('/[^a-z0-9_.-]/i',$host)){ // exists umlauts ?
            $IDN = new idna_convert();
            $out = $IDN->encode(utf8_encode($host)); // false by error
            $host = ($out)? $out : $host;
            $pwtxt = ($url_parts['user'] && $url_parts['pass'])? $url_parts['user'].':'. $url_parts['pass'].'@':'';
            $the_link = $url_parts['scheme'].'://'.$pwtxt.$host.':'.$port.$documentpath;
        }
        $socket = @fsockopen( ($ssl? 'ssl://':'').$host, $port, $errno, $errstr, 10 );
        if (!$socket) {
            //echo "$errstr ($errno)<br>\n";
        } else {
            $urlString = "GET ".$documentpath." HTTP/1.0\r\nHost: $host\r\n";
            if ($url_parts["user"] && $url_parts["pass"]) {
                $pass = $url_parts["pass"];
                $user = $url_parts["user"];
                $urlString .= "Authorization: Basic ".base64_encode("$user:$pass")."\r\n";
            }
            $urlString .= "Connection: close\r\n\r\n";
            fputs($socket, $urlString);
            stream_set_timeout($socket, 5);
            $response = '';
            do {
                $response .= fgets($socket, 128);
                $info = stream_get_meta_data($socket);
            } while (!feof($socket) && !$info['timed_out'] && strlen($response) < 1024);
            fclose($socket);
        }
        $parsed_link = parse_header($response);
        // Weg über einen Locationheader:
        if (($parsed_link["HTTP/1.1 302 Found"] || $parsed_link["HTTP/1.0 302 Found"]) && $parsed_link["Location"]) {
            $the_file_name = basename($url_parts["path"]);
            $the_link = $parsed_link["Location"];
            parse_link($parsed_link["Location"],$level + 1);
        }
        return $parsed_link;
    }
}


/**
 * creates a zip file from given ids in tmp directory
 *
 * @param array $file_ids array of document ids
 * @param bool $perm_check if true, files are checked for folder permissions
 * @param bool $size_check if true, number and size of files are checked against config values
 * @return string filename(id) of the created zip without path
 */
function createSelectedZip ($file_ids, $perm_check = TRUE, $size_check = false) {
    global $TMP_PATH, $ZIP_PATH, $SessSemName;
    $zip_file_id = false;
    $max_files = Config::GetInstance()->getValue('ZIP_DOWNLOAD_MAX_FILES');
    $max_size = Config::GetInstance()->getValue('ZIP_DOWNLOAD_MAX_SIZE') * 1024 * 1024;
    if(!$max_files && !$max_size) $size_check = false;

    if ( is_array($file_ids) && !($size_check && count($file_ids) > $max_files)){
        if ($perm_check){
            $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessSemName[1]));
            $allowed_folders = $folder_tree->getReadableFolders($GLOBALS['user']->id);
            if (is_array($allowed_folders)) $folders_cond = " AND range_id IN ('".join("','",$allowed_folders)."')";
            else ($folders_cond = " AND 0 ");
        }
        $db = new DB_Seminar();
        $in = "('".join("','",$file_ids)."')";
        $query = sprintf ("SELECT SUM(filesize) FROM dokumente WHERE url='' AND dokument_id IN %s %s", $in, ($perm_check) ? "AND seminar_id = '".$SessSemName[1]."' $folders_cond" : "");
        $db->query($query);
        $db->next_record();
        if(!($size_check && $db->f(0) > $max_size)){
            $zip_file_id = md5(uniqid("jabba",1));

            //create temporary Folder
            $tmp_full_path = "$TMP_PATH/$zip_file_id";
            mkdir($tmp_full_path,0700);
            $filelist = array();
            //create folder content
            $in = "('".join("','",$file_ids)."')";
            $query = sprintf ("SELECT dokument_id, filename, author_name, filesize, name, description, FROM_UNIXTIME(chdate) as chdate FROM dokumente WHERE dokument_id IN %s %s ORDER BY chdate, name, filename", $in, ($perm_check) ? "AND seminar_id = '".$SessSemName[1]."' $folders_cond" : "");
            $db->query($query);
            while ($db->next_record()) {
                if(check_protected_download($db->f('dokument_id'))){
                    $filename = prepareFilename($db->f('filename'), FALSE, $tmp_full_path);
                    if (@copy(get_upload_file_path($db->f('dokument_id')), $tmp_full_path.'/'.$filename)) {
                    TrackAccess($db->f('dokument_id'),'dokument');
                        $filelist[] = $db->Record + array('path' => substr($tmp_full_path.'/'.$filename, strlen("$TMP_PATH/$zip_file_id/")));
                }
            }
            }
            $caption = array('filename' => _("Dateiname"), 'filesize' => _("Größe"), 'author_name' => _("Ersteller"), 'chdate' => _("Datum"), 'name' =>  _("Name"), 'description' => _("Beschreibung"), 'path' => _("Pfad"));
            array_to_csv($filelist, $tmp_full_path . '/' . _("dateiliste.csv"), $caption);
            //zip stuff
            create_zip_from_directory($tmp_full_path, $tmp_full_path);
            rmdirr($tmp_full_path);
            @rename($tmp_full_path .".zip" , $tmp_full_path);
        }
    }
    return $zip_file_id;
}

/**
 * creates a zip file from all files in given folder, including subfolders
 *
 * @param string $folder_id id of document folder
 * @param bool $perm_check if true, files are checked for folder permissions
 * @param bool $size_check if true, number and size of files are checked against config values
 * @return string filename(id) of the created zip without path
 */
function createFolderZip ($folder_id, $perm_check = TRUE, $size_check = false) {
    global $TMP_PATH, $ZIP_PATH;
    $zip_file_id = false;
    $max_files = Config::GetInstance()->getValue('ZIP_DOWNLOAD_MAX_FILES');
    $max_size = Config::GetInstance()->getValue('ZIP_DOWNLOAD_MAX_SIZE') * 1024 * 1024;
    if(!$max_files && !$max_size) $size_check = false;

    if(!($size_check && (doc_count($folder_id) > $max_files || doc_sum_filesize($folder_id) > $max_size))){
        $zip_file_id = md5(uniqid("jabba",1));

        //create temporary Folder
        $tmp_full_path = "$TMP_PATH/$zip_file_id";
        mkdir($tmp_full_path,0700);

        //create folder content
        $filelist = createTempFolder($folder_id, $tmp_full_path, $perm_check);

        $caption = array('filename' => _("Dateiname"), 'filesize' => _("Größe"), 'author_name' => _("Ersteller"), 'chdate' => _("Datum"), 'name' =>  _("Name"), 'description' => _("Beschreibung"), 'path' => _("Pfad"));
        array_to_csv($filelist, $tmp_full_path . '/' . _("dateiliste.csv"), $caption);
        //zip stuff
        create_zip_from_directory($tmp_full_path, $tmp_full_path);
        rmdirr($tmp_full_path);
        @rename($tmp_full_path .".zip" , $tmp_full_path);
    }
    return $zip_file_id;
}

/**
 * used by createFolderZip() to dive into subfolders
 * collects a list of file metadata and returns it when recursion finishes
 *
 * @param string $folder_id id of a folder
 * @param string $tmp_full_path temporary path
 * @param bool $perm_check if true, files are checked for folder permissions
 * @param bool $in_recursion used internally to indicate recursive call
 * @return array assoc array with metadata from zipped files
 */
function createTempFolder($folder_id, $tmp_full_path, $perm_check = TRUE, $in_recursion = false) {
    global $SessSemName;
    static $filelist;

    if ($in_recursion === false) {
        $filelist = array();
        $tmp_path = $tmp_full_path;
    }
    $db = new DB_Seminar();
    if ($perm_check){
        $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessSemName[1]));
        if (!$folder_tree->isDownloadFolder($folder_id, $GLOBALS['user']->id)) return false;
    }
    //copy all documents from this folder to the temporary folder
    $linkinfo = FALSE;
    $query = sprintf ("SELECT dokument_id, filename, url, author_name, filesize, name, description, FROM_UNIXTIME(chdate) as chdate FROM dokumente WHERE range_id = '%s' %s ORDER BY name, filename", $folder_id, ($perm_check) ? "AND seminar_id = '".$SessSemName[1]."'" : "");
    $db->query($query);
    while ($db->next_record()) {
        if ($db->f("url") != "") {  // just a linked file
            $linkinfo .= "\r\n".$db->f("filename");
        } else {
            if(check_protected_download($db->f('dokument_id'))){
                $filename = prepareFilename($db->f('filename'), FALSE, $tmp_full_path);
                if (copy(get_upload_file_path($db->f('dokument_id')), $tmp_full_path.'/'.$filename)) {
                TrackAccess($db->f('dokument_id'),'dokument');
                    $filelist[] = $db->Record + array('path' => $tmp_full_path.'/'.$filename);
            }
        }
    }
    }
    if ($linkinfo) {
        $linkinfo = _("Hinweis: die folgenden Dateien sind nicht im Archiv enthalten, da sie lediglich verlinkt wurden:").$linkinfo;
        $fp = fopen ("$tmp_full_path/info.txt","a");
        fwrite ($fp,$linkinfo);
        fclose ($fp);
    }

    $db->query("SELECT folder_id, name FROM folder WHERE range_id = '$folder_id' ORDER BY name");
    while ($db->next_record()) {
        $foldername = prepareFilename($db->f('name'), FALSE, $tmp_full_path);
        $tmp_sub_full_path = $tmp_full_path.'/'.$foldername;
        mkdir($tmp_sub_full_path, 0700);
        createTempFolder($db->f("folder_id"), $tmp_sub_full_path, $perm_check, true);
    }
    if ($in_recursion === false) {
       array_walk($filelist, create_function('&$a', '$a["path"] = substr($a["path"], ' . (int)strlen($tmp_path) . ');'));
       return $filelist;
    } else {
        return true;
    }
}


/**
 * Returns the read- and executable subfolders to a given folder_id
 * @folder_id: id of the target folder
 * @return: array($subfolders, $numberofsubfolders)
 */

function getFolderChildren($folder_id){
    global $SessionSeminar, $user;

    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));

    if (!$folder_tree->isReadable($folder_id, $user->id)
    || !$folder_tree->isExecutable($folder_id, $user->id)){
        return array(0,0);
    } else {
        $num_kids = $folder_tree->getNumKids($folder_id);
        $kids = array();
        if ($num_kids){
            foreach($folder_tree->getKids($folder_id) as $one){
                if($folder_tree->isExecutable($one, $user->id)) $kids[] = $one;
            }
        }
        return array($kids, count($kids));
    }
}

function getFolderId($parent_id, $in_recursion = false){
    static $kidskids;
        if (!$kidskids || !$in_recursion){
            $kidskids = array();
        }
        $kids = getFolderChildren($parent_id);
        if ($kids[1]){
            $kidskids = array_merge((array)$kidskids,(array)$kids[0]);
            for ($i = 0; $i < $kids[1]; ++$i){
                getFolderId($kids[0][$i],true);
            }
        }
        return (!$in_recursion) ? $kidskids : null;
}

/**
 * Counts and returns the number files in the given folder and subfolders.
 * Files not visible to the current user are not counted
 *
 * @param $parent_id     a folder id
 * @param $range_id      the range id for the folder, course or institute id
 * @return integer
 */
function doc_count($parent_id, $range_id = null) {
    global $SessionSeminar, $user;
    if ($range_id === null)  {
        $range_id = $SessionSeminar;
    }
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $range_id));
    $db=new DB_Seminar;
    $arr = $folder_tree->getReadableKidsKids($parent_id,$user->id);
    if($folder_tree->isReadable($parent_id,$user->id) && $folder_tree->isExecutable($parent_id,$user->id)) $arr[] = $parent_id;
    if (!(is_array($arr) && count($arr))) return 0;
    $in="('".join("','",$arr)."')";
    $db->query ("SELECT count(*) as count FROM dokumente WHERE range_id IN $in");
    $db->next_record();
    return $db->Record[0];
}

function doc_sum_filesize ($parent_id) {
    global $SessionSeminar, $user;
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));
    $db=new DB_Seminar;
    $arr = $folder_tree->getReadableKidsKids($parent_id,$user->id);
    if($folder_tree->isReadable($parent_id,$user->id) && $folder_tree->isExecutable($parent_id,$user->id)) $arr[] = $parent_id;
    if (!(is_array($arr) && count($arr))) return 0;
    $in="('".join("','",$arr)."')";
    $db->query ("SELECT sum(filesize) FROM dokumente WHERE url='' AND range_id IN $in");
    $db->next_record();
    return $db->Record[0];
}

function doc_newest ($parent_id) {
    global $SessionSeminar, $user;
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));
    $db=new DB_Seminar;
    $arr = $folder_tree->getReadableKidsKids($parent_id,$user->id);
    if($folder_tree->isReadable($parent_id,$user->id) && $folder_tree->isExecutable($parent_id,$user->id)) $arr[] = $parent_id;
    if (!(is_array($arr) && count($arr))) return 0;
    $in="('".join("','",$arr)."')";
    $db->query ("SELECT max(chdate), max(mkdate) FROM dokumente WHERE range_id IN $in ");
    $db->next_record();
    if ($db->Record[0] > $db->Record[1])
        return $db->Record[0];
    else
        return $db->Record[1];
}

function doc_challenge ($parent_id){
    global $SessionSeminar, $user;
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));
    $db=new DB_Seminar;
    $arr = $folder_tree->getReadableKidsKids($parent_id,$user->id);
    if($folder_tree->isReadable($parent_id,$user->id) && $folder_tree->isExecutable($parent_id,$user->id)) $arr[] = $parent_id;
    if (!(is_array($arr) && count($arr))) return 0;
    $in="('".join("','",$arr)."')";
    $db->query ("SELECT dokument_id FROM dokumente WHERE range_id IN $in");
    while($db->next_record()) $result[] = $db->Record[0];
    return $result;
}

function get_user_documents_in_folder($folder_id, $user_id){
    $db = new DB_Seminar("SELECT filename, filesize, chdate FROM dokumente WHERE range_id='$folder_id' AND user_id='$user_id'");
    $ret = array();
    while ($db->next_record()){
        $ret[] = $db->f('filename') . ' ('.round($db->f('filesize')/1024).'kB, '.date("d.m.Y - H:i", $db->f('chdate')).')';
    }
    return $ret;
}

function move_item($item_id, $new_parent, $change_sem_to = false) {
    global $SessionSeminar;
    $db = new DB_Seminar;
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));

    if ($change_sem_to && !$folder_tree->isFolder($item_id)){
        $db->query("SELECT folder_id FROM folder WHERE range_id='$change_sem_to'");
        if ($db->next_record()){
            $new_folder_id = $db->f(0);
        } else {
            return false;
        }
    }


    if ($item_id != $new_parent) {

        $doc = new StudipDocument($item_id);

        if ($change_sem_to && $new_folder_id) {
            $doc['range_id'] = $new_folder_id;
            $doc['seminar_id'] = $change_sem_to;
        }
        else {
            $doc['range_id'] = $new_parent;
        }

        if (!$doc->store()) {
            //we want to move a folder, so we have first to check if we want to move a folder in a subordinated folder

            $folder = getFolderId($item_id);

            if (is_array($folder) && in_array($new_parent, $folder)) $target_is_child = true;

            if (!$target_is_child){
                if ($change_sem_to){
                    $db->query("UPDATE folder SET range_id='".$new_parent."' WHERE folder_id = '$item_id'");
                    $folder[] = $item_id;
                    // TODO (mlunzena): notify these documents
                    $db->query("UPDATE dokumente SET seminar_id='$change_sem_to' WHERE range_id IN('".join("','", $folder)."')");
                    $folder_tree->init();
                    return array(count($folder), (int)$db->affected_rows());
                } else {
                    $db->query("UPDATE folder SET range_id='$new_parent' WHERE folder_id = '$item_id'");
                    $folder_tree->init();
                    return array(1, doc_count($item_id));
                }
            }

        } else {
            return array(0,1);
        }
    }
    return false;
}

function copy_item($item_id, $new_parent, $change_sem_to = false) {
    global $SessionSeminar;

    $db=new DB_Seminar;
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));

    if ($change_sem_to && !$folder_tree->isFolder($item_id)){
        $db->query("SELECT folder_id FROM folder WHERE range_id='$change_sem_to'");
        if ($db->next_record()){
            $new_folder_id = $db->f(0);
        } else {
            return false;
        }
    }

    if ($item_id != $new_parent) {
        $db->query("SELECT dokument_id FROM dokumente WHERE dokument_id = '$item_id'");
        if ($db->next_record()){
            $ret = copy_doc($item_id,
                            (($change_sem_to) ? $new_folder_id : $new_parent),
                            $change_sem_to);

            return ($ret ? array(0,1) : false);
        } else {
            //we want to move a folder, so we have first to check if we want to move a folder in a subordinated folder
            $folder = getFolderId($item_id);

            if (is_array($folder) && in_array($new_parent, $folder)) $target_is_child = true;

            $seed = md5(uniqid('blaofuasof',1));

            if (!$target_is_child){
                if (!($folder_count = copy_folder($item_id, $new_parent, $seed)) ){
                    return false;
                }
                $folder[] = $item_id;
                $db->query("SELECT dokument_id, range_id FROM dokumente WHERE range_id IN('".join("','", $folder)."')");
                while($db->next_record()){
                    $doc_count += copy_doc($db->f('dokument_id'), md5($db->f('range_id').$seed), $change_sem_to);
                }
                $folder_tree->init();
                return array($folder_count, $doc_count);
            }
        }
    }
    return false;
}

function copy_doc($doc_id, $new_range, $new_sem = false)
{
    $stmt = DBManager::get()->prepare(
        "SELECT seminar_id, user_id, name, description, filename, mkdate, ".
        "filesize, autor_host, author_name, url, protected ".
        "FROM dokumente WHERE dokument_id = ?");
    $stmt->execute(array($doc_id));
    $src = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$src) {
        return false;
    }
    $new = array_merge($src, array(
        'range_id'    => $new_range,
        'chdate'      => time(),
        'download'    => 0,
        'priority'    => 0
    ));

    if ($new_sem) {
        $new['seminar_id'] = $new_sem;
    }

    // create a new document copying the original
    if (!$new['url']) {
        return (bool) StudipDocument::createWithFile(get_upload_file_path($doc_id), $new);
    }

    // this is just a link, just create the document
    $doc = new StudipDocument();
    $doc->setData($new);
    return $doc->store();
}

function copy_folder($folder_id, $new_range, $seed = false){
    $db = new DB_Seminar();
    if (!$seed){
        $seed = md5(uniqid('blaofuasof',1));
    }
    $db->query("SELECT MD5(CONCAT(folder_id,'$seed')), '$new_range', user_id, name,
                description, mkdate, " .time(). " as chdate,permission FROM folder WHERE folder_id='$folder_id'");
    if ($db->next_record()){
        $record = $db->Record;
        $record[3] = mysql_escape_string($record[3]);
        $record[4] = mysql_escape_string($record[4]);
        $db->query("INSERT INTO folder (folder_id, range_id, user_id, name,
                    description, mkdate, chdate,permission) VALUES ( '{$record[0]}','{$record[1]}',
                    '{$record[2]}','{$record[3]}','{$record[4]}',{$record[5]},
                    {$record[6]},{$record[7]})");
        if (!$db->affected_rows()){
            return false;
        } else {
            $count = 1;
            $folder = getFolderId($folder_id);
            if (is_array($folder)){
                foreach($folder as $id){
                    $db->query("SELECT MD5(CONCAT(folder_id,'$seed')), MD5(CONCAT(range_id,'$seed')), user_id, name,
                                description, mkdate, " .time(). " as chdate,permission FROM folder WHERE folder_id='$id'");
                    if ($db->next_record()){
                        $record = $db->Record;
                        $record[3] = mysql_escape_string($record[3]);
                        $record[4] = mysql_escape_string($record[4]);
                        $db->query("INSERT INTO folder (folder_id, range_id, user_id,
                                    name, description, mkdate, chdate, permission) VALUES (
                                    '{$record[0]}','{$record[1]}','{$record[2]}',
                                    '{$record[3]}','{$record[4]}',{$record[5]},
                                    {$record[6]},{$record[7]})");
                        $count += $db->affected_rows();
                    }
                }
            }
            return $count;
        }
    }
}

function edit_item ($item_id, $type, $name, $description, $protected=0, $url = "", $filesize="") {
    global $SessionSeminar;

    $db=new DB_Seminar;
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));

    if ($url != ""){
        $url_parts = parse_url($url);
        $the_file_name = basename($url_parts['path']);
    }
    if ($protected == "on") $protected=1;

    if ($type){
        $db->query("UPDATE folder SET  description='$description' " . (strlen($name) ? ", name='$name'" : "" ). " WHERE folder_id ='$item_id'");
        if($GLOBALS['perm']->have_studip_perm('tutor', $SessionSeminar)){
            if ($folder_tree->permissions_activated) {
                foreach(array('r'=>'read','w'=>'write','x'=>'exec') as $p => $v){
                    if ($_REQUEST["perm_$v"]) $folder_tree->setPermission($item_id, $p);
                    else $folder_tree->unsetPermission($item_id, $p);
                }
            }
            if ($_REQUEST["perm_folder"]) $folder_tree->setPermission($item_id, 'f');
            else $folder_tree->unsetPermission($item_id, 'f');
        }
        return !!$db->affected_rows();
    } else {

        $doc = new StudipDocument($item_id);
        $doc->setData(
            array(
                'name'        => $name,
                'description' => $description,
                'protected'   => $protected
            ));

        if ($url != "") {
            $doc["url"] = $url;
            $doc["filename"] = $the_file_name;
        }

        return !!$doc->store();
    }
}

function create_folder ($name, $description, $parent_id, $permission = 7) {
    global $user, $SessionSeminar;
    $db=new DB_Seminar;
    $id=md5(uniqid("salmonellen",1));
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));

    $db->query("INSERT INTO folder SET name='$name', folder_id='$id', description='$description', range_id='$parent_id', user_id='".$user->id."',permission='$permission', mkdate='".time()."', chdate='".time()."'");
    if ($db->affected_rows()) {
        $folder_tree->init();
        return $id;
        }
    }

## Upload Funktionen ################################################################################

//Ausgabe des Formulars
function form($refresh = FALSE) {
    global $UPLOAD_TYPES,$range_id,$SessSemName,$user,$folder_system_data;

    $sem_status = $GLOBALS['perm']->get_studip_perm($SessSemName[1]);

    // add skip link (position in list is one before main content => 99)
    SkipLinks::addIndex(_("Datei hochladen"), 'upload_form', 99);

    //erlaubte Dateigroesse aus Regelliste der Config.inc.php auslesen
    if ($UPLOAD_TYPES[$SessSemName["art_num"]]) {
        $max_filesize=$UPLOAD_TYPES[$SessSemName["art_num"]]["file_sizes"][$sem_status];
    }   else {
        $max_filesize=$UPLOAD_TYPES["default"]["file_sizes"][$sem_status];
    }
    $c=1;

    if ($folder_system_data['zipupload'])
        $print="\n<br><br>" . _("Sie haben diesen Ordner zum Upload ausgew&auml;hlt:")
            . '<br>' . _("Die Dateien und Ordner, die im hochzuladenden Ziparchiv enthalten sind, werden in diesen Ordner entpackt.") .  "<br><br><center><table width=\"90%\" style=\"border: 1px solid #000000;\" border=0 cellpadding=2 cellspacing=3 id=\"upload_form\">";
    else if (!$refresh)
        $print="\n<br><br>" . _("Sie haben diesen Ordner zum Upload ausgew&auml;hlt:") . "<br><br><center><table width=\"90%\" style=\"border: 1px solid #000000;\" border=0 cellpadding=2 cellspacing=3 id=\"upload_form\">";
    else
        $print="\n<br><br>" . _("Sie haben diese Datei zum Aktualisieren ausgew&auml;hlt. Sie <b>&uuml;berschreiben</b> damit die vorhandene Datei durch eine neue Version!") . "<br><br><center><table width=\"90%\" style=\"border: 1px solid #000000;\" border=0 cellpadding=2 cellspacing=3 id=\"upload_form\">";
    $print.="\n";
    $print.="\n<tr><td class=\"steel1\" width=\"20%\"><font size=-1><b>";

    //erlaubte Upload-Typen aus Regelliste der Config.inc.php auslesen
    if (!$folder_system_data['zipupload']) {
        if ($UPLOAD_TYPES[$SessSemName["art_num"]]) {
            if ($UPLOAD_TYPES[$SessSemName["art_num"]]["type"] == "allow") {
                $i=1;
                $print.= _("Unzul&auml;ssige Dateitypen:") . "</b><font></td><td class=\"steel1\" width=\"80%\"><font size=-1>";
                foreach ($UPLOAD_TYPES[$SessSemName["art_num"]]["file_types"] as $ft) {
                    if ($i !=1)
                        $print.= ", ";
                    $print.= strtoupper ($ft);
                    $i++;
                    }
                }
            else {
                $i=1;
                $print.= _("Zul&auml;ssige Dateitypen:") . "</b><font></td><td class=\"steel1\" width=\"80%\"><font size=-1>";
                foreach ($UPLOAD_TYPES[$SessSemName["art_num"]]["file_types"] as $ft) {
                    if ($i !=1)
                        $print.= ", ";
                    $print.= strtoupper ($ft);
                    $i++;
                    }
                }
            }
        else {
            if ($UPLOAD_TYPES["default"]["type"] == "allow") {
                $i=1;
                $print.= _("Unzul&auml;ssige Dateitypen:") . "</b><font></td><td class=\"steel1\" width=\"80%\"><font size=-1>";
                foreach ($UPLOAD_TYPES["default"]["file_types"] as $ft) {
                    if ($i !=1)
                        $print.= ", ";
                    $print.= strtoupper ($ft);
                    $i++;
                    }
                }
            else {
                $i=1;
                $print.= _("Zul&auml;ssige Dateitypen:") . "</b></td><font><td class=\"steel1\" width=\"80%\"><font size=-1>";
                foreach ($UPLOAD_TYPES["default"]["file_types"] as $ft) {
                    if ($i !=1)
                        $print.= ", ";
                    $print.= strtoupper ($ft);
                    $i++;
                    }
                }
            }
    } else {
        $print.= _("Zul&auml;ssige Dateitypen:") . "</b></td><font><td class=\"steel1\" width=\"80%\"><font size=-1>";
        $print .= 'ZIP';
    }
    $print.="</font></td></tr>";
    $print.="\n<tr><td class=\"steel1\" width=\"20%\"><font size=-1><b>" . _("Maximale Gr&ouml;&szlig;e:") . "</b></font></td><td class=\"steel1\" width=\"80%\"><font size=-1><b>".($max_filesize / 1048576)." </b>" . _("Megabyte") . "</font></td></tr>";
    if ($folder_system_data['zipupload']) {
        $print.="\n<tr><td class=\"steel1\" width=\"20%\"><font size=-1><b>" . _("Maximaler Inhalt des Ziparchivs:")
            . "</b></font></td><td class=\"steel1\" width=\"80%\"><font size=-1>"
            . sprintf(_("<b>%d</b> Dateien und <b>%d</b> Ordner"),get_config('ZIP_UPLOAD_MAX_FILES'), get_config('ZIP_UPLOAD_MAX_DIRS'))
            . "</font></td></tr>";
    }
    $print.= "\n<form enctype=\"multipart/form-data\" name=\"upload_form\" action=\"" . URLHelper::getLink('#anker') . "\" method=\"post\">";
    $print.= CSRFProtection::tokenTag();
    $print.= "<tr><td class=\"steelgraudunkel\" colspan=2><font size=-1>" . _("1. Klicken Sie auf <b>'Durchsuchen...'</b>, um eine Datei auszuw&auml;hlen.") . " </font></td></tr>";
    $print.= "\n<tr>";
    $print.= "\n<td class=\"steel1\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>&nbsp;<label for=\"the_file\">" . _("Dateipfad:") . "</label>&nbsp;</font><br>";
    $print.= "&nbsp;<input name=\"the_file\" id=\"the_file\" aria-required=\"true\" type=\"file\"  style=\"width: 70%\" size=\"30\">&nbsp;</td></td>";
    $print.= "\n</tr>";
    if (!$refresh && !$folder_system_data['zipupload']) {
        $print.= "<tr><td class=\"steelgraudunkel\" colspan=2><font size=-1>" . _("2. Schutz gem&auml;&szlig; Urhebberecht.") . "</font></td></tr>";
        $print.= "\n<tr><td class=\"steel1\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>";
        $print.= "\n&nbsp;<label><input type=\"RADIO\" name=\"protected\" value=\"0\"".(!$protect ? "checked" :"") .'>'._("Ja, dieses Dokument ist frei von Rechten Dritter") ;
        $print.= "</label>\n&nbsp;<label><input type=\"RADIO\" name=\"protected\" value=\"1\"".($protect ? "checked" :"") .'>'._("Nein, dieses Dokument ist <u>nicht</u> frei von Rechten Dritter");
        $print.= "</label></td></tr>";

        $print.= "<tr><td class=\"steelgraudunkel\" colspan=2><font size=-1>" . _("3. Geben Sie eine kurze Beschreibung und einen Namen f&uuml;r die Datei ein.") . "</font></td></tr>";
        $print.= "\n<tr><td class=\"steel1\" colspan=2 align=\"left\" valign=\"center\"><label><font size=-1>&nbsp;" . _("Name:") . "&nbsp;</font><br>";
        $print.= "\n&nbsp;<input type=\"TEXT\" name=\"name\" style=\"width: 70%\" size=\"40\" maxlength\"255\"></label></td></tr>";
        $print.= "\n<tr><td class=\"steel1\" colspan=2 align=\"left\" valign=\"center\"><label><font size=-1>&nbsp;" . _("Beschreibung:") . "&nbsp;</font><br>";
        $print.= "\n&nbsp;<textarea name=\"description\" style=\"width: 70%\" COLS=40 ROWS=3 WRAP=PHYSICAL></textarea></label>&nbsp;</td></tr>";
        $print.= "\n<tr><td class=\"steelgraudunkel\" colspan=2 ><font size=-1>" . _("4. Klicken Sie auf <b>'absenden'</b>, um die Datei hochzuladen") . "</font></td></tr>";
    } else if ($folder_system_data['zipupload']) {
        $print.= "\n<tr><td class=\"steelgraudunkel\" colspan=2 ><font size=-1>" . _("3. Klicken Sie auf <b>'absenden'</b>, um das Ziparchiv hochzuladen und in diesem Ordner zu entpacken.") . "</font></td></tr>";
    } else {
        $print.= "\n<tr><td class=\"steelgraudunkel\" colspan=2 ><font size=-1>" . _("3. Klicken Sie auf <b>'absenden'</b>, um die Datei hochzuladen und damit die alte Version zu &uuml;berschreiben.") . "</font></td></tr>";
    }
    $print.= "\n<tr><td class=\"steel1\" colspan=2 align=\"center\" valign=\"center\">";
    $print.= "\n<input type=\"image\" " . makeButton("absenden", "src") . tooltip(_("absenden")) . " value=\"Senden\" align=\"absmiddle\" onClick=\"return upload_start();\" name=\"create\" border=\"0\">";
    $print.="&nbsp;<a href=\"".URLHelper::getLink("?cancel_x=true#anker")."\">" . makeButton("abbrechen", "img", _("abbrechen")) . "</a></td></tr>";
    $print.= "\n<input type=\"hidden\" name=\"cmd\" value=\"upload\">";
    $print.= "\n<input type=\"hidden\" name=\"upload_seminar_id\" value=\"".$SessSemName[1]."\">";
    $print.= "\n</form></table><br></center>";

    return $print;
}

/**
 * kills forbidden characters in filenames,
 * shortens filename to 31 Characters if desired,
 * checks for unique filename in given folder and modifies
 * filename if needed
 *
 * @param string $filename original filename
 * @param bool $shorten if true, filename is shortened to 31 chars
 * @param bool $checkfolder if true, uniqueness of filename in this folder is guaranteed
 * @return string
 */
function prepareFilename($filename, $shorten = FALSE, $checkfolder = false) {
    $bad_characters = array (":", chr(92), "/", "\"", ">", "<", "*", "|", "?", " ", "(", ")", "&", "[", "]", "#", chr(36), "'", "*", ";", "^", "`", "{", "}", "|", "~", chr(255));
    $replacements = array ("", "", "", "", "", "", "", "", "", "_", "", "", "+", "", "", "", "", "", "", "-", "", "", "", "", "-", "", "");

    $filename=str_replace($bad_characters, $replacements, $filename);

    if ($filename{0} == ".")
        $filename = substr($filename, 1, strlen($filename));

    if ($shorten) {
        $ext = getFileExtension ($filename);
        $filename = substr(substr($filename, 0, strrpos($filename,$ext)-1), 0, (30 - strlen($ext))).".".$ext;
    }
    if ($checkfolder !== false) {
        $c = 0;
        $ext = getFileExtension($filename);
        if ($ext) {
          $name = substr($filename, 0, strrpos($filename,$ext)-1);
        } else {
            $name = $filename;
}
        while (file_exists($checkfolder . '/' . $filename)) {
            $filename = $name . '['.++$c.']' . ($ext ? '.' . $ext : '');
        }
    }
    return $filename;
}

//Diese Funktion dient zur Abfrage der Dateierweiterung
function getFileExtension($str) {
    $i = strrpos($str,".");
    if (!$i) { return ""; }

    $l = strlen($str) - $i;
    $ext = substr($str,$i+1,$l);

    return $ext;
}

/**
 * Checks whether a given file upload is valid and allowed.
 *
 * @param $the_file file to upload to Stud.IP
 * @param $real_file_name an optional real file name for handling files
 *   inside a ZIP (otherwise, the filename of the ZIP itself would always be
 *   used)
 *
 * @return Can the given file be uploaded to Stud.IP?
 */
function validate_upload($the_file, $real_file_name='') {
    global $UPLOAD_TYPES, $msg, $SessSemName, $user, $auth, $i_page;

    $the_file_size = $the_file['size'];
    $the_file_name = $the_file['name'];

    if ($i_page == "sms_send.php") {
        if (!$GLOBALS["ENABLE_EMAIL_ATTACHMENTS"] == true)
                $emsg.= "error§" . _("Dateianhänge für Nachrichten sind in dieser Installation nicht erlaubt!") . "§";
        $active_upload_type = "attachments";
        $sem_status = $GLOBALS['perm']->get_perm();
    } else {
        $sem_status = $GLOBALS['perm']->get_studip_perm($SessSemName[1]);
        $active_upload_type = $SessSemName["art_num"];
    }

    //erlaubte Dateigroesse aus Regelliste der Config.inc.php auslesen
    if ($UPLOAD_TYPES[$active_upload_type]) {
        $max_filesize=$UPLOAD_TYPES[$active_upload_type]["file_sizes"][$sem_status];
    } else {
        $max_filesize=$UPLOAD_TYPES["default"]["file_sizes"][$sem_status];
    }

    $error = FALSE;
    if (!$the_file) { # haben wir eine Datei?
        $emsg.= "error§" . _("Sie haben keine Datei zum Hochladen ausgew&auml;hlt!") . "§";
    } else { # pruefen, ob der Typ stimmt

        //Die Dateierweiterung von dem Original erfragen
        $pext = strtolower(getFileExtension($real_file_name ? $real_file_name : $the_file_name));
        if ($pext == "doc")
            $doc=TRUE;

        //Erweiterung mit Regelliste in config.inc.php vergleichen
        if ($UPLOAD_TYPES[$SessSemName[$active_upload_type]]) {
            if ($UPLOAD_TYPES[$SessSemName[$active_upload_type]]["type"] == "allow") {
                $t=TRUE;
                $i=1;
                foreach ($UPLOAD_TYPES[$SessSemName[$active_upload_type]]["file_types"] as $ft) {
                    if ($pext == $ft)
                        $t=FALSE;
                    if ($i !=1)
                        $exts.=",";
                    $exts.=" ".strtoupper($ft);
                    $i++;
                    }
                if (!$t) {
                    if ($i==2)
                        $emsg.= "error§" . sprintf(_("Die Datei konnte nicht &uuml;bertragen werden: Sie d&uuml;rfen den Dateityp %s nicht hochladen!"), trim($exts)) . "§";
                    else
                        $emsg.= "error§" . sprintf(_("Die Datei konnte nicht &uuml;bertragen werden: Sie d&uuml;rfen die Dateitypen %s nicht hochladen!"), trim($exts)) . "§";
                    if ($doc) {
                        $help_url = format_help_url("Basis.DateienUpload");
                        $emsg.= "info§" . sprintf(_("%sHier%s bekommen Sie Hilfe zum Upload von Word-Dokumenten."), "<a target=\"_blank\" href=\"".$help_url."\">", "</a>") . "§";
                    }
                }
            } else {
                $t=FALSE;
                $i=1;
                foreach ($UPLOAD_TYPES[$SessSemName["art_num"]]["file_types"] as $ft) {
                    if ($pext == $ft)
                        $t=TRUE;
                    if ($i !=1)
                        $exts.=",";
                    $exts.=" ".strtoupper($ft);
                    $i++;
                    }
                if (!$t) {
                    if ($i==2)
                        $emsg.= "error§" . sprintf(_("Die Datei konnte nicht &uuml;bertragen werden: Sie d&uuml;rfen nur den Dateityp %s hochladen!"), trim($exts)) . "§";
                    else
                        $emsg.= "error§" . sprintf(_("Die Datei konnte nicht &uuml;bertragen werden: Sie d&uuml;rfen nur die Dateitypen %s hochladen!"), trim($exts)) . "§";
                    if ($doc) {
                        $help_url = format_help_url("Basis.DateienUpload");
                        $emsg.= "info§" . sprintf(_("%sHier%s bekommen Sie Hilfe zum Upload von Word-Dokumenten."), "<a target=\"_blank\" href=\"".$help_url."\">", "</a>") . "§";
                    }
                    }
                }
            }
        else {
            if ($UPLOAD_TYPES["default"]["type"] == "allow") {
                $t=TRUE;
                $i=1;
                foreach ($UPLOAD_TYPES["default"]["file_types"] as $ft) {
                    if ($pext == $ft)
                        $t=FALSE;
                    if ($i !=1)
                        $exts.=",";
                    $exts.=" ".strtoupper($ft);
                    $i++;
                    }
                if (!$t) {
                    if ($i==2)
                        $emsg.= "error§" . sprintf(_("Die Datei konnte nicht &uuml;bertragen werden: Sie d&uuml;rfen den Dateityp %s nicht hochladen!"), trim($exts)) . "§";
                    else
                        $emsg.= "error§" . sprintf(_("Die Datei konnte nicht &uuml;bertragen werden: Sie d&uuml;rfen die Dateitypen %s nicht hochladen!"), trim($exts)) . "§";
                    if ($doc) {
                        $help_url = format_help_url("Basis.DateienUpload");
                        $emsg.= "info§" . sprintf(_("%sHier%s bekommen Sie Hilfe zum Upload von Word-Dokumenten."), "<a target=\"_blank\" href=\"".$help_url."\">", "</a>") . "§";
                    }
                    }
                }
            else {
                $t=FALSE;
                $i=1;
                foreach ($UPLOAD_TYPES["default"]["file_types"] as $ft) {
                    if ($pext == $ft)
                        $t=TRUE;
                    if ($i !=1)
                        $exts.=",";
                    $exts.=" ".strtoupper($ft);
                    $i++;
                    }
                if (!$t) {
                    if ($i==2)
                        $emsg.= "error§" . sprintf(_("Die Datei konnte nicht &uuml;bertragen werden: Sie d&uuml;rfen nur den Dateityp %s hochladen!"), trim($exts)) . "§";
                    else
                        $emsg.= "error§" . sprintf(_("Die Datei konnte nicht &uuml;bertragen werden: Sie d&uuml;rfen nur die Dateitypen %s hochladen!"), trim($exts)) . "§";
                    if ($doc) {
                        $help_url = format_help_url("Basis.DateienUpload");
                        $emsg.= "info§" . sprintf(_("%sHier%s bekommen Sie Hilfe zum Upload von Word-Dokumenten."), "<a target=\"_blank\" href=\"".$help_url."\">", "</a>") . "§";
                    }
                    }
                }
            }

        //pruefen ob die Groesse stimmt.
        if ($the_file_size == 0) {
            $emsg.= "error§" . _("Sie haben eine leere Datei zum Hochladen ausgew&auml;hlt!") . "§";
        } else if ($the_file_size > $max_filesize) {
            $emsg.= "error§" . sprintf(_("Die Datei konnte nicht &uuml;bertragen werden: Die maximale Gr&ouml;sse f&uuml;r einen Upload (%s Megabyte) wurde &uuml;berschritten!"), $max_filesize / 1048576);
        }
    }
    if ($emsg) {
        $msg.=$emsg;
        return FALSE;
        }
    else
        return TRUE;
}

//der eigentliche Upload
function upload($the_file, $refresh, $range_id)
{
    global $dokument_id, $msg;

    if (!validate_upload($the_file)) {
        return FALSE;
    }

    $data = getUploadMetadata($range_id, $refresh);

    $doc = StudipDocument::createWithFile($the_file['tmp_name'], $data);
    if ($doc === null) {
        $msg.= "error§" . _("Dateiübertragung gescheitert!");
        return false;
    }

    // wird noch in folder.php gebraucht
    $dokument_id = $doc->getId();

    $msg = "msg§" . _("Die Datei wurde erfolgreich auf den Server &uuml;bertragen!");
    return TRUE;
}


//Erzeugen des Datenbankeintrags zur Datei
function getUploadMetadata($range_id, $refresh = FALSE) {
    global $user;
    $upload_seminar_id = Request::option('upload_seminar_id');
    $description = trim(Request::get('description'));
    $name = trim(Request::get('name'));
    $protected = Request::int('protected');
    $the_file_name = basename($_FILES['the_file']['name']);
    $the_file_size = $_FILES['the_file']['size'];

    $name || ($name = $the_file_name);

    $result = array(
        'filename'    => $the_file_name,
        'filesize'    => $the_file_size,
        'autor_host'  => $_SERVER['REMOTE_ADDR'],
        'user_id'     => $user->id,
        'author_name' => get_fullname()
    );

    if (!$refresh) {
        $result['range_id']     = trim($range_id);
        $result['seminar_id']   = $upload_seminar_id;
        $result['description']  = $description;
        $result['name']         = $name;
        $result['protected']    = (int) $protected;
    } else {
        $result['dokument_id'] = $refresh;
    }

    return $result;
}



function JS_for_upload() {

    global $UPLOAD_TYPES, $SessSemName, $folder_system_data;

    ?>
     <SCRIPT LANGUAGE="JavaScript">
    <!-- Begin

    var upload=false;

    function upload_end()
    {
    if (upload)
        {
        msg_window.close();
        }
    return;
    }

    function upload_start()
    {
    file_name=document.upload_form.the_file.value
    if (!file_name)
         {
         alert("<?=_("Bitte wählen Sie eine Datei aus!")?>");
         document.upload_form.the_file.focus();
         return false;
         }

    if (file_name.charAt(file_name.length-1)=="\"") {
     ende=file_name.length-1; }
    else  {
     ende=file_name.length;  }

    ext=file_name.substring(file_name.lastIndexOf(".")+1,ende);
    ext=ext.toLowerCase();

    if (<?
    if (!$folder_system_data["zipupload"]){

    if ($UPLOAD_TYPES[$SessSemName["art_num"]]) {
        if ($UPLOAD_TYPES[$SessSemName["art_num"]]["type"] == "allow") {
            $i=1;
            foreach ($UPLOAD_TYPES[$SessSemName["art_num"]]["file_types"] as $ft) {
                if ($i !=1)
                    echo " && ";
                echo "ext == \"$ft\"";
                $i++;
                if ($ft=="doc")
                    $deny_doc=TRUE;
                }
            }
        else {
            $i=1;
            $deny_doc=TRUE;
            foreach ($UPLOAD_TYPES[$SessSemName["art_num"]]["file_types"] as $ft) {
                if ($i !=1)
                    echo " && ";
                echo "ext != \"$ft\"";
                $i++;
                if ($ft=="doc")
                    $deny_doc=FALSE;
                }
            }
        }
    else {
        if ($UPLOAD_TYPES["default"]["type"] == "allow") {
            $i=1;
            foreach ($UPLOAD_TYPES["default"]["file_types"] as $ft) {
                if ($i !=1)
                    echo " && ";
                echo "ext == \"$ft\"";
                $i++;
                if ($ft=="doc")
                    $deny_doc=TRUE;
                }
            }
        else {
            $i=1;
            $deny_doc=TRUE;
            foreach ($UPLOAD_TYPES["default"]["file_types"] as $ft) {
                if ($i !=1)
                    echo " && ";
                echo "ext != \"$ft\"";
                $i++;
                if ($ft=="doc")
                    $deny_doc=FALSE;
                }
            }
        }
    } else {
        echo "ext != \"zip\"";
    }
    ?>)
         {
         alert("<?=_("Dieser Dateityp ist nicht zugelassen!")?>");
         document.upload_form.the_file.focus();
         return false;
         }

    file_only = file_name.replace(/.*[/\\](.+)/, '$1');

    msg_window=window.open("","messagewindow","height=250,width=200,left=20,top=20,scrollbars=no,resizable=no,toolbar=no");
    msg_window.document.write("<html><head><title>Datei Upload</title></head>");
    msg_window.document.write("<body bgcolor='#ffffff'><center><p><img src='<?= $GLOBALS['ASSETS_URL'] ?>images/alienupload.gif' width='165' height='125'></p>");
    msg_window.document.write("<p><font face='arial, helvetica, sans-serif'><b>&nbsp;"+file_only+"</b><br>&nbsp;<?=_("wird hochgeladen.")?><br>&nbsp;<?=_("Bitte haben Sie etwas Geduld!")?><br></font></p></body></html>");

    upload=true;

    return true;
    }

    // End -->
    </script>
    <?
    }


//Steuerungsfunktion
function upload_item ($range_id, $create = FALSE, $echo = FALSE, $refresh = FALSE) {
    $the_file = $_FILES["the_file"];

    if ($create) {
        upload($the_file, $refresh, $range_id);
        return;
    }
    else {
        if ($echo) {
            echo form($refresh);
            return;
        }
        else
            return form($refresh);
    }
}


function insert_link_db($range_id, $the_file_size, $refresh = FALSE) {
    global $the_file_name, $user;
    $the_link = Request::get('the_link');
    $date = time();             //Systemzeit
    $user_id = $user->id;           // user_id erfragen...
    $range_id = trim($range_id);        // laestige white spaces loswerden
    $description = trim(Request::get('description'));      // laestige white spaces loswerden
    $name = trim(Request::get('name'));            // laestige white spaces loswerden

    $url_parts = parse_url($the_link);
    $the_file_name = basename($url_parts['path']);

    if (!$name) {
        $name = $the_file_name;
    }
    if (!$refresh) {
        $doc = new StudipDocument();
        $doc->description = remove_magic_quotes($description);
        $doc->name = $name;
        $doc->range_id = $range_id;
        $doc->user_id = $user_id;
        $doc->filename = $the_file_name;
        $doc->seminar_id = Request::option('upload_seminar_id');
        $doc->filesize = $the_file_size;
        $doc->url = $the_link;
        $doc->protected = Request::int('protect');
        $doc->autor_host = $_SERVER['REMOTE_ADDR'];
        $doc->author_name = get_fullname($user_id);

    } else {
        $doc = StudipDocument::find($refresh);
        $doc->user_id = $user_id;
        $doc->filename = $the_file_name;
        $doc->filesize = $the_file_size;
        $doc->autor_host = $_SERVER['REMOTE_ADDR'];
        $doc->author_name = get_fullname($user_id);
    }
    return $doc->store();
}


function link_item ($range_id, $create = FALSE, $echo = FALSE, $refresh = FALSE, $link_update = FALSE) {
    global $filesize;

    if ($create) {
        $link_data = parse_link(Request::get('the_link'));
        if ($link_data["HTTP/1.0 200 OK"] || $link_data["HTTP/1.1 200 OK"] || $link_data["HTTP/1.1 302 Found"] || $link_data["HTTP/1.0 302 Found"]) {
            if (!$link_update) {
                if (insert_link_db($range_id, $link_data["Content-Length"], $refresh)) {
                    if ($refresh) {
                        delete_link($refresh, TRUE);
                    }
                }
                $tmp = TRUE;
            } else {
                $filesize = $link_data["Content-Length"];
                edit_item(
                    $link_update,
                    FALSE,
                    Request::get('name'),
                    Request::get('description'),
                    Request::int('protect'),
                    Request::get('the_link'),
                    $filesize
                );
                $tmp = TRUE;
            }
        } else {
            $tmp = FALSE;

        }
        return $tmp;

    } else {
        if ($echo) {
            echo link_form($refresh,$link_update);
            return;
        } else {
            return link_form($refresh,$link_update);
        }
    }
}


function link_form ($range_id, $updating=FALSE) {
    global $SessSemName, $the_link, $protect, $description, $name, $folder_system_data, $user;
    if ($folder_system_data["update_link"])
        $updating = TRUE;
    if ($protect=="on") $protect = "checked";
    $print = "";
    $hiddenurl = FALSE;
    if ($updating == TRUE) {
        $db=new DB_Seminar;
        $db->query("SELECT * FROM dokumente WHERE dokument_id='$range_id'");
        if ($db->next_record()) {
            $the_link = $db->f("url");
            $protect = $db->f("protected");
            if ($protect==1) $protect = "checked";
            $name = $db->f("name");
            $description = $db->f("description");
            if ($db->f("user_id") != $user->id) { // check if URL can be seen
                $url_parts = @parse_url( $the_link );
                if ($url_parts["user"] && $url_parts["user"]!="anonymous") {
                    $hiddenurl = TRUE;
                }

            }
        }
    }
    if ($folder_system_data["linkerror"]==TRUE) {
        $print.="<hr>".  Assets::img('icons/16/red/accept.png', array('class' => 'text-top')) . "<font color=\"red\">";
        $print.=_("&nbsp;FEHLER: unter der angegebenen Adresse wurde keine Datei gefunden.<br>&nbsp;Bitte kontrollieren Sie die Pfadangabe!");
        $print.="</font><hr>";
    }
    // Check if URL can be seen



    $print.="\n<br><br>" . _("Sie haben diesen Ordner zum Upload ausgewählt:") . "<br><br><center><table width=\"90%\" style=\"border: 1px solid #000000;\" border=0 cellpadding=2 cellspacing=3>";

    $print.="</font></td></tr>";
    $print.= "\n<form enctype=\"multipart/form-data\" name=\"link_form\" action=\"" . URLHelper::getLink('#anker') . "\" method=\"post\">";
    $print.= CSRFProtection::tokenTag();
    $print.= "<tr><td class=\"steelgraudunkel\" colspan=2><font size=-1>" . _("1. Geben Sie hier den <b>vollständigen Pfad</b> zu der Datei an, die Sie verlinken wollen.") . " </font></td></tr>";
    $print.= "\n<tr>";
    $print.= "\n<td class=\"steel1\" colspan=2 align=\"left\" valign=\"center\"><label><font size=-1>&nbsp;" . _("Dateipfad:") . "&nbsp;</font><br>";
    if ($hiddenurl)
        $print.= "&nbsp;<input name=\"the_link\" type=\"text\"  style=\"width: 70%\" size=\"30\" value=\"***\"></label>&nbsp;</td></td>";
    else
        $print.= '&nbsp;<input name="the_link" type="text"  style="width: 70%" size="30" value="'.$the_link.'"></label>&nbsp;</td></td>';
    $print.= "\n</tr>";
    if (!$refresh) {

        $print.= "<tr><td class=\"steelgraudunkel\" colspan=2><font size=-1>" . _("2. Schutz gem&auml;&szlig; Urhebberecht.") . "</font></td></tr>";
        $print.= "\n<tr><td class=\"steel1\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>&nbsp;" . _("Dieses Dokument ist frei von Rechten Dritter:") . "&nbsp;";
        $print.= "\n&nbsp;<label><input type=\"RADIO\" name=\"protect\" value=\"0\"".(!$protect ? "checked" :"") .'>'._("Ja");
        $print.= "</label>\n&nbsp;<label><input type=\"RADIO\" name=\"protect\" value=\"1\"".($protect ? "checked" :"") .'>'._("Nein");

        $print.= "</label><tr><td class=\"steelgraudunkel\" colspan=2><font size=-1>" . _("3. Geben Sie eine kurze Beschreibung und einen Namen für die Datei ein.") . "</font></td></tr>";
        $print.= "\n<tr><td class=\"steel1\" colspan=2 align=\"left\" valign=\"center\"><label><font size=-1>&nbsp;" . _("Name:") . "&nbsp;</font><br>";
        $print.= "\n".'&nbsp;<input type="text" name="name" id="name" style="width: 70%" size="40" maxlength"255" value="'.$name.'"></label></td></tr>';

        $print.= "\n<tr><td class=\"steel1\" colspan=2 align=\"left\" valign=\"center\"><label><font size=-1>&nbsp;" . _("Beschreibung:") . "&nbsp;</font><br>";
        $print.= "\n&nbsp;<textarea name=\"description\" id=\"description\" style=\"width: 70%\" COLS=40 ROWS=3 WRAP=PHYSICAL>$description</textarea></label>&nbsp;</td></tr>";
        $print.= "\n<tr><td class=\"steelgraudunkel\"colspan=2 ><font size=-1>" . _("4. Klicken Sie auf <b>'absenden'</b>, um die Datei zu verlinken") . "</font></td></tr>";
    } else
        $print.= "\n<tr><td class=\"steelgraudunkel\"colspan=2 ><font size=-1>" . _("2. Klicken Sie auf <b>'absenden'</b>, um die Datei hochzuladen und damit die alte Version zu &uuml;berschreiben.") . "</font></td></tr>";
    $print.= "\n<tr><td class=\"steel1\" colspan=2 align=\"center\" valign=\"center\">";
    $print.= "\n" . makeButton("absenden", 'input', _("absenden"), 'create');
    $print.="&nbsp;<a href=\"".URLHelper::getLink("?cancel_x=true#anker")."\">" . makeButton("abbrechen", 'img', _("abbrechen")) . "</a></td></tr>";
    $print.= "\n<input type=\"hidden\" name=\"upload_seminar_id\" value=\"".$SessSemName[1]."\">";
    if ($updating == TRUE) {
        $print.= "\n<input type=\"hidden\" name=\"cmd\" value=\"link_update\">";
        $print.= "\n<input type=\"hidden\" name=\"link_update\" value=\"$range_id\">";
    } else {
        $print.= "\n<input type=\"hidden\" name=\"cmd\" value=\"link\">";
    }
    $print.= "\n</form></table><br></center>";

    return $print;

}

## Ende Upload Funktionen ################################################################################

/**
 * Displays the body of a file containing the decription, downloadbuttons and change-forms
 *
 */
function display_file_body($datei, $folder_id, $open, $change, $move, $upload, $all, $refresh=FALSE, $filelink="") {
    global $rechte, $user, $SessionSeminar;
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));

    $type = $datei['url'] != '' ? 6 : 0;

    $content='';

    if ($change == $datei["dokument_id"]) {     //Aenderungsmodus, Formular aufbauen
        if ($datei["protected"]==1)
            $protect = "checked";
        $content.= "\n&nbsp;<input type=\"CHECKBOX\" name=\"change_protected\" $protect>&nbsp;"._("geschützter Inhalt")."</br>";
        $content.= "<br><textarea name=\"change_description\" aria-label=\"Beschreibung des Ordners eingeben\" rows=\"3\" cols=\"40\">".htmlReady($datei["description"])."</textarea><br>";
        $content.= makeButton("uebernehmen", 'input', _("&Auml;nderungen speichern"));
        $content.= "&nbsp;" . makeButton("abbrechen", 'input', _("Abbrechen"), 'cancel');
        $content.= "<input type=\"hidden\" name=\"open\" value=\"".htmlReady($datei["dokument_id"])."_sc_\">";
        $content.= "<input type=\"hidden\" name=\"type\" value=\"0\">";
    } else {
        $content = '';
        $media_url = GetDownloadLink($datei['dokument_id'], $datei['filename'], $type);
        $media_type = get_mime_type($datei['filename']);
        if ($media_type == 'video/x-flv') {
            $cfg = Config::GetInstance();
            $DOCUMENTS_EMBEDD_FLASH_MOVIES = $cfg->getValue('DOCUMENTS_EMBEDD_FLASH_MOVIES');
            if (trim($DOCUMENTS_EMBEDD_FLASH_MOVIES) != 'deny') {
                $flash_player = get_flash_player($datei['dokument_id'], $datei['filename'], $type);
                $content = "<div style=\"margin-bottom: 10px; height: {$flash_player['height']}; width: {$flash_player['width']};\">" . $flash_player['player'] . '</div>';
            }
        } else if (strpos($media_type, 'video/') === 0 || $media_type == 'application/ogg') {
            $content = sprintf('<video class="preview" controls><source src="%s" type="%s"></video><br>', htmlspecialchars($media_url), $media_type);
        } else if (strpos($media_type, 'audio/') === 0) {
            $content = sprintf('<audio class="preview" controls><source src="%s" type="%s"></audio><br>', htmlspecialchars($media_url), $media_type);
        } else if (strpos($media_type, 'image/') === 0) {
            $content = sprintf('<img class="preview" src="%s" alt=""><br>', htmlspecialchars($media_url));
        }
        if ($datei["description"]) {
            $content .= htmlReady($datei["description"], TRUE, TRUE);
        } else {
            $content .= _("Keine Beschreibung vorhanden");
        }
        $content .=  "<br><br>" . sprintf(_("<b>Dateigr&ouml;&szlig;e:</b> %s kB"), round ($datei["filesize"] / 1024));
        $content .=  "&nbsp; " . sprintf(_("<b>Dateiname:</b> %s "),htmlReady($datei['filename']));
    }

    if ($move == $datei["dokument_id"])
        $content.="<br>" . sprintf(_("Diese Datei wurde zum Verschieben / Kopieren markiert. Bitte w&auml;hlen Sie das Einf&uuml;gen-Symbol %s, um diese Datei in den gew&uuml;nschten Ordner zu verschieben / kopieren. Wenn Sie diese Datei in eine andere Veranstaltung verschieben / kopieren möchten, wählen Sie die gewünschte Veranstaltung oben auf der Seite aus (sofern Sie Dozent oder Tutor in einer anderen Veranstaltung sind)."), "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2right.png\" border=0 " . tooltip(_("Klicken Sie dieses Symbol, um diese Datei in einen anderen Ordner einzufügen")) . ">");

    $content.= "\n";

    if ($upload == $datei["dokument_id"])
        $content.=upload_item ($upload,FALSE,FALSE,$refresh);

    //Editbereich ertstellen
    $edit='';
    if (($change != $datei["dokument_id"]) && ($upload != $datei["dokument_id"]) && $filelink != $datei["dokument_id"]) {
        if (check_protected_download($datei['dokument_id'])) {
            $edit= '&nbsp;<a href="' . GetDownloadLink( $datei['dokument_id'], $datei['filename'], $type, 'force') .'" class="extern">' . makeButton('herunterladen', 'img', _("herunterladen")) . '</a>';

            $fext = getFileExtension(strtolower($datei['filename']));
            if (($type != '6') && ($fext != 'zip') && ($fext != 'tgz') && ($fext != 'gz') && ($fext != 'bz2')) {
                $edit.= '&nbsp;<a href="'. GetDownloadLink( $datei['dokument_id'], $datei['filename'], $type, 'zip') . '" class="extern">' . makeButton('alsziparchiv', 'img', _("als ZIP-Archiv herunterladen")) . '</a>';
            }
        }
        if (($rechte) || ($datei["user_id"] == $user->id && $folder_tree->isWritable($datei["range_id"], $user->id))) {
            if ($type!=6) {
              $edit.= "&nbsp;&nbsp;&nbsp;<a href=\"".URLHelper::getLink("?open=".$datei["dokument_id"]."_c_#anker")."\">" . makeButton("bearbeiten", "img", _("bearbeiten")) . "</a>";
              $edit.= "&nbsp;<a href=\"".URLHelper::getLink("?open=".$datei["dokument_id"]."_rfu_#anker")."\">" . makeButton("aktualisieren", "img", _("aktualisieren")) . "</a>";
            } else {
                //wenn Datei ein Link ist:
                $edit.= "&nbsp;&nbsp;&nbsp;<a href=\"".URLHelper::getLink("?open=".$datei["dokument_id"]."_led_#anker")."\">" . makeButton("bearbeiten", "img", _("bearbeiten")) . "</a>";
            }
            if (!$all){
                $edit.= "&nbsp;<a href=\"".URLHelper::getLink("?open=".$datei["dokument_id"]."_m_#anker")."\">" . makeButton("verschieben", "img", _("verschieben")) . "</a>";
                $edit.= "&nbsp;<a href=\"".URLHelper::getLink("?open=".$datei["dokument_id"]."_co_#anker")."\">" . makeButton("kopieren", "img", _("kopieren")) . "</a>";
            }
            $edit.= "&nbsp;<a href=\"".URLHelper::getLink("?open=".$datei["dokument_id"]."_fd_")."\">" . makeButton("loeschen", "img", _("löschen")) . "</a>";
        }
    }

    //Dokument_Body ausgeben; dies ist auch der Bereich, der über Ajax abgerufen werden wird
    print "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0>";
    if ($datei["protected"]) {
        if(check_protected_download($datei["dokument_id"])){
            $detail .=_("Sie darf nur im Rahmen dieser Veranstaltung verwendet werden, jede weitere Verbreitung ist unzul&auml;ssig!");
        } else {
            $detail .= _("Sie k&ouml;nnen diese Datei nicht herunterladen, so lange diese Veranstaltung einen offenen Teilnehmerkreis aufweist.");
        }
        $content .= MessageBox::info(_("Diese Datei ist urheberrechtlich geschützt."), array($detail));
    }
    if ($filelink == $datei["dokument_id"])
        $content .= link_item($datei["dokument_id"],FALSE,FALSE,$datei["dokument_id"]);
    printcontent ("100%",TRUE, $content, $edit);
    print "</table>";
}

//$countfiles is important, so that each file_line has its own unique id and can be found by javascript.
$countfiles = 0;
/**
 * Displays one file/document with all of its information and options.
 *
 */
function display_file_line ($datei, $folder_id, $open, $change, $move, $upload, $all, $refresh=FALSE, $filelink="", $anchor_id, $position = "middle") {
    global $_fullname_sql,$SessionSeminar,$SessSemName, $rechte, $anfang,
        $user, $SemSecLevelWrite, $SemUserStatus, $check_all, $countfiles;
    //Einbinden einer Klasse, die Informationen über den ganzen Baum enthält
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));
    $javascriptok = true;
    print "\n\t<div class=\"".($rechte ? "draggable" : "")."\" id=\"file_".$folder_id."_$countfiles\">";
    print "<div style=\"display:none\" id=\"getmd5_fi".$folder_id."_$countfiles\">".$datei['dokument_id']."</div>";
    print "<table cellpadding=0 border=0 cellspacing=0 width=\"100%\"><tr>";
    if (!$all) {
        print "<td class=\"tree-elbow-end\">" . Assets::img("datatree_2.gif") . "</td>";
    }

    //Farbe des Pfeils bestimmen:
    $chdate = (($datei["chdate"]) ? $datei["chdate"] : $datei["mkdate"]);
    if (object_get_visit($SessSemName[1], "documents") < $chdate)
        $timecolor = "#FF0000";
    else {
        $timediff = (int) log((time() - doc_newest($folder_id)) / 86400 + 1) * 15;
        if ($timediff >= 68)
            $timediff = 68;
        $red = dechex(255 - $timediff);
        $other = dechex(119 + $timediff);
        $timecolor= "#" . $red . $other . $other;
    }

    if ($open[$datei["dokument_id"]]) {
        print "<td id=\"file_".$datei["dokument_id"]."_arrow_td\" nowrap valign=\"top\"" .
            "align=\"left\" width=1% bgcolor=\"$timecolor\" class=\"printhead3\" valign=\"bottom\"><a href=\"";
        print URLHelper::getLink("?close=".$datei["dokument_id"]."#anker");
        print "\" class=\"tree\" onClick=\"return STUDIP.Filesystem.changefilebody('".
            $datei["dokument_id"]."', '".$SessionSeminar."')\"><img id=\"file_".
            $datei["dokument_id"]."_arrow_img\" src=\"".$GLOBALS['ASSETS_URL'].
            "images/forumgraurunt2.png\"".tooltip(_("Objekt zuklappen"))."></a></td>";
    } else {
        print "<td id=\"file_".$datei["dokument_id"]."_arrow_td\" nowrap valign=\"top\" align=\"left\" width=1% bgcolor=\"$timecolor\" class=\"printhead2\" valign=\"bottom\"><a href=\"";
        print URLHelper::getLink("?open=".$datei["dokument_id"]."#anker");
        print "\" class=\"tree\" onClick=\"return STUDIP.Filesystem.changefilebody('".
            $datei["dokument_id"]."', '".$SessionSeminar."')\"><img id=\"file_".
            $datei["dokument_id"]."_arrow_img\" src=\"".$GLOBALS['ASSETS_URL'].
            "images/forumgrau2.png\"".tooltip(_("Objekt aufklappen"))."></a></td>";
    }

    // -> Pfeile zum Verschieben (bzw. die Ziehfläche)
    if ((!$all) && ($rechte)) {
        $countfiles++;
        $bewegeflaeche = "<span class=\"updown_marker\" id=\"pfeile_".$datei["dokument_id"]."\">";
        if (($position == "middle") || ($position == "bottom")) {
            $bewegeflaeche .= "<a href=\"".URLHelper::getLink('?open='.$datei['dokument_id'])."_mfu_\" title=\""._("Datei nach oben schieben").
                "\">" . Assets::img('icons/16/yellow/arr_2up.png', array('class' => 'text-top')) . "</a>";
        }
        if (($position == "middle") || ($position == "top")) {
            $bewegeflaeche .= "<a href=\"".URLHelper::getLink('?open='.
                    $datei['dokument_id'])."_mfd_\" title=\""._("Datei nach unten schieben").
                    "\">". Assets::img('icons/16/yellow/arr_2down.png', array('class' => 'text-top')) . "</a>";
        }
        $bewegeflaeche .= "</span>";
    }

    print "<td class=\"printhead\" valign=\"bottom\">";
    if ($change == $datei["dokument_id"]) {
        print "<span id=\"file_".$datei["dokument_id"]."_header\" style=\"font-weight: bold\"><a href=\"".URLHelper::getLink("?close=".$datei["dokument_id"]."#anker")."\" class=\"tree\"";
        print ' name="anker"></a>';
        print "<img src=\"".$GLOBALS['ASSETS_URL']."images/".GetFileIcon(getFileExtension($datei['filename']))."\">";
        print "<input style=\"font-size: 8pt; width: 50%;\" type=\"text\" size=\"20\" maxlength=\"255\" name=\"change_name\" aria-label=\"Ordnername eingeben\" value=\"".htmlReady($datei["name"])."\"></b>";
    } else {
        if (($move == $datei["dokument_id"]) ||  ($upload == $datei["dokument_id"]) || ($anchor_id == $datei["dokument_id"])) {
            print "<a name=\"anker\"></a>";
        }
        $type = ($datei['url'] != '')? 6 : 0;
        // LUH Spezerei:
        if (check_protected_download($datei["dokument_id"])) {
            print "<a href=\"".GetDownloadLink( $datei["dokument_id"], $datei["filename"], $type, "normal")."\" class=\"extern\"><img src=\"".$GLOBALS['ASSETS_URL']."images/".GetFileIcon(getFileExtension($datei['filename']))."\"></a>";
        } else {
            print Assets::img('icons/16/grey/info-circle.png');
        }
        //Jetzt folgt der Link zum Aufklappen
        if ($open[$datei["dokument_id"]]) {
      print "<a href=\"".URLHelper::getLink("?close=".$datei["dokument_id"]."#anker")."\" class=\"tree\" onClick=\"return STUDIP.Filesystem.changefilebody('".$datei["dokument_id"]."')\">";
            print "&nbsp;<span id=\"file_".$datei["dokument_id"]."_header\" style=\"font-weight: bold\">";
        } else {
            print "<a href=\"".URLHelper::getLink("?open=".$datei["dokument_id"]."#anker")."\" class=\"tree\" onClick=\"return STUDIP.Filesystem.changefilebody('".$datei["dokument_id"]."')\">";
            print "&nbsp;<span id=\"file_".$datei["dokument_id"]."_header\" style=\"font-weight: normal\">";
        }
        print htmlReady($datei['t_name']);

        print "</span>";
    }

    //add the size
    if (($datei["filesize"] /1024 / 1024) >= 1)
        print "&nbsp;&nbsp;(".round ($datei["filesize"] / 1024 / 1024)." MB";
    else
        print "&nbsp;&nbsp;(".round ($datei["filesize"] / 1024)." kB";

    //add number of downloads
    print " / ".(($datei["downloads"] == 1) ? $datei["downloads"]." "._("Download") : $datei["downloads"]." "._("Downloads")).")";


    //So und jetzt die rechtsbündigen Sachen:
    print "</a></td><td align=\"right\" class=\"printhead\" valign=\"bottom\">";
    if ($datei['username']) {
        print "<a href=\"".URLHelper::getLink('about.php?username='.$datei['username'])."\">".htmlReady($datei['fullname'])."</a> ";
    } else {
        print htmlReady($datei['author_name']);
    }
    print $bewegeflaeche." ";

    //Workaround for older data from previous versions (chdate is 0)
    print " ".date("d.m.Y - H:i", (($datei["chdate"]) ? $datei["chdate"] : $datei["mkdate"]));

    if ($all) {
      if ((!$upload) && ($datei["url"]=="") && check_protected_download($datei["dokument_id"])) {
        $box = sprintf ("<input type=\"CHECKBOX\" %s name=\"download_ids[]\" value=\"%s\">",($check_all) ? "checked" : "" , $datei["dokument_id"]);
        print $box;
      } else {
        echo Assets::img('icons/16/grey/decline.png', array('title' => _("Diese Dateie kann nicht als ZIP-Archiv heruntergeladen werden."), 'style' => 'padding-left:5px;'));
    }
    }
    print "</td></tr>";

    //Ab jetzt kommt der Bereich zum Runterladen und Bearbeiten:
    if (isset($open[$datei["dokument_id"]])) {
        //Dokument-Content ausgeben
        print "<tr id=\"file_".$datei["dokument_id"]."_body_row\">".(($all) ? "" : "<td></td>")."<td colspan=3><div id=\"file_".$datei["dokument_id"]."_body\">";
        //Der eigentliche Teil ist outsourced in die folgende Funktion,
        //damit der Körper auch über Ajax abgerufen werden kann.
        display_file_body($datei, $folder_id, $open, $change, $move, $upload, $all, $refresh, $filelink);
    } else {
        print "<tr id=\"file_".$datei["dokument_id"]."_body_row\">".(($all) ? "" : "<td></td>")."<td colspan=3><div id=\"file_".$datei["dokument_id"]."_body\" style=\"display:none\">";
    }
    print "</div></td></tr></table>\n\t</div>";
}

/**
 * Displays the body of a folder including the description, changeform, subfolder and files
 *
 */
function display_folder_body($folder_id, $open, $change, $move, $upload, $refresh=FALSE, $filelink="", $anchor_id) {
    global $_fullname_sql, $SessionSeminar, $SemUserStatus, $SessSemName, $user, $perm, $rechte, $countfolder;
    $db = DBManager::get();
    //Einbinden einer Klasse, die Informationen über den ganzen Baum enthält
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));
    //Hole alle Informationen, die es über $folder_id gibt
    $query = "SELECT ". $_fullname_sql['full'] ." AS fullname , username, folder_id, a.range_id, a.user_id, name, a.description, a.mkdate, a.chdate FROM folder a LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING (user_id) WHERE a.folder_id = '$folder_id' ORDER BY a.name, a.chdate";
    $result = $db->query($query)->fetch();
    $document_count = doc_count($folder_id);
    $super_folder = $folder_tree->getNextSuperFolder($folder_id);
    $is_issue_folder = ((count($folder_tree->getParents($folder_id)) > 1) && IssueDB::isIssue($result["range_id"]));
    if ($is_issue_folder) {
        $dates_for_issue = IssueDB::getDatesforIssue($result['range_id']);
    }
    print "<table cellpadding=0 border=0 cellspacing=0 width=\"100%\">";

    //Ausgabe der Optionen zu dem Ordner mit Beschreibung, Knöpfen und PiPaPo
    print "<tr>";

    if ((($document_count > 0) || ($folder_tree->hasKids($folder_id))) && ($folder_tree->isReadable($folder_id)))
        print "<td style=\"background-image: url(".$GLOBALS['ASSETS_URL']."images/datatree_grau.gif); background-repeat: repeat-y;\"><img src=\"".$GLOBALS['ASSETS_URL']."images/datatree_grau.gif\"></td>";
    else
        print "<td class=\"printcontent\">&nbsp;</td>";
    print "<td width=100% class=\"printcontent\" style=\"font-align: center\">";

    $content='';
    if ($super_folder){
        $content .=  '<img class=\"texttop\" src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/grey/lock-locked.png">&nbsp;'
            . sprintf(_("Dieser Ordner ist nicht zugänglich, da der übergeordnete Ordner \"%s\" nicht lesbar oder nicht sichtbar ist!"), htmlReady($folder_tree->getValue($super_folder,'name')))
            . '<hr>';
    }
    if ($folder_tree->isExerciseFolder($folder_id)){
        $content .=  '<img  class=\"texttop\" src="'.$GLOBALS['ASSETS_URL'].'images/icons/16/grey/edit.png">&nbsp;'
                . _("Dieser Ordner ist ein Hausaufgabenordner. Es können nur Dateien eingestellt werden.")
                . (!$rechte ? _("Sie selbst haben folgende Dateien in diesen Ordner eingestellt:")
                . '<br><b>' . htmlReady(join('; ', get_user_documents_in_folder($folder_id, $GLOBALS['user']->id))).'</b>' : '')
                . '<hr>';
    }
    if ($is_issue_folder) {
        $dates = array();
        foreach ($dates_for_issue as $date) {
            $dates[] = strftime("%x", $date['date']);
        }
        $content .= _("Dieser Ordner ist ein themenbezogener Dateiordner.");
        if(count($dates)){
            $content .= '&nbsp;' ._("Folgende Termine sind diesem Thema zugeordnet:")
            . '<br><b>' . htmlReady(join('; ', $dates)).'</b>';
        }
        $content .=  '<hr>';
    }
    if ($folder_tree->isGroupFolder($folder_id)){
        $content .=  sprintf(_("Dieser Ordner gehört der Gruppe <b>%s</b>. Nur Mitglieder dieser Gruppe können diesen Ordner sehen."),
        htmlReady(GetStatusgruppeName($result["range_id"]))) . '<hr>';
    }
    //Contentbereich erstellen
    if ($change == $folder_id) { //Aenderungsmodus, zweiter Teil
        $content .= chr(10) . '<table cellpadding="2" cellspacing="2" border="0">';
        $content .= chr(10) . '<tr><td>';
        $content.="\n<textarea name=\"change_description\" aria-label=\"Beschreibung des Ordners eingeben\" rows=\"3\" cols=\"40\">".htmlReady($result["description"])."</textarea>";
        $content .= chr(10) . '</td><td><font size="-1">';
        if($rechte){
            if ($folder_tree->permissions_activated){
                $content.= "\n<label><input style=\"vertical-align:middle\" type=\"checkbox\" value=\"1\" ".($folder_tree->isReadable($folder_id) ? "CHECKED" : "" ) . " name=\"perm_read\">&nbsp;";
                $content.= '<b>r</b> - ' . _("Lesen (Dateien k&ouml;nnen heruntergeladen werden)");
                $content.= "</label>\n<br><label><input style=\"vertical-align:middle\" type=\"checkbox\" value=\"1\" ".($folder_tree->isWritable($folder_id) ? "CHECKED" : "" ) . " name=\"perm_write\">&nbsp;";
                $content.= '<b>w</b> - ' . _("Schreiben (Dateien k&ouml;nnen heraufgeladen werden)");
                $content.= "</label>\n<br><label><input style=\"vertical-align:middle\" type=\"checkbox\" value=\"1\" ".($folder_tree->isExecutable($folder_id) ? "CHECKED" : "" ) . " name=\"perm_exec\">&nbsp;";
                $content.= '<b>x</b> - ' . _("Sichtbarkeit (Ordner wird angezeigt)") . '</label>';
            }
            if($level == 0 && $folder_tree->entity_type == 'sem'){
                $content .= "\n<br><label><input style=\"vertical-align:middle\" type=\"checkbox\" value=\"1\" ".($folder_tree->checkCreateFolder($folder_id) ? "CHECKED" : "" ) . " name=\"perm_folder\">&nbsp;";
                $content .= '<b>f</b> - ' . _("Ordner erstellen (Alle Nutzer können Ordner erstellen)") . '</label>';
            } else {
                $content .= '&nbsp;';
            }
        } else {
            $content .= '&nbsp;';
        }
        $content .= chr(10) . '</font></td></tr>';
        $content .= chr(10) . '<tr><td colspan="2">';
        $content.="\n" . makeButton("uebernehmen", 'input', _("&Auml;nderungen speichern")) . "&nbsp;";
        $content.="\n" . makeButton("abbrechen", 'input', _("Abbrechen"), 'cancel');
        $content.= "\n<input type=\"hidden\" name=\"open\" value=\"".$folder_id."_sc_\">";
        $content.="\n<input type=\"hidden\" name=\"type\" value=\"1\">";
        $content .= chr(10) . '</td></tr></table>';
    }
    elseif ($result["description"])
        $content .= htmlReady($result["description"], TRUE, TRUE);
    else
        $content .= _("Keine Beschreibung vorhanden");
    if ($move == $result["folder_id"]){
        $content .="<br>" . sprintf(_("Dieser Ordner wurde zum Verschieben / Kopieren markiert. Bitte w&auml;hlen Sie das Einf&uuml;gen-Symbol %s, um ihn in den gew&uuml;nschten Ordner zu verschieben."), "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2right.png\" border=0 " . tooltip(_("Klicken Sie auf dieses Symbol, um diesen Ordner in einen anderen Ordner einzufügen.")) . ">");
        if($rechte) $content .= _("Wenn Sie den Ordner in eine andere Veranstaltung verschieben / kopieren möchten, wählen Sie die gewünschte Veranstaltung oben auf der Seite aus.");
    }
    if ($upload == $folder_id) {
        $content .= form($refresh);
    }
    // Abfrage ob Dateilink eingeleitet
    if ($filelink == $folder_id) {
        $content .= link_item($folder_id);
    }
    $content.= "\n";
    $edit='';
    //Editbereich erstellen
    if (($change != $folder_id) && ($upload != $folder_id) && ($filelink != $folder_id)) {
        if ($perm->have_studip_perm('autor', $SessionSeminar) && $folder_tree->isWritable($folder_id, $user->id))
            $edit= "<a href=\"".URLHelper::getLink("?open=".$folder_id."_u_&rand=".rand()."#anker")."\">" . makeButton("dateihochladen", "img", _("Datei hochladen")) . "</a>";
        if ($rechte)
            $edit.= "&nbsp;<a href=\"".URLHelper::getLink("?open=".$folder_id."_l_&rand=".rand()."#anker")."\">" . makeButton("link", "img", _("Datei verlinken")) . "</a>";
        if ($document_count && $folder_tree->isReadable($folder_id, $user->id))
            $edit.= "&nbsp;&nbsp;&nbsp;<a href=\"".URLHelper::getLink("?folderzip=".$folder_id)."\">" . makeButton("ordneralszip", "img", _("Ordner als ZIP")) . "</a>";
        if ($perm->have_studip_perm('autor', $SessionSeminar) && $folder_tree->checkCreateFolder($folder_id, $user->id)) {
            if ($folder_tree->isWritable($folder_id, $user->id) && !$folder_tree->isExerciseFolder($folder_id, $user->id)) {
                $edit.= "&nbsp;&nbsp;&nbsp;<a href=\"".URLHelper::getLink("?open=".$folder_id."_n_#anker")."\">" . makeButton("neuerordner", "img", _("Ordner anlegen")) . "</a>";
            if ($rechte && get_config('ZIP_UPLOAD_ENABLE')) {
                $edit .= "&nbsp;&nbsp;&nbsp;<a href=\"".URLHelper::getLink("?open=".$folder_id."_z_&rand="
                    . rand()."#anker")."\">" . makeButton("ziphochladen", "img", _("als ZIP hochladen")) . "</a>";
                }
            }
            if ($rechte ||
                (!$document_count && $level !=0 &&
                    ($folder_tree->isWritable($folder_id, $user->id) &&
                    $folder_tree->isWritable($folder_tree->getValue($folder_id, 'parent_id'), $user->id) &&
                        !$folder_tree->isExerciseFolder($folder_id, $user->id))
                )
                    ) $edit.= " <a href=\"".URLHelper::getLink("?open=".$folder_id."_d_")."\">" . makeButton("loeschen", "img", _("löschen")) . "</a>";
            if ($folder_tree->isWritable($folder_id, $user->id) && !$folder_tree->isExerciseFolder($folder_id, $user->id))
                $edit.= " <a href=\"".URLHelper::getLink("?open=".$folder_id."_c_#anker")."\">" . makeButton("bearbeiten", "img", _("bearbeiten")) . "</a>";
            if (($rechte && $result['range_id'] != $SessSemName[1]) ||
                ($level !=0 &&
                    ($folder_tree->isWritable($folder_id, $user->id) &&
                    $folder_tree->isWritable($folder_tree->getValue($folder_id, 'parent_id'), $user->id) &&
                    !$folder_tree->isExerciseFolder($folder_id, $user->id))
                    )
                ) $edit.= " <a href=\"".URLHelper::getLink("?open=".$folder_id."_m_#anker")."\">" . makeButton("verschieben", "img", _("verschieben")) . "</a>";
            if ($rechte || ($level !=0 && !$folder_tree->isExerciseFolder($folder_id, $user->id))) {
                $edit.= " <a href=\"".URLHelper::getLink("?open=".$folder_id."_co_#anker")."\">" . makeButton("kopieren", "img", _("kopieren")) . "</a>";
            }
        }
        if ($rechte) {
            $edit .= " <a href=\"".URLHelper::getLink("?open=".$folder_id."_az_#anker")."\"".tooltip("Dateien alphabetisch sortieren").">" . makeButton("sortieren", "img", _("sortieren")) . "</a>";
        }
    }

    if (!$edit) $edit = '&nbsp;';
    print "<table width=\"100%\" cellpadding=0 cellspacing=0 border=0><tr>";
    //Ordner-Content ausgeben
    printcontent ("99%", TRUE, $content, $edit);
    print "</tr></table>";

    print "</td></tr>";

    //Ein paar Überprüfungen, was eigentlich angezeigt werden soll: Dateien und Unterordner
    $folders_kids = $folder_tree->getKids($folder_id);
    $folders_kids = $db->query("SELECT folder_id " .
            "FROM folder " .
            "WHERE range_id = ".$db->quote($folder_id)." " .
                    "ORDER BY priority ASC, name ASC")->fetchAll();

    $hasrealkids = $folder_tree->hasKids($folder_id);
    if ( ((count($folders_kids)) || ($document_count > 0))
            && (($rechte) || ($folder_tree->isExecutable($folder_id, $user->id))) ) {
        print "<tr>";
        //Der Navigationsast nach unten
        print "<td class=\"tree-elbow-line\">" . Assets::img("datatree_3.gif") . "</td>";
        //Mehrere Zeilen, die wiederum Dateien mit eventuellen Optionen sind.
        print "<td colspan=3>";

        print "<div class=\"folder_container".($rechte ? " sortable" : "")."\" id=\"folder_subfolders_".$folder_id."\">";
        //Unterordner darstellen:
        is_array($folders_kids) || $folders_kids = array();
        $subfolders = array();
        foreach ($folders_kids as $key => $unterordner) {
            if (($folder_tree->isExecutable($unterordner['folder_id'], $user->id)) || ($rechte)) { //bin ich Dozent oder Tutor?
                $subfolders[] = $unterordner['folder_id'];
            }
        }
        if ($subfolders) {
            foreach ($subfolders as $key => $subfolder) {
                $folder_pos = ((count($subfolders) > 1) ? (($key == 0) ? "top" : (($key == count($subfolders)-1) ? "bottom" : "middle")) : "alone");
                display_folder($subfolder, $open, $change, $move, $upload, $refresh, $filelink, $anchor_id, $folder_pos, false);
            }
        }
        print "</div>";

        //Dateien darstellen:
        $countfolder++;
        print "<div class=\"folder_container".($rechte ? " sortable" : "")."\" id=\"folder_".$folder_id."\">";
        if (($rechte) || ($folder_tree->isReadable($folder_id, $user->id))) {
            $query = "SELECT a.*,". $_fullname_sql['full'] ." AS fullname, " .
                            "username, " .
                            "IF(IFNULL(a.name,'')='', a.filename,a.name) AS t_name " .
                    "FROM dokumente a " .
                            "LEFT JOIN auth_user_md5 USING (user_id) " .
                            "LEFT JOIN user_info USING (user_id) " .
                    "WHERE range_id = '".$result["folder_id"]."' " .
                    "ORDER BY a.priority ASC, t_name ASC, a.chdate DESC ";
            $result2 = $db->query($query)->fetchAll();
            foreach ($result2 as $key => $datei) {
                $file_pos = ((count($result2) > 1) ? (($key == 0) ? "top" : (($key == count($result2)-1) ? "bottom" : "middle")) : "alone");
                display_file_line($datei, $folder_id, $open, $change, $move, $upload, FALSE, $refresh, $filelink, $anchor_id, $file_pos);
            }
        }
        print "</div>";
        print "</td></tr>";

    }
    print "</table>";   //Ende der zweiten Tabelle
}

$countfolder = 0;
$droppable_folder = 0;
/**
 * Displays the folder and all of its documents and recursively subfolders.
 * This function is not dependent on the recursive-level so it looks as if it all starts from here.
 *
 */
function display_folder ($folder_id, $open, $change, $move, $upload, $refresh=FALSE, $filelink="", $anchor_id, $position="middle", $isissuefolder = false) {
    global $_fullname_sql,$SessionSeminar,$SessSemName, $rechte, $anfang,
        $user, $SemSecLevelWrite, $SemUserStatus, $check_all, $countfolder, $droppable_folder;
    $option = true;
    $countfolder++;
    $more = true;
    $db = DBManager::get();
    $droppable_folder++;
    $javascriptok = true;
    //Einbinden einer Klasse, die Informationen über den ganzen Baum enthält
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $SessionSeminar));

    //Hole alle Informationen, die es über $folder_id gibt
    $query = "SELECT ". $_fullname_sql['full'] ." AS fullname , username, folder_id, a.range_id, a.user_id, name, a.description, a.mkdate, a.chdate FROM folder a LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING (user_id) WHERE a.folder_id = '$folder_id' ORDER BY a.name, a.chdate";
    $result = $db->query($query)->fetch();

    $depth = $folder_tree->getItemPath($folder_id);
    $depth = count(explode(" / ", $depth));
    print "<div id=\"folder_".(($depth > 3) ? $result['range_id'] : "root")."_".$countfolder."\"".($rechte ? " class=\"draggable_folder\"" : "").">";
    print "<div style=\"display:none\" id=\"getmd5_fo".$result['range_id']."_".$countfolder."\">".$folder_id."</div>";
    print "<table cellpadding=0 border=0 cellspacing=0 width=\"100%\"><tr>";

    //Abzweigung, wenn Ordner ein Unterordner ist
    if ($depth > 3) //Warum gerade 3, soll jeder selbst rausfinden
        print "<td class=\"tree-elbow-end\">" . Assets::img("datatree_2.gif") . "</td>";
    else
        print "<td></td>";
    print "<td valign=\"bottom\">";

    //Farbe des Pfeils bestimmen:
    $chdate = (($result["chdate"]) ? $result["chdate"] : $result["mkdate"]);
    if (object_get_visit($SessSemName[1], "documents") < $chdate)
        $neuer_ordner = TRUE;
    else
        $neuer_ordner = FALSE;
    if ($neuer_ordner == TRUE)
        $timecolor = "#FF0000";
    else {
        $timediff = (int) log((time() - doc_newest($folder_id)) / 86400 + 1) * 15;
        if ($timediff >= 68)
            $timediff = 68;
        $red = dechex(255 - $timediff);
        $other = dechex(119 + $timediff);
        $timecolor= "#" . $red . $other . $other;
    }

    //Jetzt fängt eine zweite Tabelle an mit den Zeilen: Titel, Beschreibung und Knöpfe, Unterdateien und Unterordner
    if ($rechte) {
        print "<div class=\"droppable\" id=\"dropfolder_$folder_id\">";
    }
    print "<table cellpadding=0 border=0 cellspacing=0 width=\"100%\" id=\"droppable_folder_$droppable_folder\"><tr>";

    // -> Pfeile zum Verschieben (bzw. die Ziehfläche)
    if (($rechte) && ($depth > 3)) {
        $bewegeflaeche = "<span class=\"updown_marker\" id=\"pfeile_".$folder_id."\">";
        if (($position == "middle") || ($position == "bottom")) {
            $bewegeflaeche .= "<a href=\"".URLHelper::getLink('?open='.$folder_id)."_mfou_\" title=\""._("Nach oben verschieben").
                    "\">" . Assets::img('icons/16/yellow/arr_2up.png', array('class' => 'text-top')) . "</a>";
        }
        if (($position == "middle") || ($position == "top")) {
            $bewegeflaeche .= "<a href=\"".URLHelper::getLink('?open='.
                    $folder_id)."_mfod_\" title=\""._("Nach unten verschieben").
                    "\">" . Assets::img('icons/16/yellow/arr_2down.png', array('class' => 'text-top')) . "</a>";
        }
        $bewegeflaeche .= "</span>";
    }

    //Jetzt folgt der Link zum Aufklappen
    if ($open[$folder_id]) {
        //print "<td width=1px class=\"printhead\">&nbsp;</td>";
        print "<td id=\"folder_".$folder_id."_arrow_td\" nowrap valign=\"top\" align=\"left\" width=1% bgcolor=\"$timecolor\" class=\"printhead3\" valign=\"bottom\">";
        print "<a href=\"".URLHelper::getLink("?close=".$folder_id."#anker");
        print "\" class=\"tree\" onClick=\"return STUDIP.Filesystem.changefolderbody('".$folder_id."')\"><img id=\"folder_".$folder_id."_arrow_img\" src=\"".$GLOBALS['ASSETS_URL']."images/forumgraurunt2.png\"".tooltip(_("Objekt zuklappen"))."></a>";
        print "</td>";
        //print ($javascriptok ? "<td class=\"printhead\"><a href=\"Javascript: changefolderbody('".$folder_id."')\" class=\"tree\"><span id=\"folder_".$folder_id."_header\" style=\"font-weight: bold\">" :
        print "<td class=\"printhead\" valign=\"bottom\">";
        if ($move && ($move != $folder_id) && $folder_tree->isWritable($folder_id, $user->id) && (!$folder_tree->isFolder($move) || ($folder_tree->checkCreateFolder($folder_id, $user->id) && !$folder_tree->isExerciseFolder($folder_id, $user->id)))){
                print "<a href=\"".URLHelper::getLink("?open=".$folder_id."_md_")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2right.png\"></a>&nbsp;";
        }
        if (($anchor_id == $folder_id) || (($move == $folder_id))) {
            print "<a name=\"anker\"></a>";
        }
        print "<a href=\"".URLHelper::getLink("?close=".$folder_id."#anker")."\" class=\"tree\" onClick=\"return STUDIP.Filesystem.changefolderbody('".$folder_id."')\"><span id=\"folder_".$folder_id."_header\" style=\"font-weight: bold\">";
    } else {
        //print "<td width=1px class=\"printhead\">&nbsp;</td>";
        print "<td id=\"folder_".$folder_id."_arrow_td\" nowrap valign=\"top\" align=\"left\" width=1% bgcolor=\"$timecolor\" class=\"printhead2\" valign=\"bottom\">";
        print "<a href=\"";
        print URLHelper::getLink("?open=".$folder_id."#anker");
        print "\" class=\"tree\" onClick=\"return STUDIP.Filesystem.changefolderbody('".$folder_id."')\"><img id=\"folder_".$folder_id."_arrow_img\" src=\"".$GLOBALS['ASSETS_URL']."images/forumgrau2.png\"".tooltip(_("Objekt aufklappen"))." border=0></a></td>";
        print "<td class=\"printhead\" valign=\"bottom\">";
        if ($move && ($move != $folder_id) && $folder_tree->isWritable($folder_id, $user->id) && (!$folder_tree->isFolder($move) || ($folder_tree->checkCreateFolder($folder_id, $user->id) && !$folder_tree->isExerciseFolder($folder_id, $user->id)))){
            print "&nbsp;<a href=\"".URLHelper::getLink("?open=".$folder_id."_md_")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2right.png\" border=0></a>&nbsp";
        }
        print "<a href=\"".URLHelper::getLink("?open=".$folder_id."#anker")."\" class=\"tree\" " .
                "onClick=\"return STUDIP.Filesystem.changefolderbody('".$folder_id."')\"><span id=\"folder_".$folder_id."_header\" " .
                "style=\"font-weight: normal\">";
    }

    $document_count = doc_count($folder_id);

    if ($document_count > 0)
        print "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/folder-full.png\" border=0>&nbsp;";
    else
        print "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/folder-full.png\" border=0>&nbsp;";

    //Pfeile, wenn Datei bewegt werden soll
    if ($move && ($folder_id != $move) && $folder_tree->isWritable($folder_id, $user->id) && (!$folder_tree->isFolder($move) || ($folder_tree->checkCreateFolder($folder_id, $user->id) && !$folder_tree->isExerciseFolder($folder_id, $user->id)))){
        print "</a><span class=\"move_arrows\"><a href=\"".URLHelper::getLink("?open=".$folder_id."_md_")."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/yellow/arr_2right.png\" border=0></a></span>";
        if ($open[$folder_id])
            print "<a href=\"".URLHelper::getLink("?close=".$folder_id."#anker")."\" class=\"tree\" onClick=\"return STUDIP.Filesystem.changefolderbody('".$folder_id."')\">";
        else
            print "<a href=\"".URLHelper::getLink("?open=".$folder_id."#anker")."\" class=\"tree\" onClick=\"return STUDIP.Filesystem.changefolderbody('".$folder_id."')\">";
    }

    //Dateiname, Rechte und Dokumente anzeigen
    $tmp_titel = htmlReady(mila($result['name']));
    if ($isissuefolder) {
        $issue_id = $db->query("SELECT range_id FROM folder WHERE folder_id = ".$db->quote($folder_id))->fetch();
        $dates_for_issue = IssueDB::getDatesforIssue($issue_id['range_id']);
        $dates_title = array();
        foreach ($dates_for_issue as $date) {
            $dates_title[] .= date('d.m.y, H:i', $date['date']).' - '.date('H:i', $date['end_time']);
        }
        $tmp_titel = sprintf(_("Sitzung am: %s"), implode(', ', $dates_title)) .
             ", " . ($tmp_titel ? $tmp_titel : _("ohne Titel"));
    }

    if (($change == $folder_id)
            && (!$isissuefolder)
            && ((count($folder_tree->getParents($folder_id)) > 1)
             || $result['range_id'] == md5($SessSemName[1] . 'top_folder')
             || $folder_tree->isGroupFolder($result['folder_id'])
             )
            ) { //Aenderungsmodus, Anker + Formular machen, Font tag direkt ausgeben (muss ausserhalb einer td stehen!
        $titel= "</a><input style=\"font-size:8 pt; width: 400px;\" type=\"text\" size=\"20\" maxlength=\"255\" aria-label=\"Ordnername eingeben\" name=\"change_name\" value=\"".htmlReady($result['name'])."\" >";
        if ($rechte && $folder_tree->permissions_activated)
            $titel .= '&nbsp;<font color="red">['.$folder_tree->getPermissionString($result["folder_id"]).']</font>';
    }   else {
        //create a link onto the titel, too
        if ($rechte && $folder_tree->permissions_activated ) {
            $tmp_titel .= '&nbsp;';
            $tmp_titel .= '<font color="red">['.$folder_tree->getPermissionString($result["folder_id"]).']</font>';
        }
        if ($document_count > 1)
            $titel= $tmp_titel."</span>&nbsp;&nbsp;" . sprintf(_("(%s Dokumente)"), $document_count);
        elseif ($document_count)
            $titel= $tmp_titel."</span>&nbsp;&nbsp;" . _("(1 Dokument)");
        else
            $titel= $tmp_titel;
    }
    print $titel;

    if ($isissuefolder) {
        $dates_title = array();
        foreach ($dates_for_issue as $date) {
            $dates_title[] .= date('d.m.y, H:i', $date['date']).' - '.date('H:i', $date['end_time']);
        }
        if (sizeof($dates_title) > 0) {
            $title_name = sprintf(_("Sitzung am: %s"), implode(', ', $dates_title));
            if (!$result['name']) {
                $title_name .= _(", ohne Titel");
            } else {
                $title_name .= ', '.htmlReady($result['name']);
            }
        }
    }

    print "</a>&nbsp;";

    // Schloss, wenn Folder gelockt
    if ($folder_tree->isLockedFolder($folder_id))
        print "<img class=\"text-bottom\" ".tooltip(_("Dieser Ordner ist gesperrt."))." src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/lock-locked.png\">";
    //Wenn verdeckt durch gesperrten übergeordneten Ordner
    else if ( ($super_folder = $folder_tree->getNextSuperFolder($folder_id)) )
        print "<img class=\"text-bottom\" ".tooltip(_("Dieser Ordner ist nicht zugänglich, da ein übergeordneter Ordner gesperrt ist."))." src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/lock-locked.png\">";
    // Wenn es ein Hausaufgabenordner ist
    if ($folder_tree->isExerciseFolder($folder_id))
        print "<img class=\"text-bottom\" ".tooltip(_("Dieser Ordner ist ein Hausaufgabenordner. Es können nur Dateien eingestellt werden."))." src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/grey/edit.png\">";

    print "</td>";

    //So und jetzt die rechtsbündigen Sachen:
    print "</td><td align=right class=\"printhead\" valign=\"bottom\">";

    print "<a href=\"".URLHelper::getLink('about.php?username='.$result['username'])."\">".htmlReady($result['fullname'])."</a> ";

    print $bewegeflaeche." ";

    //Workaround for older data from previous versions (chdate is 0)
    print date("d.m.Y - H:i", (($result["chdate"]) ? $result["chdate"] : $result["mkdate"]));

    print "</td></tr></table>"; //Ende des Titels, Beschreibung und Knöpfen
    if ($rechte)
        print "</div>";  //End des Droppable-Divs

    if ($open[$folder_id]) {
        print "<div id=\"folder_".$folder_id."_body\">";
        //Der ganze Teil des Unterbaus wurde in die folgende Funktion outsourced:
        display_folder_body($folder_id, $open, $change, $move, $upload, $refresh, $filelink, $anchor_id);
    } else {
        print "<div id=\"folder_".$folder_id."_body\" style=\"display: none\">";
    }
    print "</div></td></tr></table>";
    print "</div>";
}


function getLinkPath($file_id) {
    $db = new DB_Seminar;
    $db->query("SELECT url FROM dokumente WHERE dokument_id='$file_id'");
    if ($db->next_record())
        $url = $db->f("url");
    else
        $url = FALSE;
    return $url;
}

function GetFileIcon($ext, $with_img_tag = false){
    $extension = strtolower($ext);
    //Icon auswaehlen
    switch ($extension){
        case 'rtf':
        case 'doc':
        case 'docx':
            $icon = 'icons/16/blue/file-text.png';
        break;
        case 'xls':
        case 'csv':
            $icon = 'icons/16/blue/file-xls.png';
        break;
        case 'zip':
        case 'tgz':
        case 'gz':
        case 'bz2':
            $icon = 'icons/16/blue/file-archive.png';
        break;
        case 'ppt':
            $icon = 'icons/16/blue/file-presentation.png';
        break;
        case 'pdf':
            $icon = 'icons/16/blue/file-pdf.png';
        break;
        case 'gif':
        case 'jpg':
        case 'jpe':
        case 'jpeg':
        case 'png':
        case 'bmp':
            $icon = 'icons/16/blue/file-image.png';
        break;
        default:
            $icon = 'icons/16/blue/file-generic.png';
        break;
    }
    return ($with_img_tag ? '<img class=\"text-top\" src="'.$GLOBALS['ASSETS_URL'].'images/'.$icon.'" border="0">' : $icon);
}

/**
 * Determines an appropriate MIME type for a file based on the
 * extension of the file name.
 *
 * @param string $filename      file name to check
 */
function get_mime_type($filename)
{
    static $mime_types = array(
        // archive types
        'gz'   => 'application/x-gzip',
        'tgz'  => 'application/x-gzip',
        'bz2'  => 'application/x-bzip2',
        'zip'  => 'application/zip',
        // document types
        'txt'  => 'text/plain',
        'css'  => 'text/css',
        'csv'  => 'text/csv',
        'rtf'  => 'application/rtf',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'xls'  => 'application/ms-excel',
        'ppt'  => 'application/ms-powerpoint',
        'swf'  => 'application/x-shockwave-flash',
        // image types
        'gif'  => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'jpe'  => 'image/jpeg',
        'png'  => 'image/png',
        'bmp'  => 'image/x-ms-bmp',
        // audio types
        'mp3'  => 'audio/mp3',
        'oga'  => 'audio/ogg',
        'wav'  => 'audio/wave',
        'ra'   => 'application/x-pn-realaudio',
        'ram'  => 'application/x-pn-realaudio',
        // video types
        'mpeg' => 'video/mpeg',
        'mpg'  => 'video/mpeg',
        'mpe'  => 'video/mpeg',
        'qt'   => 'video/quicktime',
        'mov'  => 'video/quicktime',
        'avi'  => 'video/x-msvideo',
        'flv'  => 'video/x-flv',
        'ogg'  => 'application/ogg',
        'ogv'  => 'video/ogg',
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
    );

    $extension = strtolower(getFileExtension($filename));

    if (isset($mime_types[$extension])) {
        return $mime_types[$extension];
    } else {
        return 'application/octet-stream';
    }
}

/**
* Erzeugt einen Downloadlink abhaengig von der Konfiguration des Systems
* ($GLOBALS['SENDFILE_LINK_MODE'] = 'normal'|'old'|'rewrite')
*
* @param    string  $file_id
* @param    string  $file_name
* @param    integer $type   sendfile type 1,2,3,4,5 or 6
* @param    string  $dltype 'normal', 'zip' or 'force' (or 'force_download')
* @return   string  downloadlink
*/
function GetDownloadLink($file_id, $file_name, $type = 0, $dltype = 'normal', $range_id = '', $list_id = ''){
    $mode = (isset($GLOBALS['SENDFILE_LINK_MODE']))? $GLOBALS['SENDFILE_LINK_MODE']:'normal';
    $link[] = $GLOBALS['ABSOLUTE_URI_STUDIP'];
    $wa = '';
    switch($mode) {
    case 'rewrite':
        $link[] = 'download/';
        switch ($dltype) {
        case 'zip':
            $link[] = 'zip/';
            break;
        case 'force':
        case 'force_download':
            $link[] = 'force_download/';
            break;
        case 'normal':
        default:
            $link[] = 'normal/';
        }
        $link[] = $type . '/';
        if ($type == 5) {
            $link[] = rawurlencode($range_id) . '/' . rawurlencode($list_id);
        } else {
            $link[] =  rawurlencode(prepareFilename($file_id));
        }
        $link[] = '/' . rawurlencode(prepareFilename($file_name));
        break;
    case 'old':  // workaround for old browser (IE)
        $wa = '/';
    case 'normal':
    default:
        $link[] = 'sendfile.php?' . $wa;
        if ($dltype == 'zip'){
            $link[] = 'zip=1&';
        } elseif ($dltype == 'force_download' || $dltype == 'force') {
            $link[] = 'force_download=1&';
        }
        $link[] = 'type='.$type;
        if ($type == 5) {
            $link[] = '&range_id=' . rawurlencode($range_id) . '&list_id=' . rawurlencode($list_id);
        } else {
            $link[] = '&file_id=' . rawurlencode(prepareFilename($file_id));
        }
        $link[] = '&file_name=' . rawurlencode(prepareFilename($file_name ));
    }
    return implode('', $link);
}


/*
Die function delete_document löscht ein hochgeladenes Dokument.
Der erste Parameter ist die dokument_id des zu löschenden Dokuments.
Der Rückgabewert der Funktion ist bei Erfolg TRUE.
FALSE bedeutet einen Fehler beim Loeschen des Dokumentes.
Ausgabe wird keine produziert.
Es erfolgt keine Überprüfung der Berechtigung innerhalb der Funktion,
dies muss das aufrufende Script sicherstellen.
*/

function delete_document($dokument_id, $delete_only_file = FALSE) {
    $db = new DB_Seminar;
    $db->query("SELECT * FROM dokumente WHERE dokument_id='$dokument_id'");
    if ($db->next_record()) {
        if ($db->f("url") == "") {   //Bei verlinkten Datein nicht nachsehen ob es Datei gibt!
            @unlink(get_upload_file_path($dokument_id));
            if ($delete_only_file){
                return TRUE;
            }
        }
    }

    // eintrag aus der Datenbank werfen
    $doc = new StudipDocument($dokument_id);
    return $doc->delete();
}

function delete_link($dokument_id) {
    // eintrag aus der Datenbank werfen
    $doc = new StudipDocument($dokument_id);
    return $doc->delete();
}

/*
Die function delete_folder löscht einen kompletten Dateiordner.
Der Parameter ist die folder_id des zu löschenden Ordners.
Der Rückgabewert der Funktion ist bei Erfolg TRUE.
FALSE bedeutet einen Fehler beim Loeschen des Dokumentes.
Ausgabe wird keine produziert.
Es erfolgt keine Überprüfung der Berechtigung innerhalb der Funktion,
dies muss das aufrufende Script sicherstellen.
*/

function delete_folder($folder_id, $delete_subfolders = false) {

    global $msg;

    $db = new DB_Seminar;

    if ($delete_subfolders){
        list($subfolders, $num_subfolders) = getFolderChildren($folder_id);
        if ($num_subfolders){
            foreach ($subfolders as $one_folder){
                delete_folder($one_folder, true);
            }
        }
    }

    $db->query("SELECT name,folder_id FROM folder WHERE folder_id='$folder_id'");
    if ($db->next_record()){
        $foldername = $db->f('name');
        $db->query("SELECT dokument_id FROM dokumente WHERE range_id='$folder_id'");
        while ($db->next_record()){
            if (delete_document($db->f("dokument_id"))){
                $deleted++;
            }
        }
        $db->query("DELETE FROM folder WHERE folder_id='$folder_id'");
        if ($db->affected_rows()) {
            if ($deleted){
                $msg.="info§" . sprintf(_("Der Dateiordner <b>%s</b> und %s Dokument(e) wurden gel&ouml;scht"), htmlReady($foldername), $deleted) . "§";
            } else {
                $msg.="info§" . sprintf(_("Der Dateiordner <b>%s</b> wurde gel&ouml;scht"),htmlReady($foldername)) . "§";
                return TRUE;
            }
        } else {
            if ($deleted){
                $msg.="error§" . sprintf(_("Probleme beim L&ouml;schen des Ordners <b>%s</b>. %s Dokument(e) wurden gel&ouml;scht"),htmlReady($foldername), $deleted) . "§";
            }else{
                $msg.="error§" . sprintf(_("Probleme beim L&ouml;schen des Ordners <b>%s</b>"),htmlReady($foldername)) . "§";
            }
            return FALSE;
        }
    }
}

//Rekursive Loeschfunktion, loescht erst jeweils enthaltene Dokumente und dann den entsprechenden Ordner
function recursiv_folder_delete($parent_id) {

    $db=new DB_Seminar;
    $db2=new DB_Seminar;

    $doc_count = 0;

    $db->query ("SELECT folder_id FROM folder WHERE range_id='$parent_id'");

    while ($db->next_record()) {
        $doc_count += recursiv_folder_delete($db->f("folder_id"));

        $db2->query ("SELECT dokument_id FROM dokumente WHERE range_id='".$db->f("folder_id")."'");

        while ($db2->next_record()) {
            if (delete_document($db2->f("dokument_id")))
                $doc_count++;
            }

         $db2->query ("DELETE FROM folder WHERE folder_id ='".$db->f("folder_id")."'");
        }
    return $doc_count;
    }

function delete_all_documents($range_id){
    if (!$range_id){
        return false;
    }
    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $range_id));
    if($folder_tree->getNumKids('root')){
        foreach($folder_tree->getKids('root') as $folder_id){
            $count += recursiv_folder_delete($folder_id);
        }
    }
    return $count;
}
/**
* Delete a file, or a folder and its contents
*
* @author      Aidan Lister <aidan@php.net>
* @version     1.0
* @param       string   $dirname    The directory to delete
* @return      bool     Returns true on success, false on failure
*/
function rmdirr($dirname){
    // Simple delete for a file
    if (is_file($dirname)) {
        return @unlink($dirname);
    } else if (!is_dir($dirname)){
        return false;
    }

    // Loop through the folder
    $dir = dir($dirname);
    while (false !== ($entry = $dir->read())) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep delete directories
        if (is_dir("$dirname/$entry")) {
            rmdirr("$dirname/$entry");
        } else {
            @unlink("$dirname/$entry");
        }
    }
    // Clean up
    $dir->close();
    return @rmdir($dirname);
}

function create_zip_from_file($file_name, $zip_file_name){
    if (strtolower(substr($zip_file_name, -3)) != 'zip' ) $zip_file_name = $zip_file_name . '.zip';
    if ($GLOBALS['ZIP_USE_INTERNAL']){
        $archiv = new PclZip($zip_file_name);
        $v_list = $archiv->create($file_name, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_CB_PRE_ADD, 'pclzip_convert_filename_cb');
        return $v_list;
    } else if (@file_exists($GLOBALS['ZIP_PATH']) || ini_get('safe_mode')){
        exec($GLOBALS['ZIP_PATH'] . ' -q ' . $GLOBALS['ZIP_OPTIONS'] . " -j {$zip_file_name} $file_name", $output, $ret);
        return $ret;
    }

    // return false, if nothing worked
    return false;
}

function create_zip_from_directory($fullpath, $zip_file_name){
    if (strtolower(substr($zip_file_name, -3)) != 'zip' ) $zip_file_name = $zip_file_name . '.zip';
    if ($GLOBALS['ZIP_USE_INTERNAL']){
        $archiv = new PclZip($zip_file_name);
        $v_list = $archiv->create($fullpath, PCLZIP_OPT_REMOVE_PATH, $fullpath, PCLZIP_CB_PRE_ADD, 'pclzip_convert_filename_cb');
        return $v_list;
    } else if (@file_exists($GLOBALS['ZIP_PATH']) || ini_get('safe_mode')){
        //zip stuff
        $zippara = (ini_get('safe_mode')) ? ' -R ':' -r ';
        if (@chdir($fullpath)) {
            exec ($GLOBALS['ZIP_PATH'] . ' -q -D ' . $GLOBALS['ZIP_OPTIONS'] . ' ' . $zippara . $zip_file_name . ' *',$output, $ret);
            @chdir($GLOBALS['ABSOLUTE_PATH_STUDIP']);
        }
        return $ret;
    }
}

function create_zip_from_newest_files() {
    global $SessSemName;
    $db = DBManager::get();
    $result = $db->query("SELECT filename FROM dokumente WHERE chdate > ".object_get_visit($SessSemName[1], "documents")." OR mkdate > ".object_get_visit($SessSemName[1], "documents"))->fetchAll();
}

function unzip_file($file_name, $dir_name = '', $testonly = false){
    $ret = true;
    if ($GLOBALS['ZIP_USE_INTERNAL']){
        $archive = new PclZip($file_name);
        if ($testonly){
            $prop = $archive->properties();
            $ret = (!is_array($prop));
        } else {
            $ok = $archive->extract(PCLZIP_OPT_PATH, $dir_name, PCLZIP_CB_PRE_EXTRACT, 'pclzip_convert_filename_cb');
            $ret = (!is_array($ok));
        }
    } else if (@file_exists($GLOBALS['UNZIP_PATH']) || ini_get('safe_mode')){
        if ($testonly){
            exec($GLOBALS['UNZIP_PATH'] . " -t -qq $file_name ", $output, $ret);
        } else {
            exec($GLOBALS['UNZIP_PATH'] . " -qq $file_name " . ($dir_name ? "-d $dir_name" : ""), $output, $ret);
        }
    }
    return $ret;
}

function upload_zip_item() {
    global $msg;

    if(!$_FILES['the_file']['name']) {
        $msg .= "error§" . _("Sie haben keine Datei zum Hochladen ausgew&auml;hlt!") . "§";
        return FALSE;
    }
    $ext = strtolower(getFileExtension($_FILES['the_file']['name']));
    if($ext != "zip") {
        $msg .= "error§" . _("Die Datei kann nicht entpackt werden: Sie d&uuml;rfen nur den Dateityp .ZIP hochladen!") . "§";
        return FALSE;
    }
    $tmpname = md5(uniqid('zipupload',1));
    if (move_uploaded_file($_FILES['the_file']['tmp_name'], $GLOBALS['TMP_PATH'] . '/' . $tmpname)){
        if(unzip_file($GLOBALS['TMP_PATH'] . '/' . $tmpname, false, true)) {
            $msg .= "error§" . _("Die ZIP-Datei kann nicht ge&ouml;ffnet werden!") . "§";
            @unlink($GLOBALS['TMP_PATH'] . '/' . $tmpname);
            return FALSE;
        }
        $tmpdirname = $GLOBALS['TMP_PATH'] . '/' . md5(uniqid('zipupload',1));
        @mkdir($tmpdirname);
        if (unzip_file($GLOBALS['TMP_PATH'] . '/' . $tmpname , $tmpdirname)){
            $msg .= "error§" . _("Die ZIP-Datei kann nicht ge&ouml;ffnet werden!") . "§";
            @rmdirr($tmpdirname);
            @unlink($GLOBALS['TMP_PATH'] . '/' . $tmpname);
            return FALSE;
        }
        $ret = upload_recursively($GLOBALS['folder_system_data']['upload'], $tmpdirname);
        if ($ret['files'] || $ret['subdirs'] ){
            $msg .= 'msg§' . sprintf(_("Es wurden %d Dateien und %d Ordner erfolgreich entpackt."),$ret['files'], $ret['subdirs'] ) . '§';
            @rmdirr($tmpdirname);
            @unlink($GLOBALS['TMP_PATH'] . '/' . $tmpname);
            return true;
        }
    }
    @rmdirr($tmpdirname);
    @unlink($GLOBALS['TMP_PATH'] . '/' . $tmpname);
    $msg .= "error§" . _("Die Datei konnte nicht entpackt werden.") . "§";
    return false;
}


/**
 * Laedt eine bestehende Verzeichnisstruktur in das System.
 * Die ganze Struktur wird samt Dateien und Unterverzeichnissen rekursiv
 * eingefuegt: 1. Den aktuellen Ordner erstellen. -- 2. Die Dateien in
 * alphabetischer Reihenfolge einfuegen. -- 3. Die Verzeichnisstruktur jedes
 * Unterordners einfuegen (Rekursion).
 * Nach Einfuegen einer Datei / eines Verzeichnisses wird die Datei oder das
 * Verzeichnis geloescht.
 *
 * @param range_id Die ID des Ordners unter dem die Verzeichnisstruktur
 * @param dir
 * @return (no return value)
 */
function upload_recursively($range_id, $dir) {
    static $count = array();

    $max_files = get_config('ZIP_UPLOAD_MAX_FILES');
    $max_dirs = get_config('ZIP_UPLOAD_MAX_DIRS');

    $files = array ();
    $subdirs = array ();

    if ($count['files'] >= $max_files || $count['subdirs'] >= $max_dirs) return;

    // Versuchen, das Verzeichnis zu oeffnen
    if ($handle = @opendir($dir)) {

        // Alle Eintraege des Verzeichnisses durchlaufen
        while (false !== ($file = readdir($handle))) {

            // Verzeichnisverweise . und .. ignorieren
            if ($file != "." && $file != "..") {
                // Namen vervollstaendigen
                $file = $dir."/".$file;

                if (is_file($file)) {
                    // Datei in Dateiliste einfuegen
                    $files[] = $file;
                }
                elseif (is_dir($file)) {
                    // Verzeichnis in Verzeichnisliste einfuegen
                    $subdirs[] = $file;
                }
            }
        }
        closedir($handle);
    }

    // Listen der Dateien und Unterverzeichnisse sortieren.
    sort($files);
    sort($subdirs);

    // Alle Dateien hinzufuegen.
    while (list ($nr, $file) = each($files)) {
        if ($count['files'] >= $max_files) break;
        if (validate_upload($file, $file)) {
            $count['files'] += upload_zip_file($range_id, $file);
        }
    }

    // Alle Unterverzeichnisse hinzufuegen.
    while (list ($nr, $subdir) = each($subdirs)) {
        if ($count['subdirs'] >= $max_dirs) break;
        // Verzeichnis erstellen
        $pos = strrpos($subdir, "/");
        $name = addslashes(substr($subdir, $pos + 1, strlen($subdir) - $pos));
        $dir_id = create_folder($name, "", $range_id);
        $count['subdirs']++;
        // Verzeichnis hochladen.
        upload_recursively($dir_id, $subdir);
    }
    return $count;
}


/**
 * Eine einzelne Datei in das Verzeichnis mit der dir_id einfuegen.
 */
function upload_zip_file($dir_id, $file) {

    global $user, $upload_seminar_id;

    $file_size = filesize($file);
    if (!$file_size) {
        return false;
    }

    $pos = strrpos($file, "/");
    $file_name = substr($file, $pos + 1, strlen($file) - $pos);

    $data = array(
        'filename'    => $file_name,
        'name'        => $file_name,
        'filesize'    => $file_size,
        'autor_host'  => $_SERVER['REMOTE_ADDR'],
        'user_id'     => $user->id,
        'range_id'    => $dir_id,
        'seminar_id'  => $upload_seminar_id,
        'author_name' => get_fullname()
    );

    return StudipDocument::createWithFile($file, $data) ? 1 : 0;
}

function pclzip_convert_filename_cb($p_event, &$p_header) {
    if($p_event == PCLZIP_CB_PRE_EXTRACT){
        $p_header['filename'] = iconv("IBM437", "ISO-8859-1", $p_header['filename']);
    } elseif ($p_event == PCLZIP_CB_PRE_ADD) {
        $p_header['stored_filename'] = iconv("ISO-8859-1", "IBM437", $p_header['stored_filename']);
    }
    return 1;
}

function get_flash_player ($document_id, $filename, $type) {
    global $auth;
    // width of image in pixels
    if (is_object($auth) && $auth->auth['xres']) {
        // 50% of x-resolution maximal
        $max_width = floor($auth->auth['xres'] / 4);
    } else {
        $max_width = 400;
    }
    $width = $max_width;
    $height = round($width * 0.75);
    if ($width > 200) {
        $flash_config = $GLOBALS['FLASHPLAYER_DEFAULT_CONFIG_MAX'];
    } else {
        $flash_config = $GLOBALS['FLASHPLAYER_DEFAULT_CONFIG_MIN'];
    }
    $cfg = Config::GetInstance();
    $DOCUMENTS_EMBEDD_FLASH_MOVIES = $cfg->getValue('DOCUMENTS_EMBEDD_FLASH_MOVIES');
    if ($DOCUMENTS_EMBEDD_FLASH_MOVIES == 'autoplay') {
        $flash_config .= '&amp;autoplay=1&amp;autoload=1';
    } else if ($DOCUMENTS_EMBEDD_FLASH_MOVIES == 'autoload') {
        $flash_config .= '&amp;autoload=1';
    }
    // we need the absolute url if the player is delivered from a different base
    $movie_url = $GLOBALS['ABSOLUTE_URI_STUDIP'] . str_replace($GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'], '', GetDownloadLink($document_id, $filename, $type, 'force'));
    $flash_object  = "\n<object type=\"application/x-shockwave-flash\" id=\"FlashPlayer\" data=\"".Assets::url()."flash/player_flv.swf\" width=\"$width\" height=\"$height\">\n";
    $flash_object .= "<param name=\"movie\" value=\"".Assets::url()."flash/player_flv.swf\">\n";
    $flash_object .= "<param name=\"FlashVars\" value=\"flv=" . urlencode($movie_url) . $flash_config . "\">\n";
    $flash_object .= "<embed src=\"".Assets::url()."flash/player_flv.swf\" movie=\"{$movie_url}\" type=\"application/x-shockwave-flash\" FlashVars=\"flv={$movie_url}{$flash_config}\">\n";
    $flash_object .= "</object>\n";

    return array('player' => $flash_object, 'width' => $width, 'height' => $height);
}

/**
 * Return the absolute path of an uploaded file. The uploaded files
 * are organized in sub-folders of UPLOAD_PATH to avoid performance
 * problems with large directories.
 * If the document_id is empty, NULL is returned.
 *
 * @param string MD5 id of the uploaded file
 */
function get_upload_file_path ($document_id)
{
    global $UPLOAD_PATH;

    if ($document_id == '') {
        return NULL;
    }

    $directory = $UPLOAD_PATH.'/'.substr($document_id, 0, 2);

    if (!file_exists($directory)) {
        mkdir($directory);
    }

    return $directory.'/'.$document_id;
}

/**
 *
 * checks if the 'protected' flag of a file is set and if
 * the course access is closed
 *
 * @param string MD5 id of the file
 * @return bool
 */
function check_protected_download($document_id)
{
    $ok = true;
    if(Config::GetInstance()->getValue('ENABLE_PROTECTED_DOWNLOAD_RESTRICTION')){
        $doc = new StudipDocument($document_id);
        if($doc->getValue('protected')){
            $ok = false;
            $range_id = $doc->getValue('seminar_id');
            if(get_object_type($range_id) == 'sem'){
                $seminar = Seminar::GetInstance($range_id);
                if( $seminar->read_level > 1 ||
                    $seminar->admission_type == 3
                    || ($seminar->admission_endtime_sem > 0 && $seminar->admission_endtime_sem < time())){
                    $ok = true;
                }
            }
        }
    }
    return $ok;
}

/**
 * converts a given array to a csv format
 *
 * @param array $data the data to convert, each row should be an array
 * @param string $filename full path to a file to write to, if omitted the csv content is returned
 * @param array $caption assoc array with captions, is written to the first line, $data is filtered by keys
 * @param string $delimiter sets the field delimiter (one character only)
 * @param string $enclosure sets the field enclosure (one character only)
 * @param string $eol sets the end of line format
 * @return mixed if $filename is given the number of written bytes, else the csv content as string
 */
function array_to_csv($data, $filename = null, $caption = null, $delimiter = ';' , $enclosure = '"', $eol = "\r\n" )
{
    $fp = fopen('php://temp', 'r+');
    $fp2 = fopen('php://temp', 'r+');
    if (is_array($caption)) {
        fputcsv($fp, array_values($caption), $delimiter, $enclosure);
        rewind($fp);
        $csv = stream_get_contents($fp);
        if ($eol != PHP_EOL) {
            $csv = trim($csv);
            $csv .= $eol;
        }
        fwrite($fp2, $csv);
        ftruncate($fp, 0);
        rewind($fp);
    }
    foreach ($data as $row) {
        if (is_array($caption)) {
            $fields = array();
            foreach(array_keys($caption) as $fieldname) {
                $fields[] = $row[$fieldname];
            }
        } else {
            $fields = $row;
        }
        fputcsv($fp, $fields, $delimiter, $enclosure);
        rewind($fp);
        $csv = stream_get_contents($fp);
        if ($eol != PHP_EOL) {
            $csv = trim($csv);
            $csv .= $eol;
        }
        fwrite($fp2, $csv);
        ftruncate($fp, 0);
        rewind($fp);
    }
    fclose($fp);
    rewind($fp2);
    if ($filename === null) {
        return stream_get_contents($fp2);
    } else {
        return file_put_contents($filename, $fp2);
    }
}
?>
