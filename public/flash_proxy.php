<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// flash_proxy.php
//
//
// Copyright (c) 2008 Peter Thienel <thienel@data-quest.de>
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

require_once ('lib/datei.inc.php');

ini_set('default_socket_timeout', 5);
ob_end_clean();
ob_start();
$ok = Seminar_Session::is_current_session_authenticated();
$url = $_GET['url'];
if($ok){
    $headers = parse_link($url);
    if($headers['response_code'] == 200){
        $f = fopen($url, 'rb');
        if($f){
            stream_set_timeout($f, 5);
            $flv = fread($f, 16);
            fclose($f);
        }
        $ok = (substr($flv,0,3) == 'FLV');
    } else {
        $ok = false;
    }
}
if($ok){
    if ($headers['Content-Length']) {
        header('Content-Length: ' . $headers['Content-Length']);
    }
    header('Content-Disposition: attachment; filename="' . md5($url) . '.flv"');
    header("Content-Type: video/x-flv");
    ob_end_flush();
    readfile($url);
} else {
    ob_end_clean();
    header(" ", true, 500);
}
?>
