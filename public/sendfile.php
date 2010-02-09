<?php
# Lifter002: TEST
# Lifter007: TEST
# Lifter003: TEST
/**
* sendfile.php
*
* Send files to the browser an does permchecks
*
* @author		Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>,
*               André Noack <andre.noack@gmx.net>
* @access		public
* @package		studip_core
* @modulegroup	library
* @module		sendfile.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// sendfile.php - Datei an Browser senden
// Copyright (C) 2000 - 2002 Cornelis Kater <ckater@gwdg.de>
// Ralf Stockmann <rstockm@gwdg.de>, André Noack André Noack <andre.noack@gmx.net>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

ob_start();
page_open(array("sess" => "Seminar_Session",
				"auth" => "Seminar_Default_Auth",
				"perm" => "Seminar_Perm",
				"user" => "Seminar_User"));

require_once 'config.inc.php';
require_once 'lib/datei.inc.php';
require_once 'lib/classes/StudipLitList.class.php';

//basename() needs setlocale()
init_i18n($_SESSION['_language']);

$db = DBManager::get();
$file_id = escapeshellcmd(basename(Request::get('file_id')));
$type = Request::int('type');
if($type < 0 || $type > 7) $type = 0;

$document = new StudipDocument($file_id);

$object_id = $document->getValue('seminar_id');

$no_access = true;

//download from course or institute
if ($object_id && in_array($type, array(0,6))){
	$object_type = get_object_type($object_id);
	//download from institute is always allowed
	if ($object_type == "inst" || $object_type == "fak"){
		$no_access = false;
	}
	//download from course is allowed if course is free for all or user is participant
	if($object_type == 'sem'){
		$no_access = !$perm->have_studip_perm('user', $object_id);
		$seminar = Seminar::GetInstance($object_id);
		if($seminar->read_level == 0){
			$no_access = false;
		}
	}
	//if allowed basically, check for closed folders and protected documents
	if($no_access == false){
		$folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $object_id));
		if (!$folder_tree->isDownloadFolder($document->getValue('range_id'), $GLOBALS['user']->id)) {
			$no_access = true;
		}
		if(!check_protected_download($file_id)){
			$no_access = true;
		}
	}
}
//download from archive, allowed if former participant
if ($type == 1){
	$archiv_seminar_id = $db->query("SELECT seminar_id FROM archiv WHERE archiv_file_id = ".$db->quote($file_id))
							->fetchColumn();
	$no_access = !archiv_check_perm($archiv_seminar_id);
}
//download bibliography
if ($type == 5){
	$range_id = Request::option('range_id');
	$list_id = Request::option('list_id');
	if ($range_id == $user->id || $perm->have_studip_perm('tutor', $range_id)){
		$no_access = false;
		$the_data = StudipLitList::GetTabbedList($range_id, $list_id);
	}
}
//download message attachment
if ($type == 7){
	$st = $db->prepare("SELECT dokument_id FROM dokumente 
		INNER JOIN message_user ON message_id=range_id AND message_user.user_id = ?
		WHERE dokument_id = ?");
	$st->execute(array($user->id, $file_id));
	$no_access = !($st->fetchColumn());
}
//download ad hoc created files, always allowed
if(in_array($type, array(2,3,4))){
	$no_access = false;
}

//if download not allowed throw exception to terminate script
if ($no_access) {
	throw new Studip_AccessDeniedException(_("Sie haben keine Zugriffsberechtigung für diesen Download!"));
}

switch ($type) {
	//We want to download from the archive (this mode performs perm checks)
	case 1:
		$path_file = $ARCHIV_PATH."/".$file_id;
	break;
	//We want to download from the tmp/export-folder
	case 2:
		$path_file = $TMP_PATH."/export/".$file_id;
	break;
	//We want to download an XSL-Script
	case 3:
		$path_file = $STUDIP_BASE_PATH . "/" . $PATH_EXPORT . "/".$file_id;
	break;
	//we want to download from the studip-tmp folder (this mode performs perm checks)
	case 4:
		$path_file = $TMP_PATH . "/".$file_id;
	break;
	//download lit list as tab delimited text file
	case 5:
		$path_file = false;
	break;
	//download linked file
	case 6:
		$path_file = getLinkPath($file_id);
	break;
	//we want to download a file attached to a system message (this mode performs perm checks)
	case 7:
		$path_file = get_upload_file_path($file_id);
	break;
	//we want to download from the regular upload-folder (this mode performs perm checks)
	default:
		$path_file = get_upload_file_path($file_id);
	break;
}

//replace bad charakters to avoid problems when saving the file
$file_name = prepareFilename(basename(Request::get('file_name')));

if (Request::int('zip') && is_file($path_file)) {
	$tmp_id = md5(uniqid("suppe"));
	$zip_path_file = "$TMP_PATH/$tmp_id";
	$tmp_file_name = escapeshellcmd("$TMP_PATH/$file_name");
	@copy($path_file, $tmp_file_name);
	if (create_zip_from_file( $tmp_file_name, "$zip_path_file.zip") === false) {
		@unlink($zip_path_file . '.zip');
		@unlink($tmp_file_name);
		throw new Exception(_("Fehler beim Erstellen des Zip-Archivs!"));
	} else {
		$file_name = $file_name . ".zip";
		$path_file = $zip_path_file . ".zip";
		@unlink($tmp_file_name);
	}
}

if (Request::int('force_download')) {
	$content_type = "application/octet-stream";
	$content_disposition = "attachment";
} else {
	$content_disposition = "inline";
	switch (strtolower(getFileExtension ($file_name))) {
		case "txt":
			$content_type = "text/plain";
		break;
		case "css":
			$content_type = "text/css";
		break;
		case "gif":
			$content_type = "image/gif";
		break;
		case "jpeg":
		case "jpg":
		case "jpe":
			$content_type = "image/jpeg";
		break;
		case "bmp":
			$content_type = "image/x-ms-bmp";
		break;
		case "png":
			$content_type = "image/png";
		break;
		case "wav":
			$content_type = "audio/x-wav";
		break;
		case "ra":
			$content_type = "application/x-pn-realaudio";
		break;
		case "ram":
			$content_type = "application/x-pn-realaudio";
		break;
		case "mpeg":
		case "mpg":
		case "mpe":
			$content_type = "video/mpeg";
		break;
		case "qt":
		case "mov":
			$content_type ="video/quicktime";
		break;
		case "avi":
			$content_type = "video/x-msvideo";
		break;
		case "rtf":
			$content_type = "application/rtf";
		break;
		case "pdf":
			$content_type = "application/pdf";
		break;
		case "doc":
			$content_type = "application/msword";
		break;
		case "xls":
			$content_type = "application/ms-excel";
		break;
		case "ppt":
			$content_type = "application/ms-powerpoint";
		break;
		case "tgz":
		case "gz":
			$content_type = "application/x-gzip";
		break;
		case "bz2":
			$content_type = "application/x-bzip2";
		break;
		case "zip":
			$content_type = "application/zip";
		break;
		case "swf":
			$content_type = "application/x-shockwave-flash";
		break;
		case "csv":
			$content_type = "text/csv";
		break;
		default:
			$content_type = "application/octet-stream";
		break;
	}
}

// check if linked file is obtainable
if ($type == 6) {
	$link_data = parse_link($path_file);
	if ($link_data['response_code'] != 200) {
		throw new Exception(_("Diese Datei wird von einem externen Server geladen und ist dort momentan nicht erreichbar!"));
	}
	$filesize = $link_data['Content-Length'];
	if (!$filesize) $filesize = false;
} elseif ($type != 5){
	$filesize = @filesize($path_file);
} else {
	$filesize = strlen($the_data);
}
// close session, download will mostly be a parallel action
page_close();

header("Expires: Mon, 12 Dec 2001 08:00:00 GMT");
header("Last-Modified: " . gmdate ("D, d M Y H:i:s") . " GMT");
if ($_SERVER['HTTPS'] == "on"){
	header("Pragma: public");
	header("Cache-Control: private");
} else {
	header("Pragma: no-cache");
	header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
}
header("Cache-Control: post-check=0, pre-check=0", false);
header("Content-Type: $content_type; name=\"$file_name\"");
header("Content-Description: File Transfer");
header("Content-Transfer-Encoding: binary");
header("Accept-Ranges: bytes");
if ($filesize != FALSE) header("Content-Length: $filesize");
header("Content-Disposition: $content_disposition; filename=\"$file_name\"");
ob_end_flush();

if ($type != 5){
	@readfile($path_file);
	if(in_array($type, array(0,6))){
		TrackAccess($file_id, 'dokument');
	}
} else {
	echo $the_data;
}

//remove temporary file after zipping
if (Request::int('zip') || $type == 4) {
	@unlink($path_file);
}
?>