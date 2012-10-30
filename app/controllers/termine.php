<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
 * termine.php - Termine controller
 *
 */
global $RELATIVE_PATH_CALENDAR, $template_factory;
require_once 'lib/functions.php';
require_once 'lib/show_dates.inc.php';
require_once 'lib/user_visible.inc.php';
require_once 'studip_controller.php';

class TermineController extends StudipController
{
    /**
     * Callback function being called before an action is executed.
     */
    function before_filter(&$action, &$args)
    {
        // open session
        page_open(array('sess' => 'Seminar_Session',
                        'auth' => 'Seminar_Default_Auth',
                        'perm' => 'Seminar_Perm',
                        'user' => 'Seminar_User'));

        // set up user session
        include 'lib/seminar_open.php';

        // allow only "word" characters in arguments
        $this->validate_args($args);
        
    }

    /**
     * Callback function being called after an action is executed.
     */
    function after_filter($action, $args)
    {
        page_close();
    }

    function get_termin_action($id,$showadmin='',$type='',$info='')
    {

        if (is_null($id)) {
             //$this->set_status(400);
             //return $this->render_nothing();
        }
       
       
        $termin_item = array('termin_id'=>$id,
            'date_type'=>$type,'info'=>$info);
        $this->content = show_termin_item_content($termin_item,FALSE,"",0,$showadmin);
        
    }

    
}

