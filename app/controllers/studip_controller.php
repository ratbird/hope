<?php
/*
 * studip_controller.php - studip controller base class
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

abstract class StudipController extends Trails_Controller
{
    /**
     * Exception handler called when the performance of an action raises an
     * exception.
     *
     * @param  object     the thrown exception
     *
     * @return object     a response object
     */
    function rescue($exception)
    {
        $body = $GLOBALS['template_factory']->
                    render('unhandled_exception', compact('exception'));

        return new Trails_Response($body, array(), 500, $exception->getMessage());
    }
}
