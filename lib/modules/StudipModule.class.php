<?php
/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

interface StudipModule {
    /**
     * Return a navigation object representing this plugin in the
     * course overview table or return NULL if you want to display
     * no icon for this plugin (or course). The navigation object's
     * title will not be shown, only the image (and its associated
     * attributes like 'title') and the URL are actually used.
     *
     * By convention, new or changed plugin content is indicated
     * by a different icon and a corresponding tooltip.
     *
     * @param  string   $course_id   course or institute range id
     * @param  int      $last_visit  time of user's last visit
     * @param  string   $user_id     the user to get the navigation for
     *
     * @return object   navigation item to render or NULL
     */
    function getIconNavigation($course_id, $last_visit, $user_id);
    
    function getTabNavigation($course_id);

    /**
     * return a list of ContentElement-objects, conatinging 
     * everything new in this module
     *
     * @param  string   $course_id   the course-id to get the new stuff for
     * @param  int      $last_visit  when was the last time the user visited this module
     * @param  string   $user_id     the user to get the notifcation-objects for
     *
     * @return array an array of ContentElement-objects
     */
    function getNotificationObjects($course_id, $since, $user_id);
}
