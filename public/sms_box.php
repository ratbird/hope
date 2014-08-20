<?php
/**
 * sms_box.php - Redirects to new messaging system
 *
 * @author   Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category Stud.IP
 * @package  messaging
 * @todo     Remove this as soon as you feel the redirect is no longer needed
 * @deprecated
 */

require_once '../lib/bootstrap.php';

// determine view
$site = Request::option('sms_inout', 'in') === 'in'
      ? 'overview'
      : 'sent';

$url = URLHelper::getURL('dispatch.php/messages/' . $site);
if ($message_id = Request::option('mopen')) {
    $url .= '/' . $message_id;
}

header('Location: ' . $url);