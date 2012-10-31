<?php
# Lifter010: TODO
/**
 * message.php - Message controller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'lib/sms_functions.inc.php';
require_once 'app/controllers/authenticated_controller.php';

class MessagesController extends AuthenticatedController {


  function before_filter(&$action, &$args) {
    parent::before_filter($action, $args);
    $this->set_layout(NULL);
  }

  /**
   * Gibt die offene, oder geschlossene Tabelle einer Nachricht aus.
   *
   * @param   string    Welche Nachricht soll augegeben werden (ID)
   * @param   int       offen oder geschlossen
   * @param   int       Stelle der Nachricht innerhalb des Ordners (letzte Nachricht = 0)
   *
   * @return  string    Nachrichtentabelle
   */

  function get_msg_body_action($id = NULL, $open = NULL , $n = NULL) {
    global $count, $msging;
    $msging = new messaging();
    $count = $n;
    $GLOBALS['sms_data'] =& $_SESSION['sms_data'];
    if (is_null($id) || is_null($open) || is_null($n)) {
      $this->set_status(400);
      return $this->render_nothing();
    }
    $GLOBALS['sms_data']['open'] = $open ? $id : NULL;
    $this->render_text(studip_utf8encode(ajax_show_body($id)));
  }

  function show_print_action($message_id, $sndrec = 'rec')
  {
      $data = get_message_data($message_id, $GLOBALS['user']->id, $sndrec);
      if ($data) {
          $this->msg = $data;
          $this->msg['from'] = get_fullname($data['snd_uid']);
          $this->msg['to'] = join(', ', array_map('get_fullname', explode(',', $data['rec_uid'])));
          $this->msg['attachments'] = array_filter(array_map(array('StudipDocument','find'), array_unique(explode(',', $data['attachments']))));
          PageLayout::setTitle($data['subject']);
          $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));
      } else {
          $this->set_status(400);
          return $this->render_nothing();
      }
  }
}
