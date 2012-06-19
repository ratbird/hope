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
     * @param  string   course or institute range id
     * @param  int      time of user's last visit
     *
     * @return object   navigation item to render or NULL
     */
    function getIconNavigation($course_id, $last_visit);
    
    function getTabNavigation($course_id);
}