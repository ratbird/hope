<?php
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


  function before_filter($action, &$args) {
    parent::before_filter($action, &$args);
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
    global $sms_data, $count;
    $count = $n;
    if (is_null($id) || is_null($open) || is_null($n)) {
      $this->set_status(400);
      return $this->render_nothing();
    }
    $this->id = $id;
    $sms_data['open'] = $open ? $id : NULL;
    $this->msg = ajax_show_body($id);
    $this->render_template('messages/open_or_close');
  }
}
