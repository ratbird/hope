<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// image_proxy.php
//
// Copyright (c) 2007 André Noack <noack@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
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


require '../lib/bootstrap.php';

ini_set('default_socket_timeout', 5);

function get_error_image($error){
    global $IMAGE_PROXY_PATH;
    $errorstring = "image proxy error - " . $error;
    $imagefile = $IMAGE_PROXY_PATH . md5($errorstring);
    if(!file_exists($imagefile)){
        $width  = ImageFontWidth(3) * strlen($errorstring) + 3;
        $height = ImageFontHeight(3) + 3 ;
        $im = imagecreate($width,$height);
        $background_color = imagecolorallocate($im, 255, 255, 255); 
        $text_color = imagecolorallocate($im, 255, 0, 0);
        imagestring($im, 3, 1, 1,  $errorstring, $text_color);
        imagegif($im, $imagefile);
    }
    return array(md5($errorstring), filesize($imagefile));
}

function check_image_cache($id) {
    global $IMAGE_PROXY_CACHE_LIFETIME,$IMAGE_PROXY_PATH;
    $db = new DB_Seminar();
    $ret = null;
    $query = "SELECT *, UNIX_TIMESTAMP(chdate) as last_modified FROM image_proxy_cache WHERE id='$id' AND chdate > FROM_UNIXTIME(".(time() - $IMAGE_PROXY_CACHE_LIFETIME).")";
    $db->query($query);
    if ($db->next_record()){
        $ret = array($db->f('id'), $db->f('last_modified'), $db->f('length'), $db->f('type'));
        if($db->f('error')){
            $ret[0] = md5("image proxy error - " . $db->f('error'));
        }
    }
    return $ret;
}

function refresh_image_cache($id,$type,$length,$error){
    $db = new DB_Seminar();
    $db->queryf("REPLACE INTO image_proxy_cache (id,type,length,error) VALUES ('%s','%s','%s','%s')",
        $id,
        mysql_escape_string($type),
        mysql_escape_string($length),
        mysql_escape_string($error));
    return check_image_cache($id);
}

function garbage_collect_image_cache(){
    global $IMAGE_PROXY_MAX_FILES_IN_CACHE,$IMAGE_PROXY_PATH;
    $db = new DB_Seminar();
    $db->query("SELECT COUNT(*) FROM image_proxy_cache");
    $db->next_record();
    if($db->f(0) > $IMAGE_PROXY_MAX_FILES_IN_CACHE){
        $delete = array();
        $db->query("SELECT id FROM image_proxy_cache ORDER BY chdate ASC LIMIT " . ($db->f(0) - $IMAGE_PROXY_MAX_FILES_IN_CACHE));
        while($db->next_record()){
            $delete[] = $db->f(0);
            @unlink($IMAGE_PROXY_PATH . $db->f(0));
        }
        $db->query("DELETE FROM image_proxy_cache WHERE id IN('".join("','",$delete)."')");
    }
}

Config::GetInstance()->getValue('EXTERNAL_IMAGE_EMBEDDING') == 'proxy' OR die();

ob_end_clean();
ob_start();
require_once "lib/datei.inc.php";
if ((mt_rand() % 100) < $IMAGE_PROXY_GC_PROBABILITY ){
    garbage_collect_image_cache();
}
$url = $_GET['url'];
$id = md5($url);

if (!Seminar_Session::is_current_session_authenticated()){
    $id = md5('denied');
    list(, $length) = get_error_image('denied');
    $check = refresh_image_cache($id,'image/gif',$length,'denied');
} elseif(!($check = check_image_cache($id))){
    $error = '';
    $headers = parse_link($url);
    if (!$headers['response_code']) {
        $error = 'no response';
    } elseif ($headers['response_code'] != 200) {
        $error = (int)$headers['response_code'];
    } elseif ($headers['Content-Length'] > $IMAGE_PROXY_MAX_CONTENT_LENGTH) {
        $error = 'too big';
    } elseif (strpos($headers['Content-Type'],'image') === false) {
        $error = 'no image';
    }
    if ($error) {
        list(, $length) = get_error_image($error);
        $check = refresh_image_cache($id,'image/gif',$length,$error);
    } else {
        $imagefile = $IMAGE_PROXY_PATH . $id;
        $image = null;
        $c = 0;
        $f = fopen($url, 'rb');
        if($f){
            stream_set_timeout($f, 5);
            while (!feof($f)) {
                $image .= fread($f, 8192);
                ++$c;
                $info = stream_get_meta_data($f);
                if($c * 8192 > $IMAGE_PROXY_MAX_CONTENT_LENGTH || $info['timed_out'])   break;      
            }
            fclose($f);
            if($info['timed_out']){
                list(, $length) = get_error_image('timed out');
                $check = refresh_image_cache($id,'image/gif',$length,'timed out');
            } elseif($c * 8192 < $IMAGE_PROXY_MAX_CONTENT_LENGTH){
                $f = fopen($imagefile, 'wb');
                fwrite($f, $image);
                fclose($f);
                $error = '';
                $size = @GetImageSize($imagefile);
                // $size[2]: 1=GIF, 2=JPG, 3=PNG, false=not a valid image
                if(!$size || $size[2] > 3 || $size[2] < 1) {
                    $error = 'bad file';
                } elseif ($size[0] > $IMAGE_PROXY_MAX_IMAGE_SIZE ||  $size[1] > $IMAGE_PROXY_MAX_IMAGE_SIZE) {
                    $error = 'too big';
                }
                if ($error) {
                    @unlink($imagefile);
                    list(, $length) = get_error_image($error);
                    $check = refresh_image_cache($id,'image/gif',$length,$error);
                } else {
                    $check = refresh_image_cache($id, $size['mime'] ,filesize($imagefile), '');
                }
            } else {
                list(, $length) = get_error_image('too big');
                $check = refresh_image_cache($id,'image/gif',$length,'too big');
            }
        } else {
            list(, $length) = get_error_image('no response');
            $check = refresh_image_cache($id,'image/gif',$length,'no response');
        }
        
    }
}

list($id, $last_modified, $length, $type) = $check;
$if_modified_since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
if($if_modified_since == $last_modified){
    header("HTTP/1.0 304 Not Modified");
    exit();
}
header('Expires: '. gmdate('D, d M Y H:i:s', $last_modified + $IMAGE_PROXY_CACHE_LIFETIME ) . ' GMT');
header('Last-Modified: '. gmdate('D, d M Y H:i:s', $last_modified) . ' GMT');
header('Content-Length: '. $length);
header('Content-Type: '. $type);
ob_end_flush();
readfile($IMAGE_PROXY_PATH . $id);
?>
