<?php
/*
 * ScorePlugin.class.php - generic score plugin interface
 *
 * Copyright (c) 2008 - Marcus Lunzenauer <mlunzena@uos.de>
 * Copyright (c) 2009 - Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
interface ScorePlugin {
    
    /**
     * Calculates the score
     */
    function calculate();
}
